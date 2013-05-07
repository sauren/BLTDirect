<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

class CampaignPhoneNumbers {
	public $ID;
	public $Title;
	public $AdwordID;
	public $Phone;
	public $AdwordParameter;

	public function __construct($id = NULL){
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
		$this->AdwordParameter = Setting::GetValue('adword_campaign_parameter');
	}

	public function Get($id = NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("select * from campaign_phone_numbers where ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Title = $data->Row['Title'];
			$this->AdwordID = $data->Row['AdwordID'];
			$this->Phone = $data->Row['Phone'];
			$data->Disconnect();
			return true;
		}
		
		$data->Disconnect();
		return false;
	}

	public function GetFromAdwords($adwordId = NULL){
		if(is_null($adwordId)){
			$adwordId = isset($_REQUEST[$this->AdwordParameter]) ? strtolower($_REQUEST[$this->AdwordParameter]) : null;
		}

		if(!is_null($adwordId)){
			$this->AdwordID = $adwordId;
		} else {
			return false;
		}

		$data = new DataQuery(sprintf("select * from campaign_phone_numbers where AdwordID='%s'", mysql_real_escape_string($this->AdwordID)));
		if($data->TotalRows > 0) {
			$this->ID = $data->Row['ID'];
			$this->Title = $data->Row['Title'];
			$this->Phone = $data->Row['Phone'];
			$data->Disconnect();
			return true;
		}
		
		$data->Disconnect();
		return false;
	}

	public function Add(){
		$data = new DataQuery(sprintf("insert into campaign_phone_numbers (Title, AdwordID, Phone) values ('%s', '%s', '%s')",
			mysql_real_escape_string($this->Title),
			mysql_real_escape_string($this->AdwordID),
			mysql_real_escape_string($this->Phone)));
		
		$this->ID = $data->InsertID;
	}
	
	public function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("update campaign_phone_numbers set Title='%s', AdwordID='%s', Phone='%s' where ID=%d",
			mysql_real_escape_string($this->Title),
			mysql_real_escape_string($this->AdwordID),
			mysql_real_escape_string($this->Phone),
			mysql_real_escape_string($this->ID)));
	}
	
	public function Delete($id = NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		
		new DataQuery(sprintf("delete from campaign_phone_numbers where ID=%d", mysql_real_escape_string($this->ID)));
	}
}
