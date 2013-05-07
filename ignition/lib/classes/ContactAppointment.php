<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ContactAppointment {
	var $ID;
	var $ContactID;
	var $Message;
	var $AppointmentOn;

	function __construct($id = null) {
		$this->AppointmentOn = '0000-00-00 00:00:00';

		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM contact_appointment WHERE ContactAppointmentID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->ContactID = $data->Row['ContactID'];
			$this->Message = $data->Row['Message'];
			$this->AppointmentOn = $data->Row['AppointmentOn'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO contact_appointment (ContactID, Message, AppointmentOn) VALUES (%d, '%s', '%s')", mysql_real_escape_string($this->ContactID), mysql_real_escape_string($this->Message), mysql_real_escape_string($this->AppointmentOn)));

		$this->ID = $data->InsertID;
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE contact_appointment SET ContactID=%d, Message='%s', AppointmentOn='%s' WHERE ContactAppointmentID=%d", mysql_real_escape_string($this->ContactID), mysql_real_escape_string($this->Message), mysql_real_escape_string($this->AppointmentOn), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM contact_appointment WHERE ContactAppointmentID=%d", mysql_real_escape_string($this->ID)));
	}
}