<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class CallFrom {
	var $ID;
	var $PhoneNumber;
	var $Description;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function CallFrom($id=NULL){
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


		$data = new DataQuery(sprintf("SELECT * FROM call_from WHERE Call_From_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->Row) {
			$this->PhoneNumber = $data->Row['Phone_Number'];
			$this->Description = $data->Row['Description'];
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
		$data = new DataQuery(sprintf("INSERT INTO call_from (Phone_Number, Description, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->PhoneNumber), mysql_real_escape_string($this->Description), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update(){

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("UPDATE call_from SET Phone_Number='%s', Description='%s', Modified_On=NOW(), Modified_By=%d WHERE Call_From_ID=%d", mysql_real_escape_string($this->PhoneNumber), mysql_real_escape_string($this->Description), $GLOBALS['SESSION_USER_ID'], mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM call_from WHERE Call_From_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Exists($phoneNumber = null) {
		if(!is_null($phoneNumber)) {
			$this->PhoneNumber = $phoneNumber;
		}

		$data = new DataQuery(sprintf("SELECT Call_From_ID FROM call_from WHERE Phone_Number LIKE '%s'", mysql_real_escape_string($this->PhoneNumber)));
		if($data->TotalRows > 0) {
			$this->ID = $data->Row['Call_From_ID'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
}
?>