<?php
require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/common/constants.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseSession.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/GlobalTaxCalculator.php');

$session = new WarehouseSession();
$session->Start();

$action = isset($_REQUEST['action']) ? strtolower($_REQUEST['action']) : null;

if($action == 'logout') {
	$session->Logout();
}

if(!empty($session->Warehouse->ID)){
	$session->Warehouse->Get();
}

$session->Warehouse->Contact->Get();

global $globalTaxCalculator;

$globalTaxCalculator = new GlobalTaxCalculator($GLOBALS['SYSTEM_COUNTRY'], $GLOBALS['SYSTEM_REGION']);

header("Content-Type: text/html; charset=UTF-8");