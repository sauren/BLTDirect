<?php
ini_set('max_execution_time', '300000');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/SupplierShippingCalculator.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ShippingCostCalculator.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Despatch.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT d.Despatch_ID, w.Type_Reference_ID AS Supplier_ID, o.Postage_ID FROM despatch AS d INNER JOIN order_line AS ol ON ol.Despatch_ID=d.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID WHERE d.Postage_Cost=0 GROUP BY d.Despatch_ID"));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT SUM(ol.Cost) AS Cost, SUM(p.Weight) AS Weight FROM order_line AS ol LEFT JOIN product AS p ON p.Product_ID=ol.Product_ID WHERE ol.Despatch_ID=%d", $data->Row['Despatch_ID']));
 	$cost = $data2->Row['Cost'];
	$weight = $data2->Row['Weight'];
	$data2->Disconnect();

	$despatch = new Despatch($data->Row['Despatch_ID']);

	$calc = new SupplierShippingCalculator($despatch->Person->Address->Country->ID, $despatch->Person->Address->Region->ID, $cost, $weight, $data->Row['Postage_ID'], $data->Row['Supplier_ID']);

    $data2 = new DataQuery(sprintf("SELECT ol.Quantity, p.Shipping_Class_ID FROM order_line AS ol LEFT JOIN product AS p ON p.Product_ID=ol.Product_ID WHERE ol.Despatch_ID=%d", $data->Row['Despatch_ID']));
 	while($data2->Row) {
		$calc->Add($data2->Row['Quantity'], $data2->Row['Shipping_Class_ID']);

		$data2->Next();
	}
	$data2->Disconnect();

	$despatch->Postage->ID = $data->Row['Postage_ID'];
	$despatch->PostageCost = $calc->GetTotal();
	$despatch->Update();

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT d.Despatch_ID, o.Postage_ID FROM despatch AS d INNER JOIN order_line AS ol ON ol.Despatch_ID=d.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='B' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID WHERE d.Postage_Cost=0 GROUP BY d.Despatch_ID"));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT SUM(ol.Cost) AS Cost, SUM(p.Weight) AS Weight FROM order_line AS ol LEFT JOIN product AS p ON p.Product_ID=ol.Product_ID WHERE ol.Despatch_ID=%d", $data->Row['Despatch_ID']));
 	$cost = $data2->Row['Cost'];
	$weight = $data2->Row['Weight'];
	$data2->Disconnect();

	$despatch = new Despatch($data->Row['Despatch_ID']);

	$calc = new ShippingCostCalculator($despatch->Person->Address->Country->ID, $despatch->Person->Address->Region->ID, $cost, $weight, $data->Row['Postage_ID']);

    $data2 = new DataQuery(sprintf("SELECT ol.Quantity, p.Shipping_Class_ID FROM order_line AS ol LEFT JOIN product AS p ON p.Product_ID=ol.Product_ID WHERE ol.Despatch_ID=%d", $data->Row['Despatch_ID']));
 	while($data2->Row) {
		$calc->Add($data2->Row['Quantity'], $data2->Row['Shipping_Class_ID']);

		$data2->Next();
	}
	$data2->Disconnect();

	$despatch->Postage->ID = $data->Row['Postage_ID'];
	$despatch->PostageCost = $calc->GetTotal();
	$despatch->Update();

	$data->Next();
}
$data->Disconnect();