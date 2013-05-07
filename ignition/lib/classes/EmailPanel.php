<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailPanelAssoc.php');

class EmailPanel {
	var $ID;
	var $Name;
	var $FileName;
	var $Link;

	function EmailPanel($id=NULL) {
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

		$data = new DataQuery(sprintf("SELECT * FROM email_panel WHERE EmailPanelID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Name'];
			$this->FileName = $data->Row['FileName'];
			$this->Link = $data->Row['Link'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO email_panel (Name, FileName, Link) VALUES ('%s', '%s', '%s')", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->FileName), mysql_real_escape_string($this->Link)));

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE email_panel SET Name='%s', FileName='%s', Link='%s' WHERE EmailPanelID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->FileName), mysql_real_escape_string($this->Link), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM email_panel WHERE EmailPanelID=%d", mysql_real_escape_string($this->ID)));
		EmailPanelAssoc::DeleteEmailPanel($this->ID);
	}
}