<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

class ProductLandingProduct {
	public $id;
	public $landingId;
	public $product;
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

		$data = new DataQuery(sprintf("SELECT * FROM product_landing_product WHERE id=%d", mysql_real_escape_string($this->id)));
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
		$data = new DataQuery(sprintf("INSERT INTO product_landing_product (landingId, productId, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->landingId), mysql_real_escape_string($this->product->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {

	if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE product_landing_product SET landingId=%d, productId=%d, modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->landingId), mysql_real_escape_string($this->product->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_landing_product WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}