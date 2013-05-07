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
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'Y', 'boolean', NULL, NULL, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			redirect(sprintf("Location: %s?action=report&cat=%d&sub=%s", $_SERVER['PHP_SELF'], $form->GetValue('parent'), $form->GetValue('subfolders')));
		}
	}

	$page = new Page('Reorder Control');
	$page->Display('header');
	
	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow("Reorder control for products from a category.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Click on a the search icon to find a category to control.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 600, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow($form->GetLabel('subfolders'), $form->GetHtml('subfolders'));
	echo $webForm->AddRow('','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cat', 'Category ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('sub', 'Include Sub Categories', 'hidden', 'Y', 'boolean', 1, 1, false);

	$sub = ($form->GetValue('sub') == 'Y') ? true : false;
	$cat = $form->GetValue('cat');

	$clientString = '';

	if($cat != 0) {
		if($sub) {
			$clientString = sprintf("AND (c.Category_ID=%d %s)", mysql_real_escape_string($cat), mysql_real_escape_string(GetChildIDS($cat)));
		} else {
			$clientString = sprintf("AND (c.Category_ID=%d)", mysql_real_escape_string($cat));
		}
	} else {
		if(!$sub) {
			$clientString = sprintf("AND (c.Category_ID=%d)", mysql_real_escape_string($cat));
		}
	}

	$stock = array();
	
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_product SELECT ol.Product_ID, COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantity_Sold FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Product_ID>0 WHERE o.Created_On>=ADDDATE(NOW(), INTERVAL -3 MONTH) AND o.Status NOT LIKE 'Cancelled' AND o.Status NOT LIKE 'Unauthenticated' GROUP BY ol.Product_ID"));
	new DataQuery(sprintf("ALTER TABLE temp_product ADD INDEX Product_ID (Product_ID)"));	

	$data = new DataQuery(sprintf("SELECT p.Product_Title, p.Product_ID, p.SKU, p.Stock_Level_Alert, p.Stock_Reorder_Quantity, tp.Orders, tp.Quantity_Sold FROM product AS p LEFT JOIN product_in_categories AS c ON p.Product_ID=c.Product_ID LEFT JOIN temp_product AS tp ON tp.Product_ID=p.Product_ID WHERE p.Discontinued<>'Y' AND p.Is_Stocked='Y' %s GROUP BY p.Product_ID ORDER BY p.Product_ID ASC", mysql_real_escape_string($clientString)));
	while($data->Row) {
		$stockItem = array();
		$stockItem['id'] = $data->Row['Product_ID'];
		$stockItem['name'] = strip_tags($data->Row['Product_Title']);
		$stockItem['sku'] = $data->Row['SKU'];
		$stockItem['alert'] = $data->Row['Stock_Level_Alert'];
		$stockItem['reorder'] = $data->Row['Stock_Reorder_Quantity'];
		$stockItem['orders'] = $data->Row['Orders'];
		$stockItem['qty_sold'] = $data->Row['Quantity_Sold'];

		$form->AddField('alert_'.$stockItem['id'], 'Stock Alert', 'text', $stockItem['alert'], 'float', 0, 11, true, 'size="5"');
		$form->AddField('reorder_'.$stockItem['id'], 'Stock Reorder Quantity', 'text', $stockItem['reorder'], 'float', 0, 11, true, 'size="5"');

		$stock[] = $stockItem;

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			for($i = 0; $i < count($stock); $i++) {
				$query = array();
	
				if($stock[$i]['alert'] != $form->GetValue('alert_'.$stock[$i]['id'])) {
					$query[] = sprintf('Stock_Level_Alert=%d', mysql_real_escape_string($form->GetValue('alert_'.$stock[$i]['id'])));
				}

				if($stock[$i]['reorder'] != $form->GetValue('reorder_'.$stock[$i]['id'])) {
					$query[] = sprintf('Stock_Reorder_Quantity=%d', mysql_real_escape_string($form->GetValue('reorder_'.$stock[$i]['id'])));
				}

				if(count($query) > 0) {
					new DataQuery(sprintf("UPDATE product SET %s WHERE Product_ID=%d", implode(', ', $query), mysql_real_escape_string($stock[$i]['id'])));
				}
			}

			redirect(sprintf("Location: %s?action=report&cat=%s&sub=%s", $_SERVER['PHP_SELF'], $form->GetValue('cat'), $form->GetValue('sub')));
		}
	}

	$page = new Page('Reorder Control');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cat');
	echo $form->GetHTML('sub');
	?>

	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
	   <thead>
		  <tr>
			<th nowrap="nowrap"><strong>Product Name</strong></td>
			<th nowrap="nowrap"><strong>Part Number (SKU)</strong></td>
			<th class="dataHeadOrdered" nowrap="nowrap" align="center"><strong>Quickfind</strong></td>
			<th nowrap="nowrap" align="right"><strong>Orders</strong></td>
			<th nowrap="nowrap" align="right"><strong>Qty Sold</strong></td>
			<th nowrap="nowrap" align="right"><strong>Stock Alert Level</strong></td>
			<th nowrap="nowrap" align="right"><strong>Stock Reorder Quantity</strong></td>
		 </tr>
	   </thead>
	   <tbody>
		  <?php
		  for($i = 0; $i < count($stock); $i++) {
		  	?>
		  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			  	<td><a href="product_profile.php?pid=<?php echo $stock[$i]['id']; ?>" target="_blank"><?php echo $stock[$i]['name']; ?></a></td>
			  	<td><?php echo $stock[$i]['sku']; ?></td>
			  	<td class="dataOrdered" align="center"><?php echo $stock[$i]['id']; ?></td>
			  	<td><?php echo $stock[$i]['orders']; ?></td>
				<td><?php echo $stock[$i]['qty_sold']; ?></td>
			  	<td align="right"><?php echo $form->GetHTML('alert_'.$stock[$i]['id']); ?></td>
			  	<td align="right"><?php echo $form->GetHTML('reorder_'.$stock[$i]['id']); ?></td>
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
		$string .= "OR c.Category_ID=".mysql_real_escape_string($children->Row['Category_ID'])." ";
		$string .= GetChildIDS($children->Row['Category_ID']);
		$children->Next();
	}
	$children->Disconnect();
	return $string;
}