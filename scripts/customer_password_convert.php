<?php
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

$data = new DataQuery(sprintf("SELECT Customer_ID, Password FROM customer WHERE PasswordConverted='N'"));
while($data->Row) {
	$cipher = new Cipher($data->Row['Password']);
	$cipher->Decrypt();

	new DataQuery(sprintf("UPDATE customer SET Password='%s',PasswordConverted='Y' WHERE Customer_ID=%d", sha1($cipher->Value), $data->Row['Customer_ID']));

	$data->Next();
}
$data->Disconnect();