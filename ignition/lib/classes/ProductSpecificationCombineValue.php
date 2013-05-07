<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');

class ProductSpecificationCombineValue {
	public $id;
	public $combineId;
	public $value;
	
	public function __construct($id = null) {
		$this->value = new ProductSpecValue();

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

		$data = new DataQuery(sprintf("SELECT * FROM product_specification_combine_value WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->combineId = $data->Row['productSpecificationCombineId'];
			$this->value->ID = $data->Row['productSpecificationValueId'];

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO product_specification_combine_value (productSpecificationCombineId, productSpecificationValueId) VALUES (%d, %d)", mysql_real_escape_string($this->combineId), mysql_real_escape_string($this->value->ID)));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE product_specification_combine_value SET productSpecificationValueId=%d' WHERE id=%d", mysql_real_escape_string($this->value->ID), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_specification_combine_value WHERE id=%d", mysql_real_escape_string($this->id)));
	}

	static function DeleteProductSpecificationCombine($id){
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM product_specification_combine_value WHERE id=%d", mysql_real_escape_string($id)));
	}
}