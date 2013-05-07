<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class EmailQueueAttachment {
	var $ID;
	var $EmailQueueID;
	var $FilePath;
	var $WebPath;

	function EmailQueueAttachment($id=NULL){
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

		$data = new DataQuery(sprintf("SELECT * FROM email_queue_attachment WHERE Email_Queue_Attachment_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->EmailQueueID = $data->Row['Email_Queue_ID'];
			$this->FilePath = $data->Row['File_Path'];
			$this->WebPath = $data->Row['Web_Path'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO email_queue_attachment (Email_Queue_ID, File_Path, Web_Path) VALUES (%d, '%s', '%s')", mysql_real_escape_string($this->EmailQueueID), mysql_real_escape_string(stripslashes($this->FilePath)), mysql_real_escape_string(stripslashes($this->WebPath))));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE email_queue_attachment SET Email_Queue_ID=%d, File_Path='%s', Web_Path='%s' WHERE Email_Queue_Attachment_ID=%d", mysql_real_escape_string($this->EmailQueueID), mysql_real_escape_string(stripslashes($this->FilePath)), mysql_real_escape_string(stripslashes($this->WebPath)), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM email_queue_attachment WHERE Email_Queue_Attachment_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	static function DeleteEmailQueue($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM email_queue_attachment WHERE Email_Queue_ID=%d", mysql_real_escape_string($id)));
	}
}
?>