<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

$data = new DataQuery(sprintf("SELECT ua.userId, rp.Registry_ID FROM user_access AS ua INNER JOIN registry_permissions AS rp ON rp.Access_ID=ua.accessId GROUP BY ua.userId, rp.Registry_ID"));
while($data->Row) {
	new DataQuery(sprintf("INSERT INTO user_registry (userId, registryId) VALUES (%d, %d)", $data->Row['userId'], $data->Row['Registry_ID']));

	$data->Next();
}
$data->Disconnect();