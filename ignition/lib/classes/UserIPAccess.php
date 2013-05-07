<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class UserIPAccess {
	var $ID;
	var $UserID;
	var $Access;
	var $Restrictions;

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
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM users_ipaccess WHERE IP_Access_ID=%d", mysql_real_escape_string($this->ID)));

		if($data->TotalRows >0) {
			$this->UserID = $data->Row['User_ID'];
			$this->Access = $data->Row['IP_Access'];
			$this->Restrictions = $data->Row['IP_Restrictions'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetByUserID($userID = null) {
		if(!is_null($userID)) {
			$this->UserID = $userID;
		}

		$data = new DataQuery(sprintf("SELECT * FROM users_ipaccess WHERE User_ID=%d", mysql_real_escape_string($this->UserID)));

		if($data->TotalRows >0) {
			$this->ID = $data->Row['IP_Access_ID'];
			$this->Access = $data->Row['IP_Access'];
			$this->Restrictions = $data->Row['IP_Restrictions'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetAccess() {
		$access = array();
		$items = explode(',', $this->Access);

		foreach($items as $item) {
			if(!empty($item)) {
				if(strpos($item, '-')) {
					$range = explode('-', $item);

					$array = array();
					$array[] = sprintf("%u", ip2long(trim($range[0])));
					$array[] = sprintf("%u", ip2long(trim($range[count($range)-1])));

					$access[] = $array;
				} else {
					$access[] = sprintf("%u", ip2long(trim($item)));
				}
			}
		}

		return $access;
	}

	function GetRestrictions() {
		$restrictions = array();
		$items = explode(',', $this->Restrictions);

		foreach($items as $item) {
			if(strlen($item) > 0) {
				if(strpos($item, '-')) {
					$range = explode('-', $item);

					$array = array();
					$array[] = sprintf("%u", ip2long($range[0]));
					$array[] = sprintf("%u", ip2long($range[count($range)-1]));

					$restrictions[] = $array;
				} else {
					$restrictions[] = sprintf("%u", ip2long($item));
				}
			}
		}

		return $restrictions;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO users_ipaccess (User_ID, IP_Access, IP_Restrictions) VALUES (%d, '%s', '%s')", mysql_real_escape_string($this->UserID), mysql_real_escape_string($this->Access), mysql_real_escape_string($this->Restrictions)));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE users_ipaccess SET IP_Access='%s', IP_Restrictions='%s' WHERE IP_Access_ID=%d", mysql_real_escape_string($this->Access), mysql_real_escape_string($this->Restrictions), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM users_ipaccess WHERE IP_Access_ID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteUser($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM users_ipaccess WHERE User_ID=%d", mysql_real_escape_string($id)));
	}
}

