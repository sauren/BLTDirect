<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerSessionItemArchive.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserAgent.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');

class CustomerSessionArchive {
	var $ID;
	var $PHPSessionID;
	var $IsActive;
	var $Customer;
	var $CreatedOn;
	var $Referrer;
	var $ReferrerSearchTerm;
	var $Token;
	var $UserAgent;
	var $IPAddress;
	var $AffiliateID;
	var $Cookie;
	var $IsLoggedIn;

	function __construct() {
		$this->IsActive = 'Y';
		$this->Customer = new Customer();
		$this->UserAgent = new UserAgent();
		$this->IPAddress = '0.0.0.0';
		$this->IsLoggedIn = false;
	}

	function Get($phpSessionId = null) {
		if(!is_null($phpSessionId)) {
			$this->PHPSessionID = $phpSessionId;
		}

		$data = new DataQuery(sprintf("SELECT Session_ID, PHP_Session_ID, Is_Active, Customer_ID, Affiliate_ID, Referrer, Referrer_Search_Term, Token, INET_NTOA(IP_Address) AS IP_Address, User_Agent_ID, Created_On FROM customer_session_archive WHERE PHP_Session_ID='%s'", mysql_real_escape_string($this->PHPSessionID)));
		if($data->TotalRows > 0) {
			$this->ID = $data->Row['Session_ID'];
			$this->PHPSessionID = $data->Row['PHP_Session_ID'];
			$this->IsActive = $data->Row['Is_Active'];
			$this->Customer->ID = $data->Row['Customer_ID'];
			$this->AffiliateID = $data->Row['Affiliate_ID'];
			$this->Referrer = $data->Row['Referrer'];
			$this->ReferrerSearchTerm = $data->Row['Referrer_Search_Term'];
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

	function GetByID($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT PHP_Session_ID FROM customer_session_archive WHERE Session_ID='%s'", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['PHP_Session_ID']);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}
}