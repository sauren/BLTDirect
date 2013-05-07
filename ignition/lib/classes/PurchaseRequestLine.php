<?php
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/DataQuery.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/Product.php");

class PurchaseRequestLine {
	public $ID;
	public $PurchaseRequestID;
	public $Product;
	public $Quantity;
	public $IsStocked;
	public $StockArrivalDays;
	public $StockAvailable;
	public $IsPurchased;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

	public function __construct($id = null) {
		$this->Product = new Product();
		$this->IsStocked = 'N';
		$this->IsPurchased = 'N';

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

		$data = new DataQuery(sprintf("SELECT * FROM purchase_request_line WHERE PurchaseRequestLineID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->PurchaseRequestID = $data->Row['PurchaseRequestID'];
			$this->Product->ID = $data->Row['ProductID'];
			$this->Quantity = $data->Row['Quantity'];
			$this->IsStocked = $data->Row['IsStocked'];
			$this->StockArrivalDays = $data->Row['StockArrivalDays'];
			$this->StockAvailable = $data->Row['StockAvailable'];
			$this->IsPurchased = $data->Row['IsPurchased'];
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
		$data = new DataQuery(sprintf("INSERT INTO purchase_request_line (PurchaseRequestID, ProductID, Quantity, IsStocked, StockArrivalDays, StockAvailable, IsPurchased, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, %d, %d, '%s', %d, %d, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->PurchaseRequestID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->IsStocked), mysql_real_escape_string($this->StockArrivalDays), mysql_real_escape_string($this->StockAvailable), mysql_real_escape_string($this->IsPurchased), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE purchase_request_line SET PurchaseRequestID=%d, ProductID=%d, Quantity=%d, IsStocked='%s', StockArrivalDays=%d, StockAvailable=%d, IsPurchased='%s', ModifiedOn=NOW(), ModifiedBy=%d WHERE PurchaseRequestLineID=%d", mysql_real_escape_string($this->PurchaseRequestID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->IsStocked), mysql_real_escape_string($this->StockArrivalDays), mysql_real_escape_string($this->StockAvailable), mysql_real_escape_string($this->IsPurchased), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM purchase_request_line WHERE PurchaseRequestLineID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeletePurchaseRequest($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM purchase_request_line WHERE PurchaseRequestID=%d", mysql_real_escape_string($id)));
	}
}