<?php
ini_set('max_execution_time', '86400');

require_once('../../lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-base/classes/GoogleBaseRequest.php');

$request = new GoogleBaseRequest();
$request->login();

if($request->isAuthenticated()) {
	//$request->insertItem(382);
	//$request->insertBatchItems();
}

$GLOBALS['DBCONNECTION']->Close();