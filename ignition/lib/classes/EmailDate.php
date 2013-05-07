<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailPanel.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailDateProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailPanelAssoc.php');

class EmailDate {
	var $ID;
	var $EmailID;
	var $EmailBannerID;
	var $EmailProductPoolID;
	var $ProductLines;
	var $Date;
	var $Subject;
	var $IsRandomised;
	var $Panel;
	var $PanelsFetched;

	function EmailDate($id=NULL) {
		$this->IsRandomised = 'Y';
		$this->Panel = array();
		$this->PanelsFetched = false;

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

		$data = new DataQuery(sprintf("SELECT * FROM email_date WHERE EmailDateID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->EmailID = $data->Row['EmailID'];
			$this->EmailBannerID = $data->Row['EmailBannerID'];
			$this->EmailProductPoolID = $data->Row['EmailProductPoolID'];
			$this->ProductLines = $data->Row['ProductLines'];
			$this->Date = $data->Row['Date'];
			$this->Subject = $data->Row['Subject'];
			$this->IsRandomised = $data->Row['IsRandomised'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetPanels() {
		$this->Panel = array();
		$this->PanelsFetched = true;

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT EmailPanelID FROM email_panel_assoc WHERE EmailDateID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Panel[] = new EmailPanel($data->Row['EmailPanelID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO email_date (EmailID, EmailBannerID, EmailProductPoolID, ProductLines, Date, Subject, IsRandomised) VALUES (%d, %d, %d, %d, '%s', '%s', '%s')", mysql_real_escape_string($this->EmailID), mysql_real_escape_string($this->EmailBannerID), mysql_real_escape_string($this->EmailProductPoolID), mysql_real_escape_string($this->ProductLines), mysql_real_escape_string($this->Date), mysql_real_escape_string($this->Subject), mysql_real_escape_string($this->IsRandomised)));

		$this->ID = $data->InsertID;
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE email_date SET EmailID=%d, EmailBannerID=%d, EmailProductPoolID=%d, ProductLines=%d, Date='%s', Subject='%s', IsRandomised='%s' WHERE EmailDateID=%d", mysql_real_escape_string($this->EmailID), mysql_real_escape_string($this->EmailBannerID), mysql_real_escape_string($this->EmailProductPoolID), mysql_real_escape_string($this->ProductLines), mysql_real_escape_string($this->Date), mysql_real_escape_string($this->Subject), mysql_real_escape_string($this->IsRandomised), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM email_date WHERE EmailDateID=%d", mysql_real_escape_string($this->ID)));
		EmailDateProduct::DeleteEmailDate($this->ID);
		EmailPanelAssoc::DeleteEmailDate($this->ID);
	}
}