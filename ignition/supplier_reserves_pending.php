<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);
view($session->Supplier->ID);
exit;

function view($supplierId) {
    $page = new Page('Pending Reserves', 'Listing all pending reserves.');
	$page->Display('header');

	$table = new DataTable('reserves');
	$table->SetSQL(sprintf("SELECT r.*, DATE(r.createdOn) AS createdDate, IF(c2.Contact_ID IS NULL, CONCAT_WS(' ', p.Name_First, p.Name_Last), CONCAT_WS(' ', o.Org_Name, CONCAT('(', CONCAT_WS(' ', p.Name_First, p.Name_Last), ')'))) AS supplier FROM reserve AS r INNER JOIN supplier AS s ON s.Supplier_ID=r.supplierId INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE r.status LIKE 'Pending' AND r.supplierId=%d", $supplierId));
	$table->AddField('ID', 'id');
	$table->AddField('Created Date','createdDate');
	$table->AddLink('reserve_details.php?id=%s', '<img src="images/folderopen.gif" alt="Open" border="0" />', 'id');
	$table->SetMaxRows(25);
	$table->SetOrderBy('createdDate');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}