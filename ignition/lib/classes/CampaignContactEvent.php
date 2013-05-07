<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignEvent.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContact.php');

class CampaignContactEvent {
	var $ID;
	var $CampaignContact;
	var $CampaignEvent;
	var $IsComplete;
	var $IsActive;
	var $IsEmailSent;
	var $IsEmailFailed;
	var $IsEmailViewed;
	var $IsEmailFollowed;
	var $IsPhoneScheduled;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function CampaignContactEvent($id=NULL) {
		$this->CampaignContact = new CampaignContact();
		$this->CampaignEvent = new CampaignEvent();
		$this->IsComplete = 'N';
		$this->IsActive = 'N';
		$this->IsEmailSent = 'N';
		$this->IsEmailFailed = 'N';
		$this->IsEmailViewed = 'N';
		$this->IsEmailFollowed = 'N';
		$this->IsPhoneScheduled = 'N';

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

		$data = new DataQuery(sprintf("SELECT * FROM campaign_contact_event WHERE Campaign_Contact_Event_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->CampaignContact->ID = $data->Row['Campaign_Contact_ID'];
			$this->CampaignEvent->ID = $data->Row['Campaign_Event_ID'];
			$this->IsComplete = $data->Row['Is_Complete'];
			$this->IsActive = $data->Row['Is_Active'];
			$this->IsEmailSent = $data->Row['Is_Email_Sent'];
			$this->IsEmailFailed = $data->Row['Is_Email_Failed'];
			$this->IsEmailViewed = $data->Row['Is_Email_Viewed'];
			$this->IsEmailFollowed = $data->Row['Is_Email_Followed'];
			$this->IsPhoneScheduled = $data->Row['Is_Phone_Scheduled'];
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
		$data = new DataQuery(sprintf("INSERT INTO campaign_contact_event (Campaign_Contact_ID, Campaign_Event_ID, Is_Complete, Is_Active, Is_Email_Sent, Is_Email_Failed, Is_Email_Viewed, Is_Email_Followed, Is_Phone_Scheduled, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->CampaignContact->ID), mysql_real_escape_string($this->CampaignEvent->ID), mysql_real_escape_string($this->IsComplete), mysql_real_escape_string($this->IsActive), mysql_real_escape_string($this->IsEmailSent), mysql_real_escape_string($this->IsEmailFailed), mysql_real_escape_string($this->IsEmailViewed), mysql_real_escape_string($this->IsEmailFollowed), mysql_real_escape_string($this->IsPhoneScheduled), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Complete='%s', Is_Active='%s', Is_Email_Sent='%s', Is_Email_Failed='%s', Is_Email_Viewed='%s', Is_Email_Followed='%s', Is_Phone_Scheduled='%s', Modified_On=NOW(), Modified_By=%d WHERE Campaign_Contact_Event_ID=%d", mysql_real_escape_string($this->IsComplete), mysql_real_escape_string($this->IsActive), mysql_real_escape_string($this->IsEmailSent), mysql_real_escape_string($this->IsEmailFailed), mysql_real_escape_string($this->IsEmailViewed), mysql_real_escape_string($this->IsEmailFollowed), mysql_real_escape_string($this->IsPhoneScheduled), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		if(!is_numeric($id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM campaign_contact_event WHERE Campaign_Contact_Event_ID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteCampaignContact($id){
		new DataQuery(sprintf("DELETE FROM campaign_contact_event WHERE Campaign_Contact_ID=%d", mysql_real_escape_string($id)));
	}

	static function DeleteCampaignEvent($id){
		new DataQuery(sprintf("DELETE FROM campaign_contact_event WHERE Campaign_Event_ID=%d", mysql_real_escape_string($id)));
	}

	function DeleteByCampaignContact($campaignContactId=NULL) {
		if(!is_null($campaignContactId)) {
			$this->CampaignContact->ID = $campaignContactId;
		}

		new DataQuery(sprintf("DELETE FROM campaign_contact_event WHERE Campaign_Contact_ID=%d", mysql_real_escape_string($this->CampaignContact->ID)));
	}
}
?>