<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

class ProductSupplierFlexible {
	var $ID;
	var $Product;
	var $Supplier;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	
	public function __construct($id=NULL) {
		$this->Product = new Product();
		$this->Supplier = new Supplier();
		
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}
	
	function Get($id=NULL) {
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT * FROM product_supplier_flexible WHERE ProductSupplierFlexibleID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Product->ID = $data->Row['ProductID'];
			$this->Supplier->ID = $data->Row['SupplierID'];
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
	
	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO product_supplier_flexible (ProductID, SupplierID, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->ID = $data->InsertID;
	}
	
	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE product_supplier_flexible SET ProductID=%d, SupplierID=%d, ModifiedOn=NOW(), ModifiedBy=%d WHERE ProductSupplierFlexibleID=%d", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}
	
	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		
		new DataQuery(sprintf("DELETE FROM product_supplier_flexible WHERE ProductSupplierFlexibleID=%d", mysql_real_escape_string($this->ID)));
	}
}