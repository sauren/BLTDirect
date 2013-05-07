<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT Product_ID FROM product"));
while($data->Row) {
	$product = new Product($data->Row['Product_ID']);
	$product->UpdateSpecCache();

	$data->Next();
}
$data->Disconnect();