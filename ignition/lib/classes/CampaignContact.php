<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Campaign.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContactEvent.php');

class CampaignContact {
	var $ID;
	var $Contact;
	var $Campaign;
	var $CampaignContactEvent;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $OwnedBy;

	function CampaignContact($id=NULL){
		$this->Campaign = new Campaign();
		$this->Contact = new Contact();
		$this->CampaignContactEvent = array();

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

		$data = new DataQuery(sprintf("SELECT * FROM campaign_contact WHERE Campaign_Contact_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Contact->ID = $data->Row['Contact_ID'];
			$this->Campaign->ID = $data->Row['Campaign_ID'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$this->OwnedBy = $data->Row['Owned_By'];

			$data2 = new DataQuery(sprintf("SELECT * FROM campaign_contact_event WHERE Campaign_Contact_ID=%d", mysql_real_escape_string($this->ID)));
			while($data2->Row) {
				$contactEvent = new CampaignContactEvent();
				$contactEvent->ID = $data2->Row['Campaign_Contact_Event_ID'];
				$contactEvent->CampaignContact->ID = $data2->Row['Campaign_Contact_ID'];
				$contactEvent->CampaignEvent->ID = $data2->Row['Campaign_Event_ID'];
				$contactEvent->IsComplete = $data2->Row['Is_Complete'];
				$contactEvent->IsActive = $data2->Row['Is_Active'];
				$contactEvent->IsEmailSent = $data2->Row['Is_Email_Sent'];
				$contactEvent->IsEmailFailed = $data2->Row['Is_Email_Failed'];
				$contactEvent->IsEmailViewed = $data2->Row['Is_Email_Viewed'];
				$contactEvent->IsEmailFollowed = $data2->Row['Is_Email_Followed'];
				$contactEvent->IsPhoneScheduled = $data2->Row['Is_Phone_Scheduled'];
				$contactEvent->CreatedOn = $data2->Row['Created_On'];
				$contactEvent->CreatedBy = $data2->Row['Created_By'];
				$contactEvent->ModifiedOn = $data2->Row['Modified_On'];
				$contactEvent->ModifiedBy = $data2->Row['Modified_By'];

				$this->CampaignContactEvent[$contactEvent->ID] = $contactEvent;

				$data2->Next();
			}
			$data2->Disconnect();

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM campaign_contact WHERE Campaign_Contact_ID=%d", mysql_real_escape_string($this->ID)));
		CampaignContactEvent::DeleteCampaignContact($this->ID);
	}

	function DeleteByCampaign($campaignId=NULL){
		if(!is_null($campaignId)) {
			$this->Campaign->ID = $campaignId;
		}

		new DataQuery(sprintf("DELETE FROM campaign_contact WHERE Campaign_ID=%d", mysql_real_escape_string($this->Campaign->ID)));
	}

	function DeleteByContact($contactId=NULL){
		if(!is_null($contactId)) {
			$this->Contact->ID = $contactId;
		}

		new DataQuery(sprintf("DELETE FROM campaign_contact WHERE Contact_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO campaign_contact (Contact_ID, Campaign_ID, Created_On, Created_By, Modified_On, Modified_By, Owned_By) VALUES ('%s', '%s', NOW(), %d, NOW(), %d, %d)", mysql_real_escape_string($this->Contact->ID), mysql_real_escape_string($this->Campaign->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->OwnedBy)));
		$this->ID = $data->InsertID;
		$data->Disconnect();

		$contactEvent = new CampaignContactEvent();
		$contactEvent->CampaignContact->ID = $this->ID;

		$data = new DataQuery(sprintf("SELECT Campaign_Event_ID, Is_Default FROM campaign_event WHERE Campaign_ID=%d", mysql_real_escape_string($this->Campaign->ID)));
		while($data->Row) {
			$contactEvent->CampaignEvent->ID = $data->Row['Campaign_Event_ID'];
			$contactEvent->IsActive = $data->Row['Is_Default'];
			$contactEvent->Add();

			$data->Next();
		}
		$data->Disconnect();
	}

	function Update() {
	}

	static function DeleteCampaign($id){
		new DataQuery(sprintf("DELETE FROM campaign_contact WHERE Campaign_ID=%d", mysql_real_escape_string($id)));
	}

	static function DeleteContact($id){
		new DataQuery(sprintf("DELETE FROM campaign_contact WHERE Contact_ID=%d", mysql_real_escape_string($id)));
	}
}
?>
