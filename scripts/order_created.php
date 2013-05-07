<?php
ini_set('max_execution_time', '1800');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Quote.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$quote = new Quote();

$data = new DataQuery(sprintf("SELECT Quote_ID, Order_ID FROM orders WHERE Quote_ID>0 AND Created_By=0"));
while($data->Row) {
	$quote->Get($data->Row['Quote_ID']);

	if($quote->CreatedBy > 0) {

		$data2 = new DataQuery(sprintf("UPDATE orders SET Created_By=%d WHERE Order_ID=%d", $quote->CreatedBy, $data->Row['Order_ID']));
		$data2->Disconnect();

		echo sprintf("UPDATE orders SET Created_By=%d WHERE Order_ID=%d", $quote->CreatedBy, $data->Row['Order_ID']).'<br />';
	}

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>