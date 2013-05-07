<?php
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/LibraryFileType.php');

class LibraryFileDirectory {
	var $ID;
	var $FileType;
	var $ParentID;
	var $Name;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function __construct($id = null) {
		$this->FileType = new LibraryFileType();
		
		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM library_file_directory WHERE File_Directory_ID=%d", mysql_real_escape_string($this->ID)));
		if(!is_numeric($this->ID)){
			return false;
		}
		if($data->TotalRows > 0) {
			$this->FileType->ID = $data->Row['File_Type_ID'];
			$this->ParentID = $data->Row['Parent_ID'];
			$this->Name = $data->Row['Name'];
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
		$data = new DataQuery(sprintf("INSERT INTO library_file_directory (File_Type_ID, Parent_ID, Name, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->FileType->ID), mysql_real_escape_string($this->ParentID), mysql_real_escape_string($this->Name), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE library_file_directory SET File_Type_ID=%d, Parent_ID=%d, Name='%s', Modified_On=Now(), Modified_By=%d WHERE File_Directory_ID=%d", mysql_real_escape_string($this->FileType->ID), mysql_real_escape_string($this->ParentID), mysql_real_escape_string($this->Name), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$this->DeleteChildren($this->ID);

		new DataQuery(sprintf("DELETE FROM library_file_directory WHERE File_Directory_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function DeleteChildren($parentId = 0) {
		if($parentId > 0) {
			$data = new DataQuery(sprintf("SELECT File_Directory_ID FROM library_file_directory WHERE Parent_ID=%d", mysql_real_escape_string($parentId)));
			while($data->Row) {
				$this->DeleteChildren($data->Row['File_Directory_ID']);

				$data->Next();
			}
			$data->Disconnect();
			if(!is_numeric($this->ID)){
				return false;
			}

			new DataQuery(sprintf("DELETE FROM library_file_directory WHERE Parent_ID=%d", mysql_real_escape_string($parentId)));
		}
	}
}