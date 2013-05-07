<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);
view($session->Supplier->ID);
exit;

function view($supplierId) {
    $page = new Page('Pending Supplier Return Requests', 'Listing all pending supplier return requests.');
	$page->Display('header');

	$table = new DataTable('requests');
	$table->SetSQL(sprintf("SELECT srr.*, DATE(srr.CreatedOn) AS CreatedDate FROM supplier_return_request AS srr WHERE srr.Status LIKE 'Pending' AND srr.SupplierID=%d", $supplierId));
	$table->AddField('ID', 'SupplierReturnRequestID', 'left');
	$table->AddField('Created Date', 'CreatedDate', 'left');
	$table->AddField('Status', 'Status', 'left');
	$table->AddField('Authorisation', 'AuthorisationNumber', 'left');
	$table->AddField('Total', 'Total', 'right');
	$table->AddLink("supplier_supplier_return_request_details.php?id=%s", "<img src=\"images/folderopen.gif\" alt=\"Open Supplier Return Request\" border=\"0\">", "SupplierReturnRequestID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("CreatedDate");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}