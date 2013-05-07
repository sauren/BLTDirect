<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

class TestSupplierProduct {
	public $ID;
	public $TestSupplierID;
	public $Product;
	public $Quantity;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

	public function __construct($id=NULL) {
		$this->Product = new Product();

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM test_supplier_product WHERE TestSupplierProductID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->TestSupplierID = $data->Row['TestSupplierID'];
			$this->Product->ID = $data->Row['ProductID'];
			$this->Quantity = $data->Row['Quantity'];
			$this->CreatedOn = $data->Row['CreatedOn'];
			$this->CreatedBy = $data->Row['CreatedBy'];
			$this->ModifiedOn = $data->Row['ModifiedOn'];
			$this->ModifiedBy = $data->Row['ModifiedBy'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO test_supplier_product (TestSupplierID, ProductID, Quantity, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, %d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->TestSupplierID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE test_supplier_product SET TestSupplierID=%d, ProductID=%d, Quantity=%d, ModifiedOn=NOW(), ModifiedBy=%d WHERE TestSupplierProductID=%d", mysql_real_escape_string($this->TestSupplierID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM test_supplier_product WHERE TestSupplierProductID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteTestSupplier($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM test_supplier_product WHERE TestSupplierID=%d", mysql_real_escape_string($id)));
	}
}
?>