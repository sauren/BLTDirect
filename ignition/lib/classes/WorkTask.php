<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTaskArchive.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTaskChecklist.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTaskSchedule.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTaskUser.php');

class WorkTask {
	public $id;
	public $name;
	public $startedOn;
	public $period;
	public $isUploadRequired;
	public $file;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	public $checklist;
	public $checklistFetched;
	public $user;
	public $usersFetched;
	
	public function __construct($id = null) {
		$this->startedOn = '0000-00-00 00:00:00';
		$this->isUploadRequired = 'N';
		$this->checklist = array();
		$this->checklistFetched = false;
		$this->user = array();
		$this->usersFetched = false;
		
		$this->file = new IFile();
		$this->file->Extensions = '';
		$this->file->OnConflict = 'makeunique';
		$this->file->SetDirectory($GLOBALS['WORKTASK_DOCUMENT_DIR_FS']);
		
		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->id)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM work_task WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				if($key != 'file') {
					$this->$key = $value;
				}
			}
			
			$this->file->FileName = $data->Row['file'];
			
            		$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function getChecklists() {
		$this->checklist = array();
		$this->checklistFetched = true;
		if(!is_numeric($this->id)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT id FROM work_task_checklist WHERE workTaskId=%d", mysql_real_escape_string($this->id)));
		while($data->Row) {
			$this->checklist[] = new WorkTaskChecklist($data->Row['id']);

            $data->Next();
		}
		$data->Disconnect();
	}
	
	public function getUsers() {
		$this->user = array();
		$this->usersFetched = true;
		if(!is_numeric($this->id)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT id FROM work_task_user WHERE workTaskId=%d", mysql_real_escape_string($this->id)));
		while($data->Row) {
			$this->user[] = new WorkTaskUser($data->Row['id']);

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
		
		$data = new DataQuery(sprintf("INSERT INTO work_task (name, startedOn, period, isUploadRequired, file, createdOn, createdBy, modifiedOn, modifiedBy) VALUES ('%s', '%s', '%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->name), mysql_real_escape_string($this->startedOn), mysql_real_escape_string($this->period), mysql_real_escape_string($this->isUploadRequired), mysql_real_escape_string($this->file->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

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
		
		new DataQuery(sprintf("UPDATE work_task SET name='%s', startedOn='%s', period='%s', isUploadRequired='%s', file='%s', modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->name), mysql_real_escape_string($this->startedOn), mysql_real_escape_string($this->period), mysql_real_escape_string($this->isUploadRequired), mysql_real_escape_string($this->file->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
		
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
		
		$data = new DataQuery(sprintf("SELECT id FROM work_task_archive WHERE workTaskId=%d", mysql_real_escape_string($this->id)));
		while($data->Row) {
			$archive = new WorkTaskArchive($data->Row['id']);
			$archive->delete();
			
			$data->Next();
		}
		$data->Disconnect();
		
		$data = new DataQuery(sprintf("SELECT id FROM work_task_schedule WHERE workTaskId=%d", mysql_real_escape_string($this->id)));
		while($data->Row) {
			$schedule = new WorkTaskSchedule($data->Row['id']);
			$schedule->delete();
			
			$data->Next();
		}
		$data->Disconnect();

		new DataQuery(sprintf("DELETE FROM work_task WHERE id=%d", mysql_real_escape_string($this->id)));
		WorkTaskChecklist::DeleteWorkTask($this->id);
		WorkTaskUser::DeleteWorkTask($this->id);
		
		if(!empty($this->file->FileName) && $this->file->Exists()) {
			$this->file->Delete();
		}
	}
}