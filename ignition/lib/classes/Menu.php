<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DataQuery.php");

class Menu {
	var $ID;
	var $Title;
	var $Link;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function Menu($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM menu WHERE Menu_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Title = $data->Row['Title'];
			$this->Link = $data->Row['Link'];
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
		$data = new DataQuery(sprintf("INSERT INTO menu (Title, Link, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Link), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {
		new DataQuery(sprintf("UPDATE menu SET Title='%s', Link='%s', Modified_On=NOW(), Modified_By=%d WHERE Menu_ID=%d", mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Link), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		new DataQuery(sprintf("DELETE FROM menu WHERE Menu_ID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>