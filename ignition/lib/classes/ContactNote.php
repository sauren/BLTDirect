<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ContactNote {
	var $ID;
	var $ContactID;
	var $Description;
	var $IsUnread;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function ContactNote($id=NULL) {
		$this->IsUnread = 'Y';

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM contact_note WHERE Contact_Note_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->ContactID = $data->Row['Contact_ID'];
			$this->Description = $data->Row['Description'];
			$this->IsUnread = $data->Row['Is_Unread'];
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

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO contact_note (Contact_ID, Description, Is_Unread, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', '%s', Now(), %d, Now(), %d)", mysql_real_escape_string($this->ContactID), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->IsUnread), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE contact_note SET Description='%s', Is_Unread='%s', Modified_On=Now(), Modified_By=%d WHERE Contact_Note_ID=%d", mysql_real_escape_string($this->Description), mysql_real_escape_string($this->IsUnread), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM contact_note WHERE Contact_Note_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}
}
?>