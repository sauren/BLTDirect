<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class EmailQueueModule {
	var $ID;
	var $Module;
	var $IsPurgingActive;

	function EmailQueueModule($id=NULL){
		$this->IsPurgingActive = 'N';

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


		$data = new DataQuery(sprintf("SELECT * FROM email_queue_module WHERE Email_Queue_Module_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->Module = $data->Row['Module'];
			$this->IsPurgingActive = $data->Row['Is_Purging_Active'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO email_queue_module (Module, Is_Purging_Active) VALUES ('%s', '%s')", addslashes(stripslashes($this->Module)), mysql_real_escape_string($this->IsPurgingActive)));

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE email_queue_module SET Module='%s', Is_Purging_Active='%s' WHERE Email_Queue_Module_ID=%d", mysql_real_escape_string(stripslashes($this->Module)), mysql_real_escape_string($this->IsPurgingActive), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM email_queue_module WHERE Email_Queue_Module_ID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>