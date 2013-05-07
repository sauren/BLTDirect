<?php
require_once('lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/common/constants.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Session.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Page.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/User.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/UserRecent.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Country.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/GlobalTaxCalculator.php');

$session = new Session();
$session->ValidateID();
$session->Record();

if(stristr($_SERVER['PHP_SELF'], 'user_password.php') === false) {
	$session->Secure(2);
}

$GLOBALS['SESSION_USER_ID'] = $session->UserID;
$GLOBALS['SESSION_ID'] = $session->GetID();

$systemCountry = new Country($GLOBALS['SYSTEM_COUNTRY']);

$action = isset($_REQUEST['action']) ? strtolower($_REQUEST['action']) : '';

if(!isset($_SESSION['BypassWorkTasks'])) {
	$_SESSION['BypassWorkTasks'] = false;
}

if($action == 'logout') {
	$session->Logout();
	
} elseif($action == 'bypass') {
	if($session->UserID > 0) {
		$user = new User($session->UserID);
	
		if($user->CanBypassWorkTasks == 'Y') {
			$_SESSION['BypassWorkTasks'] = true;
		}
	}
}

if($session->UserID > 0) {
	if($session->User->IsPasswordOld()) {
		if(stristr($_SERVER['PHP_SELF'], 'user_password.php') === false) {
			redirect(sprintf('Location: user_password.php'));
		}
	}
}

if(!$_SESSION['BypassWorkTasks']) {
	if(stristr($_SERVER['PHP_SELF'], 'work_task_schedules.php') === false && stristr($_SERVER['PHP_SELF'], 'user_password.php') === false) {
		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM work_task_schedule WHERE userId=%d AND isComplete='N' AND scheduledOn<NOW()", mysql_real_escape_string($session->UserID)));
		if($data->Row['Count'] > 0) {
			redirect(sprintf('Location: work_task_schedules.php'));
		}
		$data->Disconnect();
	}
}

global $globalTaxCalculator;

$globalTaxCalculator = new GlobalTaxCalculator($GLOBALS['SYSTEM_COUNTRY'], $GLOBALS['SYSTEM_REGION']);

if(!isset($ignoreHeader) || !$ignoreHeader){
	header("Content-Type: text/html; charset=UTF-8");
}