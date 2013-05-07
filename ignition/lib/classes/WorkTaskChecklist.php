<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkTaskScheduleChecklist.php');

class WorkTaskChecklist {
	public $id;
	public $workTaskId;
	public $name;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	
	public function __construct($id = null) {
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

		$data = new DataQuery(sprintf("SELECT * FROM work_task_checklist WHERE id=%d", mysql_real_escape_string($this->id)));
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
		$data = new DataQuery(sprintf("INSERT INTO work_task_checklist (workTaskId, name, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->workTaskId), mysql_real_escape_string($this->name), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE work_task_checklist SET workTaskId=%d, name='%s', modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->workTaskId), mysql_real_escape_string($this->name), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM work_task_checklist WHERE id=%d", mysql_real_escape_string($this->id)));
		WorkTaskScheduleChecklist::DeleteWorkTaskChecklist($this->ID);
	}

	static function DeleteWorkTask($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM work_task_checklist WHERE workTaskId=%d", mysql_real_escape_string($id)));
	}
}