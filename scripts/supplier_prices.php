<?php
ini_set('max_execution_time', '300');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/SupplierProductPrice.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT sp.Supplier_ID, sp.Product_ID, sp.Cost, sp.Modified_On FROM supplier_product AS sp INNER JOIN product AS p ON p.Product_ID=sp.Product_ID WHERE sp.Cost>0"));
while($data->Row) {
	$price = new SupplierProductPrice();
	$price->Supplier->ID = $data->Row['Supplier_ID'];
	$price->Product->ID = $data->Row['Product_ID'];
	$price->Quantity = 1;
	$price->Cost =  $data->Row['Cost'];
	$price->Add();
	
	new DataQuery(sprintf("UPDATE supplier_product_price SET Created_On='%s', Modified_On='%s' WHERE Supplier_Product_Price_ID=%d", $data->Row['Modified_On'], $data->Row['Modified_On'], $price->ID));
	
	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();