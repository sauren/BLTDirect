<?php
require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpec.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValueImage.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$groupId = 185;

$group = new ProductSpecGroup($groupId);
$group->Name = 'Copy of ' . $group->Name;
$group->Add();

$data = new DataQuery(sprintf("SELECT Value_ID FROM product_specification_value WHERE Group_ID=%d", $groupId));
while($data->Row) {
	$value = new ProductSpecValue($data->Row['Value_ID']);
	$value->Group->ID = $group->ID;
	$value->Add();
	
	$data2 = new DataQuery(sprintf("SELECT id FROM product_specification_value_image WHERE valueId=%d", $data->Row['Value_ID']));
	while($data2->Row) {
		$image = new ProductSpecValueImage($data2->Row['id']);
		$image->valueId = $value->ID;
		$image->add();
		
		$data2->Next();
	}
	$data2->Disconnect();
	
	$data2 = new DataQuery(sprintf("SELECT Specification_ID FROM product_specification WHERE Value_ID=%d", $data->Row['Value_ID']));
	while($data2->Row) {
		$spec = new ProductSpec($data2->Row['Specification_ID']);
		$spec->Value->ID = $value->ID;
		$spec->Add();
		
		$data2->Next();
	}
	$data2->Disconnect();
	
	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();