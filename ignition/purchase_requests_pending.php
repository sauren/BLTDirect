<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseRequest.php');

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
		$request = new PurchaseRequest();
		$request->Delete($_REQUEST['id']);
	}

	redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
}

function view() {
	$page = new Page('Pending Purchase Requests', 'Listing all pending purchase requests.');
	$page->Display('header');

	$table = new DataTable('requests');
	$table->SetSQL(sprintf("SELECT pr.*, IF((LENGTH(TRIM(o.Org_Name)) > 0) AND (LENGTH(TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last))) > 0), CONCAT_WS(' ', TRIM(o.Org_Name), CONCAT('(', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), ')')), IF(LENGTH(TRIM(o.Org_Name)) > 0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS Supplier_Name FROM purchase_request AS pr INNER JOIN supplier AS s ON s.Supplier_ID=pr.SupplierID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE pr.Status LIKE 'Pending'"));
	$table->AddField('ID','PurchaseRequestID');
	$table->AddField('Request Date','CreatedOn');
	$table->AddField('Supplier','Supplier_Name');
	$table->AddLink('purchase_request_details.php?id=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'PurchaseRequestID');
	$table->AddLink(sprintf('javascript:confirmRequest(\'%s?action=remove&id=%%s\', \'Are you sure you want to remove this item?\');', $_SERVER['PHP_SELF']), '<img src="images/aztector_6.gif" alt="Remove" border="0" />', 'PurchaseRequestID');
	$table->SetMaxRows(25);
	$table->SetOrderBy('CreatedOn');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
}