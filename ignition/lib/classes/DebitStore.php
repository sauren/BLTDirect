<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

class DebitStore {
	var $ID;
	var $Supplier;
	var $Product;
	var $Description;
	var $Quantity;
	var $Cost;
	var $DebitedOn;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function DebitLine($id = null) {
		$this->Supplier = new Supplier();
		$this->Product = new Product();
		$this->DebitedOn = '0000-00-00 00:00:00';

		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM debit_store WHERE DebitStoreID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->ID = $data->Row['DebitStoreID'];
			$this->Supplier->ID = $data->Row['SupplierID'];
			$this->Product->ID = $data->Row['ProductID'];
			$this->Description = $data->Row['Description'];
			$this->Quantity = $data->Row['Quantity'];
			$this->Cost = $data->Row['Cost'];
			$this->DebitedOn = $data->Row['DebitedOn'];
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

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO debit_store (SupplierID, ProductID, Description, Quantity, Cost, DebitedOn, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, %d, '%s', %d, %f, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->DebitedOn), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE debit_store SET SupplierID=%d, ProductID=%d, Description='%s', Quantity=%d, Cost=%f, DebitedOn='%s', ModifiedOn=NOW(), ModifiedBy=%d WHERE DebitStoreID=%d", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->DebitedOn), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM debit_store WHERE DebitStoreID=%d", mysql_real_escape_string($this->ID)));
	}
}