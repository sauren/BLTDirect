<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);
view($session->Supplier->ID);
exit;

function view($supplierId) {
    $page = new Page('Unfulfilled Purchase Orders', 'Listing all unfulfilled purchase orders.');
	$page->Display('header');

	$table = new DataTable('purchaseorders');
	$table->SetSQL(sprintf("SELECT *, DATE(Created_On) AS CreatedDate FROM purchase WHERE Supplier_ID=%d AND For_Branch>0 AND Purchase_Status IN ('Unfulfilled', 'Partially Fulfilled') AND Purchased_On<NOW()", $supplierId));
	$table->AddField('ID', 'Purchase_ID', 'left');
	$table->AddField('Created Date','CreatedDate', 'left');
	$table->AddField('Status', 'Purchase_Status', 'left');
	$table->AddField('Complete', 'Is_Supplier_Complete', 'center');
	$table->AddField('Notes', 'Supplier_Notes', 'left');
	$table->AddLink('supplier_purchase_order_details.php?id=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'Purchase_ID');
	$table->SetMaxRows(25);
	$table->SetOrderBy('CreatedDate');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}