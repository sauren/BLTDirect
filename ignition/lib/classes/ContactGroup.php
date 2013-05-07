<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroupAssoc.php');

class ContactGroup{
	var $ID;
	var $Name;
	var $Description;

	function ContactGroup($id=NULL){
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM contact_group WHERE Contact_Group_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Name'];
			$this->Description = $data->Row['Description'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("DELETE FROM contact_group WHERE Contact_Group_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();

		ContactGroupAssoc::DeleteContactgroup($this->ID);
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO contact_group (Name, Description, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s','%s',Now(),%d, Now(),%d)", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Description), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE contact_group SET Name='%s', Description='%s', Modified_On=NOW(), Modified_By=%d WHERE Contact_Group_ID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Description), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

}
?>