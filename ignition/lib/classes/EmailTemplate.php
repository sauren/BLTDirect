<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Email.php');

class EmailTemplate {
	var $ID;
	var $Name;
	var $Template;

	function EmailTemplate($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM email_template WHERE EmailTemplateID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Name'];
			$this->Template = $data->Row['Template'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO email_template (Name, Template) VALUES ('%s', '%s')", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Template)));

		$this->ID = $data->InsertID;
	}

	function Update(){
		new DataQuery(sprintf("UPDATE email_template SET Name='%s', Template='%s' WHERE EmailTemplateID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Template), $this->ID));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		new DataQuery(sprintf("DELETE FROM email_template WHERE EmailTemplateID=%d", $this->ID));
		Email::EmailTemplate($this->ID);
	}
}