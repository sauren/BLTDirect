<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

class ReturnLog {
	public $id;
	public $orderId;
	public $type;
	public $referenceId;
	public $log;
	public $createdOn;

	public function __construct($id = null) {
		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM return_log WHERE id=%d", mysql_real_escape_string($this->id)));
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
		$data = new DataQuery(sprintf("INSERT INTO return_log (orderId, type, referenceId, log, createdOn) VALUES (%d, '%s', %d, '%s', NOW())", mysql_real_escape_string($this->orderId), mysql_real_escape_string($this->type), mysql_real_escape_string($this->referenceId), mysql_real_escape_string($this->log)));

		$this->id = $data->InsertID;
	}

	public function update() {
		new DataQuery(sprintf("UPDATE return_log SET orderId=%d, type='%s', referenceId=%d, log='%s' WHERE id=%d", mysql_real_escape_string($this->orderId), mysql_real_escape_string($this->type), mysql_real_escape_string($this->referenceId), mysql_real_escape_string($this->log), mysql_real_escape_string($this->id)));
	}

	public function delete() {
		new DataQuery(sprintf("DELETE FROM return_log WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}