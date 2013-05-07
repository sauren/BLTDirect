<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecificationCombineValue.php');

class ProductSpecificationCombine {
	public $id;
	public $group;
	public $name;
	
	public function __construct($id = null) {
		$this->group = new ProductSpecGroup();

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

		$data = new DataQuery(sprintf("SELECT * FROM product_specification_combine WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->group->ID = $data->Row['productSpecificationGroupId'];

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO product_specification_combine (productSpecificationGroupId, name) VALUES (%d, '%s')", mysql_real_escape_string($this->group->ID), mysql_real_escape_string($this->name)));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("UPDATE product_specification_combine SET productSpecificationGroupId=%d, name='%s' WHERE id=%d", mysql_real_escape_string($this->group->ID), mysql_real_escape_string($this->name), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->id)){
			return false;
		}


		new DataQuery(sprintf("DELETE FROM product_specification_combine WHERE id=%d", mysql_real_escape_string($this->id)));
		ProductSpecificationCombineValue::DeleteProductSpecificationCombine($this->ID);
	}
}