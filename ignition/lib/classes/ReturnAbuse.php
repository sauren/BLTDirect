<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

class ReturnAbuse {
	public $id;
	public $orderId;
	public $counter;

	public function __construct($id = null) {
		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM return_abuse WHERE id=%d", mysql_real_escape_string($this->id)));
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

	public function getByOrderId($orderId = null) {
		if(!is_null($orderId)) {
			$this->orderId = $orderId;
		}

		$data = new DataQuery(sprintf("SELECT id FROM return_abuse WHERE orderId=%d", mysql_real_escape_string($this->orderId)));
		if($data->TotalRows > 0) {
			$this->get($data->Row['id']);

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO return_abuse (orderId, counter) VALUES (%d, %d)", $this->orderId, $this->counter));

		$this->id = $data->InsertID;
	}

	public function update() {
		new DataQuery(sprintf("UPDATE return_abuse SET orderId=%d, counter=%d WHERE id=%d", $this->orderId, $this->counter, $this->id));
	}

	public function delete() {
		new DataQuery(sprintf("DELETE FROM return_abuse WHERE id=%d", $this->id));
	}

	public function increment() {
		$this->counter++;
		$this->update();
	}
}