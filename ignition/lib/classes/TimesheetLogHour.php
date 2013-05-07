<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class TimesheetLogHour {
	public $id;
	public $timesheetLogId;
	public $type;
	public $hours;
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

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM timesheet_log_hour WHERE id=%d", mysql_real_escape_string($this->id)));
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
		$data = new DataQuery(sprintf("INSERT INTO timesheet_log_hour (timesheetLogId, type, hours, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, '%s', %f, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->timesheetLogId), mysql_real_escape_string($this->type), mysql_real_escape_string($this->hours), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE timesheet_log_hour SET timesheetLogId=%d, type='%s', hours=%f, modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->timesheetLogId), mysql_real_escape_string($this->type), mysql_real_escape_string($this->hours), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM timesheet_log_hour WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}