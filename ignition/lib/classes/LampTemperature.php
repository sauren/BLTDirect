<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductSpecValue.php");

class LampTemperature {
	var $ID;
	var $Reference;
	var $Colour;
	var $CR1Ra;
	var $Value;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function LampTemperature($id = null) {
		$this->Value = new ProductSpecValue();

		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM lamp_temperature WHERE Lamp_Temperature_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Reference = $data->Row['Reference'];
			$this->Colour = $data->Row['Colour'];
			$this->CR1Ra = $data->Row['CR1_Ra'];
			$this->Value->ID = $data->Row['Specification_Value_ID'];
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
		$data = new DataQuery(sprintf("INSERT INTO lamp_temperature (Reference, Colour, CR1_Ra, Specification_Value_ID, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', '%s', %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Reference), mysql_real_escape_string($this->Colour), mysql_real_escape_string($this->CR1Ra), mysql_real_escape_string($this->Value->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE lamp_temperature SET Reference='%s', Colour='%s', CR1_Ra='%s', Specification_Value_ID=%d, Modified_On=NOW(), Modified_By=%d WHERE Lamp_Temperature_ID=%d", mysql_real_escape_string($this->Reference), mysql_real_escape_string($this->Colour), mysql_real_escape_string($this->CR1Ra), mysql_real_escape_string($this->Value->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM lamp_temperature WHERE Lamp_Temperature_ID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>