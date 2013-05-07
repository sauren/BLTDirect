<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProductLocation.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerLocation.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$locations = array();

$location = new CustomerLocation();

$data = new DataQuery(sprintf("SELECT Customer_ID, Notes FROM customer_product WHERE Notes<>'' GROUP BY Customer_ID, Notes"));
while($data->Row) {
	$location->Customer->ID = $data->Row['Customer_ID'];
	$location->Name = trim($data->Row['Notes']);
	$location->Add();

	if(!isset($locations[$location->Customer->ID])) {
		$locations[$location->Customer->ID] = array();
	}

	if(!isset($locations[$location->Customer->ID][$location->Name])) {
		$locations[$location->Customer->ID][$location->Name] = array();
	}

	$locations[$location->Customer->ID][$location->Name] = $location->ID;

	$data->Next();
}
$data->Disconnect();

$assoc = new CustomerProductLocation();

$data = new DataQuery(sprintf("SELECT Customer_ID, Customer_Product_ID, Notes FROM customer_product WHERE Notes<>''"));
while($data->Row) {
	if(isset($locations[$data->Row['Customer_ID']][$data->Row['Notes']])) {
		$assoc->Location->ID = $locations[$data->Row['Customer_ID']][$data->Row['Notes']];
		$assoc->Product->ID = $data->Row['Customer_Product_ID'];
		$assoc->Add();
	}

	$data->Next();
}
$data->Disconnect();

include('lib/common/appFooter.php');