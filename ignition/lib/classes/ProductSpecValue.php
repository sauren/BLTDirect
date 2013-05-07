<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValueImage.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpec.php');

class ProductSpecValue {
	public $ID;
	public $Value;
	public $Group;
	public $Hide;
	
	public function __construct($id=NULL) {
		$this->Group = new ProductSpecGroup();
		$this->Hide = 'N';
		
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id=NULL, $connection = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM product_specification_value WHERE Value_ID=%d", mysql_real_escape_string($this->ID)), $connection);
		if($data->TotalRows > 0) {
			$this->Value = $data->Row['Value'];
			$this->Group->ID = $data->Row['Group_ID'];
			$this->Hide = $data->Row['Hide'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function Add($connection = null) {
		$check = new DataQuery(sprintf("SELECT * FROM product_specification_value WHERE Group_ID=%d AND Value LIKE '%s'", mysql_real_escape_string($this->Group->ID), mysql_real_escape_string(trim($this->Value))), $connection);
		if($check->TotalRows == 0){
			$data = new DataQuery(sprintf("INSERT INTO product_specification_value (Group_ID, Value, Hide) VALUES (%d, '%s', '%s')", mysql_real_escape_string($this->Group->ID), mysql_real_escape_string(trim($this->Value)), mysql_real_escape_string($this->Hide)), $connection);
			$this->ID = $data->InsertID;

			$check->Disconnect();
			return true;
		}

		$check->Disconnect();
		return false;
	}

	public function Update($connection = null) {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE product_specification_value SET Value='%s', Hide='%s' WHERE Value_ID=%d", mysql_real_escape_string(trim($this->Value)), mysql_real_escape_string($this->Hide), mysql_real_escape_string($this->ID)), $connection);
	}

	public function Delete($id=NULL, $connection = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		ProductSpec::DeleteProductSpecValue($this->ID);
		
		new DataQuery(sprintf("DELETE FROM product_specification_value WHERE Value_ID=%d", mysql_real_escape_string($this->ID)), $connection);
		
		$data = new DataQuery(sprintf("SELECT id FROM product_specification_value_image WHERE valueId=%d", mysql_real_escape_string($this->ID)), $connection);
		while($data->Row) {
			$item = new ProductSpecValueImage();
			$item->delete($data->Row['id']);
		
			$data->Next();	
		}
		$data->Disconnect();
	}

	public function getUnitValue() {
		return trim(sprintf('%s %s', $this->Value, $this->Group->Units));
	}
}