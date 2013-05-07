<?php
ini_set('max_execution_time', '1800');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT p.Product_ID, psv.Value FROM product AS p INNER JOIN product_specification AS ps ON ps.Product_ID=p.Product_ID INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID AND psv.Group_ID=121 WHERE p.Shipping_Class_ID=47 ORDER BY psv.Value ASC"));
while($data->Row) {
	if($data->Row['Value'] > 600) {
		$product = new Product($data->Row['Product_ID']);
		$product->ShippingClass->ID = 71;
		$product->Update();
	}

	$data->Next();
}
$data->Disconnect();

include('lib/common/appFooter.php');