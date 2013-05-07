<?php
require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpec.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$groupId = 268;

$specs = array();

$data = new DataQuery(sprintf("SELECT ps.Specification_ID, ps.Product_ID, psv2.Value FROM product_specification_value AS psv INNER JOIN product_specification AS ps ON ps.Value_ID=psv.Value_ID INNER JOIN product_specification AS ps2 ON ps2.Product_ID=ps.Product_ID INNER JOIN product_specification_value AS psv2 ON psv2.Value_ID=ps2.Value_ID AND psv2.Group_ID=54 WHERE psv.Group_ID=%d AND psv.Value LIKE 'Reflector/Spotlight'", $groupId));
while($data->Row) {
	if(!isset($specs[$data->Row['Value']])) {
		$specs[$data->Row['Value']] = array();
	}
	
	$specs[$data->Row['Value']][] = $data->Row;

	$data->Next();
}
$data->Disconnect();

foreach($specs as $diameter=>$products) {
	$value = new ProductSpecValue();
	$value->Value = $diameter . 'mm Reflector/Spotlight';
	$value->Group->ID = $groupId;
	$value->Add();
	
	foreach($products as $product) {
		$spec = new ProductSpec();
		$spec->Delete($product['Specification_ID']);
		
		$spec = new ProductSpec();
		$spec->Value->ID = $value->ID;
		$spec->Product->ID = $product['Product_ID'];
		$spec->Add();
	}
}

$GLOBALS['DBCONNECTION']->Close();