<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT p.Product_ID, ol.Order_Line_ID FROM product AS p INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID AND ol.Cost=0 WHERE Product_Type='G'"));
while($data->Row) {
	$cost = 0;

    $data2 = new DataQuery(sprintf("SELECT Product_ID, Component_Quantity FROM product_components WHERE Component_Of_Product_ID=%d", $data->Row['Product_ID']));
	while($data2->Row) {
        $data3 = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", $data2->Row['Product_ID']));
		if($data3->TotalRows > 0) {
			$cost += $data3->Row['Cost'] * $data2->Row['Component_Quantity'];
		}
		$data3->Disconnect();

		$data2->Next();
	}
	$data2->Disconnect();

	new DataQuery(sprintf("UPDATE order_line SET Cost=%f WHERE Order_Line_ID=%d", $cost, $data->Row['Order_Line_ID']));

	$data->Next();
}
$data->Disconnect();