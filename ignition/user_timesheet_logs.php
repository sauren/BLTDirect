<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

if($action == 'hours') {
	$session->Secure(2);
	hours();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function hours() {
	if(!isset($_REQUEST['userid'])) {
		redirect(sprintf("Location: users.php"));
	}

	$user = new User($_REQUEST['userid']);
	
	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: users.php"));
	}

	$page = new Page(sprintf('<a href="users.php">Users</a> &gt; <a href="?id=%d">Timesheet Log</a> &gt; Log Hours', $user->ID), sprintf('Listing logged hours for %s.', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName))));
	$page->Display('header');

	$table = new DataTable('hours');
	$table->SetSQL(sprintf("SELECT * FROM timesheet_log_hour WHERE timesheetLogId=%d", $_REQUEST['id']));
	$table->AddField("ID", "id", "left");
	$table->AddField("Type", "type", "left");
	$table->AddField("Hours", "hours", "right");
	$table->SetMaxRows(25);
	$table->SetOrderBy("type");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: users.php"));
	}

	$user = new User($_REQUEST['id']);

	$page = new Page(sprintf('<a href="users.php">Users</a> &gt; Timesheet Log'), sprintf('Listing timesheet log records for %s.', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName))));
	$page->Display('header');

	$table = new DataTable('logs');
	$table->SetSQL(sprintf("SELECT tl.*, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS createdName FROM timesheet_log AS tl LEFT JOIN users AS u ON u.User_ID=tl.createdBy INNER JOIN person AS p ON p.Person_ID=u.Person_ID WHERE tl.userId=%d", $user->ID));
	$table->AddField("ID", "id", "left");
	$table->AddField("Created On", "createdOn", "left");
	$table->AddField("Created By", "createdName", "left");
	$table->AddField("Period Start", "periodStartOn", "left");
	$table->AddField("Period End", "periodEndOn", "left");
	$table->AddField("Bonus", "bonus", "right");
	$table->AddLink(sprintf("?action=hours&id=%%s&userid=%d", $user->ID), "<img src=\"./images/folderopen.gif\" alt=\"Hours\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("createdOn");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}