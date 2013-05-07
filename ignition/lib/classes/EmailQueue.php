<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/EmailQueueAttachment.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

class EmailQueue {
	var $ID;
	var $IsSent;
	var $Priority;
	var $Type;
	var $Receipt;
	var $ToAddress;
	var $FromAddress;
	var $Subject;
	var $Body;
	var $ReturnPath;
	var $SendAfter;
	var $SentOn;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $IsBcc;
	var $ModuleID;
	var $Attachments;

	public function __construct($id=NULL){
		$this->IsSent = 'N';
		$this->Priority = 'N';
		$this->Type = 'H';
		$this->Receipt = 'N';
		$this->IsBcc = 'N';
		$this->Attachments = array();
		$this->ReturnPath = $GLOBALS['EMAIL_RETURN'];
		$this->FromAddress = $GLOBALS['EMAIL_FROM'];
		$this->SendAfter = '0000-00-00 00:00:00';
		$this->SentOn = '0000-00-00 00:00:00';

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

		$data = new DataQuery(sprintf("SELECT * FROM email_queue WHERE Email_Queue_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->ModuleID = $data->Row['Module_ID'];
			$this->IsSent = $data->Row['Is_Sent'];
			$this->IsBcc = $data->Row['Is_Bcc'];
			$this->Priority = $data->Row['Priority'];
			$this->Type = $data->Row['Type'];
			$this->Receipt = $data->Row['Receipt'];
			$this->ToAddress = $data->Row['To_Address'];
			$this->FromAddress = $data->Row['From_Address'];
			$this->Subject = $data->Row['Subject'];
			$this->Body = $data->Row['Body'];
			$this->ReturnPath = $data->Row['Return_Path'];
			$this->SendAfter = $data->Row['Send_After'];
			$this->SentOn = $data->Row['Sent_On'];
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

	public function GetModuleID($module = '') {
		$data = new DataQuery(sprintf("SELECT Email_Queue_Module_ID FROM email_queue_module WHERE Reference LIKE '%s' LIMIT 0, 1", mysql_real_escape_string($module)));
		$this->ModuleID = ($data->TotalRows > 0) ? $data->Row['Email_Queue_Module_ID'] : 0;
		$data->Disconnect();
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO email_queue (Module_ID, Is_Sent, Priority, Type, Receipt, To_Address, From_Address, Subject, Body, Return_Path, Send_After, Is_Bcc, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->ModuleID), mysql_real_escape_string($this->IsSent), mysql_real_escape_string($this->Priority), mysql_real_escape_string($this->Type), mysql_real_escape_string($this->Receipt), mysql_real_escape_string(stripslashes($this->ToAddress)), mysql_real_escape_string(stripslashes($this->FromAddress)), mysql_real_escape_string(stripslashes($this->Subject)), mysql_real_escape_string(stripslashes($this->Body)), mysql_real_escape_string(stripslashes($this->ReturnPath)), mysql_real_escape_string($this->SendAfter), mysql_real_escape_string($this->IsBcc), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;

		$cypher = new Cipher($this->ID);
		$cypher->Encrypt();

		$findReplace = new FindReplace();
		$findReplace->Add('/\[QUEUEID\]/', $this->ID);
		$findReplace->Add('/\[QUEUEREFERENCE\]/', base64_encode($cypher->Value));

		$this->Body = $findReplace->Execute($this->Body);
		$this->Update();
	}

	function Update(){
		new DataQuery(
			sprintf(<<<SQL
UPDATE email_queue
SET
	Module_ID=%d,
	Is_Sent='%s',
	Priority='%s',
	Type='%s',
	Receipt='%s',
	To_Address='%s',
	From_Address='%s',
	Subject='%s',
	Body='%s',
	Return_Path='%s',
	Send_After='%s',
	Sent_On='%s',
	Is_Bcc='%s',
	Modified_On=NOW(),
	Modified_By=%d
WHERE Email_Queue_ID=%d
SQL
		,
			mysql_real_escape_string($this->ModuleID),
			mysql_real_escape_string($this->IsSent),
			mysql_real_escape_string($this->Priority),
			mysql_real_escape_string($this->Type),
			mysql_real_escape_string($this->Receipt),
			mysql_real_escape_string(stripslashes($this->ToAddress)),
			mysql_real_escape_string(stripslashes($this->FromAddress)),
			mysql_real_escape_string(stripslashes($this->Subject)),
			addslashes(stripslashes($this->Body)),
			mysql_real_escape_string(stripslashes($this->ReturnPath)),
			mysql_real_escape_string($this->SendAfter),
			mysql_real_escape_string($this->SentOn),
			mysql_real_escape_string($this->IsBcc),
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
			mysql_real_escape_string($this->ID)
			)
		);
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM email_queue WHERE Email_Queue_ID=%d", mysql_real_escape_string($this->ID)));
		EmailQueueAttachment::DeleteEmailQueue($this->ID);
	}

	function SetSent($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("UPDATE email_queue SET Is_Sent='Y', Sent_On=NOW() WHERE Email_Queue_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function AddAttachment($filePath = '', $webPath = '') {
		if(!empty($filePath) && !empty($webPath)) {
			$attachment = new EmailQueueAttachment();
			$attachment->FilePath = $filePath;
			$attachment->WebPath = $webPath;
			$attachment->EmailQueueID = $this->ID;
			$attachment->Add();

			$this->Attachments[] = $attachment;
		}
	}

	function GetAttachments() {
		$this->Attachments = array();

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Email_Queue_Attachment_ID FROM email_queue_attachment WHERE Email_Queue_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Attachments[] = new EmailQueueAttachment($data->Row['Email_Queue_Attachment_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function SetSendAfter($seconds) {
		if($seconds > 0) {
			$this->SendAfter = date('Y-m-d H:i:s', strtotime(sprintf('+%d seconds', $seconds)));
		}
	}
}