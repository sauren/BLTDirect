<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTask.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTaskSchedule.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTaskScheduleChecklist.php');

if($action == 'complete') {
	$session->Secure(3);
	complete();
	exit;
} elseif($action == 'download') {
	$session->Secure(2);
	download();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function complete() {
	$schedule = new WorkTaskSchedule();
	
	if(!isset($_REQUEST['id']) || !$schedule->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$task = new WorkTask();
	$task->get($schedule->workTaskId);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'complete', 'alpha', 8, 8);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('issatisfactory', 'Is Satisfactory', 'select', '', 'anything', 1, 1, true);
	$form->AddOption('issatisfactory', '', '');
	$form->AddOption('issatisfactory', 'Y', 'Yes');
	$form->AddOption('issatisfactory', 'N', 'No');
	$form->AddField('comments', 'Comments', 'textarea', '', 'anything', null, null, false, 'rows="5" style="width: 300px;"');
	
	if($task->isUploadRequired == 'Y') {
		$form->AddField('file', 'File', 'file', '', 'file', null, null);
	}

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
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
			
			$schedule->email();

			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">Health &amp; Safety Task Schedules</a> &gt; Update Health &amp; Safety Task Schedule', 'Complete a health &amp; safety task schedule.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Complete a health &amp; safety task schedule');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Enter general health &amp; safety task schedule details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	
	if(!empty($task->file->FileName)) {
		echo $webForm->AddRow('Download', sprintf('<a href="?action=download&id=%d">%s</a>', $task->id, $task->file->FileName));
	}
	
	echo $webForm->AddRow($form->GetLabel('issatisfactory'), $form->GetHTML('issatisfactory') . $form->GetIcon('issatisfactory'));
	echo $webForm->AddRow($form->GetLabel('comments'), $form->GetHTML('comments') . $form->GetIcon('comments'));
	
	if($task->isUploadRequired == 'Y') {
		echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file') . $form->GetIcon('file'));
	}
	
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';" /> <input type="submit" name="complete" value="complete" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo '<br />';
	echo '<h3>Checklist</h3>';
	echo '<p>This health &amp; safety task schedule has the following checklist items.</p>';
	
	$table = new DataTable('worktaskchecklists');
	$table->SetExtractVars();
	$table->SetSQL(sprintf("SELECT * FROM work_task_checklist WHERE workTaskId=%d", $schedule->workTaskId));
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

	$filePath = $GLOBALS['WORKTASK_DOCUMENT_DIR_FS'] . $task->file->FileName;
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

function view() {
	$page = new Page('Health &amp; Safety Task Schedules', 'Listing all health &amp; safety tasks.');
	$page->Display('header');

	if(!$_SESSION['BypassWorkTasks'])  {
		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM work_task_schedule WHERE userId=%d AND isComplete='N' AND scheduledOn<NOW()", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		if($data->Row['Count'] > 0) {
			$message = 'You must complete any outstanding scheduled health &amp; safety tasks before access to the remainder of this control panel is restored for your user account.';
			
			$user = new User($GLOBALS['SESSION_USER_ID']);
			
			if($user->CanBypassWorkTasks == 'Y') {
				$message .= '<br /><br /><a href="?action=bypass">Click here</a> to bypass your incomplete schedules.';
			}
			
			$bubble = new Bubble('Incomplete Schedules', $message);
			
			echo $bubble->GetHTML();
			echo '<br />';
		}
		$data->Disconnect();
	}
	
	$table = new DataTable('worktaskschedules');
	$table->SetSQL(sprintf("SELECT wts.*, wt.name, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS userName FROM work_task_schedule AS wts INNER JOIN work_task AS wt ON wt.id=wts.workTaskId LEFT JOIN users AS u ON u.User_ID=wts.userId LEFT JOIN person AS p ON p.Person_ID=u.Person_ID WHERE wts.userId=%d AND wts.scheduledOn<NOW()", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
	$table->AddField('', 'file', 'hidden');
	$table->AddField('', 'isComplete', 'hidden');
	$table->AddField("ID#", "id");
	$table->AddField("Scheduled", "scheduledOn", "left");
	$table->AddField("Task", "name", "left");
	$table->AddField("User", "userName", "left");
	$table->AddField("Is Complete", "isComplete", "left");
	$table->AddField("Complete On", "completedOn", "left");
	$table->AddLink("?action=complete&id=%s","<img src=\"images/button-tick.gif\" alt=\"Complete\" border=\"0\">", "id", true, false, array('isComplete', '=', 'N'));
	$table->SetMaxRows(25);
	$table->SetOrderBy("scheduledOn");
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}