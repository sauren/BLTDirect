<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierIPAccess.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserIPAccess.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');

class WarehouseSession{
	var $ID;
	var $Warehouse;
	var $LastSeen;
	var $Created;
	var $FirstPage;
	var $Referrer;
	var $LastPage;
	var $IsLoggedIn;

	function WarehouseSession(){
		$this->Warehouse= new Warehouse();
	}

	function Start(){
		if(isset($_REQUEST['imodsid']) && !empty($_REQUEST['imodsid'])){
			$parsedId = base64_decode($_REQUEST['imodsid']);
			$sid = $this->GetID($parsedId);
			
			$check = new DataQuery(sprintf("select * from warehouse_session where WSID='%s'", mysql_real_escape_string($sid)));

			if($check->TotalRows > 0){
				session_id($parsedId);
			}
			$check->Disconnect();
		}
		
		session_name('sess_warehouse_old');
		session_start();
		
		$this->ID = $this->GetID();
		$this->Record();
	}

	function Secure(){
		if(!empty($this->Warehouse->ID)){
			return true;
		} else {
			$this->Redirect();
			return false;
		}
	}

	function Redirect(){
		$url = ($GLOBALS['USE_SSL'])?$GLOBALS['HTTPS_SERVER']:$GLOBALS['HTTP_SERVER'];
		redirect(sprintf("Location: %swarehouse/gateway.php?direct=%s&imodsid=%s", $url, $_SERVER['PHP_SELF'], base64_encode(session_id())));
	}

	function Record(){
		if($this->Get()){
			$requested = (isset($_SERVER['REQUEST_URI']))? $_SERVER['REQUEST_URI'] : "Unsupported";
			$this->LastPage = truncate($requested, 255, '');
			$this->Update();
		} else {
			$this->Create();
		}
	}

	function Create(){
		$requested = (isset($_SERVER['REQUEST_URI']))? $_SERVER['REQUEST_URI'] : "Unsupported";
		$createSession = new DataQuery(sprintf("INSERT INTO warehouse_session (
													WSID,
													Referrer,
													Created_On,
													Last_Seen,
													First_Page,
													Last_Page,
													Warehouse_ID) VALUES ('%s', '%s', Now(), Now(), '%s', '%s', NULL)",
		mysql_real_escape_string($this->ID),
		mysql_real_escape_string(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),
		mysql_real_escape_string($requested),
		mysql_real_escape_string($requested)));
	}

	function Update(){
		$update = new DataQuery(sprintf("update warehouse_session set
									Warehouse_ID=%d,
									Last_Seen=Now(),
									Last_Page='%s'
									where WSID='%s'",
		mysql_real_escape_string($this->Warehouse->ID),
		mysql_real_escape_string($this->LastPage),
		mysql_real_escape_string($this->ID)));
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;

		$check = new DataQuery(sprintf("select * from warehouse_session where WSID='%s'", mysql_real_escape_string($this->ID)));
		if($check->TotalRows > 0){
			$this->Warehouse->ID = $check->Row['Warehouse_ID'];
			$this->LastSeen = $check->Row['Last_Seen'];
			$this->Created = $check->Row['Created_On'];
			$this->FirstPage = $check->Row['First_Page'];
			$this->Referrer = $check->Row['Referrer'];
			$this->LastPage = $check->Row['Last_Page'];

			$this->IsLoggedIn = (empty($this->Warehouse->ID))?false:true;
			$check->Disconnect();
			return true;
		} else {
			$check->Disconnect();
			return false;
		}
	}

	function checkForBranch($uname, $pass) {
		$data = new DataQuery(sprintf("SELECT User_ID, Branch_ID FROM users WHERE User_Name='%s' AND User_Password='%s'", mysql_real_escape_string($uname), mysql_real_escape_string(md5($pass))));
		if($data->TotalRows > 0){
			$ip = new UserIPAccess();
			$ip->GetByUserID($data->Row['User_ID']);

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
				return $data->Row['Branch_ID'];
			}
		}
		$data->Disconnect();

		return false;
	}

	function checkForSupplier($uname, $pass) {
		$cipher = new Cipher($pass);
		$cipher->Encrypt();

		$data = new DataQuery(sprintf("SELECT Supplier_ID FROM supplier WHERE Username='%s' AND Password='%s'", mysql_real_escape_string($uname), mysql_real_escape_string($cipher->Value)));
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
				return $data->Row['Supplier_ID'];
			}
		}
		$data->Disconnect();

		return false;
	}

	function Login($username = null, $password = null) {
		$branchID = $this->checkForBranch($username,$password);
		$SupplierID = $this->checkForSupplier($username,$password);

		if($branchID != false) {
			$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse WHERE Type='B' AND Type_Reference_ID=%d", mysql_real_escape_string($branchID)));

			$this->Warehouse->ID = $data->Row['Warehouse_ID'];
			$this->Update();
			$data->Disconnect();

			return true;

		} elseif($SupplierID != false) {
			$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse WHERE Type='S' AND Type_Reference_ID=%d", mysql_real_escape_string($SupplierID)));

			$this->Warehouse->ID = $data->Row['Warehouse_ID'];
			$this->Update();
			$data->Disconnect();

			return true;
		}

		return false;
	}

	function Logout(){
		new DataQuery(sprintf("update warehouse_session set Warehouse_ID=0 where WSID='%s'", mysql_real_escape_string($this->ID)));

		redirect("Location: " . $_SERVER['PHP_SELF']);
	}

	function GetID($id=NULL){
		if(!is_null($id)){
			return md5($id . trim($_SERVER['HTTP_USER_AGENT']));
		} else {
			return md5(session_id() . trim($_SERVER['HTTP_USER_AGENT']));
		}
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
		if (ini_get('session.use_cookies'))
		if(checkPhpVersion('5.2.0')){
			setcookie('PHPSESSID', session_id(), NULL, '/', '', isset($_SERVER["HTTPS"]), true);
		} else {
			setcookie('PHPSESSID', session_id(), NULL, '/', '', isset($_SERVER["HTTPS"]));
		}
		return TRUE;
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
