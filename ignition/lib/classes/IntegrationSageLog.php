<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class IntegrationSageLog {
	public $id;
	public $integrationSageId;
	public $type;
	public $referenceId;
	public $accountReference;
	public $contactName;
	public $amount;
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

		$data = new DataQuery(sprintf("SELECT * FROM integration_sage_log WHERE id=%d", mysql_real_escape_string($this->id)));
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
		$data = new DataQuery(sprintf("INSERT INTO integration_sage_log (integrationSageId, type, referenceId, accountReference, contactName, amount, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, '%s', %d, '%s', '%s', %f, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->integrationSageId), mysql_real_escape_string($this->type), mysql_real_escape_string($this->referenceId), mysql_real_escape_string($this->accountReference), mysql_real_escape_string($this->contactName), mysql_real_escape_string($this->amount), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE integration_sage_log SET integrationSageId=%d, type='%s', referenceId=%d, accountReference='%s', contactName='%s', amount=%f, modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->integrationSageId), mysql_real_escape_string($this->type), mysql_real_escape_string($this->referenceId), mysql_real_escape_string($this->accountReference), mysql_real_escape_string($this->contactName), mysql_real_escape_string($this->amount), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM integration_sage_log WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}