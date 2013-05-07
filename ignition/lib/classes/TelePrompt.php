<?php
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');

class TelePrompt {
	var $ID;
	var $Title;
	var $Ref;
	var $Body;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function __construct($id = null) {
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

		$data = new DataQuery(sprintf("SELECT * FROM teleprompt WHERE TelePrompt_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Title = $data->Row['Title'];
			$this->Ref = $data->Row['Ref'];
			$this->Body = $data->Row['Body'];
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
		$data = new DataQuery(sprintf("INSERT INTO teleprompt (Title, Ref, Body, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', '%s', Now(), %d, Now(), %d)", mysql_real_escape_string(stripslashes($this->Title)), mysql_real_escape_string(stripslashes($this->Ref)), mysql_real_escape_string(stripslashes($this->Body)), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->ID = $data->InsertID;
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE teleprompt SET Title='%s', Body='%s', Modified_On=Now(), Modified_By=%d WHERE TelePrompt_ID=%d", mysql_real_escape_string(stripslashes($this->Title)), mysql_real_escape_string(stripslashes($this->Body)), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Remove($id = null) {
		if(!is_null($id) && is_numeric($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM teleprompt WHERE TelePrompt_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Output($ref=null) {
		if(!is_null($ref)){
			$data = new DataQuery(sprintf("SELECT Body FROM teleprompt WHERE Ref LIKE '%s'", mysql_real_escape_string($ref)));
			if($data->TotalRows > 0) {
				$this->Body = '<div style="border: 1px solid #F7DB5A; background-color:#FFF0AB; padding:15px; margin-bottom:10px;">'.$data->Row['Body'].'</div>';
				
				$data->Disconnect();
				return true;
			}

			$data->Disconnect();
			return false;
		}
	}
}