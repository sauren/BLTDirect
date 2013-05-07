<?php
ini_set('max_execution_time', '900');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
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
	$page = new Page('Product Stock Control');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'Y', 'boolean', NULL, NULL, false);
	$form->AddField('loc', 'Shelf Location', 'select', '', 'anything', 0, 255, true);
	$form->AddOption('loc', '', '--All--');

	$data = new DataQuery(sprintf("SELECT DISTINCT ws.Shelf_Location FROM warehouse AS w INNER JOIN warehouse_stock AS ws ON ws.Warehouse_ID=w.Warehouse_ID INNER JOIN users AS u ON w.Type_Reference_ID=u.Branch_ID WHERE w.Type='B' AND u.User_ID=%d ORDER BY ws.Shelf_Location ASC", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
	while($data->Row) {
		$form->AddOption('loc', $data->Row['Shelf_Location'], $data->Row['Shelf_Location']);
		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		report($form->GetValue('parent'), $form->GetValue('subfolders'), $form->GetValue('loc'));
		exit;
	}

	$page->Display('header');
	
	$bubble = new Bubble('Usage Warning' , 'This facility is out dated and required updating.');

	echo $bubble->GetHTML();
	echo '<br />';

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Stock control for Products from a Category.");
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
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Select a shelf location to filter products by.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('loc'), $form->GetHTML('loc'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Click search once you are done');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function report($cat = 0, $sub = 'Y', $location = '') {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 1, 12);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cat', 'Category', 'hidden', $cat, 'numeric_unsigned', 1, 11);
	$form->AddField('sub', 'Sub Folder', 'hidden', $sub, 'boolean', 1, 1, false);
	$form->AddField('location', 'Location', 'hidden', $location, 'anything', 0, 255, false);

	$sub = ($form->GetValue('sub') == 'Y') ? true : false;
	$cat = $form->GetValue('cat');
	$location = $form->GetValue('location');

	$form->AddField('order','Sort by','select','O','alpha_numeric',0,40,false);
	$form->AddOption('order','N','Product Name');
	$form->AddOption('order','Q','Quickfind');
	$form->AddOption('order','O','Preferred Supplier');
	$form->AddOption('order','T','Orders');
	$form->AddOption('order','Z','Qty Sold');
	$form->AddOption('order','S','Qty Stocked');
	$form->AddOption('order','I','Qty Incoming');
	$form->AddOption('order','L','Shelf Location');

	if($form->GetValue('order')=='Q'){
		$ordering = 'ws.Product_ID';
	}elseif($form->GetValue('order')=='T'){
		$ordering = 'tp.Orders';
	}elseif($form->GetValue('order')=='Z'){
		$ordering = 'tp.Quantity_Sold';
	}elseif($form->GetValue('order')=='S'){
		$ordering = 'ws.Quantity_In_Stock';
	}elseif($form->GetValue('order')=='L'){
		$ordering = 'ws.Shelf_Location';
	} else {
		$ordering = 'p.Product_Title';
	}

	$clientString = "";

	if($cat != 0) {
		if($sub) {
			$clientString = sprintf("AND (cat.Category_ID=%d %s) ", mysql_real_escape_string($cat), GetChildIDS($cat));
		} else {
			$clientString = sprintf("AND cat.Category_ID=%d ", mysql_real_escape_string($cat));
		}
	} else {
		if(!$sub) {
			$clientString = sprintf("AND (cat.Category_ID IS NULL OR cat.Category_ID=%d) ", mysql_real_escape_string($cat));
		}
	}

	$stock = array();
	
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_product SELECT ol.Product_ID, COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantity_Sold FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Product_ID>0 WHERE o.Created_On>=ADDDATE(NOW(), INTERVAL -3 MONTH) AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY ol.Product_ID"));
	new DataQuery(sprintf("ALTER TABLE temp_product ADD INDEX Product_ID (Product_ID)"));	

	if($location != '') {
		$data = new DataQuery(sprintf("SELECT ws.*, p.Product_Title, p.SKU,
							o.Org_Name, tp.Orders, tp.Quantity_Sold FROM warehouse_stock ws
							INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID
							INNER JOIN users AS u ON w.Type_Reference_ID=u.Branch_ID
							INNER JOIN product AS p ON ws.Product_ID=p.Product_ID
							LEFT JOIN product_in_categories AS cat ON p.Product_ID=cat.Product_ID
							LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
							LEFT JOIN supplier AS s ON s.Supplier_ID=sp.Supplier_ID
							LEFT JOIN contact AS c1 ON c1.Contact_ID=s.Contact_ID
							LEFT JOIN contact AS co ON co.Contact_ID=c1.Parent_Contact_ID
							LEFT JOIN organisation AS o ON o.Org_ID=co.Org_ID
							LEFT JOIN temp_product AS tp ON tp.Product_ID=p.Product_ID
							WHERE w.Type = 'B' AND u.User_ID = %d AND p.Discontinued<>'Y'
							AND (sp.Product_ID IS NULL OR sp.Preferred_Supplier='Y')
							AND ws.Shelf_Location LIKE '%s'
							%sGROUP BY p.Product_ID Order By %s ASC",
							mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($location), mysql_real_escape_string($clientString), mysql_real_escape_string($ordering)));
	} else {
		$data = new DataQuery(sprintf("SELECT ws.*, p.Product_Title, p.SKU,
							o.Org_Name, tp.Orders, tp.Quantity_Sold FROM warehouse_stock ws
							INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID
							INNER JOIN users AS u ON w.Type_Reference_ID=u.Branch_ID
							INNER JOIN product AS p ON ws.Product_ID=p.Product_ID
							LEFT JOIN product_in_categories AS cat ON p.Product_ID=cat.Product_ID
							LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
							LEFT JOIN supplier AS s ON s.Supplier_ID=sp.Supplier_ID
							LEFT JOIN contact AS c1 ON c1.Contact_ID=s.Contact_ID
							LEFT JOIN contact AS co ON co.Contact_ID=c1.Parent_Contact_ID
							LEFT JOIN organisation AS o ON o.Org_ID=co.Org_ID
							LEFT JOIN temp_product AS tp ON tp.Product_ID=p.Product_ID
							WHERE w.Type = 'B' AND u.User_ID = %d AND p.Discontinued<>'Y'
							AND (sp.Product_ID IS NULL OR sp.Preferred_Supplier='Y')
							%sGROUP BY p.Product_ID Order By %s ASC",
							mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($clientString), mysql_real_escape_string($ordering)));
	}

	/*
	$data = new DataQuery(sprintf("SELECT ws.*, p.Product_Title, COUNT(c.Product_ID) AS Components FROM warehouse_stock ws
							INNER JOIN warehouse w ON ws.Warehouse_ID = w.Warehouse_ID
							INNER JOIN users u ON w.Type_Reference_ID = u.Branch_ID
							INNER JOIN product p ON ws.Product_ID = p.Product_ID
							LEFT JOIN product_components AS c ON c.Component_Of_Product_ID=p.Product_ID
							WHERE w.Type = 'B' AND u.User_ID = %d AND p.Discontinued <> 'Y'
							GROUP BY p.Product_ID Order By %s ASC",$GLOBALS['SESSION_USER_ID'], $ordering));
	*/

	while($data->Row) {
			//$supFind = new DataQuery(sprintf("SELECT sp.Supplier_Product_ID, sp.Cost, o.Org_Name FROM supplier_product AS sp INNER JOIN supplier AS s ON s.Supplier_ID = sp.Supplier_ID INNER JOIN Contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN contact AS co ON co.Contact_ID=c.Parent_Contact_ID INNER JOIN Organisation AS o ON o.Org_ID=co.Org_ID WHERE sp.Product_ID=%d AND sp.Preferred_Supplier='Y'",$data->Row['Product_ID']));
			//$priceFind = new DataQuery(sprintf("SELECT Product_Price_ID, Price_Base_Our, Price_Base_RRP FROM product_prices WHERE Product_ID=%d AND Price_Starts_On<=now() Order By Price_Starts_On desc",$data->Row['Product_ID']));

			$stockItem = array();
			$stockItem['id'] = $data->Row['Product_ID'];
			$stockItem['stock_id'] = $data->Row['Stock_ID'];
			$stockItem['name'] = strip_tags($data->Row['Product_Title']);
			$stockItem['sku'] = $data->Row['SKU'];
			$stockItem['supplier'] = (empty($data->Row['Org_Name'])) ? '&nbsp;' : $data->Row['Org_Name'];
			$stockItem['qty_stocked'] = $data->Row['Quantity_In_Stock'];
			$stockItem['location'] = $data->Row['Shelf_Location'];
			$stockItem['monitor'] = $data->Row['Moniter_Stock'];
			$stockItem['orders'] = $data->Row['Orders'];
			$stockItem['qty_sold'] = $data->Row['Quantity_Sold'];
			$stockItem['writtenoff'] = $data->Row['Is_Writtenoff'];

			if($data->Row['Is_Writtenoff'] == 'Y'){
				$form->AddField('qty_'.$stockItem['id'], 'Quantity', 'text', $stockItem['qty_stocked'], 'numeric_signed', 1, 9, true, 'size="1" disabled="disabled"');
			} else {
				$form->AddField('qty_'.$stockItem['id'], 'Quantity', 'text', $stockItem['qty_stocked'], 'numeric_signed', 1, 9, true, 'size="1"');
			}
			$form->AddField('location_'.$stockItem['id'], 'Shelf Location', 'text', $stockItem['location'], 'anything', 0, 45, true, 'size="10"');
			$form->AddField('monitor_'.$stockItem['id'], 'Monitor Stock', 'text', $stockItem['monitor'], 'alpha_numeric', 0, 11, true, 'size="10"');
			$form->AddOption('monitor_'.$stockItem['id'], 'Y', 'Yes');
			$form->AddOption('monitor_'.$stockItem['id'], 'N', 'No');

			$stock[] = $stockItem;

			//$supFind->Disconnect();
			//$priceFind->Disconnect();
		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == "true")) {
		if(isset($_REQUEST['report'])) {
			if($form->Validate()) {
				for($i = 0; $i < count($stock); $i++) {
					if($stock[$i]['qty_stocked'] != $form->GetValue('qty_'.$stock[$i]['id'])) {

						$data = new DataQuery(sprintf("SELECT Quantity_In_Stock, Product_ID FROM warehouse_stock WHERE Stock_ID=%d", $stock[$i]['stock_id']));
						if($data->TotalRows > 0) {
							if($form->GetValue('qty_'.$stock[$i]['id']) > $data->Row['Quantity_In_Stock']) {
								$data2 = new DataQuery(sprintf("SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE o.Is_Warehouse_Undeclined='Y' AND o.Is_Restocked='N' AND ol.Product_ID=%d GROUP BY o.Order_ID", mysql_real_escape_string($data->Row['Product_ID'])));
								while($data2->Row) {
									$data3 = new DataQuery(sprintf("UPDATE orders SET Is_Restocked='Y' WHERE Order_ID=%d", $data2->Row['Order_ID']));
									$data3->Disconnect();

									$data2->Next();
								}
								$data2->Disconnect();
							}
						}
						$data->Disconnect();

						new DataQuery(sprintf("UPDATE warehouse_stock SET Quantity_In_Stock=%d WHERE Stock_ID=%d", mysql_real_escape_string($form->GetValue('qty_'.$stock[$i]['id'])), mysql_real_escape_string($stock[$i]['stock_id'])));
					}

					$query = array();

					if($stock[$i]['location'] != $form->GetValue('location_'.$stock[$i]['id'])) {
						$query[] = sprintf('Shelf_Location=\'%s\'', mysql_real_escape_string($form->GetValue('location_'.$stock[$i]['id'])));
					}

					if($stock[$i]['monitor'] != $form->GetValue('monitor_'.$stock[$i]['id'])) {
						$query[] = sprintf('Moniter_Stock=\'%s\'', mysql_real_escape_string($form->GetValue('monitor_'.$stock[$i]['id'])));
					}
					
					if(count($query) > 0) {
						new DataQuery(sprintf("UPDATE warehouse_stock SET %s WHERE Stock_ID=%d", implode(', ', $query), mysql_real_escape_string($stock[$i]['stock_id'])));
					}
				}
			}
		}
	}

	$page = new Page('Product Stock Control');
	$page->Display('header');

	$window = new StandardWindow('Sort products');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cat');
	echo $form->GetHTML('sub');
	echo $form->GetHTML('location');

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
			<th nowrap><strong>Part Number</strong></td>
			<th <?php print ($form->GetValue('order')=='Q') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="center"><strong>Quickfind </strong></td>
			<th <?php print ($form->GetValue('order')=='O') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="right"><strong>Preferred Supplier</strong></td>
			<th nowrap align="center"><strong>Written Off</strong></td>
			<th <?php print ($form->GetValue('order')=='T') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="right"><strong>Orders</strong></td>
			<th <?php print ($form->GetValue('order')=='Z') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="right"><strong>Qty Sold</strong></td>
			<th <?php print ($form->GetValue('order')=='S') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="right"><strong>Qty Stocked</strong></td>
			<th <?php print ($form->GetValue('order')=='L') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="right"><strong>Shelf Location</strong></td>
			<th nowrap align="right"><strong>Monitor Stock</strong></td>
		  </tr>
		  </thead>
		  <tbody>
		  <?php
		  for($i = 0; $i < count($stock); $i++) {
		  	?>
		  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			  	<td <?php print ($form->GetValue('order')=='N') ? 'class="dataOrdered"' : ''; ?>><a href="product_profile.php?pid=<?php echo $stock[$i]['id']; ?>" target="_blank"><?php echo $stock[$i]['name']; ?></a></td>
			  	<td><?php echo $stock[$i]['sku']; ?></td>
			  	<td <?php print ($form->GetValue('order')=='Q') ? 'class="dataOrdered"' : ''; ?>align="center"><a href="product_profile.php?pid=<?php echo $stock[$i]['id']; ?>"><?php echo $stock[$i]['id']; ?></a></td>
			  	<td <?php print ($form->GetValue('order')=='O') ? 'class="dataOrdered"' : ''; ?>><?php echo $stock[$i]['supplier']; ?></td>
			  	<td><?php echo $stock[$i]['writtenoff']; ?></td>
				<td <?php print ($form->GetValue('order')=='T') ? 'class="dataOrdered"' : ''; ?>><?php echo $stock[$i]['orders']; ?></td>
			  	<td <?php print ($form->GetValue('order')=='Z') ? 'class="dataOrdered"' : ''; ?>><?php echo $stock[$i]['qty_sold']; ?></td>
			  	<td <?php print ($form->GetValue('order')=='S') ? 'class="dataOrdered"' : ''; ?>align="right"><?php echo $form->GetHTML('qty_'.$stock[$i]['id']); ?></td>
			  	<td <?php print ($form->GetValue('order')=='L') ? 'class="dataOrdered"' : ''; ?>align="right"><?php echo $form->GetHTML('location_'.$stock[$i]['id']); ?></td>
			  	<td align="right"><?php echo $form->GetHTML('monitor_'.$stock[$i]['id']); ?></td>
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