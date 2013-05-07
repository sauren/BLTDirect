<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTask.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTaskChecklist.php');

if($action == 'add') {
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'update') {
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$task = new WorkTaskChecklist($_REQUEST['id']);
		$task->delete();
		
		redirect(sprintf('Location: ?id=%d', $task->workTaskId));
	}

	redirect('Location: work_tasks.php');
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Health &amp; Safety Task ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', '', 'paragraph', 0, 120);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$checklist = new WorkTaskChecklist();
			$checklist->workTaskId = $form->GetValue('id');
			$checklist->name = $form->GetValue('name');
			$checklist->add();

			redirect(sprintf('Location: ?id=%d', $checklist->workTaskId));
		}
	}

	$page = new Page(sprintf('<a href="work_tasks.php">Health &amp; Safety Tasks</a> &gt; <a href="?id=%d">Checklists</a> &gt; Add Checklist', $form->GetValue('id')), 'Add a new checklist.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Adding a checklist');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Enter checklist details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?id=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $form->GetValue('id'), $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	$checklist = new WorkTaskChecklist();
	
	if(!isset($_REQUEST['id']) || !$checklist->get($_REQUEST['id'])) {
		redirect('Location: work_tasks.php');
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Health &amp; Safety Task Checklist ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $checklist->name, 'paragraph', 0, 120);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$checklist->name = $form->GetValue('name');
			$checklist->update();

			redirect(sprintf('Location: ?id=%d', $checklist->workTaskId));
		}
	}

	$page = new Page(sprintf('<a href="work_tasks.php">Health &amp; Safety Tasks</a> &gt; <a href="?id=%d">Checklists</a> &gt; Add Checklist', $checklist->workTaskId), 'Update an existing checklist.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Updating a checklist');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Enter checklist details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?id=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $checklist->workTaskId, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

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
	$table->AddLink("?action=update&id=%s","<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "id");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s', 'Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add checklist" class="btn" onclick="window.location.href=\'?action=add&id=%d\'" />', $task->id);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}