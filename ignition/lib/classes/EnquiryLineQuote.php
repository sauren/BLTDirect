<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');

class EnquiryLineQuote {
	var $ID;
	var $EnquiryLineID;
	var $Quote;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function EnquiryLineQuote($id=NULL){
		$this->Quote = new Quote();

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM enquiry_line_quote WHERE Enquiry_Line_Quote_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->Row) {
			$this->EnquiryLineID = $data->Row['Enquiry_Line_ID'];
			$this->Quote->ID = $data->Row['Quote_ID'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO enquiry_line_quote (Enquiry_Line_ID, Quote_ID, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->EnquiryLineID), mysql_real_escape_string($this->Quote->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE enquiry_line_quote SET Quote_ID=%d, Modified_On=NOW(), Modified_By=%d WHERE Enquiry_Line_Quote_ID=%d", mysql_real_escape_string($this->Quote->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("DELETE FROM enquiry_line_quote WHERE Enquiry_Line_Quote_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}
}
?>