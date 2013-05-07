<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class OverheadType {
	var $ID;
	var $DeveloperKey;
	var $Name;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function OverheadType($id=NULL){
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

		$data = new DataQuery(sprintf("SELECT * FROM overhead_type WHERE Overhead_Type_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->Row) {
			$this->DeveloperKey = stripslashes($data->Row['Developer_Key']);
			$this->Name = stripslashes($data->Row['Name']);
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
		$data = new DataQuery(sprintf("INSERT INTO overhead_type (Developer_Key, Name, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string(stripslashes($this->DeveloperKey)), mysql_real_escape_string(stripslashes($this->Name)), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE overhead_type SET Developer_Key='%s', Name='%s', Modified_On=NOW(), Modified_By=%d WHERE Overhead_Type_ID=%d", mysql_real_escape_string(stripslashes($this->DeveloperKey)), mysql_real_escape_string(stripslashes($this->Name)), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM overhead_type WHERE Overhead_Type_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}
}
?>