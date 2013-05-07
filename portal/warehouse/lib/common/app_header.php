<?php
require_once('../../ignition/lib/classes/ApplicationHeader.php');
require_once('config.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/common/constants.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/UserSession.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/GlobalTaxCalculator.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Page.php');

$session = new UserSession($GLOBALS['PORTAL_NAME'], $GLOBALS['PORTAL_URL']);
$session->Start();

$GLOBALS['SESSION_USER_ID'] = $session->User->ID;

$action = isset($_REQUEST['action']) ? strtolower($_REQUEST['action']) : '';

if(!isset($_SESSION['BypassWorkTasks'])) {
	$_SESSION['BypassWorkTasks'] = false;
}

if($action == 'logout') {
	$session->Logout();
	
} elseif($action == 'bypass') {
	if($session->User->ID > 0) {
		$user = new User($session->User->ID);
	
		if($user->CanBypassWorkTasks == 'Y') {
			$_SESSION['BypassWorkTasks'] = true;
		}
	}
}

if(!empty($session->User->ID)){
	$session->User->Get();
}

if($session->User->ID > 0) {
	if($session->User->IsPasswordOld()) {
		if(stristr($_SERVER['PHP_SELF'], 'user_password.php') === false) {
			redirect(sprintf('Location: user_password.php'));
		}
	}
}

if(!$_SESSION['BypassWorkTasks']) {
	if(stristr($_SERVER['PHP_SELF'], 'work_task_schedules.php') === false) {
		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM work_task_schedule WHERE userId=%d AND isComplete='N' AND scheduledOn<NOW()", mysql_real_escape_string($session->User->ID)));
		if($data->Row['Count'] > 0) {
			redirect(sprintf('Location: work_task_schedules.php'));
		}
		$data->Disconnect();
	}
}

global $globalTaxCalculator;

$globalTaxCalculator = new GlobalTaxCalculator($GLOBALS['SYSTEM_COUNTRY'], $GLOBALS['SYSTEM_REGION']);

header("Content-Type: text/html; charset=UTF-8");