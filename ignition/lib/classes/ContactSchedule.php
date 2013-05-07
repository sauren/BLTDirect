<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactScheduleType.php');

class ContactSchedule {
	var $ID;
	var $ParentID;
	var $ContactID;
	var $Type;
	var $Note;
	var $ScheduledOn;
	var $IsComplete;
	var $CompletedOn;
	var $Status;
	var $Message;
	var $OwnedBy;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function ContactSchedule($id=NULL) {
		$this->Type = new ContactScheduleType();
		$this->ScheduledOn = '0000-00-00 00:00:00';
		$this->IsComplete = 'N';
		$this->CompletedOn = '0000-00-00 00:00:00';

		if(!is_null($id)) {
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

		$data = new DataQuery(sprintf("SELECT * FROM contact_schedule WHERE Contact_Schedule_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->ParentID = $data->Row['Parent_Contact_Schedule_ID'];
			$this->ContactID = $data->Row['Contact_ID'];
			$this->Type->ID = $data->Row['Contact_Schedule_Type_ID'];
			$this->Note = $data->Row['Note'];
			$this->ScheduledOn = $data->Row['Scheduled_On'];
			$this->IsComplete = $data->Row['Is_Complete'];
			$this->CompletedOn = $data->Row['Completed_On'];
			$this->Status = $data->Row['Status'];
			$this->Message = $data->Row['Message'];
			$this->OwnedBy = $data->Row['Owned_By'];
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
		$data = new DataQuery(sprintf("INSERT INTO contact_schedule (Parent_Contact_Schedule_ID, Contact_ID, Contact_Schedule_Type_ID, Note, Scheduled_On, Is_Complete, Completed_On, Status, Message, Owned_By, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->ParentID), mysql_real_escape_string($this->ContactID), mysql_real_escape_string($this->Type->ID), mysql_real_escape_string($this->Note), mysql_real_escape_string($this->ScheduledOn), mysql_real_escape_string($this->IsComplete), mysql_real_escape_string($this->CompletedOn), mysql_real_escape_string($this->Status), mysql_real_escape_string($this->Message), mysql_real_escape_string($this->OwnedBy), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {


		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("UPDATE contact_schedule SET Parent_Contact_Schedule_ID=%d, Note='%s', Scheduled_On='%s', Is_Complete='%s', Completed_On='%s', Status='%s', Message='%s', Owned_By=%d, Modified_On=NOW(), Modified_By=%d WHERE Contact_Schedule_ID=%d", mysql_real_escape_string($this->ParentID), mysql_real_escape_string($this->Note), mysql_real_escape_string($this->ScheduledOn), mysql_real_escape_string($this->IsComplete), mysql_real_escape_string($this->CompletedOn), mysql_real_escape_string($this->Status), mysql_real_escape_string($this->Message), mysql_real_escape_string($this->OwnedBy), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM contact_schedule WHERE Contact_Schedule_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Complete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}

		$this->IsComplete = 'Y';
		$this->CompletedOn = now();
		$this->Update();
	}
}
?>