<?php
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/DataQuery.php");

class SupplierReturnRequestLineType {
	public $ID;
	public $Name;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

	public function __construct($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM supplier_return_request_line_type WHERE SupplierReturnRequestLineTypeID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Name'];
			$this->CreatedOn = $data->Row['CreatedOn'];
			$this->CreatedBy = $data->Row['CreatedBy'];
			$this->ModifiedOn = $data->Row['ModifiedOn'];
			$this->ModifiedBy = $data->Row['ModifiedBy'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function GetByName($name = null) {
		if(!is_null($name)) {
			$this->Name = $name;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT SupplierReturnRequestLineTypeID FROM supplier_return_request_line_type WHERE Name LIKE '%s'", mysql_real_escape_string($this->Name)));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['SupplierReturnRequestLineTypeID']);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO supplier_return_request_line_type (Name, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES ('%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Name), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE supplier_return_request_line_type SET Name='%s', ModifiedOn=NOW(), ModifiedBy=%d WHERE SupplierReturnRequestLineTypeID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM supplier_return_request_line_type WHERE SupplierReturnRequestLineTypeID=%d", mysql_real_escape_string($this->ID)));
	}
}