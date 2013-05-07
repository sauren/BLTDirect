<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');

class ProductSpecGroupUnit {
	public $id;
	public $group;
	public $unit;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	
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

		if(!is_numeric($this->id)) {
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM product_specification_group_unit WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->group->ID = $data->Row['groupId'];		

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO product_specification_group_unit (groupId, unit, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->group->ID), mysql_real_escape_string($this->unit), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->id)) {
			return false;
		}

		new DataQuery(sprintf("UPDATE product_specification_group_unit SET groupId=%d, unit='%s', modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->group->ID), mysql_real_escape_string($this->unit), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)) {
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_specification_group_unit WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}