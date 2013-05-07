<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class EmailPanelAssoc {
	var $ID;
	var $EmailDateID;
	var $EmailPanelID;

	function EmailPanelAssoc($id=NULL) {
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

		$data = new DataQuery(sprintf("SELECT * FROM email_panel_assoc WHERE EmailPanelAssocID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->EmailDateID = $data->Row['EmailDateID'];
			$this->EmailPanelID = $data->Row['EmailPanelID'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO email_panel_assoc (EmailDateID, EmailPanelID) VALUES (%d, %d)", mysql_real_escape_string($this->EmailDateID), mysql_real_escape_string($this->EmailPanelID)));

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE email_panel_assoc SET EmailDateID=%d, EmailPanelID=%d WHERE EmailPanelAssocID=%d", mysql_real_escape_string($this->EmailDateID), mysql_real_escape_string($this->EmailPanelID), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM email_panel_assoc WHERE EmailPanelAssocID=%d", mysql_real_escape_string($this->ID)));
	}

	static function DeleteEmailDate($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM email_panel_assoc WHERE EmailDateID=%d", mysql_real_escape_string($id)));
	}

	static function DeleteEmailPanel($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM email_panel_assoc WHERE EmailPanelID=%d", mysql_real_escape_string($id)));
	}
}
?>