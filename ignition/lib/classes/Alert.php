<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class Alert {
	public $id;
	public $referenceId;
	public $referenceId2;
	public $owner;
	public $type;
	public $description;
	public $isComplete;
	public $completedOn;
	public $completedBy;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	
	public function __construct($id = null) {
		$this->isComplete = 'N';
		$this->completedOn = '0000-00-00 00:00:00';
		
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

		$data = new DataQuery(sprintf("SELECT * FROM alert WHERE id=%d", mysql_real_escape_string($this->id)));
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
		$data = new DataQuery(sprintf("INSERT INTO alert (referenceId, referenceId2, owner, type, description, isComplete, completedOn, completedBy, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, %d, '%s', '%s', '%s', '%s', '%s', %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->referenceId), mysql_real_escape_string($this->referenceId2), mysql_real_escape_string($this->owner), mysql_real_escape_string($this->type), mysql_real_escape_string($this->description), mysql_real_escape_string($this->isComplete), mysql_real_escape_string($this->completedOn), mysql_real_escape_string($this->completedBy), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		new DataQuery(sprintf("UPDATE alert SET referenceId=%d, referenceId2=%d, owner='%s', type='%s', description='%s', isComplete='%s', completedOn='%s', completedBy=%d, modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->referenceId), mysql_real_escape_string($this->referenceId2), mysql_real_escape_string($this->owner), mysql_real_escape_string($this->type), mysql_real_escape_string($this->description), mysql_real_escape_string($this->isComplete), mysql_real_escape_string($this->completedOn), mysql_real_escape_string($this->completedBy), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM alert WHERE id=%d", mysql_real_escape_string($this->id)));
	}
	
	public function complete() {
		$this->isComplete = 'Y';
		$this->completedOn = now();
		$this->completedBy = $GLOBALS['SESSION_USER_ID'];
		$this->update();	
	}
}