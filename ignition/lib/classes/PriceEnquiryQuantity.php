<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiry.php');

class PriceEnquiryQuantity {
	var $ID;
	var $PriceEnquiryID;
	var $Quantity;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function __construct($id = NULL) {
		if (!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM price_enquiry_quantity WHERE Price_Enquiry_Quantity_ID=%d", mysql_real_escape_string($this->ID)));
		if ($data->TotalRows > 0) {
			$this->PriceEnquiryID = $data->Row["Price_Enquiry_ID"];
			$this->Quantity = $data->Row["Quantity"];
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
		$data = new DataQuery(sprintf("INSERT INTO price_enquiry_quantity (Price_Enquiry_ID, Quantity, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->PriceEnquiryID), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE price_enquiry_quantity SET Quantity=%d, Modified_On=NOW(), Modified_By=%d WHERE Price_Enquiry_Quantity_ID=%d", mysql_real_escape_string($this->Quantity), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = NULL) {
		if (!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM price_enquiry_quantity WHERE Price_Enquiry_Quantity_ID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeletePriceEnquiry($id){

		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM price_enquiry_quantity WHERE Price_Enquiry_ID=%d", mysql_real_escape_string($id)));
	}
}