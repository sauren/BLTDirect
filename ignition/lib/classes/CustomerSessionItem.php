<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerSession.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserAgent.php');

class CustomerSessionItem {
	var $ID;
	var $Session;
	var $Customer;
	var $PageRequest;
	var $Token;
	var $IPAddress;
	var $UserAgent;
	var $CreatedOn;

	function CustomerSessionItem($id = null) {
		$this->Customer = new Customer();
		$this->Session = new CustomerSession();
		$this->UserAgent = new UserAgent();
		$this->IPAddress = '0.0.0.0';
		
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
		
		$data = new DataQuery(sprintf("SELECT Session_ID, Customer_ID, Page_Request, Token, INET_NTOA(IP_Address) AS IP_Address, User_Agent_ID, Created_On FROM customer_session_item WHERE Session_Item_ID='%s'", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->Session->ID = $data->Row['Session_ID'];
			$this->Customer->ID = $data->Row['Customer_ID'];
			$this->PageRequest = $data->Row['Page_Request'];
			$this->Token = $data->Row['Token'];
			$this->IPAddress = $data->Row['IP_Address'];
			$this->UserAgent->ID = $data->Row['User_Agent_ID'];
			$this->CreatedOn = $data->Row['Created_On'];

			$data->Disconnect();
			return true;
		}
		
		$data->Disconnect();
		return false;
	}
	
	function Add() {	
		$data = new DataQuery(sprintf("INSERT INTO customer_session_item (Session_ID, Customer_ID, Page_Request, Token, IP_Address, User_Agent_ID, Created_On) VALUES (%d, %d, '%s', %d, INET_ATON('%s'), %d, NOW())", mysql_real_escape_string($this->Session->ID), mysql_real_escape_string($this->Customer->ID), mysql_real_escape_string($this->PageRequest), mysql_real_escape_string($this->Token), mysql_real_escape_string($this->IPAddress), mysql_real_escape_string($this->UserAgent->ID)));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}
}
?>