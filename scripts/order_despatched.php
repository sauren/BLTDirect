<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

$data = new DataQuery(sprintf("SELECT Order_ID, MAX(Despatched_On) AS Despatched_On FROM despatch GROUP BY Order_ID"));
while($data->Row) {
	new DataQuery(sprintf("UPDATE orders SET Despatched_On='%s' WHERE Order_ID=%d", $data->Row['Despatched_On'], $data->Row['Order_ID']));

	$data->Next();
}
$data->Disconnect();