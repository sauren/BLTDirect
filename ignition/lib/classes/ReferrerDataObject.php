<?php
class ReferrerDataObject {
	var $ID;
	var $Domain;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function ReferrerDataObject($id = null) {
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

		$data = new DataQuery(sprintf("SELECT * FROM referrer WHERE ReferrerID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Domain = $data->Row['Domain'];
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

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO referrer (Domain, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES ('%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Domain), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE referrer SET Domain='%s', ModifiedOn=NOW(), ModifiedBy=%d WHERE ReferrerID=%d", mysql_real_escape_string($this->Domain), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM referrer WHERE ReferrerID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>