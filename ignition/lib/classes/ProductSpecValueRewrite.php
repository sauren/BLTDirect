<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');

class ProductSpecValueRewrite {
	public $id;
	public $value;
	public $rewrite;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	
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

		if(!is_numeric($this->id)) {
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM product_specification_value_rewrite WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->value->ID = $data->Row['valueId'];		

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO product_specification_value_rewrite (valueId, valueRewrite, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->value->ID), mysql_real_escape_string($this->rewrite), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->id)) {
			return false;
		}

		new DataQuery(sprintf("UPDATE product_specification_value_rewrite SET valueId=%d, valueRewrite='%s', modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->value->ID), mysql_real_escape_string($this->rewrite), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)) {
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_specification_value_rewrite WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}