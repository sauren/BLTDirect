<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

class SupplierProductHoldRequest {
	var $ID;
	var $Supplier;
	var $Product;
	var $Quantity;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	
	function __construct($id = NULL) {
		$this->Supplier = new Supplier();
		$this->Product = new Product();
		
		if(!is_null($id)) {
			$this->Get($id);
		}
	}
	
	function Get($id = NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT * FROM supplier_product_hold_request WHERE SupplierProductHoldRequestID=%d ", mysql_real_escape_string($this->ID)), $connection);
		if ($data->TotalRows > 0) {
			$this->Supplier->ID = $data->Row["SupplierID"];
			$this->Product->ID = $data->Row["ProductID"];
			$this->Quantity = $data->Row["Quantity"];
			$this->CreatedOn = $data->Row["CreatedOn"];
			$this->CreatedBy = $data->Row["CreatedBy"];
			$this->ModifiedOn = $data->Row["ModifiedOn"];
			$this->ModifiedBy = $data->Row["ModifiedBy"];
			
			$data->Disconnect();
			return true;
		}
		
		$data->Disconnect();
		return false;
	}
	
	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO supplier_product_hold_request (SupplierID, ProductID, Quantity, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, %d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($GLOBALS["SESSION_USER_ID"]), mysql_real_escape_string($GLOBALS["SESSION_USER_ID"])));
		
		$this->ID = $data->InsertID;
	}
	
	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE supplier_product_hold_request SET Quantity=%f, ModifiedOn=Now(), ModifiedBy=%d WHERE SupplierProductHoldRequestID=%d", mysql_real_escape_string($this->Quantity), mysql_real_escape_string($GLOBALS["SESSION_USER_ID"]), mysql_real_escape_string($this->ID)));
	}
	
	function Delete($id = NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		
		new DataQuery(sprintf("DELETE FROM supplier_product_hold_request WHERE SupplierProductHoldRequestID=%d", mysql_real_escape_string($this->ID)));
	}
}