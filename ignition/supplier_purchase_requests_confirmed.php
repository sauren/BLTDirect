<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);
view($session->Supplier->ID);
exit;

function view($supplierId) {
    $page = new Page('Confirmed Purchase Requests', 'Listing all confirmed purchase requests.');
	$page->Display('header');

	$table = new DataTable('purchaserequests');
	$table->SetSQL(sprintf("SELECT *, DATE(CreatedOn) AS CreatedDate FROM purchase_request WHERE Status LIKE 'Confirmed' AND SupplierID=%d", $supplierId));
	$table->AddField('ID', 'PurchaseRequestID');
	$table->AddField('Created Date','CreatedDate');
	$table->AddField('Status', 'Status', 'left');
	$table->AddLink('supplier_purchase_request_details.php?id=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'PurchaseRequestID');
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