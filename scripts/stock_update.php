<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/OrderNote.php');

//$data = new DataQuery(sprintf("SELECT ws.Stock_ID, ws.Product_ID FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND Type='B' WHERE ws.Cost=0"));
$data = new DataQuery(sprintf("SELECT ws.Stock_ID, ws.Product_ID FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Warehouse_ID=30"));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT MIN(Cost) AS Cost FROM supplier_product WHERE Cost>0 AND Product_ID=%d", $data->Row['Product_ID']));
	if($data2->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE warehouse_stock SET Cost=%f WHERE Stock_ID=%d", $data2->Row['Cost'], $data->Row['Stock_ID']));
	}
	$data2->Disconnect();
	
	$data->Next();	
}
$data->Disconnect();

$order->Recalculate();