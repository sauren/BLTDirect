<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierReturnRequest.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	if(isset($_REQUEST['id'])) {
		$request = new SupplierReturnRequest();
		$request->Delete($_REQUEST['id']);
	}

	redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
}

function view() {
	$page = new Page('Outstanding Warehouse Return', 'Listing all pending supplier return requests.');
	$page->Display('header');

	$table = new DataTable('requests');
	$table->SetSQL(sprintf("SELECT srr.*, IF((LENGTH(TRIM(o.Org_Name)) > 0) AND (LENGTH(TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last))) > 0), CONCAT_WS(' ', TRIM(o.Org_Name), CONCAT('(', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), ')')), IF(LENGTH(TRIM(o.Org_Name)) > 0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS SupplierName FROM supplier_return_request AS srr INNER JOIN supplier AS s ON s.Supplier_ID=srr.SupplierID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE srr.Status LIKE 'Pending' AND srr.PurchaseID>0"));
	$table->AddField('ID', 'SupplierReturnRequestID');
	$table->AddField('Request Date','CreatedOn');
	$table->AddField('Supplier','SupplierName');
	$table->AddField('Authorisation', 'AuthorisationNumber');
	$table->AddField('Total', 'Total', 'right');
	$table->AddLink('supplier_return_request_details.php?requestid=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'SupplierReturnRequestID');
	$table->AddLink(sprintf('javascript:confirmRequest(\'%s?action=remove&id=%%s\', \'Are you sure you want to remove this item?\');', $_SERVER['PHP_SELF']), '<img src="images/aztector_6.gif" alt="Remove" border="0" />', 'SupplierReturnRequestID');
	$table->SetMaxRows(25);
	$table->SetOrderBy('CreatedOn');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
}