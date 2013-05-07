<?php
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/DataQuery.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/Product.php");

class ProductReorder {
	public $ID;
	public $Product;
	public $ReorderQuantity;
	public $IsHidden;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

	public function __construct($id = null) {
		$this->Product = new Product();
		$this->IsHidden = 'N';

		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM product_reorder WHERE ProductReorderID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Product->ID = $data->Row['ProductID'];
			$this->ReorderQuantity = $data->Row['ReorderQuantity'];
			$this->IsHidden = $data->Row['IsHidden'];
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
	
	public function GetByProductID($productId = null) {
		if(!is_null($productId)) {
			$this->Product->ID = $productId;
		}

		$data = new DataQuery(sprintf("SELECT ProductReorderID FROM product_reorder WHERE ProductID=%d", mysql_real_escape_string($this->Product->ID)));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['ProductReorderID']);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO product_reorder (ProductID, ReorderQuantity, IsHidden, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, %d, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->ReorderQuantity), mysql_real_escape_string($this->IsHidden), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE product_reorder SET ProductID=%d, ReorderQuantity=%d, IsHidden='%s', ModifiedOn=NOW(), ModifiedBy=%d WHERE ProductReorderID=%d", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->ReorderQuantity), mysql_real_escape_string($this->IsHidden), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_reorder WHERE ProductReorderID=%d", mysql_real_escape_string($this->ID)));
	}
}