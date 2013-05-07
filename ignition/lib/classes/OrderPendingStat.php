<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

class OrderPendingStat {
	public $id;
	public $ordersPackable;
	public $ordersUnpackable;
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

		$data = new DataQuery(sprintf("SELECT * FROM order_pending_stat WHERE id=%d", mysql_real_escape_string($this->id)));
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
		$data = new DataQuery(sprintf("INSERT INTO order_pending_stat (ordersPackable, ordersUnpackable, createdOn) VALUES (%d, %d, NOW())", $this->ordersPackable, $this->ordersUnpackable));

		$this->id = $data->InsertID;
	}

	public function update() {
		new DataQuery(sprintf("UPDATE order_pending_stat SET ordersPackable=%d, ordersUnpackable=%d WHERE id=%d", $this->ordersPackable, $this->ordersUnpackable, $this->id));
	}

	public function delete() {
		new DataQuery(sprintf("DELETE FROM order_pending_stat WHERE id=%d", $this->id));
	}
}