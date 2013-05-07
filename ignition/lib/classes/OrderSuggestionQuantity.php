<?php
class OrderSuggestionQuantity {
	var $id;
	var $quantityBreakPoint;
	var $quantityCosted;

	public function __construct($id=NULL) {
		if(isset($id)){
			$this->id = $id;
			$this->get();
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM order_suggestion_quantity WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->Row) {
			$this->quantityBreakPoint = $data->Row['quantityBreakPoint'];
			$this->quantityCosted = $data->Row['quantityCosted'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO order_suggestion_quantity (quantityBreakPoint, quantityCosted) VALUES (%d, %d)", mysql_real_escape_string($this->quantityBreakPoint), mysql_real_escape_string($this->quantityCosted)));

		$this->id = $data->InsertID;
	}

	public function update() {

		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE order_suggestion_quantity SET quantityBreakPoint=%d, quantityCosted=%d WHERE id=%d", mysql_real_escape_string($this->quantityBreakPoint), mysql_real_escape_string($this->quantityCosted), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM order_suggestion_quantity WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}