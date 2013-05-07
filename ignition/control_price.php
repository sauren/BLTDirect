<?php
ini_set('max_execution_time', '900');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

if($action == 'report') {
	$session->Secure(3);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start(){
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'Y', 'boolean', NULL, NULL, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		report($form->GetValue('parent'), ($form->GetValue('subfolders') =='Y') ? true : false);
		exit;
	}

	$page = new Page('Product Price Control');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Price control for Products from a Category.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Click on a the search icon to find a category to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 600, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('parent') . $temp_1, '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->AddRow('&nbsp','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $form->Close();
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function report($cat = 0, $sub = 'Y') {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 1, 12);
	$form->SetValue('action', 'report');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cat', 'Category ID', 'hidden', $cat, 'numeric_unsigned', 1, 11);
	$form->AddField('sub', 'Include Sub Categories', 'hidden', ($sub) ? 'Y' : 'N', 'boolean', 1, 1, false);

	$sub = ($form->GetValue('sub') == 'Y') ? true : false;
	$cat = $form->GetValue('cat');

	$form->AddField('order','Sort by','select','O','alpha_numeric',0,40,false);
	$form->AddOption('order','N','Product Name');
	$form->AddOption('order','U','Part Number (SKU)');
	$form->AddOption('order','Q','Quickfind');
	$form->AddOption('order','O','Preferred Supplier');
	$form->AddOption('order','C','Supplier Cost');
	/*//$form->AddOption('order','R','Internet RRP');
	$form->AddOption('order','P','Internet Sell Price');
	$form->AddOption('order','F','Internet Offer Price');*/

	if($form->GetValue('order')=='Q'){
		$ordering = 'p.Product_ID';
	}elseif($form->GetValue('order')=='O'){
		$ordering = 'o.Org_Name';
	}elseif($form->GetValue('order')=='C'){
		$ordering = 'sp.Cost';

		## dont know why but doesnt work yet
	}elseif($form->GetValue('order')=='R'){
		$ordering = 'pp.Price_Base_RRP';

	}elseif($form->GetValue('order')=='P'){
		$ordering = 'pp.Price_Base_Our';
	}elseif($form->GetValue('order')=='F'){
		$ordering = 'po.Price_Offer';
	}elseif($form->GetValue('order')=='U'){
		$ordering = 'p.SKU';
	} else {
		$ordering = 'p.Product_Title';
	}

	$clientString = "";

	if($cat != 0) {
		if($sub) {
			$clientString = sprintf("AND (cat.Category_ID=%d %s) ", $cat, GetChildIDS($cat));
		} else {
			$clientString = sprintf("AND (cat.Category_ID=%d) ", $cat);
		}
	} else {
		if(!$sub) {
			$clientString = sprintf("AND (cat.Category_ID=%d) ", $cat);
		}
	}

	$stock = array();

	$data = new DataQuery(sprintf("SELECT p.Product_Title, p.Product_ID, p.SKU,
							sp.Supplier_Product_ID, sp.Cost, o.Org_Name FROM product AS p
							LEFT JOIN product_in_categories AS cat ON p.Product_ID=cat.Product_ID
							LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
							LEFT JOIN supplier AS s ON s.Supplier_ID=sp.Supplier_ID
							LEFT JOIN contact AS c1 ON c1.Contact_ID=s.Contact_ID
							LEFT JOIN contact AS co ON co.Contact_ID=c1.Parent_Contact_ID
							LEFT JOIN organisation AS o ON o.Org_ID=co.Org_ID
							WHERE p.Discontinued<>'Y'
							AND (sp.Product_ID IS NULL OR sp.Preferred_Supplier='Y')
							%sORDER BY %s ASC",
							mysql_real_escape_string($clientString), mysql_real_escape_string($ordering)));

	/*$data = new DataQuery(sprintf("SELECT p.Product_Title, p.Product_ID, p.SKU,
							sp.Supplier_Product_ID, sp.Cost, o.Org_Name,
							pp.Product_Price_ID, pp.Price_Base_Our, pp.Price_Base_RRP,
							po.Product_Offer_ID, po.Price_Offer FROM product AS p
							LEFT JOIN product_in_categories AS cat ON p.Product_ID=cat.Product_ID
							LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
							LEFT JOIN supplier AS s ON s.Supplier_ID=sp.Supplier_ID
							LEFT JOIN contact AS c1 ON c1.Contact_ID=s.Contact_ID
							LEFT JOIN contact AS co ON co.Contact_ID=c1.Parent_Contact_ID
							LEFT JOIN organisation AS o ON o.Org_ID=co.Org_ID
							LEFT JOIN product_prices AS pp ON pp.Product_ID=p.Product_ID
							LEFT JOIN product_offers AS po ON po.Product_ID=p.Product_ID
							WHERE p.Discontinued<>'Y'
							AND (sp.Product_ID IS NULL OR sp.Preferred_Supplier='Y')
							AND (pp.Product_ID IS NULL OR pp.Price_Starts_On<=Now())
							AND (po.Product_ID IS NULL OR (po.Offer_Start_On<=Now() AND po.Offer_End_On>=Now()))
							%sGROUP BY p.Product_ID Order By %s ASC, pp.Price_Starts_On DESC",
							$clientString, $ordering));*/

	while($data->Row) {
		$data2 = new DataQuery(sprintf("SELECT pp.Product_Price_ID, pp.Price_Base_Our, pp.Price_Base_RRP,
										po.Product_Offer_ID, po.Price_Offer FROM product_prices AS pp
										LEFT JOIN product_offers AS po ON po.Product_ID=pp.Product_ID
										WHERE pp.Product_ID=%d
										AND (pp.Product_ID IS NULL OR pp.Price_Starts_On<=Now())
										AND (po.Product_ID IS NULL OR ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')))
										ORDER BY pp.Price_Starts_On DESC", mysql_real_escape_string($data->Row['Product_ID'])));

		$stockItem = array();
		$stockItem['id'] = $data->Row['Product_ID'];
		$stockItem['stock_id'] = $data->Row['Stock_ID'];
		$stockItem['supplier_product_id'] = $data->Row['Supplier_Product_ID'];
		$stockItem['product_price_id'] = $data2->Row['Product_Price_ID'];
		$stockItem['product_offer_id'] = $data2->Row['Product_Offer_ID'];
		$stockItem['name'] = strip_tags($data->Row['Product_Title']);
		$stockItem['sku'] = $data->Row['SKU'];
		$stockItem['supplier'] = (empty($data->Row['Org_Name'])) ? '&nbsp;' : $data->Row['Org_Name'];
		$stockItem['cost'] = (empty($data->Row['Cost'])) ? null : $data->Row['Cost'];
		$stockItem['rrp'] = (empty($data2->Row['Price_Base_RRP']))? null : $data2->Row['Price_Base_RRP'];
		$stockItem['price'] = (empty($data2->Row['Price_Base_Our']))? null : $data2->Row['Price_Base_Our'];
		$stockItem['offer'] = (empty($data2->Row['Price_Offer']))? null : $data2->Row['Price_Offer'];

		if(!is_null($stockItem['cost'])) {
			$form->AddField('cost_'.$stockItem['id'], 'Preferred Supplier Cost', 'text', $stockItem['cost'], 'float', 0, 11, true, 'size="5"');
		}
		if(!is_null($stockItem['price'])) {
			$form->AddField('price_'.$stockItem['id'], 'Sell Price', 'text', $stockItem['price'], 'float', 0, 11, true, 'size="5"');
		}
		if(!is_null($stockItem['rrp'])) {
			$form->AddField('rrp_'.$stockItem['id'], 'RRP', 'text', $stockItem['rrp'], 'float', 0, 11, true, 'size="5"');
		}
		if(!is_null($stockItem['offer'])) {
			$form->AddField('offer_'.$stockItem['id'], 'Offer Price', 'text', $stockItem['offer'], 'float', 0, 11, true, 'size="5"');
		}

		$stock[] = $stockItem;

		$data2->Disconnect();

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['action']) && ($_REQUEST['action'] == "report") && (isset($_REQUEST['confirm']))) {
		if(!isset($_REQUEST['ordering'])) {
			if($form->Validate()) {

				for($i = 0; $i < count($stock); $i++) {
					if($stock[$i]['cost'] != $form->GetValue('cost_'.$stock[$i]['id'])) {
						new DataQuery(sprintf("UPDATE supplier_product SET Cost=%f WHERE Supplier_Product_ID=%d", mysql_real_escape_string($form->GetValue('cost_'.$stock[$i]['id'])), mysql_real_escape_string($stock[$i]['supplier_product_id'])));
					}

					if($stock[$i]['price'] != $form->GetValue('price_'.$stock[$i]['id'])) {
						new DataQuery(sprintf("UPDATE product_prices SET Price_Base_Our=%f WHERE Product_Price_ID=%d", mysql_real_escape_string($form->GetValue('price_'.$stock[$i]['id'])), mysql_real_escape_string($stock[$i]['product_price_id'])));
					}

					if($stock[$i]['rrp'] != $form->GetValue('rrp_'.$stock[$i]['id'])) {
						new DataQuery(sprintf("UPDATE product_prices SET Price_Base_RRP=%f WHERE Product_Price_ID=%d", mysql_real_escape_string($form->GetValue('rrp_'.$stock[$i]['id'])), mysql_real_escape_string($stock[$i]['product_price_id'])));
					}

					if($stock[$i]['offer'] != $form->GetValue('offer_'.$stock[$i]['id'])) {
						new DataQuery(sprintf("UPDATE product_offers SET Price_Offer=%f WHERE Product_Offer_ID=%d", mysql_real_escape_string($form->GetValue('offer_'.$stock[$i]['id'])), mysql_real_escape_string($stock[$i]['product_offer_id'])));
					}
				}

				redirect(sprintf("Location: %s?action=report&cat=%s&sub=%s&order=%s", $_SERVER['PHP_SELF'], $cat, $sub, $form->GetValue('order')));
			}
		}
	}

	$page = new Page('Product Price Control');
	$page->Display('header');

	$window = new StandardWindow('Sort products');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cat');
	echo $form->GetHTML('sub');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('order'),$form->GetHTML('order'));
	echo $webForm->AddRow('','<input type="submit" id="ordering" name="ordering" value="sort" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo "<br />";

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}
	?>
	<br />
	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
		  <thead>
		  <tr>
			<th <?php print ($form->GetValue('order')=='N') ? 'class="dataHeadOrdered"' : ''; ?>nowrap><strong>Product Name</strong></td>
			<th <?php print ($form->GetValue('order')=='U') ? 'class="dataHeadOrdered"' : ''; ?>nowrap><strong>Part Number (SKU)</strong></td>
			<th <?php print ($form->GetValue('order')=='Q') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="center"><strong>Quickfind </strong></td>
			<th <?php print ($form->GetValue('order')=='O') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="right"><strong>Preferred Supplier</strong></td>
			<th <?php print ($form->GetValue('order')=='C') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="right"><strong>Supplier Cost</strong></td>
			<th <?php print ($form->GetValue('order')=='R') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="right"><strong>Internet RRP</strong></td>
			<th <?php print ($form->GetValue('order')=='P') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="right"><strong>Internet Sell Price</strong></td>
			<th <?php print ($form->GetValue('order')=='F') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="right"><strong>Internet Offer Price</strong></td>
		 </tr>
		  </thead>
		  <tbody>
		  <?php
		  for($i = 0; $i < count($stock); $i++) {
		  	?>
		  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		  	<td <?php print ($form->GetValue('order')=='N') ? 'class="dataOrdered"' : ''; ?>><a href="product_profile.php?pid=<?php echo $stock[$i]['id']; ?>" target="_blank"><?php echo $stock[$i]['name']; ?></a></td>
		  	<td <?php print ($form->GetValue('order')=='U') ? 'class="dataOrdered"' : ''; ?>><?php echo $stock[$i]['sku']; ?></td>
		  	<td <?php print ($form->GetValue('order')=='Q') ? 'class="dataOrdered"' : ''; ?>align="center"><?php echo $stock[$i]['id']; ?></td>
		  	<td <?php print ($form->GetValue('order')=='O') ? 'class="dataOrdered"' : ''; ?>><?php echo $stock[$i]['supplier']; ?></td>
		  	<td <?php print ($form->GetValue('order')=='C') ? 'class="dataOrdered"' : ''; ?>align="right"><?php echo (!is_null($stock[$i]['cost'])) ? $form->GetHTML('cost_'.$stock[$i]['id']) : 'N/A'; ?></td>
		  	<td <?php print ($form->GetValue('order')=='R') ? 'class="dataOrdered"' : ''; ?>align="right"><?php echo (!is_null($stock[$i]['rrp'])) ? $form->GetHTML('rrp_'.$stock[$i]['id']) : 'N/A'; ?></td>
		  	<td <?php print ($form->GetValue('order')=='P') ? 'class="dataOrdered"' : ''; ?>align="right"><?php echo (!is_null($stock[$i]['price'])) ? $form->GetHTML('price_'.$stock[$i]['id']) : 'N/A'; ?></td>
		  	<td <?php print ($form->GetValue('order')=='F') ? 'class="dataOrdered"' : ''; ?>align="right"><?php echo (!is_null($stock[$i]['offer'])) ? $form->GetHTML('offer_'.$stock[$i]['id']) : 'N/A'; ?></td>
		  	</tr>
			 <?php
		  }
		  ?>
		  </tbody>
	</table>

	<br />

	<input type="submit" class="btn" value="update" name="report" />

	<?php
	echo $form->Close();
}

function GetChildIDS($cat) {
	$string = "";
	$children = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($cat)));
	while($children->Row) {
		$string .= "OR cat.Category_ID=".$children->Row['Category_ID']." ";
		$string .= GetChildIDS($children->Row['Category_ID']);
		$children->Next();
	}
	$children->Disconnect();
	return $string;
}
?>