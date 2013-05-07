<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

class TimesheetLog {
	public $id;
	public $user;
	public $periodStartOn;
	public $periodEndOn;
	public $bonus;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	
	public function __construct($id = null) {
		$this->user = new User();
		$this->periodStartOn = '0000-00-00 00:00:00';
		$this->periodEndOn = '0000-00-00 00:00:00';
		
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

		$data = new DataQuery(sprintf("SELECT * FROM timesheet_log WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->user->ID = $data->Row['userId'];
			
            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO timesheet_log (userId, periodStartOn, periodEndOn, bonus, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, '%s', '%s', %f, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->user->ID), mysql_real_escape_string($this->periodStartOn), mysql_real_escape_string($this->periodEndOn), mysql_real_escape_string($this->bonus), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE timesheet_log SET userId=%d, periodStartOn='%s', periodEndOn='%s', bonus=%f, modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->user->ID), mysql_real_escape_string($this->periodStartOn), mysql_real_escape_string($this->periodEndOn), mysql_real_escape_string($this->bonus), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM timesheet_log WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}