<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT Order_ID, Customer_ID FROM orders WHERE Order_Prefix='N' AND Parent_ID=0"));
while($data->Row) {
	echo $data->Row['Order_ID'].'<br />';

	$data2 = new DataQuery(sprintf("SELECT Order_ID FROM orders WHERE Customer_ID=%d AND Order_ID<%d ORDER BY Order_ID DESC LIMIT 0, 1", $data->Row['Customer_ID'], $data->Row['Order_ID']));
	if($data2->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE orders SET Parent_ID=%d WHERE Order_ID=%d", $data2->Row['Order_ID'], $data->Row['Order_ID']));

		$data2->Next();
	}
	$data2->Disconnect();

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Order_ID, Return_ID FROM orders WHERE Order_Prefix='R' AND Parent_ID=0"));
while($data->Row) {
	echo $data->Row['Order_ID'].'<br />';

	$data2 = new DataQuery(sprintf("SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN `return` AS r ON r.Order_Line_ID=ol.Order_Line_ID WHERE r.Return_ID=%d", $data->Row['Return_ID']));
	if($data2->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE orders SET Parent_ID=%d WHERE Order_ID=%d", $data2->Row['Order_ID'], $data->Row['Order_ID']));

		$data2->Next();
	}
	$data2->Disconnect();

	$data->Next();
}
$data->Disconnect();