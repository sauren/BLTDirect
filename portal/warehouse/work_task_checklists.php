<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTask.php');

$session->Secure(2);
view();
exit;

function view() {
	$task = new WorkTask();
	
	if(!isset($_REQUEST['id']) || !$task->get($_REQUEST['id'])) {
		redirect('Location: work_tasks.php');	
	}
	
	$page = new Page('<a href="work_tasks.php">Health &amp; Safety Tasks</a> &gt; Checklists', 'Listing all checklists.');
	$page->Display('header');

	$table = new DataTable('worktaskchecklists');
	$table->SetSQL(sprintf("SELECT * FROM work_task_checklist WHERE workTaskId=%d", $task->id));
	$table->AddField("ID#", "id");
	$table->AddField("Name", "name", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}