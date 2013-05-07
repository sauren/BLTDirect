<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);
view($session->Supplier->ID);
exit;

function view($supplierId) {
    $page = new Page('Backordered Products', 'Listing backordered products in pending orders.');
	$page->Display('header');

	$table = new DataTable('products');
	$table->SetSQL(sprintf("SELECT ol.*, SUM(ol.Quantity) AS Quantity FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Despatch_ID=0 AND ol.Line_Status LIKE 'Backordered' INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type_Reference_ID=%d AND w.Type='S' WHERE o.Is_Declined='N' AND o.Is_Failed='N' AND o.Is_Warehouse_Declined='N' AND o.Status IN ('Packing', 'Partially Despatched') GROUP BY ol.Product_ID", $supplierId));
	$table->AddField('ID', 'Product_ID');
	$table->AddField('Product','Product_Title');
	$table->AddField('Quantity','Quantity', 'right');
	$table->AddField('Expected','Backorder_Expected_On');
	$table->AddLink("supplier_order_details.php?orderid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Order_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Backorder_Expected_On');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}