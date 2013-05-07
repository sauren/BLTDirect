<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Template.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTask.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTaskChecklist.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTaskScheduleChecklist.php');

class WorkTaskSchedule {
	public $id;
	public $workTaskId;
	public $user;
	public $isComplete;
	public $isSatisfactory;
	public $comments;
	public $file;
	public $scheduledOn;
	public $completedOn;
	public $completedBy;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	public $checklist;
	public $checklistsFetched;

	public function __construct($id = null) {
		$this->user = new User();
		$this->isComplete = 'N';
		$this->isSatisfactory = 'N';
		$this->scheduledOn = '0000-00-00 00:00:00';
		$this->completedOn = '0000-00-00 00:00:00';
		$this->checklist = array();
		$this->checklistsFetched = false;
		
		$this->file = new IFile();
		$this->file->Extensions = '';
		$this->file->OnConflict = 'makeunique';
		$this->file->SetDirectory($GLOBALS['WORKTASK_SCHEDULE_DOCUMENT_DIR_FS']);

		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($id)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM work_task_schedule WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				if($key != 'file') {
					$this->$key = $value;
				}
			}
			
			$this->user->ID = $data->Row['userId'];
			$this->file->FileName = $data->Row['file'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function getChecklist() {
		$this->checklist = array();
		$this->checklistsFetched = true;

		if(!is_numeric($id)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT id FROM work_task_schedule_checklist WHERE workTaskScheduleId=%d", mysql_real_escape_string($this->id)));
		while($data->Row) {
			$this->checklist[] = new WorkTaskScheduleChecklist($data->Row['id']);
			
			$data->Next();
		}
		$data->Disconnect();	
	}
	
	public function add($fileField = null) {
		if(!is_null($fileField) && isset($_FILES[$fileField]) && !empty($_FILES[$fileField]['name'])){
			if(!$this->file->Upload($fileField)){
				return false;
			}
		}
		
		$data = new DataQuery(sprintf("INSERT INTO work_task_schedule (workTaskId, userId, isComplete, isSatisfactory, comments, file, scheduledOn, completedOn, completedBy, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, %d, '%s', '%s', '%s', '%s', '%s', '%s', %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->workTaskId), mysql_real_escape_string($this->user->ID), mysql_real_escape_string($this->isComplete), mysql_real_escape_string($this->isSatisfactory), mysql_real_escape_string($this->comments), mysql_real_escape_string($this->file->FileName), mysql_real_escape_string($this->scheduledOn), mysql_real_escape_string($this->completedOn), mysql_real_escape_string($this->completedBy), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
		
		return true;
	}
	
	public function update($fileField = null) {
		$oldFile = new IFile($this->file->FileName, $this->file->Directory);

		if(!is_null($fileField) && isset($_FILES[$fileField]) && !empty($_FILES[$fileField]['name'])) {
			if(!$this->file->Upload($fileField)) {
				return false;
			} else {
				$oldFile->Delete();
			}
		}

		if(!is_numeric($this->id)){
			return false;
		}
		
		new DataQuery(sprintf("UPDATE work_task_schedule SET workTaskId=%d, userId=%d, isComplete='%s', isSatisfactory='%s', comments='%s', file='%s', scheduledOn='%s', completedOn='%s', completedBy=%d, modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->workTaskId), mysql_real_escape_string($this->user->ID), mysql_real_escape_string($this->isComplete), mysql_real_escape_string($this->isSatisfactory), mysql_real_escape_string($this->comments), mysql_real_escape_string($this->file->FileName), mysql_real_escape_string($this->scheduledOn), mysql_real_escape_string($this->completedOn), mysql_real_escape_string($this->completedBy), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
		
		return true;
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(empty($this->file->FileName)) {
			$this->get();
		}

		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM work_task_schedule WHERE id=%d", mysql_real_escape_string($this->id)));
		WorkTaskScheduleChecklist::DeleteWorkTaskSchedule($this->id);
		
		if(!empty($this->file->FileName) && $this->file->Exists()) {
			$this->file->Delete();
		}
	}
	
	public function complete() {
		$this->isComplete = 'Y';
		$this->completedOn = date('Y-m-d H:i:s');
		$this->completedBy = $GLOBALS['SESSION_USER_ID'];
		$this->update();
	}
	
	public function email() {
		if($this->isSatisfactory == 'N') {
			$this->user->Get();
			$this->getChecklist();
			
			$task = new WorkTask();
			$task->get($this->workTaskId);
			
			$checklistCount = 0;

			for($i=0; $i<count($this->checklist); $i++) {
				if($this->checklist[$i]->isSatisfactory == 'N') {
					$checklistCount++;
				}
			}
			
			$itemsHtml = '';
			
			if($checklistCount > 0) {
				$itemsHtml = '<table width="100%" border="0" cellspacing="0" cellpadding="5"><tr><th style="border-bottom: 1px solid #FA8F00; text-align: left;">Checklist</th><th style="border-bottom: 1px solid #FA8F00; text-align: left;">Comments</th></tr>';

				for($i=0; $i<count($this->checklist); $i++) {
					if($this->checklist[$i]->isSatisfactory == 'N') {
						$checklist = new WorkTaskChecklist($this->checklist[$i]->id);
						
						$itemsHtml .= sprintf('<tr><td>%s</td><td>%s</td></tr>', $checklist->name, $this->checklist[$i]->comments);
					}
				}
				
				$itemsHtml .= '</table><br />';
			}
			
			$commentsHtml = !empty($this->comments) ? sprintf('<table width="100%%" border="0" cellspacing="0" cellpadding="5"><tr><th style="border-bottom: 1px solid #FA8F00; text-align: left;">Comments</th></tr><tr><td>%s</td></tr></table><br />', $this->comments) : '';

			$completedBy = new User();
			$completedBy->Get($this->completedBy);
			
			$findReplace = new FindReplace();
			$findReplace->Add('/\[WORKTASK_NAME\]/', $task->name);
			$findReplace->Add('/\[WORKTASK_COMPLETED_ON\]/', cDatetime($this->completedOn, 'shortdate'));
			$findReplace->Add('/\[WORKTASK_COMPLETED_BY\]/', $completedBy->Person->GetFullName());
			$findReplace->Add('/\[WORKTASK_COMMENTS\]/', $commentsHtml);
			$findReplace->Add('/\[WORKTASK_ITEMS\]/', $itemsHtml);

			$customHtml = $findReplace->Execute(Template::GetContent('email_work_task'));

			$findReplace = new FindReplace();
			$findReplace->Add('/\[NAME\]/', 'Administrator');
			$findReplace->Add('/\[BODY\]/', $customHtml);

			$standardTemplate = file($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/email/template_standard.tpl');
			$standardHtml = '';
			
			for($i=0; $i<count($standardTemplate); $i++){
				$standardHtml .= $findReplace->Execute($standardTemplate[$i]);
			}
			
			$queue = new EmailQueue();
			$queue->GetModuleID('work');
			$queue->Priority = 'H';
			$queue->ToAddress = 'steve@bltdirect.com';
			$queue->Subject = sprintf('%s - Unsatisfactory Work Task (%s)', $GLOBALS['COMPANY'], $task->name);
			$queue->Body = $standardHtml;
			$queue->Add();
		}
	}
}