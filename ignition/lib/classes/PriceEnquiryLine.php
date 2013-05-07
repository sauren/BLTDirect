<?php
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquirySupplierLine.php');

class PriceEnquiryLine {
	public $ID;
	public $PriceEnquiryID;
	public $Product;
	public $Quantity;
	public $Orders;

	public function __construct($id = NULL) {
		$this->Product = new Product();

		if (!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id = NULL) {
		if (!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM price_enquiry_line WHERE Price_Enquiry_Line_ID=%d ", mysql_real_escape_string($this->ID)));
		if ($data->TotalRows > 0) {
			$this->PriceEnquiryID = $data->Row["Price_Enquiry_ID"];
			$this->Product->ID = $data->Row["Product_ID"];
			$this->Quantity = $data->Row["Quantity"];
			$this->Orders = $data->Row["Orders"];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO price_enquiry_line (Price_Enquiry_ID, Product_ID, Quantity, Orders) VALUES (%d, %d, %d, %d)", mysql_real_escape_string($this->PriceEnquiryID), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Orders)));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE price_enquiry_line SET Quantity=%d, Orders=%d WHERE Price_Enquiry_Line_ID=%d", mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Orders), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM price_enquiry_line WHERE Price_Enquiry_Line_ID=%d", mysql_real_escape_string($this->ID)));
		PriceEnquirySupplierLine::DeletePriceEnquiryLine($this->ID);
	}

	static function DeletePriceEnquiry($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM price_enquiry_line WHERE Price_Enquiry_ID=%d", mysql_real_escape_string($id)));
	}
}