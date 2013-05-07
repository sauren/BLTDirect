<?php
ini_set('max_execution_time', '86400');

require_once('../../lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-blogger/classes/GoogleBloggerRequest.php');

$request = new GoogleBloggerRequest();
$request->login();

if($request->isAuthenticated()) {
	echo "logged in";
	$blogId = '4376532464300698814';
}

$GLOBALS['DBCONNECTION']->Close();