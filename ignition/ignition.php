<?php
require_once('lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Session.php');

$session = new Session();
$session->ValidateID();
$session->Record();

if(isset($_REQUEST['serve'])) {
	if(strtolower($_REQUEST['serve']) == 'navigation') {
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/navWin.php');
		exit();
	} elseif(strtolower($_REQUEST['serve']) == 'session') {
		if(strtolower($_REQUEST['action']) == 'logout'){
			$session->Logout();
		}
	}
}

$GLOBALS['DBCONNECTION']->Close();