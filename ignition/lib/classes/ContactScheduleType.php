<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ContactScheduleType {
	var $ID;
	var $Name;
	var $Reference;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function ContactScheduleType($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM contact_schedule_type WHERE Contact_Schedule_Type_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Name'];
			$this->Reference = $data->Row['Reference'];
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

	function GetByReference($reference = null) {
		if(!is_null($reference)) {
			$this->Reference = $reference;
		}

		$data = new DataQuery(sprintf("SELECT Contact_Schedule_Type_ID FROM contact_schedule_type WHERE Reference LIKE '%s'", mysql_real_escape_string($this->Reference)));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['Contact_Schedule_Type_ID']);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO contact_schedule_type (Name, Reference, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Reference), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE contact_schedule_type SET Name='%s', Reference='%s', Modified_On=NOW(), Modified_By=%d WHERE Contact_Schedule_Type_ID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Reference), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM contact_schedule_type WHERE Contact_Schedule_Type_ID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>