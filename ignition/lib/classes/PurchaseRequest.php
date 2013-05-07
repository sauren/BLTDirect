<?php
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/DataQuery.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/PurchaseRequestLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/Supplier.php");

class PurchaseRequest {
	public $ID;
	public $Supplier;
	public $Status;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;
	public $Line;
	public $LinesFetched;

	public function __construct($id = null) {
		$this->Supplier = new Supplier();
		$this->Line = array();
		$this->LinesFetched = false;

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

		$data = new DataQuery(sprintf("SELECT * FROM purchase_request WHERE PurchaseRequestID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Supplier->ID = $data->Row['SupplierID'];
			$this->Status = $data->Row['Status'];
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

	public function GetLines() {
		$this->Line = array();
		$this->LinesFetched = true;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT PurchaseRequestLineID FROM purchase_request_line WHERE PurchaseRequestID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Line[] = new PurchaseRequestLine($data->Row['PurchaseRequestLineID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO purchase_request (SupplierID, Status, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Status), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE purchase_request SET SupplierID=%d, Status='%s', ModifiedOn=NOW(), ModifiedBy=%d WHERE PurchaseRequestID=%d", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->Status), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		if(!$this->LinesFetched) {
			$this->GetLines();
		}

		for($i=0; $i<count($this->Line); $i++) {
			new DataQuery(sprintf("UPDATE product_reorder SET IsHidden='N' WHERE ProductID=%d", mysql_real_escape_string($this->Line[$i]->Product->ID)));
		}


		new DataQuery(sprintf("DELETE FROM purchase_request WHERE PurchaseRequestID=%d", mysql_real_escape_string($this->ID)));
		PurchaseRequestLine::DeletePurchaseRequest($this->ID);
	}
}