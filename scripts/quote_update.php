<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT Quote_ID FROM quote WHERE Status LIKE 'Pending'"));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM orders WHERE Quote_ID=%d", $data->Row['Quote_ID']));
	if($data2->Row['Count'] > 0) {
		$data3 = new DataQuery(sprintf("UPDATE quote SET Status='Ordered' WHERE Quote_ID=%d", $data->Row['Quote_ID']));
		$data3->Disconnect();
	}
	$data2->Disconnect();

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>