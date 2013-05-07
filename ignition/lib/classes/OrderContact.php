<?php
class OrderContact {
	public $ID;
	public $OrderID;
	public $CreatedOn;
	public $CreatedBy;

	public function __construct($id = null) {
		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	public function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM order_contact WHERE OrderContactID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->OrderID = $data->Row['OrderID'];
			$this->CreatedOn = $data->Row['CreatedOn'];
			$this->CreatedBy = $data->Row['CreatedBy'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO order_contact (OrderID, CreatedOn, CreatedBy) VALUES (%d, NOW(), %d)", mysql_real_escape_string($this->OrderID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM order_contact WHERE OrderContactID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteOrder($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from order_contact where OrderID=%d", mysql_real_escape_string($id)));
	}
}