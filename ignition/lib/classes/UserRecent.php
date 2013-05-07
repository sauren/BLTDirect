<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class UserRecent {
	var $ID;
	var $UserID;
	var $Name;
	var $Url;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function UserRecent($id = null) {
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

		$data = new DataQuery(sprintf("SELECT * FROM users_recent WHERE User_Recent_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows >0) {
			$this->UserID = $data->Row['User_ID'];
			$this->Name = $data->Row['Recent_Name'];
			$this->Url = $data->Row['Recent_Url'];
			$this->CreatedOn = $data->Row["Created_On"];
			$this->CreatedBy = $data->Row["Created_By"];
			$this->ModifiedOn = $data->Row["Modified_On"];
			$this->ModifiedBy = $data->Row["Modified_By"];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO users_recent (User_ID, Recent_Name, Recent_Url, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->UserID), mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Url), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE users_recent SET User_ID=%d, Recent_Name='%s', Recent_Url='%s', Modified_On=NOW(), Modified_By=%d WHERE User_Recent_ID=%d", mysql_real_escape_string($this->UserID), mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Url), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM users_recent WHERE User_Recent_ID=%d", mysql_real_escape_string($this->ID)));
	}

	static function Record($name, $url) {
		$recent = new UserRecent();

		$data = new DataQuery(sprintf("SELECT User_Recent_ID FROM users_recent WHERE User_ID=%d AND Recent_Url LIKE '%s'", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($url)));
		if($data->TotalRows > 0) {
			$recent->Delete($data->Row['User_Recent_ID']);
		}
		$data->Disconnect();

		$recent->UserID = $GLOBALS['SESSION_USER_ID'];
		$recent->Name = $name;
		$recent->Url = $url;
		$recent->Add();
	}
}
?>