<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerSessionArchive.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserAgent.php');

class CustomerSessionItemArchive {
	var $ID;
	var $Session;
	var $Customer;
	var $PageRequest;
	var $Token;
	var $IPAddress;
	var $UserAgent;
	var $CreatedOn;

	function __construct($id = null) {
		$this->Customer = new Customer();
		$this->Session = new CustomerSessionArchive();
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

		$data = new DataQuery(sprintf("SELECT Session_ID, Customer_ID, Page_Request, Token, INET_NTOA(IP_Address) AS IP_Address, User_Agent_ID, Created_On FROM customer_session_item_archive WHERE Session_Item_ID='%s'", mysql_real_escape_string($this->ID)));
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
}