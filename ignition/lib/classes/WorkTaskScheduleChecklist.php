<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class WorkTaskScheduleChecklist {
	public $id;
	public $workTaskScheduleId;
	public $workTaskChecklistId;
	public $isSatisfactory;
	public $comments;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	
	public function __construct($id = null) {
		$this->isSatisfactory = 'N';

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

		$data = new DataQuery(sprintf("SELECT * FROM work_task_schedule_checklist WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO work_task_schedule_checklist (workTaskScheduleId, workTaskChecklistId, isSatisfactory, comments, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, %d, '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->workTaskScheduleId), mysql_real_escape_string($this->workTaskChecklistId), mysql_real_escape_string($this->isSatisfactory), mysql_real_escape_string($this->comments), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE work_task_schedule_checklist SET workTaskScheduleId=%d, workTaskChecklistId=%d, isSatisfactory='%s', comments='%s', modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->workTaskScheduleId), mysql_real_escape_string($this->workTaskChecklistId), mysql_real_escape_string($this->isSatisfactory), mysql_real_escape_string($this->comments), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM work_task_schedule_checklist WHERE id=%d", mysql_real_escape_string($this->id)));
	}

	static function DeleteWorkTaskChecklist($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM work_task_schedule_checklist WHERE workTaskChecklistId=%d", mysql_real_escape_string($id)));
	}

	static function DeleteWorkTaskSchedule($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM work_task_schedule_checklist WHERE workTaskScheduleId=%d", mysql_real_escape_string($id)));
	}
}