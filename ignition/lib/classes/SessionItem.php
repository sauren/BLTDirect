<?php
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class SessionItem {
	var $ID;
	var $SessionID;
	var $PageRequest;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	
	function SessionItem($id = null) {
		$this->CreatedBy = 0;
		$this->ModifiedBy = 0;
		
		if (!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}
	
	function Get($id = null) {
		if (!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT * FROM session_item WHERE Session_Item_ID=%d", mysql_real_escape_string($this->ID)));
		if ($data->TotalRows > 0) {
			$this->SessionID = $data->Row['Session_ID'];
			$this->PageRequest = $data->Row['Page_Request'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			
			$data->Disconnect;
			return true;
		}
		
		$data->Disconnect;
		return false;
	}
	
	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO session_item (Session_ID, Page_Request, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->SessionID), mysql_real_escape_string($this->PageRequest), mysql_real_escape_string($this->CreatedBy), mysql_real_escape_string($this->ModifiedBy)));
		$data->Disconnect();
	}
	
	function Update() {
		$data = new DataQuery(sprintf("UPDATE session_item SET Page_Request='%s', Modified_On=NOW(), Modified_By=%d WHERE Session_Item_ID=%d", mysql_real_escape_string($this->PageRequest), mysql_real_escape_string($this->ModifiedBy)));
		$data->Disconnect();
	}
	
	function Delete($id = null) {
		if (!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("DELETE FROM session_item WHERE Session_Item_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}
}
?>