<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

class ReserveItem {
	public $id;
	public $reserveId;
	public $product;
	public $quantity;
	public $quantityRemaining;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	
	public function __construct($id = null) {
		$this->product = new Product();

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

		$data = new DataQuery(sprintf("SELECT * FROM reserve_item WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->product->ID = $data->Row['productId'];

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO reserve_item (reserveId, productId, quantity, quantityRemaining, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, %d, %d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->reserveId), mysql_real_escape_string($this->product->ID), mysql_real_escape_string($this->quantity), mysql_real_escape_string($this->quantityRemaining), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE reserve_item SET reserveId=%d, productId=%d, quantity=%d, quantityRemaining=%d, modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->reserveId), mysql_real_escape_string($this->product->ID), mysql_real_escape_string($this->quantity), mysql_real_escape_string($this->quantityRemaining), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM reserve_item WHERE id=%d", mysql_real_escape_string($this->id)));
	}

	static function DeleteReserve($id){
		new DataQuery(sprintf("DELETE FROM reserve_item WHERE reserveId=%d", mysql_real_escape_string($id)));
	}
}