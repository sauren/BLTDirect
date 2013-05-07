<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkLog.php');

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
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$log = new WorkLog();
		$log->delete($_REQUEST['id']);
	}

	redirect('Location: ?action=view');
}

function view() {
	$page = new Page('Health &amp; Safety Logs', 'Listing all health &amp; safety logs.');
	$page->Display('header');

	$table = new DataTable('worklogs');
	$table->SetSQL("SELECT wl.*, DATE(wl.createdOn) AS createdOn, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS user FROM work_log AS wl INNER JOIN users AS u ON u.User_ID=wl.createdBy INNER JOIN person AS p ON p.Person_ID=u.Person_ID");
	$table->AddField("ID#", "id");
	$table->AddField("Created", "createdOn", "left");
	$table->AddField("Type", "type", "left");
	$table->AddField("Log", "log", "left");
	$table->AddField("User", "user", "left");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"./images/button-cross.gif\" alt=\"Remove item\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("createdOn");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}