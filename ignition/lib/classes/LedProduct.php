<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/LedLocation.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

class LedProduct {
	public $id;
	public $location;
	public $product;
	public $quantity;
	public $position;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	
	public function __construct($id = null) {
		$this->location = new LedLocation();
		$this->product = new Product();
		
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

		$data = new DataQuery(sprintf("SELECT * FROM led_product WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->location->id = $this->locationId;
			$this->product->ID = $this->productId;
			
            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO led_product (locationId, productId, quantity, position, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, %d, %d, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->location->id), mysql_real_escape_string($this->product->ID), mysql_real_escape_string($this->quantity), mysql_real_escape_string($this->position), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE led_product SET locationId=%d, productId=%d, quantity=%d, position='%s', modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->location->id), mysql_real_escape_string($this->product->ID), mysql_real_escape_string($this->quantity), mysql_real_escape_string($this->position), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM led_product WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}