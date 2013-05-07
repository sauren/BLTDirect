<?php
require_once('../../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/IntegrationSage.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/SageTemplateCompany.php");

function replace($matches) {
	return strtolower($matches[0]);
}

$username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';

$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM users WHERE User_Name LIKE '%s' AND User_Password LIKE '%s'", mysql_real_escape_string($username), mysql_real_escape_string(md5(isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : ''))));
if($data->Row['Count'] == 0) {
	header('WWW-Authenticate: Basic');
    header("HTTP/1.0 401 Unauthorized");
    header("Status-Code: 401");
    exit;
}
$data->Disconnect();

if(isset($_REQUEST['hash'])) {
	$fileName = sprintf('%sremote/sage/feeds/%s', $GLOBALS['DATA_DIR_FS'], $_REQUEST['hash']);

	if(file_exists($fileName)) {
		$cipher = new Cipher(file_get_contents($fileName));
		$cipher->Decrypt();

		$output = $cipher->Value;
		$output = preg_replace_callback('/\<[^\!\>]+\/\>/', 'replace', $output);
		
		echo '<pre>';
		echo htmlentities($output);
	}
}