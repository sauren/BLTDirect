<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(2);
view($session->Supplier->ID);
exit;

function view($supplierId) {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 1, 11);
	$form->AddField('purchasedate', 'Purchase Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('filter', 'Filter Orders', 'select', '', 'alpha', 0, 1, false);
	$form->AddOption('filter', '', '');
	$form->AddOption('filter', 'P', 'Pending Only');
	$form->AddOption('filter', 'B', 'Backordered Only');

	$page = new Page('Pending Orders', 'Listing all pending orders.');
	$page->LinkScript('js/scw.js');
	$page->Display('header');

	$window = new StandardWindow('Search orders');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('filter'), $form->GetHTML('filter') . '<input type="submit" name="search" value="search" class="btn" />');
	echo $webForm->AddRow($form->GetLabel('purchasedate'), $form->GetHTML('purchasedate') . '<input type="button" name="print" value="print purchases" class="btn" onclick="popUrl(\'supplier_purchase_print.php?date=\' + document.getElementById(\'purchasedate\').value, 800, 600);" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();
	
	echo '<br />';
	?>

	<div>
		<span style="padding-right: 10px;"><img src="images/legend_red.gif" height="22" width="22" alt="VAT Free" align="absmiddle" /> VAT free orders</span>
		<span style="padding-right: 10px;"><img src="images/legend_orange.gif" height="22" width="22" alt="Restocked" align="absmiddle" /> Restocked undeclined warehouse orders</span>
		<span style="padding-right: 10px;"><img src="images/legend_yellow.gif" height="22" width="22" alt="Next Day Delivery" align="absmiddle" /> Next day delivery orders</span>
		<span style="padding-right: 10px;"><img src="images/legend_green.gif" height="22" width="22" alt="Undeclined" align="absmiddle" /> Undeclined warehouse orders</span>
		<span style="padding-right: 10px;"><img src="images/legend_cyan.gif" height="22" width="22" alt="Over &pound;100.00" align="absmiddle" /> Value over &pound;100.00 orders</span>
		<span style="padding-right: 10px;"><img src="images/legend_blue.gif" height="22" width="22" alt="Plain Label" align="absmiddle" /> Plain despatch label orders</span>
	</div>
	<br />

	<?php
	$filter = '';

	switch($form->GetValue('filter')) {
		case 'P':
			$filter = ' AND ISNULL(ol2.Backorder_Expected_On)';
			break;

		case 'B':
			$filter = ' AND ol2.Backorder_Expected_On<>\'0000-00-00 00:00:00\'';
			break;
	}

	$table = new DataTable('orders');
	$table->SetSQL(sprintf("SELECT o.*, p.Postage_Title, p.Postage_Days, IF(MIN(ol2.Backorder_Expected_On)<>'0000-00-00 00:00:00', MIN(ol2.Backorder_Expected_On), '') AS Backorder_Date FROM orders AS o INNER JOIN postage AS p ON o.Postage_ID=p.Postage_ID INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Despatch_ID=0 INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' AND w.Type_Reference_ID=%d LEFT JOIN (SELECT ol.Order_ID, ol.Backorder_Expected_On FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' AND w.Type_Reference_ID=%d WHERE ol.Despatch_ID=0 AND ol.Line_Status LIKE 'Backordered') AS ol2 ON ol2.Order_ID=o.Order_ID WHERE o.Is_Declined='N' AND o.Is_Failed='N' AND o.Is_Warehouse_Declined='N' AND o.Status IN ('Packing', 'Partially Despatched') AND o.Is_Collection='N'%s GROUP BY o.Order_ID", $supplierId, $supplierId, $filter));
	$table->AddBackgroundCondition('Is_Restocked', 'Y', '==', '#FFD399', '#EEB577');
	$table->AddBackgroundCondition('Postage_Days', '1', '==', '#FFF499', '#EEE177');
	$table->AddBackgroundCondition('Is_Warehouse_Undeclined', 'Y', '==', '#99FF99', '#77EE77');
	$table->AddBackgroundCondition('TaxExemptCode', '', '!=', '#FF9999', '#EE7777');
	$table->AddBackgroundCondition('Is_Plain_Label', 'Y', '==', '#99C5FF', '#77B0EE');
	$table->AddBackgroundCondition('Warehouse_Total', '100.00', '>', '#99FFFB', '#8EECE8');
	$table->AddField('', 'Is_Restocked', 'hidden');
	$table->AddField('', 'TaxExemptCode', 'hidden');
	$table->AddField('', 'Is_Warehouse_Undeclined', 'hidden');
	$table->AddField('', 'Postage_Days', 'hidden');
	$table->AddField('', 'Is_Plain_Label', 'hidden');
	$table->AddField('', 'Warehouse_Total', 'hidden');
	$table->AddField('ID', 'Order_ID');
	$table->AddField('Created Date','Created_On');
	$table->AddField('Status', 'Status', 'left');
	$table->AddField('Postage Details', 'Postage_Title', 'left');
	$table->AddField('Prefix', 'Order_Prefix', 'center');
	$table->AddField('Expected', 'Backorder_Date', 'left');
	$table->AddLink('supplier_order_details.php?orderid=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'Order_ID');
	$table->SetMaxRows(25);
	$table->SetOrderBy('Created_On');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo '<input name="print orders" type="button" value="print together" class="btn" onclick="popUrl(\'supplier_order_print_picking.php?style=break\', 800, 600);" /> ';
	echo '<input name="print orders" type="button" value="print individually" class="btn" onclick="popUrl(\'supplier_order_print_picking.php?style=page\', 800, 600);" /> ';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}