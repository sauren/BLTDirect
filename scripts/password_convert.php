<?php
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

$data = new DataQuery(sprintf("SELECT User_ID, User_Password FROM users"));
while($data->Row) {
	$cipher = new Cipher($data->Row['User_Password']);
	$cipher->Decrypt();

	echo $cipher->Value;
	echo '<br />';

	new DataQuery(sprintf("UPDATE users SET User_Password='%s' WHERE User_ID=%d", md5($cipher->Value), $data->Row['User_ID']));

	$data->Next();
}
$data->Disconnect();