<?php
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');

class LibraryFileType {
	var $ID;
	var $Name;
	var $Reference;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function __construct($id = null) {
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM library_file_type WHERE File_Type_ID=%d", $this->ID));
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

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO library_file_type (Name, Reference, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', NOW(), %d, NOW(), %d)", $this->Name, $this->Reference, $GLOBALS['SESSION_USER_ID'], $GLOBALS['SESSION_USER_ID']));
		
		$this->ID = $data->InsertID;
	}

	function Update() {
		new DataQuery(sprintf("UPDATE library_file_type SET Name='%s', Reference='%s', Modified_On=Now(), Modified_By=%d WHERE File_Type_ID=%d", $this->Name, $this->Reference, $GLOBALS['SESSION_USER_ID'], $this->ID));
	}

	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		new DataQuery(sprintf("DELETE FROM library_file_type WHERE File_Type_ID=%d", $this->ID));
	}
}