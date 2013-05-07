<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class UserAgent {
	var $ID;
	var $String;
	var $IsBot;

	function UserAgent($id = null) {
		$this->IsBot = 'N';

		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM user_agent WHERE User_Agent_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->ID = $data->Row['User_Agent_ID'];
			$this->String = $data->Row['String'];
			$this->IsBot = $data->Row['Is_Bot'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetByUserAgent($userAgent = null) {
		if(!is_null($userAgent)) {
			$this->String = $userAgent;
		}

		$data = new DataQuery(sprintf("SELECT * FROM user_agent WHERE Hash='%s'", md5(trim($this->String))));
		if($data->TotalRows > 0){
			$this->ID = $data->Row['User_Agent_ID'];
			$this->String = $data->Row['String'];
			$this->IsBot = $data->Row['Is_Bot'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO user_agent (String, Hash, Is_Bot) VALUES ('%s', '%s', '%s')", mysql_real_escape_string(trim($this->String)), md5(trim($this->String)), mysql_real_escape_string($this->IsBot)));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE user_agent SET Hash='%s', Is_Bot='%s' WHERE User_Agent_ID=%d", md5(trim($this->String)), mysql_real_escape_string($this->IsBot), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM user_agent WHERE User_Agent_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Exists($userAgent) {
		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM user_agent WHERE Hash='%s'", md5(trim($userAgent))));
		if($data->Row['Count'] > 0) {
			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
}