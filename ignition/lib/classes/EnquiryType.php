<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class EnquiryType {
	var $ID;
	var $DeveloperKey;
	var $Name;
	var $IsPublic;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function __construct($id=NULL) {
		$this->IsPublic = 'N';
		
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
		$data = new DataQuery(sprintf("SELECT * FROM enquiry_type WHERE Enquiry_Type_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->Row) {
			$this->DeveloperKey = $data->Row['Developer_Key'];
			$this->Name = $data->Row['Name'];
			$this->IsPublic = $data->Row['Is_Public'];
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

	function GetByDeveloperKey($key = null) {
		if(!is_null($key)) {
			$this->DeveloperKey = $key;
		}

		$data = new DataQuery(sprintf("SELECT Enquiry_Type_ID FROM enquiry_type WHERE Developer_Key LIKE '%s'", mysql_real_escape_string($this->DeveloperKey)));
		if($data->Row) {
			$this->Get($data->Row['Enquiry_Type_ID']);

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO enquiry_type (Developer_Key, Name, Is_Public, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string(stripslashes($this->DeveloperKey)), mysql_real_escape_string(stripslashes($this->Name)), mysql_real_escape_string($this->IsPublic), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE enquiry_type SET Developer_Key='%s', Name='%s', Is_Public='%s', Modified_On=NOW(), Modified_By=%d WHERE Enquiry_Type_ID=%d", mysql_real_escape_string(stripslashes($this->DeveloperKey)), mysql_real_escape_string(stripslashes($this->Name)), mysql_real_escape_string($this->IsPublic), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM enquiry_type WHERE Enquiry_Type_ID=%d", mysql_real_escape_string($this->ID)));
	}
}