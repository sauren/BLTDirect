<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class EnquiryTemplate {
	var $ID;
	var $Title;
	var $Template;
	var $TypeID;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function EnquiryTemplate($id=NULL){
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

		$data = new DataQuery(sprintf("SELECT * FROM enquiry_template WHERE Enquiry_Template_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->Row) {
			$this->Title = stripslashes($data->Row['Title']);
			$this->Template = stripslashes($data->Row['Template']);
			$this->TypeID = $data->Row['Enquiry_Type_ID'];
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
		$data = new DataQuery(sprintf("INSERT INTO enquiry_template (Title, Template, Enquiry_Type_ID, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string(stripslashes($this->Title)), mysql_real_escape_string(stripslashes($this->Template)), mysql_real_escape_string($this->TypeID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE enquiry_template SET Title='%s', Template='%s', Enquiry_Type_ID=%d, Modified_On=NOW(), Modified_By=%d WHERE Enquiry_Template_ID=%d", mysql_real_escape_string(stripslashes($this->Title)), mysql_real_escape_string(stripslashes($this->Template)), mysql_real_escape_string($this->TypeID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM enquiry_template WHERE Enquiry_Template_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}
}
?>