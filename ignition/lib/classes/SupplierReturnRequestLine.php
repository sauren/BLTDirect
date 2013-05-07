<?php
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/DataQuery.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/Product.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/SupplierReturnRequestLineType.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/PurchaseLine.php");

class SupplierReturnRequestLine {
	public $ID;
	public $SupplierReturnRequestID;
	public $Type;
	public $PurchaseLine;
	public $Product;
	public $Quantity;
	public $Cost;
	public $Reason;
	public $RelatedProduct;
	public $HandlingMethod;
	public $HandlingCharge;
	public $IsRejected;
	public $RejectedReason;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

	public function __construct($id = null) {
		$this->Type = new SupplierReturnRequestLineType();
		$this->PurchaseLine = new PurchaseLine();
		$this->Product = new Product();
		$this->RelatedProduct = new Product();
		$this->HandlingMethod = 'R';
		$this->IsRejected = 'N';

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

		$data = new DataQuery(sprintf("SELECT * FROM supplier_return_request_line WHERE SupplierReturnRequestLineID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->SupplierReturnRequestID = $data->Row['SupplierReturnRequestID'];
			$this->Type->ID = $data->Row['SupplierReturnRequestLineTypeID'];
			$this->PurchaseLine->ID = $data->Row['PurchaseLineID'];
			$this->Product->ID = $data->Row['ProductID'];
			$this->Quantity = $data->Row['Quantity'];
			$this->Cost = $data->Row['Cost'];
			$this->Reason = $data->Row['Reason'];
			$this->RelatedProduct->ID = $data->Row['RelatedProductID'];
			$this->HandlingMethod = $data->Row['HandlingMethod'];
			$this->HandlingCharge = $data->Row['HandlingCharge'];
			$this->IsRejected = $data->Row['IsRejected'];
			$this->RejectedReason = $data->Row['RejectedReason'];
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
		$data = new DataQuery(sprintf("INSERT INTO supplier_return_request_line (SupplierReturnRequestID, SupplierReturnRequestLineTypeID, PurchaseLineID, ProductID, Quantity, Cost, Reason, RelatedProductID, HandlingMethod, HandlingCharge, IsRejected, RejectedReason, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, %d, %d, %d, %d, %f, '%s', %d, '%s', %f, '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->SupplierReturnRequestID), mysql_real_escape_string($this->Type->ID), mysql_real_escape_string($this->PurchaseLine->ID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->Reason), mysql_real_escape_string($this->RelatedProduct->ID), mysql_real_escape_string($this->HandlingMethod), mysql_real_escape_string($this->HandlingCharge), mysql_real_escape_string($this->IsRejected), mysql_real_escape_string($this->RejectedReason), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE supplier_return_request_line SET SupplierReturnRequestID=%d, SupplierReturnRequestLineTypeID=%d, PurchaseLineID=%d, ProductID=%d, Quantity=%d, Cost=%f, Reason='%s', RelatedProductID=%d, HandlingMethod='%s', HandlingCharge=%f, IsRejected='%s', RejectedReason='%s', ModifiedOn=NOW(), ModifiedBy=%d WHERE SupplierReturnRequestLineID=%d", mysql_real_escape_string($this->SupplierReturnRequestID), mysql_real_escape_string($this->Type->ID), mysql_real_escape_string($this->PurchaseLine->ID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->Reason), mysql_real_escape_string($this->RelatedProduct->ID), mysql_real_escape_string($this->HandlingMethod), mysql_real_escape_string($this->HandlingCharge), mysql_real_escape_string($this->IsRejected), mysql_real_escape_string($this->RejectedReason), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM supplier_return_request_line WHERE SupplierReturnRequestLineID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteSupplierReturnRequest($id){

		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM supplier_return_request_line WHERE SupplierReturnRequestID=%d", mysql_real_escape_string($id)));
	}
}