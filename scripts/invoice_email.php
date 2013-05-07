<?php
ini_set('max_execution_time', '3600');
ini_set('display_errors','on');
ini_set('memory_limit', '1024M');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();
$GLOBALS['SITE_LIVE'] = false;

$data = new DataQuery(sprintf("SELECT i.Invoice_ID FROM invoice AS i WHERE i.Customer_ID=42448 AND i.Created_On>'2009-03-01 00:00:00' ORDER BY i.Invoice_ID ASC"));

echo $data->TotalRows. '<br /><br />';

while($data->Row) {
	echo $data->Row['Invoice_ID'] . '<br />';
	
	$invoice = new Invoice($data->Row['Invoice_ID']);
	//$invoice->EmailCustomer();

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>