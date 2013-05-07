<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerSessionItem.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerSessionCookie.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserAgent.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
class CustomerSession {
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
		$this->Cookie = new CustomerSessionCookie();
		$this->Customer = new Customer();
		$this->UserAgent = new UserAgent();
		$this->IPAddress = '0.0.0.0';
		$this->IsLoggedIn = false;
	}

	function Start() {
		if(isset($_REQUEST['imodsid']) && !empty($_REQUEST['imodsid'])){
			$sessionId = base64_decode($_REQUEST['imodsid']);

			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM customer_session WHERE PHP_Session_ID='%s'", mysql_real_escape_string($sessionId)));
			if($data->Row['Count'] > 0){
				session_id($sessionId);
			}
			$data->Disconnect();
		}

		session_name('sess_public');
		if(checkPhpVersion('5.2.0')){
			session_set_cookie_params(0, '/', '', false, true);
		} else {
			session_set_cookie_params(0, '/', '', false);			
		}
		@session_start();

		$this->PHPSessionID = session_id();
		$this->IPAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
		$this->UserAgent->String = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

		if(!empty($this->UserAgent->String)) {
			if(!$this->UserAgent->GetByUserAgent()) {
				$this->UserAgent->Add();
			}
		}

		if($this->Get()){
			if(!$this->Validate()) {
				$this->Destroy();

				session_write_close();

				redirect(sprintf("Location: %sbreach.php", ($GLOBALS['USE_SSL']) ? $GLOBALS['HTTPS_SERVER'] : $GLOBALS['HTTP_SERVER'], $_SERVER['PHP_SELF']));
			} else {
				$this->IsLoggedIn = (empty($this->Customer->ID)) ? false : true;

				$this->Update();
				$this->AddItem();
			}
		} else {
			$this->Destroy();

			$this->PHPSessionID = session_id();
			$this->Referrer = $this->GetReferrer();

			$referrer = new Referrer($this->Referrer);

			$this->ReferrerSearchTerm = $referrer->SearchString;

			$this->Add();
			$this->AddItem();
		}

		if(isset($_REQUEST['auto'])) {
			$cypher = new Cipher($_REQUEST['auto']);
			$cypher->Decrypt();

			$loginData = unserialize($cypher->Value);

			$timeNow = time();
			$timeExpires = (isset($loginData[2]) && (strtotime($loginData[2]) !== false)) ? strtotime($loginData[2]) : $timeNow;

			if($timeExpires >= $timeNow) {
				$data = new DataQuery(sprintf('SELECT cu.Customer_ID FROM customer AS cu INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID WHERE cu.Is_Active=\'Y\' AND c.Contact_ID=%d AND c.Created_On=\'%s\'', mysql_real_escape_string($loginData[0]), mysql_real_escape_string($loginData[1])));
				if($data->TotalRows > 0){
					$this->Customer->ID = $data->Row['Customer_ID'];
					$this->Update();
				}
				$data->Disconnect();
			}

			$query = array();
			$queryParts = explode('&', parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY));

			foreach($queryParts as $part) {
				$partItems = explode('=', $part);

				if(strcasecmp($partItems[0], 'auto') <> 0) {
					$query[] = implode('=', $partItems);
				}
			}

			redirectTo(sprintf('%s%s', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), !empty($query) ? sprintf('?%s', implode('&', $query)) : ''));
		}
	}

	function Get($phpSessionId = null) {
		if(!is_null($phpSessionId)) {
			$this->PHPSessionID = $phpSessionId;
		}

		$data = new DataQuery(sprintf("SELECT Session_ID, PHP_Session_ID, Is_Active, Customer_ID, Affiliate_ID, Referrer, Referrer_Search_Term, Token, INET_NTOA(IP_Address) AS IP_Address, User_Agent_ID, Created_On FROM customer_session WHERE PHP_Session_ID='%s'", mysql_real_escape_string($this->PHPSessionID)));
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

		$data = new DataQuery(sprintf("SELECT PHP_Session_ID FROM customer_session WHERE Session_ID='%s'", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['PHP_Session_ID']);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	function GetReferrer() {
		$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		if(isset($_REQUEST['trace']) && !empty($_REQUEST['trace'])){
			$referrer = sprintf('%s : %s', urldecode($_REQUEST['trace']), $referrer);
		}

		return $referrer;
	}

	function GetAffiliate() {
		$affiliateId = 0;

		if(isset($_REQUEST['trace']) && !empty($_REQUEST['trace'])) {
			$affiliateStr = trim($_REQUEST['trace']);

			if((strlen($affiliateStr) >= 9) && (substr($affiliateStr, 0, 9) == 'affiliate')) {
				$affiliateStr = substr($affiliateStr, 9, strlen($affiliateStr));

				if(is_numeric($affiliateStr) && ($affiliateStr > 0)) {
					$affiliateId = $affiliateStr;
				}
			}
		}

		return $affiliateId;
	}

	function Add() {
		$this->Token = $this->GenerateToken();

		$this->Cookie->Add('Token', $this->Token);
		$this->Cookie->Set();

		$data = new DataQuery(sprintf("INSERT INTO customer_session (PHP_Session_ID, Is_Active, Customer_ID, Affiliate_ID, Referrer, Referrer_Search_Term, Token, IP_Address, User_Agent_ID, Created_On) VALUES ('%s', '%s', %d, %d, '%s', '%s', %d, INET_ATON('%s'), %d, NOW())", mysql_real_escape_string($this->PHPSessionID), mysql_real_escape_string($this->IsActive), mysql_real_escape_string($this->Customer->ID), mysql_real_escape_string($this->GetAffiliate()), mysql_real_escape_string($this->Referrer), mysql_real_escape_string($this->ReferrerSearchTerm), mysql_real_escape_string($this->Token), mysql_real_escape_string($this->IPAddress), mysql_real_escape_string($this->UserAgent->ID)));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function AddItem() {
		$sessionItem = new CustomerSessionItem();
		$sessionItem->Session->ID = $this->ID;
		$sessionItem->Customer->ID = $this->Customer->ID;
		$sessionItem->PageRequest = (isset($_SERVER['REQUEST_URI'])) ? truncate($_SERVER['REQUEST_URI'], 255, '') : '';
		$sessionItem->Token = $this->Token;
		$sessionItem->IPAddress = $this->IPAddress;
		$sessionItem->UserAgent->ID = $this->UserAgent->ID;
		$sessionItem->Add();
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("UPDATE customer_session SET Is_Active='%s', Customer_ID=%d, Token=%d, IP_Address=INET_ATON('%s'), User_Agent_ID=%d WHERE Session_ID='%s'", mysql_real_escape_string($this->IsActive), mysql_real_escape_string($this->Customer->ID), mysql_real_escape_string($this->Token), mysql_real_escape_string($this->IPAddress), mysql_real_escape_string($this->UserAgent->ID), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}
################################# Global Variable Added ##################################
	function Redirect() {
		session_write_close();
		$var=$_SERVER['PHP_SELF'];
		$var1=strpos($var, $GLOBALS['MOBILE_LINK']);
		$GLOBALS['HTTP_SERVER'];
		if($var1 != false){
		$var=str_replace($GLOBALS['MOBILE_LINK'],'',$var);
		$url=$url.$GLOBALS['MOBILE_LINK'];
			}
		redirect(sprintf("Location: gateway.php?direct=%s&imodsid=%s", ($GLOBALS['USE_SSL']) ? $GLOBALS['HTTPS_SERVER'] : $url, $var, base64_encode(session_id())));
		
	}
################################# Global Variable Added ##################################
	function Secure() {
		if(!empty($this->Customer->ID)) {
			/* if($this->Customer->IsPasswordOld()){
				$this->Customer->Redirect();
			} */ 

			if($GLOBALS['USE_SSL'] && ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT'])) {
				redirect(sprintf("Location: %s%s%s", ($GLOBALS['USE_SSL']) ? $GLOBALS['HTTPS_SERVER'] : $GLOBALS['HTTP_SERVER'], substr($_SERVER['PHP_SELF'], 1), !empty($_SERVER['QUERY_STRING']) ? sprintf('?%s', $_SERVER['QUERY_STRING']) : ''));
			}

			return true;
		}

		$this->Redirect();
	}

	function Login($username = null, $password = null) {

		   $data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE (Username='%s' OR (Username_Secondary='%s' AND Is_Secondary_Active='Y')) AND Password='%s' AND Is_Active='Y'", mysql_real_escape_string($username), mysql_real_escape_string($username), mysql_real_escape_string(sha1($password))));
               if($data->TotalRows > 0){
                       $this->Customer->ID = $data->Row['Customer_ID'];
                       $this->Update();

                       $this->IsLoggedIn = (empty($this->Customer->ID)) ? false : true;

                       $data->Disconnect();
                       return true;
               }

               $data->Disconnect();
               return false;
       }


	function Logout(){
		$this->Customer->ID = 0;
		$this->Update();

		session_write_close();

		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	function Destroy() {
		session_destroy();
		session_start();
		session_regenerate_id();
	}

	function Validate() {
		if($this->IsActive == 'N') {
			return false;
		}

		/*$tokenReceived = $this->Cookie->Get('Token');

		if($tokenReceived != $this->Token) {
			$data = new DataQuery(sprintf("SELECT * FROM customer_session_item WHERE Token=%d AND Created_On>ADDDATE(NOW(), INTERVAL -5 SECOND) AND Session_ID=%d", $tokenReceived, $this->ID));
			if($data->TotalRows == 0) {
				$this->IsActive = 'N';
				$this->Update();

				$data->Disconnect();
				return false;
			}
			$data->Disconnect();
		}*/

		$this->Token = $this->GenerateToken();

		$this->Cookie->Add('Token', $this->Token);
		$this->Cookie->Set();

		return true;
	}

	function GenerateToken(){
		return rand(0, 99999999999);
	}
}
