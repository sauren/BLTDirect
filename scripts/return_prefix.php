<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT o.Order_ID, rr.Resultant_Prefix FROM orders AS o INNER JOIN `return` AS r ON r.Return_ID=o.Return_ID INNER JOIN return_reason AS rr ON rr.Reason_ID=r.Reason_ID AND rr.Resultant_Prefix<>'R'"));
while($data->Row) {
	echo $data->Row['Order_ID'].'<br />';

	new DataQuery(sprintf("UPDATE orders SET Order_Prefix='%s' WHERE Order_ID=%d", $data->Row['Resultant_Prefix'], $data->Row['Order_ID']));

	$data->Next();
}
$data->Disconnect();