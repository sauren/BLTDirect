<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTask.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTaskArchive.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTaskSchedule.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTaskScheduleChecklist.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTaskUser.php');


if($action == 'add') {
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'addschedule') {
	$session->Secure(3);
	addSchedule();
	exit;
} elseif($action == 'addarchive') {
	$session->Secure(3);
	addArchive();
	exit;
} elseif($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'removeschedule') {
	$session->Secure(3);
	removeSchedule();
	exit;
} elseif($action == 'removearchive') {
	$session->Secure(3);
	removeArchive();
	exit;	
} elseif($action == 'update') {
	$session->Secure(3);
	update();
	exit;
} elseif($action == 'completeschedule') {
	$session->Secure(3);
	completeSchedule();
	exit;
} elseif($action == 'schedules') {
	$session->Secure(3);
	schedules();
	exit;
} elseif($action == 'schedulechecklist') {
	$session->Secure(3);
	scheduleChecklist();
	exit;
} elseif($action == 'archives') {
	$session->Secure(3);
	archives();
	exit;
} elseif($action == 'download') {
	$session->Secure(2);
	download();
	exit;
} elseif($action == 'downloadschedule') {
	$session->Secure(2);
	downloadSchedule();
	exit;
} elseif($action == 'downloadarchive') {
	$session->Secure(2);
	downloadArchive();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$task = new WorkTask();
		$task->delete($_REQUEST['id']);
	}

	redirect('Location: ?action=view');
}

function removeSchedule() {
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$schedule = new WorkTaskSchedule($_REQUEST['id']);
		$schedule->delete();
		
		redirect(sprintf('Location: ?action=schedules&id=%d', $schedule->workTaskId));
	}

	redirect('Location: ?action=view');
}

function removeArchive() {
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$archive = new WorkTaskArchive($_REQUEST['id']);
		$archive->delete();
		
		redirect(sprintf('Location: ?action=archives&id=%d', $archive->workTaskId));
	}

	redirect('Location: ?action=view');
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Name', 'text', '', 'paragraph', 0, 120);
	$form->AddField('user', 'User', 'selectmultiple', '', 'numeric_unsigned', 1, 11);
	
	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('user', $data->Row['User_ID'], $data->Row['Name']);
		
		$data->Next();
	}
	$data->Disconnect();
	
	$form->AddField('startedon', 'Started On', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('period', 'Period', 'select', '1', 'float', 1, 11);
	$form->AddOption('period', '1', 'Daily');
	$form->AddOption('period', '7', 'Weekly');
	$form->AddOption('period', '15.2188', 'Bimonthly');
	$form->AddOption('period', '30.4375', 'Monthly');
	$form->AddOption('period', '91.3125', 'Quarterly');
	$form->AddOption('period', '182.625', 'Biannually');
	$form->AddOption('period', '365.25', 'Annually');
	$form->AddOption('period', '1826.25', 'Quintiennially');
	$form->AddField('isuploadrequired', 'Is Upload Required', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('file', 'File', 'file', '', 'file', null, null, false);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$task = new WorkTask();
			$task->name = $form->GetValue('name');
			$task->startedOn = (strlen($form->GetValue('startedon')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startedon'), 6, 4), substr($form->GetValue('startedon'), 3, 2), substr($form->GetValue('startedon'), 0, 2)) : '0000-00-00 00:00:00';
			$task->period = $form->GetValue('period');
			$task->isUploadRequired = $form->GetValue('isuploadrequired');
			$task->add('file');
			
			foreach($form->GetValue('user') as $userId) {
				$user = new WorkTaskUser();
				$user->workTaskId = $task->id;
				$user->user->ID = $userId;
				$user->add();
			}

			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">Health &amp; Safety Tasks</a> &gt; Add Health &amp; Safety Task', 'Add a new health &amp; safety task.');
	$page->LinkScript('js/scw.js');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Adding a health &amp; safety task');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	
	echo $window->Open();
	echo $window->AddHeader('Enter health &amp; safety task details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('startedon'), $form->GetHTML('startedon') . $form->GetIcon('startedon'));
	echo $webForm->AddRow($form->GetLabel('user'), $form->GetHTML('user') . $form->GetIcon('user'));
	echo $webForm->AddRow($form->GetLabel('period'), $form->GetHTML('period') . $form->GetIcon('period'));
	echo $webForm->AddRow($form->GetLabel('isuploadrequired'), $form->GetHTML('isuploadrequired') . $form->GetIcon('isuploadrequired'));
	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addSchedule() {
	$task = new WorkTask();
	
	if(!isset($_REQUEST['id']) || !$task->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addschedule', 'alpha', 11, 11);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Health &amp; Safety Task ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('user', 'User', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('user', '0', '');
	
	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('user', $data->Row['User_ID'], $data->Row['Name']);
		
		$data->Next();
	}
	$data->Disconnect();
	
	$form->AddField('scheduledon', 'Scheduled On', 'text', date('d/m/Y'), 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$schedule = new WorkTaskSchedule();
			$schedule->workTaskId = $task->id;
			$schedule->user->ID = $form->GetValue('user');
			$schedule->scheduledOn = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('scheduledon'), 6, 4), substr($form->GetValue('scheduledon'), 3, 2), substr($form->GetValue('scheduledon'), 0, 2));
			$schedule->add();
	
			redirect(sprintf('Location: ?action=schedules&id=%d', $task->id));
		}
	}

	$page = new Page(sprintf('<a href="?action=view">Health &amp; Safety Tasks</a> &gt; <a href="?action=schedules&id=%d">Schedules</a> &gt; Add Schedule', $task->id), 'Add a health &amp; safety task schedule.');
	$page->LinkScript('js/scw.js');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Adding a schedule');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Enter schedule details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('scheduledon'), $form->GetHTML('scheduledon') . $form->GetIcon('scheduledon'));
	echo $webForm->AddRow($form->GetLabel('user'), $form->GetHTML('user') . $form->GetIcon('user'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=schedules&id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $task->id, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addArchive() {
	$task = new WorkTask();
	
	if(!isset($_REQUEST['id']) || !$task->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addarchive', 'alpha', 10, 10);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Health &amp; Safety Task ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', '', 'anything', 1, 120);
	$form->AddField('file', 'File', 'file', '', 'file', null, null);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$archive = new WorkTaskArchive();
			$archive->workTaskId = $task->id;
			$archive->name = $form->GetValue('name');
			$archive->add('file');
	
			redirect(sprintf('Location: ?action=archives&id=%d', $task->id));
		}
	}

	$page = new Page(sprintf('<a href="?action=view">Health &amp; Safety Tasks</a> &gt; <a href="?action=archives&id=%d">Archives</a> &gt; Add Archive', $task->id), 'Add a health &amp; safety task archive.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Adding an archive');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Enter archive details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=archives&id=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $task->id, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	$task = new WorkTask();
	
	if(!isset($_REQUEST['id']) || !$task->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$task->getUsers();

	$users = array();
	
	foreach($task->user as $user) {
		$users[] = $user->user->ID;
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $task->name, 'paragraph', 0, 120);
	$form->AddField('user', 'User', 'selectmultiple', $users, 'numeric_unsigned', 1, 11);
	
	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('user', $data->Row['User_ID'], $data->Row['Name']);
		
		$data->Next();
	}
	$data->Disconnect();	
	
	$form->AddField('startedon', 'Started On', 'text', ($task->startedOn != '0000-00-00 00:00:00') ? sprintf('%s/%s/%s', substr($task->startedOn, 8, 2), substr($task->startedOn, 5, 2), substr($task->startedOn, 0, 4)) : '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('period', 'Period', 'select', $task->period, 'float', 1, 11);
	$form->AddOption('period', '1', 'Daily');
	$form->AddOption('period', '7', 'Weekly');
	$form->AddOption('period', '15.2188', 'Bimonthly');
	$form->AddOption('period', '30.4375', 'Monthly');
	$form->AddOption('period', '91.3125', 'Quarterly');
	$form->AddOption('period', '182.625', 'Biannually');
	$form->AddOption('period', '365.25', 'Annually');
	$form->AddOption('period', '1826.25', 'Quintiennially');
	$form->AddField('isuploadrequired', 'Is Upload Required', 'checkbox', $task->isUploadRequired, 'boolean', 1, 1, false);
	$form->AddField('file', 'File', 'file', '', 'file', null, null, false);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			new DataQuery(sprintf("DELETE FROM work_task_user WHERE workTaskId=%d", mysql_real_escape_string($task->id)));
			
			$task->name = $form->GetValue('name');
			$task->startedOn = (strlen($form->GetValue('startedon')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startedon'), 6, 4), substr($form->GetValue('startedon'), 3, 2), substr($form->GetValue('startedon'), 0, 2)) : '0000-00-00 00:00:00';
			$task->period = $form->GetValue('period');
			$task->isUploadRequired = $form->GetValue('isuploadrequired');
			$task->update('file');
			
			foreach($form->GetValue('user') as $userId) {
				$user = new WorkTaskUser();
				$user->workTaskId = $task->id;
				$user->user->ID = $userId;
				$user->add();
			}

			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">Health &amp; Safety Tasks</a> &gt; Update Health &amp; Safety Task', 'Edit a health &amp; safety task.');
	$page->LinkScript('js/scw.js');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Updating a health &amp; safety task');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Update health &amp; safety task details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('startedon'), $form->GetHTML('startedon') . $form->GetIcon('startedon'));
	echo $webForm->AddRow($form->GetLabel('user'), $form->GetHTML('user') . $form->GetIcon('user'));
	echo $webForm->AddRow($form->GetLabel('period'), $form->GetHTML('period') . $form->GetIcon('period'));
	echo $webForm->AddRow($form->GetLabel('isuploadrequired'), $form->GetHTML('isuploadrequired') . $form->GetIcon('isuploadrequired'));
	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function completeSchedule() {
	$schedule = new WorkTaskSchedule();
	
	if(!isset($_REQUEST['id']) || !$schedule->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$task = new WorkTask($schedule->workTaskId);
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'completeschedule', 'alpha', 16, 16);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Health &amp; Safety Task Schedule ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('issatisfactory', 'Is Satisfactory', 'select', '', 'anything', 1, 1, true);
	$form->AddOption('issatisfactory', '', '');
	$form->AddOption('issatisfactory', 'Y', 'Yes');
	$form->AddOption('issatisfactory', 'N', 'No');
	$form->AddField('comments', 'Comments', 'textarea', '', 'anything', null, null, false, 'rows="5" style="width: 300px;"');
	
	if($task->isUploadRequired == 'Y') {
		$form->AddField('file', 'File', 'file', '', 'file', null, null);
	}
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$schedule->isSatisfactory = $form->GetValue('issatisfactory');
			$schedule->comments = $form->GetValue('comments');
			$schedule->update(($task->isUploadRequired == 'Y') ? 'file' : null);
			$schedule->complete();
			
			$items = array();

			foreach($_REQUEST as $key=>$value) {
				if(preg_match('/^issatisfactory_([0-9]+)$/', $key, $matches) || preg_match('/^comments_([0-9]+)$/', $key, $matches)) {
					$items[$matches[1]] = $matches[1];
				}	
			}
			
			foreach($items as $item) {
				$checklist = new WorkTaskScheduleChecklist();
				$checklist->workTaskScheduleId = $schedule->id;
				$checklist->workTaskChecklistId = $item;
				$checklist->isSatisfactory = isset($_REQUEST['issatisfactory_' . $item]) ? 'Y' : 'N';
				$checklist->comments = $_REQUEST['comments_' . $item];
				$checklist->add();
			}
	
			redirect(sprintf('Location: ?action=schedules&id=%d', $task->id));
		}
	}

	$page = new Page(sprintf('<a href="?action=view">Health &amp; Safety Tasks</a> &gt; <a href="?action=schedules&id=%d">Schedules</a> &gt; Complete Schedule', $task->id), 'Add a health &amp; safety task schedule.');
	$page->LinkScript('js/scw.js');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Completing a schedule');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Enter schedule details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('issatisfactory'), $form->GetHTML('issatisfactory') . $form->GetIcon('issatisfactory'));
	echo $webForm->AddRow($form->GetLabel('comments'), $form->GetHTML('comments') . $form->GetIcon('comments'));
	
	if($task->isUploadRequired == 'Y') {
		echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
	}
	
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=schedules&id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $task->id, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo '<br />';
	echo '<h3>Checklist</h3>';
	echo '<p>This health &amp; safety task schedule has the following checklist items.</p>';
	
	$table = new DataTable('worktaskchecklists');
	$table->SetExtractVars();
	$table->SetSQL(sprintf("SELECT * FROM work_task_checklist WHERE workTaskId=%d", mysql_real_escape_string($schedule->workTaskId)));
	$table->AddField("ID#", "id");
	$table->AddField("Name", "name", "left");
	$table->AddInput('Is Satisfactory', 'N', 'Y', 'issatisfactory', 'id', 'checkbox');
	$table->AddInput('Comments', 'N', '', 'comments', 'id', 'text', 'style="width: 600px;"');
	$table->SetMaxRows(25);
	$table->SetOrderBy("name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function download() {
	$task = new WorkTask();
	
	if(!isset($_REQUEST['id']) || !$task->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	if(empty($task->file->FileName) || !file_exists($GLOBALS['WORKTASK_DOCUMENT_DIR_FS'] . $task->file->FileName)) {
		redirect('Location: ?action=view');
	}

	$fileName = $task->file->FileName;
	$filePath = $GLOBALS['WORKTASK_DOCUMENT_DIR_FS'] . $fileName;
	$fileSize = filesize($filePath);

	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private", false);
	header("Content-Transfer-Encoding: binary");
	header("Content-Type: application/force-download");
	header("Content-Length: " . $fileSize);
	header("Content-Disposition: attachment; filename=" . $fileName);

	readfile($filePath);

	require_once('lib/common/app_footer.php');
}

function downloadSchedule() {
	$schedule = new WorkTaskSchedule();
	
	if(!isset($_REQUEST['id']) || !$schedule->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	if(empty($schedule->file->FileName) || !file_exists($GLOBALS['WORKTASK_SCHEDULE_DOCUMENT_DIR_FS'] . $schedule->file->FileName)) {
		redirect('Location: ?action=view');
	}

	$fileName = $schedule->file->FileName;
	$filePath = $GLOBALS['WORKTASK_SCHEDULE_DOCUMENT_DIR_FS'] . $schedule->file->FileName;
	$fileSize = filesize($filePath);

	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private", false);
	header("Content-Transfer-Encoding: binary");
	header("Content-Type: application/force-download");
	header("Content-Length: " . $fileSize);
	header("Content-Disposition: attachment; filename=" . $fileName);

	readfile($filePath);

	require_once('lib/common/app_footer.php');
}

function downloadArchive() {
	$archive = new WorkTaskArchive();
	
	if(!isset($_REQUEST['id']) || !$archive->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	if(empty($archive->file->FileName) || !file_exists($GLOBALS['WORKTASK_ARCHIVE_DOCUMENT_DIR_FS'] . $archive->file->FileName)) {
		redirect('Location: ?action=view');
	}

	$filePath = $GLOBALS['WORKTASK_ARCHIVE_DOCUMENT_DIR_FS'] . $archive->file->FileName;
	$fileSize = filesize($filePath);

	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private", false);
	header("Content-Transfer-Encoding: binary");
	header("Content-Type: application/force-download");
	header("Content-Length: " . $fileSize);
	header("Content-Disposition: attachment; filename=" . $filePath);

	readfile($filePath);

	require_once('lib/common/app_footer.php');
}

function schedules() {
	$task = new WorkTask();
	
	if(!isset($_REQUEST['id']) || !$task->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$page = new Page('<a href="?action=view">Health &amp; Safety Tasks</a> &gt; Schedules', 'Listing all schedules for this health &amp; safety task.');
	$page->Display('header');

	$table = new DataTable('worktaskschedules');
	$table->SetSQL(sprintf("SELECT wts.*, DATE(wts.scheduledOn) AS scheduledOn, DATE(wts.completedOn) AS completedOn, wt.name, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS userName, CONCAT_WS(' ', p2.Name_First, p2.Name_Last) AS completedUserName, COUNT(wtsc.id) AS checklistCount FROM work_task_schedule AS wts INNER JOIN work_task AS wt ON wt.id=wts.workTaskId LEFT JOIN work_task_schedule_checklist AS wtsc ON wtsc.workTaskScheduleId=wts.id LEFT JOIN users AS u ON u.User_ID=wts.userId LEFT JOIN person AS p ON p.Person_ID=u.Person_ID LEFT JOIN users AS u2 ON u2.User_ID=wts.completedBy LEFT JOIN person AS p2 ON p2.Person_ID=u2.Person_ID WHERE wt.id=%d GROUP BY wts.id", mysql_real_escape_string($task->id)));
	$table->AddField('', 'isComplete', 'hidden');
	$table->AddField('', 'file', 'hidden');
	$table->AddField('', 'checklistCount', 'hidden');
	$table->AddField("ID#", "id");
	$table->AddField("Scheduled", "scheduledOn", "left");
	$table->AddField("Task", "name", "left");
	$table->AddField("User", "userName", "left");
	$table->AddField("Is Satisfactory", "isSatisfactory", "center");
	$table->AddField("Comments", "comments", "left");
	$table->AddField("Is Complete", "isComplete", "center");
	$table->AddField("Completed On", "completedOn", "left");
	$table->AddField("Completed By", "completedUserName", "left");
	$table->AddLink("?action=schedulechecklist&id=%s","<img src=\"images/folderopen.gif\" alt=\"Checklists\" border=\"0\">", "id", true, false, array('checklistCount', '>', '0'));
	$table->AddLink("?action=downloadschedule&id=%s","<img src=\"images/aztector_4.gif\" alt=\"Download\" border=\"0\">", "id", true, false, array('file', '!=', ''));
	$table->AddLink("?action=completeschedule&id=%s","<img src=\"images/button-tick.gif\" alt=\"Update\" border=\"0\">", "id", true, false, array('isComplete', '=', 'N'));
	$table->AddLink("javascript:confirmRequest('?action=removeschedule&id=%s', 'Are you sure you want to remove this item?');", "<img src=\"images/button-cross.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("scheduledOn");
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add schedule" class="btn" onclick="window.location.href=\'?action=addschedule&id=%d\'" />', $task->id);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function scheduleChecklist() {
	$schedule = new WorkTaskSchedule();
	
	if(!isset($_REQUEST['id']) || !$schedule->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$page = new Page(sprintf('<a href="?action=view">Health &amp; Safety Tasks</a> &gt; <a href="?action=schedules&id=%d">Schedules</a> &gt; Schedule Checklist', $schedule->workTaskId), 'Listing all checklist items for this schedule.');
	$page->Display('header');

	$table = new DataTable('worktaskschedulechecklist');
	$table->SetSQL(sprintf("SELECT wtc.id, wtc.name, wtsc.isSatisfactory, wtsc.comments FROM work_task_schedule AS wts INNER JOIN work_task_checklist AS wtc ON wtc.workTaskId=wts.workTaskID INNER JOIN work_task_schedule_checklist AS wtsc ON wtsc.workTaskScheduleId=wts.id AND wtsc.workTaskChecklistId=wtc.id WHERE wts.id=%d", mysql_real_escape_string($schedule->id)));
	$table->AddField("ID#", "id");
	$table->AddField("Checklist", "name", "left");
	$table->AddField("Is Satisfactory", "isSatisfactory", "center");
	$table->AddField("Comments", "comments", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("name");
	$table->Order = 'ASC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function archives() {
	$task = new WorkTask();
	
	if(!isset($_REQUEST['id']) || !$task->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$page = new Page('<a href="?action=view">Health &amp; Safety Tasks</a> &gt; Archives', 'Listing all archives for this health &amp; safety task.');
	$page->Display('header');

	$table = new DataTable('worktaskarchives');
	$table->SetSQL(sprintf("SELECT * FROM work_task_archive WHERE workTaskId=%d", mysql_real_escape_string($task->id)));
	$table->AddField('', 'file', 'hidden');
	$table->AddField("ID#", "id");
	$table->AddField("Name", "name", "left");
	$table->AddLink("?action=downloadarchive&id=%s","<img src=\"images/aztector_4.gif\" alt=\"Download\" border=\"0\">", "id", true, false, array('file', '!=', ''));
	$table->AddLink("javascript:confirmRequest('?action=removearchive&id=%s', 'Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("name");
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add archive" class="btn" onclick="window.location.href=\'?action=addarchive&id=%d\'" />', $task->id);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$page = new Page('Health &amp; Safety Tasks', 'Listing all health &amp; safety tasks.');
	$page->Display('header');

	$table = new DataTable('worktasks');
	$table->SetSQL("SELECT wt.*, IF(wtu.users>1, '<em>&lt;Multiple Users&gt;</em>', wtu.user) AS user, IF(wt.period=1, 'Daily', IF(wt.period=7, 'Weekly', IF(wt.period=15.2188, 'Bimonthly', IF(wt.period=30.4375, 'Monthly', IF(wt.period=91.3125, 'Quarterly', IF(wt.period=182.625, 'Biannually', IF(wt.period=365.25, 'Annually', IF(wt.period=1826.25, 'Quintiennially', '')))))))) AS periodText, DATE(wt.startedOn) AS startedOn, DATE(MIN(wts.scheduledOn)) AS firstScheduledOn, DATE(MIN(wts2.scheduledOn)) AS nextScheduledOn FROM work_task AS wt LEFT JOIN work_task_schedule AS wts ON wts.workTaskId=wt.id LEFT JOIN work_task_schedule AS wts2 ON wts2.workTaskId=wt.id AND wts2.isComplete='N' LEFT JOIN (SELECT wtu.workTaskId, COUNT(wtu.userId) AS users, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS user FROM work_task_user AS wtu INNER JOIN users AS u ON u.User_ID=wtu.userId INNER JOIN person AS p ON p.Person_ID=u.Person_ID GROUP BY wtu.workTaskId) AS wtu ON wtu.workTaskId=wt.id GROUP BY wt.id");
	$table->AddField("", "file", "hidden");
	$table->AddField("ID#", "id");
	$table->AddField("Name", "name", "left");
	$table->AddField("Period", "periodText", "left");
	$table->AddField("User", "user", "left");
	$table->AddField("First Schedule", "firstScheduledOn", "left");
	$table->AddField("Next Schedule", "nextScheduledOn", "left");
	$table->AddLink("?action=download&id=%s","<img src=\"images/aztector_4.gif\" alt=\"Download\" border=\"0\">", "id", true, false, array('file', '!=', ''));
	$table->AddLink("?action=archives&id=%s","<img src=\"images/icon_view_2.gif\" alt=\"Archives\" border=\"0\">", "id");
	$table->AddLink("?action=schedules&id=%s","<img src=\"images/folderopen.gif\" alt=\"Schedules\" border=\"0\">", "id");
	$table->AddLink("work_task_checklists.php?id=%s","<img src=\"images/i_document.gif\" alt=\"Checklists\" border=\"0\">", "id");
	$table->AddLink("?action=update&id=%s","<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add task" class="btn" onclick="window.location.href=\'?action=add\'" />';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}