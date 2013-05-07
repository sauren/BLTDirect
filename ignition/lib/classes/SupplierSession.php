<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Session.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SessionItem.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierIPAccess.php');

class SupplierSession {
	var $ID;
	var $Supplier;
	var $LastSeen;
	var $Created;
	var $Referrer;
	var $LastPage;
	var $IsLoggedIn;
	var $PortalName;
	var $PortalUrl;
	var $WarehouseID;

	function __construct($portalName, $portalUrl) {
		$this->PortalName = $portalName;
		$this->PortalUrl = $portalUrl;
		
		$this->Supplier = new Supplier();
	}

	function Start() {
		if($GLOBALS['USE_SSL'] && ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT'])) {
			redirect(sprintf("Location: %s%s%s", rtrim($GLOBALS['HTTPS_SERVER'], '/'), $_SERVER['PHP_SELF'], (strlen($_SERVER['QUERY_STRING']) > 0) ? sprintf('?%s', $_SERVER['QUERY_STRING']) : ''));
		}
		
		if(isset($_REQUEST['imodsid']) && !empty($_REQUEST['imodsid'])){
			$parsedId = base64_decode($_REQUEST['imodsid']);
			$sid = $this->GetID($parsedId);

			$check = new DataQuery(sprintf("SELECT * FROM sessions WHERE Session_ID='%s'", mysql_real_escape_string($sid)));
			if($check->TotalRows > 0){
				session_id($parsedId);
			}
			$check->Disconnect();
		}

		session_name('sess_' . strtolower($this->PortalName));
		session_start();

		$this->ID = $this->GetID();
		$this->Record();
	}

	function Secure() {
		if(!empty($this->Supplier->ID)){
			return true;
		} else {
			$this->Redirect();
		}
	}

	function Redirect(){
		redirect(sprintf("Location: %s%slogin.php?direct=%s&imodsid=%s", ($GLOBALS['USE_SSL']) ? $GLOBALS['HTTPS_SERVER'] : $GLOBALS['HTTP_SERVER'], $this->PortalUrl, $_SERVER['PHP_SELF'], base64_encode(session_id())));
	}

	function Record(){
		if($this->Get()){
			$this->LastPage = truncate((isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '', 255, '');
			$this->Update();
		} else {
			$this->Create();
		}

		$item = new SessionItem();
		$item->SessionID = $this->ID;
		$item->PageRequest = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		$item->CreatedBy = $this->Supplier->ID;
		$item->ModifiedBy = $this->Supplier->ID;
		$item->Add();
	}

	function Create(){
		$this->ID = $this->NewID();

		new DataQuery(sprintf("INSERT INTO sessions (Session_ID, Created_On, Supplier_ID, Last_Page, Referrer) VALUES ('%s', NOW(), %d, '%s', '%s')", mysql_real_escape_string($this->ID), mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''), mysql_real_escape_string(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '')));
	}

	function Update(){
		Session::UpdateSupplierSession($this->Supplier->ID, $this->LastPage, $this->ID);
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM sessions WHERE Session_ID='%s'", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->Supplier->ID = $data->Row['Supplier_ID'];
			$this->LastSeen = $data->Row['Last_Seen'];
			$this->Created = $data->Row['Created_On'];
			$this->Referrer = $data->Row['Referrer'];
			$this->LastPage = $data->Row['Last_Page'];
			$this->IsLoggedIn = (empty($this->Supplier->ID)) ? false : true;

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Login($username = null, $password = null){
		$pass = new Cipher($password);
		$pass->Encrypt();

		$data = new DataQuery(sprintf("SELECT Supplier_ID FROM supplier WHERE Username LIKE '%s' AND Password='%s' AND Is_Active='Y'", mysql_real_escape_string($username), mysql_real_escape_string($pass->Value)));
		if($data->TotalRows > 0) {
			$ip = new SupplierIPAccess();
			$ip->GetBySupplierID($data->Row['Supplier_ID']);

			$ipAccess = $ip->GetAccess();

			if(!empty($ipAccess)) {
				$ipAccess[] = sprintf('%u', ip2long('127.0.0.1'));
			}

			$ipRestrictions = $ip->GetRestrictions();

			$access = true;

			if($access) {
				$ipRestrictionsPass = true;

				if(count($ipRestrictions) > 0) {
					for($i = 0; $i < count($ipRestrictions); $i++) {
						if(is_array($ipRestrictions[$i])) {
							if($ipRestrictions[$i][0] == $ipRestrictions[$i][1]) {
								if(sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) == $ipRestrictions[$i]) {
									$ipRestrictionsPass = false;
									break;
								}
							} elseif($ipRestrictions[$i][0] < $ipRestrictions[$i][1]) {
								if((sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) >= $ipRestrictions[$i][0]) && (sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) <= $ipRestrictions[$i][1])) {
									$ipRestrictionsPass = false;
									break;
								}
							} else {
								if((sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) <= $ipRestrictions[$i][0]) && (sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) >= $ipRestrictions[$i][1])) {
									$ipRestrictionsPass = false;
									break;
								}
							}
						} else {
							if(sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) == $ipRestrictions[$i]) {
								$ipRestrictionsPass = false;
								break;
							}
						}
					}
				}

				if(!$ipRestrictionsPass) {
					$access = false;
				}
			}

			if($access) {
				$ipAccessPass = true;

				if(count($ipAccess) > 0) {
					$ipAccessPass = false;

					for($i = 0; $i < count($ipAccess); $i++) {
						if(is_array($ipAccess[$i])) {
							if($ipAccess[$i][0] == $ipAccess[$i][1]) {
								if(sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) == $ipAccess[$i]) {
									$ipAccessPass = true;
									break;
								}
							} elseif($ipAccess[$i][0] < $ipAccess[$i][1]) {
								if((sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) >= $ipAccess[$i][0]) && (sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) <= $ipAccess[$i][1])) {
									$ipAccessPass = true;
									break;
								}
							} else {
								if((sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) <= $ipAccess[$i][0]) && (sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) >= $ipAccess[$i][1])) {
									$ipAccessPass = true;
									break;
								}
							}
						} else {
							if(sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) == $ipAccess[$i]) {
								$ipAccessPass = true;
								break;
							}
						}
					}
				}

				if(!$ipAccessPass) {
					$access = false;
				}
			}

			if($access) {
				$this->Supplier->ID = $data->Row['Supplier_ID'];
				$this->Update();
				
				return true;
			}
		}

		return false;
	}

	function Logout() {
		Session::SupplierLogout($this->ID);
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	function GetID($id=NULL){
		return session_id();
	}

	function NewID(){
		session_destroy();
		session_start();

		if(function_exists('session_regenerate_id')){
			session_regenerate_id();
		} else {
			$this->Regenerate();
		}
		$this->ID = $this->GetID();

		return $this->ID;
	}

	function Regenerate(){
		$tv = gettimeofday();
		$buf = sprintf("%.15s%ld%ld%0.8f", $_SERVER['REMOTE_ADDR'], $tv['sec'], $tv['usec'], $this->Combined_LCG() * 10);

		session_id(md5($buf));

		if (ini_get('session.use_cookies')) {
			if(checkPhpVersion('5.2.0')){
				setcookie('PHPSESSID', session_id(), NULL, '/', '', isset($_SERVER["HTTPS"]), true);
			} else {
				setcookie('PHPSESSID', session_id(), NULL, '/', '', isset($_SERVER["HTTPS"]));
			}
		}

		return true;
	}

	function Combined_LCG() {
		$tv = gettimeofday();
		$lcg['s1'] = $tv['sec'] ^ (~$tv['usec']);
		$lcg['s2'] = posix_getpid();

		$q = (int) ($lcg['s1'] / 53668);
		$lcg['s1'] = (int) (40014 * ($lcg['s1'] - 53668 * $q) - 12211 * $q);
		if ($lcg['s1'] < 0)
		$lcg['s1'] += 2147483563;

		$q = (int) ($lcg['s2'] / 52774);
		$lcg['s2'] = (int) (40692 * ($lcg['s2'] - 52774 * $q) - 3791 * $q);
		if ($lcg['s2'] < 0)
		$lcg['s2'] += 2147483399;

		$z = (int) ($lcg['s1'] - $lcg['s2']);
		if ($z < 1) {
			$z += 2147483562;
		}

		return $z * 4.656613e-10;
	}
}
