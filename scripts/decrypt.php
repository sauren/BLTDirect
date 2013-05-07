<?php
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

$username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
$password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';

if(($username != 'bengummer') && ($password != 'bengummer')) {
	header('WWW-Authenticate: Basic');
    header("HTTP/1.0 401 Unauthorized");
    header("Status-Code: 401");
    exit;
}

if(isset($_REQUEST['string'])) {
	$cipher = new Cipher($_REQUEST['string']);
	$cipher->Decrypt();

	echo $cipher->Value;
}