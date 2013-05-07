<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class BlacklistUserAgent {
	var $ID;
	var $UserAgent;
	var $Reason;
	var $CreatedBy;
	var $CreatedOn;
	var $ModifiedBy;
	var $ModifiedOn;

	function BlacklistUserAgent($id = null) {		
		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	function Get($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}


		
		$data = new DataQuery(sprintf("SELECT * FROM blacklist_user_agent WHERE Blacklist_User_Agent_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->UserAgent = $data->Row['User_Agent'];
			$this->Reason = $data->Row['Reason'];
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
		$data = new DataQuery(sprintf("INSERT INTO blacklist_user_agent (User_Agent, Reason, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->UserAgent), mysql_real_escape_string($this->Reason), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}
	
	function Update() {	
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE blacklist_user_agent SET User_Agent='%s', Reason='%s', Modified_On=NOW(), Modified_By=%d WHERE Blacklist_User_Agent_ID=%d", mysql_real_escape_string($this->UserAgent), mysql_real_escape_string($this->Reason), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}
	
	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("DELETE FROM blacklist_user_agent WHERE Blacklist_User_Agent_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}
	
	function Validate() {
		$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		
		if(!empty($userAgent)) {
			$data = new DataQuery(sprintf("SELECT User_Agent, Reason FROM blacklist_user_agent"));
			if($data->TotalRows > 0) {
				$reasons = array();
				
				while($data->Row) {
					if((trim($data->Row['User_Agent']) == trim($userAgent)) || (preg_match(sprintf('/%s/', $data->Row['User_Agent']), $userAgent))) {
						$reasons[] = $data->Row['Reason'];
					}
					
					$data->Next();
				}
				
				if(count($reasons) > 0) {				
					echo sprintf('<p><strong>Blacklisted User Agent:</strong> %s<br />Please call %s on %s.</p>', $userAgent, $GLOBALS['COMPANY'], $GLOBALS['COMPANY_PHONE']);
					echo sprintf('<p><u>User Agent blacklisted for the following reasons:</u></p>');
								
					foreach($reasons as $reason) {
						echo sprintf('<p>%s</p>', nl2br($reason));
						
						$data->Next();
					}
								
					$data->Disconnect();
	
					$GLOBALS['DBCONNECTION']->Close();
					exit;
				}
			}
			$data->Disconnect();
		}
	}
	
	function IsBlacklisted($userAgent) {
		$data = new DataQuery(sprintf("SELECT User_Agent, Reason FROM blacklist_user_agent"));
		while($data->Row) {
			if((trim($data->Row['User_Agent']) == trim($userAgent)) || (preg_match(sprintf('/%s/', $data->Row['User_Agent']), $userAgent))) {
				$data->Disconnect();
				return true;
			}
			
			$data->Next();
		}

		$data->Disconnect();
		return false;
	}
}
?>