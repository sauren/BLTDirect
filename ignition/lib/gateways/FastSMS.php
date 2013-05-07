<?php
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class SMSProcessor {
	var $API;
	var $Username;
	var $Password;
	var $Action;
	var $Data;
	var $Response;
	var $DestinationNumber;
	var $SourceNumber;
	var $Message;
	var $ScheduledDate;
	var $Error;
	
	function SMSProcessor() {
		$this->API = 'http://api2.fastsms.co.uk/api/api.php';
		$this->Username = $GLOBALS['FASTSMS_USERNAME'];
		$this->Password = $GLOBALS['FASTSMS_PASSWORD'];
		$this->Data = array();
		$this->Error = array();
	}
	
	function GetCredits() {
		$this->Action = 'CheckCredits';
		
		return $this->Execute();
	}
	
	function SendSMS() {
		$this->Action = 'Send';
		
		return $this->Execute();
	}
	
	function Execute() {
		$this->PrepareData();
		$this->RequestPost();
		
		return $this->FilterResponse();
	}
	
	function FilterResponse() {
		switch ($this->Response["Status"]) {
			case 'OK':
				return true;
				break;
			default:
				$this->Error[] = 'Sorry, an Error occured whilst contacting the SMS server.';
				$this->Error[] = $this->Response["StatusDetail"];
				
				return false;
				break;
		}
	}
	
	function FormatData() {
		$output = '';
		
		foreach ($this->Data as $key => $value) {
			$output .= sprintf("&%s=%s", $key, urlencode($value));
		}
		
		return substr($output, 1);
	}
	
	function RequestPost() {
		@set_time_limit(120);
		
		$data = $this->FormatData();
		$output = array();
		$curlSession = curl_init();

		curl_setopt($curlSession, CURLOPT_URL, $this->API);
		curl_setopt($curlSession, CURLOPT_HEADER, 0);
		curl_setopt($curlSession, CURLOPT_POST, 1);
		curl_setopt($curlSession, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlSession, CURLOPT_TIMEOUT, 90);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 1);
		
		$response = curl_exec($curlSession);
		$response = explode(',', $response);
		
		if (curl_error($curlSession)) {
			$output['Status'] = "FAIL";
			$output['StatusDetail'] = curl_error($curlSession);
		} else {
			if (is_numeric($response[0]) && ($response[0] < 0)) {
				$output['Status'] = "FAIL";
				
				switch ($response[0]) {
					case -100:
						$output['StatusDetail'] = 'Not Enough Credits';
						break;
					case -101:
						$output['StatusDetail'] = 'Invalid CreditID';
						break;
					case -200:
						$output['StatusDetail'] = 'Invalid Contact';
						break;
					case -300:
						$output['StatusDetail'] = 'General Database Error';
						break;
					case -400:
						$output['StatusDetail'] = 'Some numbers in list failed';
						break;
					case -401:
						$output['StatusDetail'] = 'Invalid Destination Address';
						break;
					case -402:
						$output['StatusDetail'] = 'Invalid Source Address - Alphanumeric too long';
						break;
					case -403:
						$output['StatusDetail'] = 'Invalid Source Address - Invalid Number';
						break;
					case -404:
						$output['StatusDetail'] = 'Blank Body';
						break;
					case -405:
						$output['StatusDetail'] = 'Invalid Validity Period';
						break;
					case -406:
						$output['StatusDetail'] = 'No Route Available';
						break;
					case -407:
						$output['StatusDetail'] = 'Invalid Schedule Date';
						break;
					case -408:
						$output['StatusDetail'] = 'Distribution List is Empty';
						break;
					case -409:
						$output['StatusDetail'] = 'Group is Empty';
						break;
					case -410:
						$output['StatusDetail'] = 'Invalid Distribution List';
						break;
					case -411:
						$output['StatusDetail'] = 'You have exceeded the limit of messages you can send in a single day to a single number';
						break;
					case -501:
						$output['StatusDetail'] = 'Unknown Username/Password';
						break;
					case -502:
						$output['StatusDetail'] = 'Unknown Action';
						break;
					case -503:
						$output['StatusDetail'] = 'Unknown Message ID';
						break;
					case -504:
						$output['StatusDetail'] = 'Invalid From Date';
						break;
					case -505:
						$output['StatusDetail'] = 'Invalid To Date';
						break;
					case -506:
						$output['StatusDetail'] = '[Internal Use]';
						break;
					case -507:
						$output['StatusDetail'] = 'Invalid/Missing Details';
						break;
					case -508:
						$output['StatusDetail'] = 'Error Creating User';
						break;
					case -509:
						$output['StatusDetail'] = 'Unknown/Invalid User';
						break;
					case -510:
						$output['StatusDetail'] = 'You cannot set a user\'s credits to be less than 0';
						break;
					case -511:
						$output['StatusDetail'] = 'The system is down for maintenance';
						break;
					case -601:
						$output['StatusDetail'] = 'Unknown Report Type';
						break;
				}
			} else {
				$output['Status'] = "OK";
				$output['Response'] = $response;
			}
		}
		
		curl_close($curlSession);
		
		$this->Response = $output;
		
		return $this->Response;
	}
	
	function PrepareData() {
		$this->Data['Username'] = $this->Username;
		$this->Data['Password'] = $this->Password;
		$this->Data['Action'] = $this->Action;
		
		switch ($this->Action) {
			case 'Send':
				$this->Data['DestinationAddress'] = str_replace(' ', '', $this->DestinationNumber);
				$this->Data['SourceAddress'] = str_replace(' ', '', $this->SourceNumber);
				$this->Data['Body'] = $this->Message;
				
				if (!empty($this->ScheduledDate)) {
					$this->Data['ScheduleDate'] = $this->ScheduledDate;
				}
				
				break;
		}
	}
}