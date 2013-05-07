<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ContactAccountIndex {
	var $ID;
	var $Reference;
	var $NextIndexNumber;

	function __construct($id = null) {
		$this->NextIndexNumber = 1;

		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM contact_account_index WHERE Contact_Account_Index_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Reference = $data->Row['Reference'];
			$this->NextIndexNumber = $data->Row['Next_Index_Number'];

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

		$data = new DataQuery(sprintf("SELECT Contact_Account_Index_ID FROM contact_account_index WHERE Reference LIKE '%s'", mysql_real_escape_string(strtoupper($this->Reference))));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['Contact_Account_Index_ID']);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO contact_account_index (Reference, Next_Index_Number) VALUES ('%s', %d)", mysql_real_escape_string(strtoupper($this->Reference)), mysql_real_escape_string($this->NextIndexNumber)));

		$this->ID = $data->InsertID;
	}

	function Update(){

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE contact_account_index SET Next_Index_Number=%d WHERE Contact_Account_Index_ID=%d", mysql_real_escape_string($this->NextIndexNumber), mysql_real_escape_string($this->ID)));
	}

	function IncreaseIndex($reference = null) {
		if(!is_null($reference)) {
			$this->Reference = $reference;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		if($this->GetByReference()) {
			new DataQuery(sprintf("UPDATE contact_account_index SET Next_Index_Number=Next_Index_Number+1 WHERE Contact_Account_Index_ID=%d", mysql_real_escape_string($this->ID)));
		} else {
			new DataQuery(sprintf("INSERT INTO contact_account_index (Reference, Next_Index_Number) VALUES ('%s', 2)", mysql_real_escape_string(strtoupper($this->Reference))));
		}
	}

	function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM contact_account_index WHERE Contact_Account_Index_ID=%d", mysql_real_escape_string($this->ID)));
	}
}