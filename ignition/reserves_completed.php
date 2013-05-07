<?php
require_once('lib/common/app_header.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Reserve.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$reserve = new Reserve($_REQUEST['id']);
		$reserve->Delete();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Completed Reserves', 'Below is a list of all completed items.');
	$page->Display('header');

	$table = new DataTable("reserves");
	$table->SetSQL(sprintf("SELECT r.*, IF(c2.Contact_ID IS NULL, CONCAT_WS(' ', p.Name_First, p.Name_Last), CONCAT_WS(' ', o.Org_Name, CONCAT('(', CONCAT_WS(' ', p.Name_First, p.Name_Last), ')'))) AS supplier FROM reserve AS r INNER JOIN supplier AS s ON s.Supplier_ID=r.supplierId INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE r.status LIKE 'Completed'"));
	$table->AddField('Reserve ID', 'id', 'left');
	$table->AddField('Reserve Date', 'createdOn', 'left');
	$table->AddField('Supplier', 'supplier', 'left');
	$table->AddLink("reserve_details.php?id=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "id");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/button-cross.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("createdOn");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}