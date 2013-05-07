<?php
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquirySupplier.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiryLine.php');

class PriceEnquirySupplierLine {
	var $ID;
	var $PriceEnquirySupplierID;
	var $PriceEnquiryLineID;
	var $IsInStock;
	var $StockBackorderDays;

	public function __construct($id = NULL) {
		$this->IsInStock = 'U';

		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = NULL) {
		if (!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM price_enquiry_supplier_line WHERE Price_Enquiry_Supplier_Line_ID=%d ", mysql_real_escape_string($this->ID)));
		if ($data->TotalRows > 0) {
			$this->PriceEnquirySupplierID = $data->Row["Price_Enquiry_Supplier_ID"];
			$this->PriceEnquiryLineID = $data->Row["Price_Enquiry_Line_ID"];
			$this->IsInStock = $data->Row["Is_In_Stock"];
			$this->StockBackorderDays = $data->Row["Stock_Backorder_Days"];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetByEnquiryLineAndSupplierID($enquiryLineId = NULL, $enquirySupplierId = NULL) {
		if (!is_null($enquiryLineId)) {
			$this->PriceEnquiryLineID = $enquiryLineId;
		}

		if (!is_null($enquirySupplierId)) {
			$this->PriceEnquirySupplierID = $enquirySupplierId;
		}

		$data = new DataQuery(sprintf("SELECT Price_Enquiry_Supplier_Line_ID FROM price_enquiry_supplier_line WHERE Price_Enquiry_Line_ID=%d AND Price_Enquiry_Supplier_ID=%d", mysql_real_escape_string($this->PriceEnquiryLineID), mysql_real_escape_string($this->PriceEnquirySupplierID)));
		if ($data->TotalRows > 0) {
			$return = $this->Get($data->Row["Price_Enquiry_Supplier_Line_ID"]);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO price_enquiry_supplier_line (Price_Enquiry_Supplier_ID, Price_Enquiry_Line_ID, Is_In_Stock, Stock_Backorder_Days) VALUES (%d, %d, '%s', %d)", mysql_real_escape_string($this->PriceEnquirySupplierID), mysql_real_escape_string($this->PriceEnquiryLineID), mysql_real_escape_string($this->IsInStock), mysql_real_escape_string($this->StockBackorderDays)));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE price_enquiry_supplier_line SET Is_In_Stock='%s', Stock_Backorder_Days=%d WHERE Price_Enquiry_Supplier_Line_ID=%d", mysql_real_escape_string($this->IsInStock), mysql_real_escape_string($this->StockBackorderDays), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = NULL) {
		if (!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM price_enquiry_supplier_line WHERE Price_Enquiry_Supplier_Line_ID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeletePriceEnquiry($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM price_enquiry_supplier_line WHERE Price_Enquiry_Supplier_ID=%d", mysql_real_escape_string($id)));
	}

	static function DeletePriceEnquiryLine($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM price_enquiry_supplier_line WHERE Price_Enquiry_Line_ID=%d", mysql_real_escape_string($id)));
	}

	static function DeletePriceEnquirySupplier($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM price_enquiry_supplier_line WHERE Price_Enquiry_Supplier_ID=%d", mysql_real_escape_string($id)));
	}
}