<?php
ini_set('max_execution_time', '90000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$data = new DataQuery(sprintf("SELECT p.Product_ID FROM warehouse_stock AS ws INNER JOIN product AS p ON p.Product_ID=ws.Product_ID WHERE ws.Warehouse_ID=30 AND (p.Is_Stocked='N' OR p.Monitor_Stock='N')"));
while($data->Row) {
	new DataQuery(sprintf("UPDATE product SET Is_Stocked='Y', Monitor_Stock='Y' WHERE Product_ID=%d", $data->Row['Product_ID']));

	$data->Next();
}
$data->Disconnect();