<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroup.php');

class ContactGroupAssoc {
	var $ID;
	var $ContactGroup;
	var $Contact;

	function ContactGroupAssoc($id=NULL){
		$this->Contact = new Contact();
		$this->ContactGroup = new ContactGroup();

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;


		$data = new DataQuery(sprintf("SELECT * FROM contact_group_assoc WHERE Contact_Group_Assoc_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Contact->ID = $data->Row['Contact_ID'];
			$this->ContactGroup->ID = $data->Row['Contact_Group_ID'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM contact_group_assoc WHERE Contact_Group_Assoc_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function Add() {

		$data = new DataQuery(sprintf("INSERT INTO contact_group_assoc (Contact_Group_ID, Contact_ID, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d,%d,Now(),%d, Now(),%d)", mysql_real_escape_string($this->ContactGroup->ID), mysql_real_escape_string($this->Contact->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update() {
	}

	static function DeleteContact($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM contact_group_assoc WHERE Contact_ID=%d", mysql_real_escape_string($id)));
	}

	static function DeleteContactGroup($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM contact_group_assoc WHERE Contact_Group_ID=%d", mysql_real_escape_string($id)));
	}
}
?>