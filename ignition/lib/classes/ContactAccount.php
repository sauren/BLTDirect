<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ContactAccount {
	var $ID;
	var $ContactID;
	var $AccountManagerID;
	var $StartAccountOn;
	var $EndAccountOn;
	var $CreatedOn;
	var $CreatedBy;

	function __construct($id = null) {
		$this->StartAccountOn = '0000-00-00 00:00:00';
		$this->EndAccountOn = '0000-00-00 00:00:00';

		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	function Get($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM contact_account WHERE Contact_Account_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->ContactID = $data->Row['Contact_ID'];
			$this->AccountManagerID = $data->Row['Account_Manager_ID'];
			$this->StartAccountOn = $data->Row['Start_Account_On'];
			$this->EndAccountOn = $data->Row['End_Account_On'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO contact_account (Contact_ID, Account_Manager_ID, Start_Account_On, End_Account_On, Created_On, Created_By) VALUES (%d, %d, '%s', '%s', NOW(), %d)", mysql_real_escape_string($this->ContactID), mysql_real_escape_string($this->AccountManagerID), mysql_real_escape_string($this->StartAccountOn), mysql_real_escape_string($this->EndAccountOn), $GLOBALS['SESSION_USER_ID']));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)) {
			return false;
		}

		new DataQuery(sprintf("UPDATE contact_account SET Contact_ID=%d, Account_Manager_ID=%d, Start_Account_On='%s', End_Account_On='%s' WHERE Contact_Account_ID=%d", mysql_real_escape_string($this->ContactID), mysql_real_escape_string($this->AccountManagerID), mysql_real_escape_string($this->StartAccountOn), mysql_real_escape_string($this->EndAccountOn), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM contact_account WHERE Contact_Account_ID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>