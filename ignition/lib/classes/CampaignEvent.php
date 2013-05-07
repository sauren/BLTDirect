<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Campaign.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContactEvent.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

class CampaignEvent {
	var $ID;
	var $Campaign;
	var $Type;
	var $Title;
	var $Scheduled;
	var $IsDefault;
	var $IsAutomatic;
	var $IsAutomaticDisabling;
	var $IsDated;
	var $IsBcc;
	var $MaximumBccCount;
	var $Template;
	var $Subject;
	var $FromAddress;
	var $OwnedBy;
	var $QueueRate;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function CampaignEvent($id=NULL) {
		$this->Campaign = new Campaign();
		$this->Type = 'E';
		$this->IsDefault = 'Y';
		$this->IsAutomatic = 'N';
		$this->IsAutomaticDisabling = 'N';
		$this->IsDated = 'Y';
		$this->IsBcc = 'N';
		$this->MaximumBccCount = Setting::GetValue('campaign_default_bcc');
		$this->QueueRate = Setting::GetValue('campaign_queue_rate');

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM campaign_event WHERE Campaign_Event_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Campaign->ID = $data->Row['Campaign_ID'];
			$this->Type = $data->Row['Type'];
			$this->Title = $data->Row['Title'];
			$this->Scheduled = $data->Row['Scheduled'];
			$this->IsDefault = $data->Row['Is_Default'];
			$this->IsAutomatic = $data->Row['Is_Automatic'];
			$this->IsAutomaticDisabling = $data->Row['Is_Automatic_Disabling'];
			$this->IsDated = $data->Row['Is_Dated'];
			$this->IsBcc = $data->Row['Is_Bcc'];
			$this->MaximumBccCount = $data->Row['Maximum_Bcc_Count'];
			$this->Template = $data->Row['Template'];
			$this->Subject = $data->Row['Subject'];
			$this->FromAddress = $data->Row['From_Address'];
			$this->OwnedBy = $data->Row['Owned_By'];
			$this->QueueRate = $data->Row['Queue_Rate'];
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
		$data = new DataQuery(sprintf("INSERT INTO campaign_event (Campaign_ID, Type, Title, Scheduled, Is_Default, Is_Automatic, Is_Automatic_Disabling, Is_Dated, Is_Bcc, Maximum_Bcc_Count, Template, Subject, From_Address, Owned_By, Queue_Rate, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', %d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Campaign->ID), mysql_real_escape_string($this->Type), mysql_real_escape_string(stripslashes($this->Title)), mysql_real_escape_string($this->Scheduled), mysql_real_escape_string($this->IsDefault), mysql_real_escape_string($this->IsAutomatic), mysql_real_escape_string($this->IsAutomaticDisabling), mysql_real_escape_string($this->IsDated), mysql_real_escape_string($this->IsBcc), mysql_real_escape_string($this->MaximumBccCount), mysql_real_escape_string(stripslashes($this->Template)), mysql_real_escape_string(stripslashes($this->Subject)), mysql_real_escape_string($this->FromAddress), mysql_real_escape_string($this->OwnedBy), mysql_real_escape_string($this->QueueRate), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;

		$contactEvent = new CampaignContactEvent();
		$contactEvent->CampaignEvent->ID = $this->ID;

		$data = new DataQuery(sprintf("SELECT Campaign_Contact_ID FROM campaign_contact WHERE Campaign_ID=%d", mysql_real_escape_string($this->Campaign->ID)));
		while($data->Row) {
			$contactEvent->CampaignContact->ID = $data->Row['Campaign_Contact_ID'];
			$contactEvent->IsActive = $this->IsDefault;
			$contactEvent->Add();

			$data->Next();
		}
		$data->Disconnect();
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE campaign_event SET Type='%s', Title='%s', Scheduled=%d, Is_Default='%s', Is_Automatic='%s', Is_Automatic_Disabling='%s', Is_Dated='%s', Is_Bcc='%s', Maximum_Bcc_Count=%d, Template='%s', Subject='%s', From_Address='%s', Owned_By=%d, Queue_Rate=%d, Modified_On=NOW(), Modified_By=%d WHERE Campaign_Event_ID=%d", mysql_real_escape_string($this->Type), mysql_real_escape_string(stripslashes($this->Title)), mysql_real_escape_string($this->Scheduled), mysql_real_escape_string($this->IsDefault), mysql_real_escape_string($this->IsAutomatic), mysql_real_escape_string($this->IsAutomaticDisabling), mysql_real_escape_string($this->IsDated), mysql_real_escape_string($this->IsBcc), mysql_real_escape_string($this->MaximumBccCount), mysql_real_escape_string(stripslashes($this->Template)), mysql_real_escape_string(stripslashes($this->Subject)), mysql_real_escape_string($this->FromAddress), mysql_real_escape_string($this->OwnedBy), mysql_real_escape_string($this->QueueRate), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM campaign_event WHERE Campaign_Event_ID=%d", mysql_real_escape_string($this->ID)));
		CampaignContactEvent::DeleteCampaignEvent($this->ID);
	}

	static function DeleteCampaign($id){
		new DataQuery(sprintf("DELETE FROM campaign_event WHERE Campaign_ID=%d", mysql_real_escape_string($id)));
	}
}
?>