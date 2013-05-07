<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Channel.php');

class Website {
	var $ID;
	var $Channel;
	var $Domain;
	var $CreatedBy;
	var $CreatedOn;
	var $ModifiedBy;
	var $ModifiedOn;

	public function __construct($id = null) {
		$this->Channel = new Channel();

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

		$data = new DataQuery(sprintf("SELECT * FROM website WHERE Website_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->Channel->ID = $data->Row['Channel_ID'];
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

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO website (Channel_ID, Domain, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Channel->ID), mysql_real_escape_string($this->Domain), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE website SET Channel_ID=%d, Domain='%s', Modified_On=NOW(), Modified_By=%d WHERE Website_ID=%d", mysql_real_escape_string($this->Channel->ID), mysql_real_escape_string($this->Domain), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM website WHERE Website_ID=%d", mysql_real_escape_string($this->ID)));
	}
}