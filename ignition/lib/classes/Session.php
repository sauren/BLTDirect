<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SessionItem.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserIPAccess.php');

class Session{
	var $AccessGrantedURL = 'ui/';
	var $AccessDeniedURL = 'login.php';
	var $ID;
	var $UserID;
	var $Country;
	var $User;
	var $WarehouseID;
	
	function Session(){
		$this->User = new User();
		
		session_name('sess_ignition');
		session_start();

		$this->ID = $this->GetID();

		if($GLOBALS['USE_SSL'] && ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT'])){
			$url = rtrim($GLOBALS['HTTPS_SERVER'], '/') . $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING'];

			redirect("Location: ". $url);
		}

		$access = array();

		$data = new DataQuery('SELECT IP_Access FROM users_ipaccess');
		while($data->Row) {
			$items = explode(',', $data->Row['IP_Access']);

			foreach($items as $item) {
				if(!empty($item)) {
					if(strpos($item, '-')) {
						$range = explode('-', $item);

						$array = array();
						$array[] = sprintf("%u", ip2long(trim($range[0])));
						$array[] = sprintf("%u", ip2long(trim($range[count($range)-1])));

						$access[] = $array;
					} else {
						$access[] = sprintf("%u", ip2long(trim($item)));
					}
				}
			}

			$data->Next();	
		}
		$data->Disconnect();

		$hasAccess = true;

		if(count($access) > 0) {
			$hasAccess = false;

			for($i=0; $i<count($access); $i++) {
				if(is_array($access[$i])) {
					if($access[$i][0] == $access[$i][1]) {
						if(sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) == $access[$i]) {
							$hasAccess = true;
							break;
						}
					} elseif($access[$i][0] < $access[$i][1]) {
						if((sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) >= $access[$i][0]) && (sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) <= $access[$i][1])) {
							$hasAccess = true;
							break;
						}
					} else {
						if((sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) <= $access[$i][0]) && (sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) >= $access[$i][1])) {
							$hasAccess = true;
							break;
						}
					}
				} else {
					if(sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])) == $access[$i]) {
						$hasAccess = true;
						break;
					}
				}
			}
		}

		if(!$hasAccess) {
			header("HTTP/1.1 404 Not Found");
			exit;
		}
	}

	function Login($username=null, $password=null){
		$loginQuery = new DataQuery(sprintf("SELECT User_ID, Country_ID, User_Password FROM users WHERE User_Name='%s' AND Is_Active='Y' AND IsLocked='N'", mysql_real_escape_string($username)));

		if($loginQuery->TotalRows > 0){
			if($loginQuery->Row['User_Password'] != md5($password)) {
				$user = new User($loginQuery->Row['User_ID']);
				$user->FailedLogins++;
				$user->Update();

				$loginQuery->Disconnect();
				$this->DenyAccess('denied');
				return false;
			}

			$ip = new UserIPAccess();
			$ip->GetByUserID($loginQuery->Row['User_ID']);

			$ipAccess = $ip->GetAccess();

			if(!empty($ipAccess)) {
				$ipAccess[] = sprintf('%u', ip2long('127.0.0.1'));
			}

			$ipRestrictions = $ip->GetRestrictions();

			$access = true;

			if($access) {
				$data = new DataQuery(sprintf("select count(*) Count
from registry_permissions as rp
join registry as r
join user_access ua on ua.accessId = rp.Access_ID
where r.Registry_ID=rp.Registry_ID and ua.userId = %d and r.Script_File LIKE 'login.php'", mysql_real_escape_string($loginQuery->Row['User_ID'])));
				if($data->Row['Count'] == 0) {
					$access = false;
				}
				
				$data->Disconnect();

				if(!$access) {
					$data = new DataQuery(sprintf("select count(*) Count
from user_registry as ur
inner join registry as r
where r.Registry_ID=ur.registryId and ur.userId=%d and r.Script_File LIKE 'login.php'", mysql_real_escape_string($loginQuery->Row['User_ID'])));
					if($data->Row['Count'] > 0) {
						$access = TRUE;
					}
					$data->Disconnect();
				}
			}

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
				$user = new User($loginQuery->Row['User_ID']);
				$user->FailedLogins = 0;
				$user->Update();

				$this->Country = new Country($loginQuery->Row['Country_ID']);
				$this->Create($loginQuery->Row['User_ID']);
				$loginQuery->Disconnect();
				$this->GrantAccess();
				return true;
			} else {
				$loginQuery->Disconnect();
				$this->DenyAccess('restricted');
				return false;
			}
		} else {
			$loginQuery->Disconnect();
			$this->DenyAccess('denied');
			return false;
		}
	}

	function Logout($type=NULL){
		$this->ID = $this->NewID();
		$this->DenyAccess($type);
	}

	function Create($userID){
		$this->ID = $this->NewID();

		new DataQuery(sprintf("INSERT INTO sessions (Session_ID, Created_On, User_ID, Last_Page, Referrer, Country_ID) VALUES ('%s', Now(), %d, '%s','%s', %d)", mysql_real_escape_string($this->ID), mysql_real_escape_string($userID), mysql_real_escape_string((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')), mysql_real_escape_string(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''), mysql_real_escape_string($this->Country->ID)));
	}

	function Record(){
		new DataQuery(sprintf("UPDATE sessions SET Last_Seen=NOW(), Last_Page='%s' WHERE Session_ID='%s'", mysql_real_escape_string((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')), mysql_real_escape_string($this->ID)));

		$item = new SessionItem();
		$item->SessionID = $this->ID;
		$item->PageRequest = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		$item->CreatedBy = $this->UserID;
		$item->ModifiedBy = $this->UserID;
		$item->Add();
	}

	function Secure($permission=3){
		if((strcasecmp(basename($_SERVER['PHP_SELF']), 'login.php') <> 0) && (strcasecmp(basename($_SERVER['PHP_SELF']), 'forgotten.php') <> 0)) {
			$foundNeedle = false;
			$permissionGranted = false;
			$needle = basename($_SERVER['SCRIPT_NAME']);

			$data = new DataQuery(sprintf("select rp.Access_ID, rp.Permission_ID, r.Script_File
	from registry_permissions as rp
	join registry as r
	join user_access ua on ua.accessId = rp.Access_ID
	where r.Registry_ID=rp.Registry_ID and ua.userId = %d and r.Script_File like '%s'", mysql_real_escape_string($this->UserID), mysql_real_escape_string($needle)));

			$foundNeedle = ($data->TotalRows > 0)?true:false;

			if(!$foundNeedle) {
				$data = new DataQuery(sprintf("select count(*) Count
		from user_registry as ur
		inner join registry as r
		where r.Registry_ID=ur.registryId and ur.userId=%d and r.Script_File LIKE '%s'", mysql_real_escape_string($this->UserID), mysql_real_escape_string($needle)));
				if($data->Row['Count'] > 0) {
					$foundNeedle = true;
				}
				$data->Disconnect();
			}

			if(!$foundNeedle){
				$this->DenyPermission();
				exit;
			}
		}
	}

	function GetID(){
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

	function ValidateID(){
		if((strcasecmp(basename($_SERVER['PHP_SELF']), 'login.php') <> 0) && (strcasecmp(basename($_SERVER['PHP_SELF']), 'forgotten.php') <> 0)) {
			
			$validSession = new DataQuery(sprintf("SELECT users.Country_ID, users.User_ID FROM sessions INNER JOIN users ON sessions.User_ID = users.User_ID WHERE sessions.Session_ID='%s' AND users.Is_Active='Y'",mysql_real_escape_string($this->ID)));
			$validSession->Disconnect();
			if($validSession->TotalRows == 0){
				$this->DenyAccess('invalid');
			} else {
				$this->UserID = $validSession->Row["User_ID"];
				$this->User->Get($validSession->Row["User_ID"]);
				$this->Country = new Country($validSession->Row["Country_ID"]);
				
				return true;
			}
		}

		return false;
	}

	function GrantAccess(){
		session_write_close();
		redirect(sprintf("Location: %s%s", $GLOBALS['IGNITION_ROOT'], $this->AccessGrantedURL));
	}

	function DenyAccess($type){
		$url = $this->AccessDeniedURL;
		if(isset($type)){
			$url .= "?log=" . $type;
		}
		session_write_close();
		redirect(sprintf("Location: %s%s", $GLOBALS['IGNITION_ROOT'], $url));
	}

	function DenyPermission(){
		$page = new Page("Permission Denied.","You have been denied permission to access this area. You should contact your administrator if you think you are seeing this message in error!");
		$page->Display('header');
		echo "<br>";
		echo '<input type="button" name="back" value="back" class="btn" onclick="window.history.go(-1);" /> ';
		$page->Display('footer');
		require_once('lib/common/app_footer.php');
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
	
	function GetWarehouseID() {
		if(empty($this->WarehouseID)) {
			$branchId = $this->User->Branch->ID;
			
			if($branchId == 0) {
				$data = new DataQuery("SELECT Branch_ID FROM branch WHERE Is_HQ='Y'");
				$branchId = $data->Row['Branch_ID'];
				$data->Disconnect();
			}

			$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse WHERE Type_Reference_ID=%d AND Type='B'", mysql_real_escape_string($branchId)));
			$this->WarehouseID = $data->Row['Warehouse_ID'];
			$data->Disconnect();
		}
		
		return $this->WarehouseID;
	}

	static function UpdateSupplierSession($SupplierId, $LastPage, $id){
		new DataQuery(sprintf("UPDATE sessions SET Supplier_ID=%d, Last_Seen=NOW(), Last_Page='%s' WHERE Session_ID='%s'", mysql_real_escape_string($SupplierId), mysql_real_escape_string($LastPage), mysql_real_escape_string($id)));
	}

	static function SupplierLogout($id){
			new DataQuery(sprintf("UPDATE sessions SET Supplier_ID=0 WHERE Session_ID='%s'", mysql_real_escape_string($id)));
	}

	static function UpdateUserSession($UserId, $LastPage, $id){
		new DataQuery(sprintf("UPDATE sessions SET User_ID=%d, Last_Seen=NOW(), Last_Page='%s' WHERE Session_ID='%s'", mysql_real_escape_string($UserId), mysql_real_escape_string($LastPage), mysql_real_escape_string($id)));
	}

	static function UserLogout($id){
		new DataQuery(sprintf("UPDATE sessions SET User_ID=0 WHERE Session_ID='%s'", mysql_real_escape_string($id)));
	}
}
