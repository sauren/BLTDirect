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

new DataQuery(sprintf("UPDATE invoice SET Invoice_Net=0, Invoice_Shipping=0, Invoice_Discount=0, Invoice_Tax=0, Invoice_Total=0 WHERE Payment_Method_ID=7"));

$data = new DataQuery(sprintf("SELECT Invoice_ID FROM invoice WHERE Payment_Method_ID=7"));
while($data->Row) {
	new DataQuery(sprintf("UPDATE invoice_line SET Line_Total=0, Line_Discount=0, Line_Tax=0, Discount_Information='' WHERE Invoice_ID=%d", $data->Row['Invoice_ID']));

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();