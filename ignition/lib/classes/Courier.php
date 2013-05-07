<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class Courier {
	var $ID;
	var $Name;
	var $AccountRef;
	var $URL;
	var $IsDefault;
	var $IsTrackingActive;
	var $TrackingValidation;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	
	function __construct($id=NULL) {
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}
	
	function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT * FROM courier WHERE Courier_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->Name = $data->Row['Courier_Name'];
			$this->AccountRef = $data->Row['Account_Ref'];
			$this->URL = $data->Row['Courier_URL'];
			$this->IsDefault = $data->Row['Is_Default'];
			$this->IsTrackingActive = $data->Row['Is_Tracking_Active'];
			$this->TrackingValidation = $data->Row['Tracking_Validation'];
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
		if($this->IsDefault == 'Y') {
			$this->ResetDefault();
		}
		
		$data = new DataQuery(sprintf("insert into courier (
											Courier_Name, 
											Account_Ref, 
											Courier_URL, 
											Is_Default,
											Is_Tracking_Active,
											Tracking_Validation,
											Created_On, 
											Created_By, 
											Modified_On, 
											Modified_By) values ('%s', '%s', '%s', '%s', '%s','%s', Now(), %d, Now(), %d)", 
											mysql_real_escape_string($this->Name), 
											mysql_real_escape_string($this->AccountRef), 
											mysql_real_escape_string($this->URL), 
											mysql_real_escape_string($this->IsDefault),
											mysql_real_escape_string($this->IsTrackingActive),
											mysql_real_escape_string($this->TrackingValidation),
											mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), 
											mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
	}
	
	function Update() {
		if($this->IsDefault == 'Y') {
			$this->ResetDefault();
		}
		
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("update courier set
											Courier_Name='%s', 
											Account_Ref='%s', 
											Courier_URL='%s', 
											Is_Default='%s',
											Is_Tracking_Active='%s',
											Tracking_Validation='%s',
											Modified_On=Now(), 
											Modified_By=%d
											where Courier_ID=%d", 
											mysql_real_escape_string($this->Name), 
											mysql_real_escape_string($this->AccountRef), 
											mysql_real_escape_string($this->URL), 
											mysql_real_escape_string($this->IsDefault),
											mysql_real_escape_string($this->IsTrackingActive),
											mysql_real_escape_string($this->TrackingValidation),
											mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), 
											mysql_real_escape_string($this->ID)));
	}
	
	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		
		new DataQuery(sprintf("delete from courier where Courier_ID=%d", mysql_real_escape_string($this->ID)));
	}
	
	function ResetDefault() {
		new DataQuery("UPDATE courier SET Is_Default='N'");
	}
}