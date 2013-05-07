<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT Order_ID FROM orders WHERE Status LIKE 'Unread'"));
while($data->Row) {
	echo $data->Row['Order_ID'].'<br />';

	$order = new Order($data->Row['Order_ID']);
	$order->GetLines();
	
	if($order->CheckAutomaticPack()) {
		$order->SetAutomaticPack();
	} else {
		$order->AutoShip();
	}

	$data->Next();
}
$data->Disconnect();