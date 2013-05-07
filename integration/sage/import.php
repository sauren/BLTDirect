<?php
require_once('../../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/IntegrationSage.php");

$username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';

$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM users WHERE User_Name LIKE '%s' AND User_Password LIKE '%s'", mysql_real_escape_string($username), mysql_real_escape_string(md5(isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : ''))));
if($data->Row['Count'] == 0) {
	header('WWW-Authenticate: Basic');
    header("HTTP/1.0 401 Unauthorized");
    header("Status-Code: 401");
    exit;
}
$data->Disconnect();

$xmlInput = file_get_contents("php://input");

if(!empty($xmlInput)) {
	$fileName = md5(sprintf('import_%s', date('Ymd_His')));

	$importData = preg_replace('/<Company.*?>/', '<Company>', $xmlInput, 1);
	$importData = xml2array($importData);

	$hasData = false;

	if(isset($importData['Company'][0])) {
		foreach($importData['Company'][0] as $key=>$value) {
			if(!empty($value) && is_array($value)) {
				$hasData = true;
				break;
			}
		}
	}

	if($hasData) {
		$fileHandler = fopen(sprintf('%sremote/sage/feeds/%s', $GLOBALS['DATA_DIR_FS'], $fileName), 'w');
		if($fileHandler) {
			$cipher = new Cipher($xmlInput);
			$cipher->Encrypt();

			fwrite($fileHandler, $cipher->Value);
			fclose($fileHandler);

			$integration = new IntegrationSage();
			$integration->DataFeed = $fileName;
			$integration->Type = 'Import';
			$integration->Add();
		}
	}
}