<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);
view($session->Supplier->ID);
exit;

function view($supplierId) {
	$page = new Page('Despatched Orders', 'Listing all despatched orders.');
	$page->Display('header');

	$table = new DataTable('orders');
	$table->SetSQL(sprintf("SELECT o.*, p.Postage_Title, p.Postage_Days FROM orders AS o INNER JOIN postage AS p ON o.Postage_ID=p.Postage_ID INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' AND w.Type_Reference_ID=%d WHERE o.Status IN ('Despatched') GROUP BY o.Order_ID", $supplierId));
	$table->AddField('ID', 'Order_ID');
	$table->AddField('Created Date','Created_On');
	$table->AddField('Status', 'Status', 'left');
	$table->AddField('Postage Details', 'Postage_Title', 'left');
	$table->AddField('Prefix', 'Order_Prefix', 'center');
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