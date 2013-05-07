<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/UrlAlias.php');

$data = new DataQuery(sprintf("SELECT cp.Customer_ID, cp.Customer_Product_ID, COUNT(*) AS Counter
FROM customer AS cu
INNER JOIN contact AS c ON cu.Contact_ID=c.Contact_ID AND c.Parent_Contact_ID=0
INNER JOIN customer_product AS cp ON cp.Customer_ID=cu.Customer_ID
GROUP BY cp.Customer_ID, cp.Product_ID
HAVING Counter>1"));
while($data->Row) {
	new DataQuery(sprintf("DELETE FROM customer_product WHERE Customer_Product_ID=%d", $data->Row['Customer_Product_ID']));
	
	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT c.Parent_Contact_ID, cp.Customer_Product_ID, COUNT(*) AS Counter
FROM customer AS cu
INNER JOIN contact AS c ON cu.Contact_ID=c.Contact_ID AND c.Parent_Contact_ID>0
INNER JOIN customer_product AS cp ON cp.Customer_ID=cu.Customer_ID
GROUP BY c.Parent_Contact_ID, cp.Product_ID
HAVING Counter>1"));
while($data->Row) {
	new DataQuery(sprintf("DELETE FROM customer_product WHERE Customer_Product_ID=%d", $data->Row['Customer_Product_ID']));
	
	$data->Next();
}
$data->Disconnect();