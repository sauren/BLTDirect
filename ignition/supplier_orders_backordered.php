<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);
view($session->Supplier->ID);
exit;

function view($supplierId) {
	$page = new Page('Pending Orders', 'Listing all pending orders.');
	$page->Display('header');
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
	$table = new DataTable('orders');
	$table->SetSQL(sprintf("SELECT o.*, p.Postage_Title, p.Postage_Days, IF(MIN(ol2.Backorder_Expected_On)<>'0000-00-00 00:00:00', MIN(ol2.Backorder_Expected_On), '') AS Backorder_Date FROM orders AS o INNER JOIN postage AS p ON o.Postage_ID=p.Postage_ID INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Despatch_ID=0 INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' AND w.Type_Reference_ID=%d LEFT JOIN (SELECT ol.Order_ID, ol.Backorder_Expected_On FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' AND w.Type_Reference_ID=%d WHERE ol.Despatch_ID=0 AND ol.Line_Status LIKE 'Backordered') AS ol2 ON ol2.Order_ID=o.Order_ID WHERE o.Is_Declined='N' AND o.Is_Failed='N' AND o.Is_Warehouse_Declined='N' AND o.Status IN ('Packing', 'Partially Despatched') AND o.Is_Collection='N' AND ol2.Order_ID IS NOT NULL GROUP BY o.Order_ID", $supplierId, $supplierId));
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

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}