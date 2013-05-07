<?php
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');

class SupplierReturn {
	var $ID;
	var $Supplier;
	var $Product;
	var $Order;
	var $Cost;
	var $Quantity;
	var $PurchasedOn;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function SupplierReturn($id = NULL) {
		$this->Supplier = new Supplier();
		$this->Product = new Product();
		$this->Order = new Order();
		$this->PurchasedOn = '0000-00-00 00:00:00';

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

		$data = new DataQuery(sprintf("SELECT * FROM supplier_return WHERE Supplier_Return_ID=%d ", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Supplier->ID = $data->Row["Supplier_ID"];
			$this->Product->ID = $data->Row["Product_ID"];
			$this->Order->ID = $data->Row["Order_ID"];
			$this->Cost = $data->Row["Cost"];
			$this->Quantity = $data->Row["Quantity"];
			$this->PurchasedOn = $data->Row["Purchased_On"];
			$this->CreatedOn = $data->Row["Created_On"];
			$this->CreatedBy = $data->Row["Created_By"];
			$this->ModifiedOn = $data->Row["Modified_On"];
			$this->ModifiedBy = $data->Row["Modified_By"];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO supplier_return (Supplier_ID, Product_ID, Order_ID, Cost, Quantity, Purchased_On, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, %d, %f, %d, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Order->ID), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->PurchasedOn), mysql_real_escape_string($GLOBALS["SESSION_USER_ID"]), mysql_real_escape_string($GLOBALS["SESSION_USER_ID"])));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE supplier_return SET Cost=%f, Quantity=%d, Purchased_On='%s', Modified_On=NOW(), Modified_By=%d WHERE Supplier_Return_ID=%d", mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->PurchasedOn), mysql_real_escape_string($GLOBALS["SESSION_USER_ID"]), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM supplier_return WHERE Supplier_Return_ID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>