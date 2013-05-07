<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContactEvent.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignEvent.php');

class Campaign {
	var $ID;
	var $Title;
	var $Description;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function Campaign($id=NULL){
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

		$data = new DataQuery(sprintf("SELECT * FROM campaign WHERE Campaign_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Title = $data->Row['Title'];
			$this->Description = $data->Row['Description'];
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

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM campaign WHERE Campaign_ID=%d", $this->ID));
		new DataQuery(sprintf("DELETE campaign_contact_event FROM campaign_event, campaign_contact_event WHERE campaign_event.Campaign_Event_ID=campaign_contact_event.Campaign_Event_ID AND campaign_event.Campaign_ID=%d", mysql_real_escape_string($this->ID)));
		CampaignEvent::DeleteCampaign($this->ID);
		CampaignContact::DeleteCampaign($this->ID);
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO campaign (Title, Description, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Description), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE campaign SET Title='%s', Description='%s', Modified_On=NOW(), Modified_By=%d WHERE Campaign_ID=%d", mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Description), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Copy() {
		$campaignId = $this->ID;

		$this->Title = sprintf('Copy of %s', $this->Title);
		$this->Add();
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("UPDATE campaign SET Created_On='%s' WHERE Campaign_ID=%d", mysql_real_escape_string($this->CreatedOn), mysql_real_escape_string($this->ID)));
		$data->Disconnect();

		$campaignEventAssoc = array();
		$campaignEvent = new CampaignEvent();
		$campaignContactAssoc = array();
		$campaignContact = new CampaignContact();
		$campaignContactEvent = new CampaignContactEvent();
		$campaignEventArr = array();
		$campaignEventStr = '';

		$data = new DataQuery(sprintf("SELECT * FROM campaign_event WHERE Campaign_ID=%d ORDER BY Created_On ASC", mysql_real_escape_string($campaignId)));
		while($data->Row) {
			$data2 = new CampaignEvent();
			$data2->ID = $this->ID;
			$data2->Campaign = $data->Row['Campaign'];
			$data2->Type = $data->Row['Type'];
			$data2->Title = $data->Row['Title'];
			$data2->Scheduled = $data->Row['Scheduled'];
			$data2->IsDefault = $data->Row['Is_Default'];
			$data2->IsAutomatic = $data->Row['Is_Automatic'];
			$data2->IsAutomaticDisabling = $data->Row['Is_Automatic_Disabling'];
			$data2->IsDated = $data->Row['Is_Dated'];
			$data2->IsBcc = $data->Row['Is_Bcc'];
			$data2->MaximumBccCount = $data->Row['Maximum_Bcc_Count'];
			$data2->Template = $data->Row['Template'];
			$data2->Subject = $data->Row['Subject'];
			$data2->FromAddress = $data->Row['From_Address'];
			$data2->OwnedBy = $data->Row['Owned_By'];
			$data2->QueueRate = $data->Row['Queue_Rate'];
			$data2->CreatedOn = $data->Row['Created_On'];
			$data2->CreatedBy = $GLOBALS['SESSION_USER_ID'];
			$data2->ModifiedOn = NOW();
			$data2->ModifiedBy = $GLOBALS['SESSION_USER_ID'];
			$data2->add();
			$campaignEventAssoc[$data->Row['Campaign_Event_ID']] = $data2->InsertID;
			$campaignEventArr[] = $data->Row['Campaign_Event_ID'];

			$data2->Disconnect();

			$data->Next();
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT * FROM campaign_contact WHERE Campaign_ID=%d ORDER BY Created_On ASC", mysql_real_escape_string($campaignId)));
		while($data->Row) {
			$data2 = new CampaignContact();
			$data2->ID = $this->ID;
			$data2->Contact = $data->Row['Contact_ID'];
			$data2->Campaign = $data->Row['Campaign_ID'];
			$data2->CampaignContactEvent = $data->Row['Campaign_Contact_ID'];
			$data2->CreatedOn = $data->Row['Created_On'];
			$data2->CreatedBy = $GLOBALS['SESSION_USER_ID'];
			$data2->ModifiedOn = NOW();
			$data2->ModifiedBy = $GLOBALS['SESSION_USER_ID'];
			$data2->OwnedBy = $data->Row['Owned_By'];
			$data2->add();

			$campaignContactAssoc[$data->Row['Campaign_Contact_ID']] = $data2->InsertID;

			$data2->Disconnect();

			$data->Next();
		}
		$data->Disconnect();

		if(count($campaignEventArr) > 0) {
			$campaignEventStr = sprintf('(Campaign_Event_ID=%s)', implode(' OR Campaign_Event_ID=', $campaignEventArr));

			$data = new DataQuery(sprintf("SELECT Campaign_Contact_Event_ID, Campaign_Contact_ID, Campaign_Event_ID FROM campaign_contact_event WHERE %s ORDER BY Created_On ASC", mysql_real_escape_string($campaignEventStr)));
			while($data->Row) {
				if(isset($campaignEventAssoc[$data->Row['Campaign_Event_ID']]) && isset($campaignContactAssoc[$data->Row['Campaign_Contact_ID']])) {
					$campaignContactEvent->Get($data->Row['Campaign_Contact_Event_ID']);
					$campaignContactEvent->CampaignEvent->ID = $campaignEventAssoc[$data->Row['Campaign_Event_ID']];
					$campaignContactEvent->CampaignContact->ID = $campaignContactAssoc[$data->Row['Campaign_Contact_ID']];
					$campaignContactEvent->Add();
				}

				$data->Next();
			}
			$data->Disconnect();
		}
	}
}