<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpec.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$categoryTypes = array();
$categoryTypes[91] = 4605;
$categoryTypes[774] = 4606;
$categoryTypes[228] = 4607;
$categoryTypes[14] = 4601;
$categoryTypes[15] = 4602;
$categoryTypes[16] = 4603;
$categoryTypes[13] = 4600;
$categoryTypes[66] = 4604;
$categoryTypes[1905] = 4617;
$categoryTypes[241] = 4614;
$categoryTypes[759] = 4608;
$categoryTypes[785] = 4609;
$categoryTypes[284] = 4616;
$categoryTypes[235] = 4613;
$categoryTypes[372] = 4610;
$categoryTypes[233] = 4611;
$categoryTypes[265] = 4615;
$categoryTypes[1209] = 4612;
$categoryTypes[1029] = 4618;

function addSpecValues($categoryId, $valueId) {
	$spec = new ProductSpec();
	
	$data = new DataQuery(sprintf("SELECT Product_ID FROM product_in_categories WHERE Category_ID=%d", $categoryId));
	while($data->Row) {
		$spec->Value->ID = $valueId;
		$spec->Product->ID = $data->Row['Product_ID'];
		$spec->Add();
		
		$data->Next();
	}
	$data->Disconnect();
	
	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", $categoryId));
	while($data->Row) {
		addSpecValues($data->Row['Category_ID'], $valueId);
		
		$data->Next();
	}
	$data->Disconnect();
}

foreach($categoryTypes as $categoryId=>$valueId) {
	addSpecValues($categoryId, $valueId);
}

$GLOBALS['DBCONNECTION']->Close();