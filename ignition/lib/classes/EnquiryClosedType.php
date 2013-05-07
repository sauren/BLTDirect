<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class EnquiryClosedType {
	var $ID;
	var $Name;
	var $IsDefault;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function EnquiryClosedType($id=NULL){
		$this->IsDefault = 'N';

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

		$data = new DataQuery(sprintf("SELECT * FROM enquiry_closed_type WHERE Enquiry_Closed_Type_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->Row) {
			$this->Name = $data->Row['Name'];
			$this->IsDefault = $data->Row['Is_Default'];
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
		if($this->IsDefault == 'Y'){
			$data = new DataQuery("UPDATE enquiry_closed_type SET Is_Default='N'");
			$data->Disconnect();
		}

		$data = new DataQuery(sprintf("INSERT INTO enquiry_closed_type (Name, Is_Default, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string(stripslashes($this->Name)), mysql_real_escape_string($this->IsDefault), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update(){
		if($this->IsDefault == 'Y'){
			$data = new DataQuery("UPDATE enquiry_closed_type SET Is_Default='N'");
			$data->Disconnect();
		}


		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("UPDATE enquiry_closed_type SET Name='%s', Is_Default='%s', Modified_On=NOW(), Modified_By=%d WHERE Enquiry_Closed_Type_ID=%d", mysql_real_escape_string(stripslashes($this->Name)), mysql_real_escape_string($this->IsDefault), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM enquiry_closed_type WHERE Enquiry_Closed_Type_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}
}
?>