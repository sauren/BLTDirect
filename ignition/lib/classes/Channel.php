<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class Channel {
	var $ID;
	var $Name;
	var $Domain;
	var $CreatedBy;
	var $CreatedOn;
	var $ModifiedBy;
	var $ModifiedOn;

	public function __construct($id = null) {
		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	public function Get($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM channel WHERE Channel_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->Name = $data->Row['Name'];
			$this->Domain = $data->Row['Domain'];
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

	public function GetByDomain($domain = null){
		$data = new DataQuery(sprintf("SELECT c.Channel_ID FROM website AS w INNER JOIN channel AS c ON c.Channel_ID=w.Channel_ID WHERE w.Domain LIKE '%%%s'", mysql_real_escape_string($domain)));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['Channel_ID']);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO channel (Name, Domain, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Domain), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE channel SET Name='%s', Domain='%s', Modified_On=NOW(), Modified_By=%d WHERE Channel_ID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Domain), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM channel WHERE Channel_ID=%d", mysql_real_escape_string($this->ID)));
	}
}