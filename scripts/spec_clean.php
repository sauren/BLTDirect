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

$matches = array();
$matches[] = 'Watts,';
$matches[] = 'Watts';
$matches[] = 'Watt,';
$matches[] = 'Watt';
$matches[] = 'W';

$data = new DataQuery(sprintf("SELECT Value_ID, Value FROM product_specification_value WHERE Group_ID=211 ORDER BY Value ASC"));
while($data->Row) {	
	for($i=0; $i<count($matches); $i++) {
		if(preg_match(sprintf('/^([\d.\s])*[\s]*(%s)$/i', $matches[$i]), $data->Row['Value'])) {
			$value = $data->Row['Value'];
			
			for($j=0; $j<count($matches); $j++) {
				$value = str_replace(strtolower($matches[$j]), '', $value);
				$value = str_replace($matches[$j], '', $value);				
			}
			
			$value = trim($value);
			
			$updateValue = is_numeric($value) ? $value.'W' : $$value;
			
			$data2 = new DataQuery(sprintf("UPDATE product_specification_value SET Value='%s' WHERE Value_ID=%d", $updateValue, $data->Row['Value_ID']));
			$data2->Disconnect();
			
			break;
		} elseif(is_numeric(trim($data->Row['Value']))) {
			$value = trim($data->Row['Value']);
			
			$updateValue = $value.'W';
			
			$data2 = new DataQuery(sprintf("UPDATE product_specification_value SET Value='%s' WHERE Value_ID=%d", $updateValue, $data->Row['Value_ID']));
			$data2->Disconnect();
			
			break;
		}
	}
	
	$data->Next();
}
$data->Disconnect();



$matches = array();
$matches[] = 'Volts,';
$matches[] = 'Volts';
$matches[] = 'Volt,';
$matches[] = 'Volt';
$matches[] = 'V';

$data = new DataQuery(sprintf("SELECT Value_ID, Value FROM product_specification_value WHERE Group_ID=209 ORDER BY Value ASC"));
while($data->Row) {	
	for($i=0; $i<count($matches); $i++) {
		if(preg_match(sprintf('/^([\d.\s])*[\s]*(%s)$/i', $matches[$i]), $data->Row['Value'])) {
			$value = $data->Row['Value'];
			
			for($j=0; $j<count($matches); $j++) {
				$value = str_replace(strtolower($matches[$j]), '', $value);
				$value = str_replace($matches[$j], '', $value);				
			}
			
			$value = trim($value);
			
			$updateValue = is_numeric($value) ? $value.'V' : $$value;
			
			$data2 = new DataQuery(sprintf("UPDATE product_specification_value SET Value='%s' WHERE Value_ID=%d", $updateValue, $data->Row['Value_ID']));
			$data2->Disconnect();
			
			break;
		} elseif(is_numeric(trim($data->Row['Value']))) {
			$value = trim($data->Row['Value']);
			
			$updateValue = $value.'V';
			
			$data2 = new DataQuery(sprintf("UPDATE product_specification_value SET Value='%s' WHERE Value_ID=%d", $updateValue, $data->Row['Value_ID']));
			$data2->Disconnect();
			
			break;
		}
	}
	
	$data->Next();
}
$data->Disconnect();



$matches = array();
$matches[] = 'Kelvins,';
$matches[] = 'Kelvins';
$matches[] = 'Kelvin,';
$matches[] = 'Kelvin';
$matches[] = 'K';

$data = new DataQuery(sprintf("SELECT Value_ID, Value FROM product_specification_value WHERE Group_ID=42 ORDER BY Value ASC"));
while($data->Row) {	
	for($i=0; $i<count($matches); $i++) {
		if(preg_match(sprintf('/^([\d.\s])*[\s]*(%s)$/i', $matches[$i]), $data->Row['Value'])) {
			$value = $data->Row['Value'];
			
			for($j=0; $j<count($matches); $j++) {
				$value = str_replace(strtolower($matches[$j]), '', $value);
				$value = str_replace($matches[$j], '', $value);				
			}
			
			$value = trim($value);
			
			$updateValue = is_numeric($value) ? $value.' Kelvin' : $$value;
			
			$data2 = new DataQuery(sprintf("UPDATE product_specification_value SET Value='%s' WHERE Value_ID=%d", $updateValue, $data->Row['Value_ID']));
			$data2->Disconnect();
			
			break;
		} elseif(is_numeric(trim($data->Row['Value']))) {
			$value = trim($data->Row['Value']);
			
			$updateValue = $value.' Kelvin';
			
			$data2 = new DataQuery(sprintf("UPDATE product_specification_value SET Value='%s' WHERE Value_ID=%d", $updateValue, $data->Row['Value_ID']));
			$data2->Disconnect();
			
			break;
		}
	}
	
	$data->Next();
}
$data->Disconnect();



$matches = array();
$matches[] = 'Watts,';
$matches[] = 'Watts';
$matches[] = 'Watt,';
$matches[] = 'Watt';
$matches[] = 'W';

$data = new DataQuery(sprintf("SELECT Value_ID, Value FROM product_specification_value WHERE Group_ID=73 ORDER BY Value ASC"));
while($data->Row) {	
	for($i=0; $i<count($matches); $i++) {
		if(preg_match(sprintf('/^([\d.\s])*[\s]*(%s)$/i', $matches[$i]), $data->Row['Value'])) {
			$value = $data->Row['Value'];
			
			for($j=0; $j<count($matches); $j++) {
				$value = str_replace(strtolower($matches[$j]), '', $value);
				$value = str_replace($matches[$j], '', $value);				
			}
			
			$value = trim($value);
			
			$updateValue = is_numeric($value) ? $value.'W' : $$value;
			
			$data2 = new DataQuery(sprintf("UPDATE product_specification_value SET Value='%s' WHERE Value_ID=%d", $updateValue, $data->Row['Value_ID']));
			$data2->Disconnect();
			
			break;
		} elseif(is_numeric(trim($data->Row['Value']))) {
			$value = trim($data->Row['Value']);
			
			$updateValue = $value.'W';
			
			$data2 = new DataQuery(sprintf("UPDATE product_specification_value SET Value='%s' WHERE Value_ID=%d", $updateValue, $data->Row['Value_ID']));
			$data2->Disconnect();
			
			break;
		}
	}
	
	$data->Next();
}
$data->Disconnect();



$mergeGroups = array();
$mergeGroups[] = 211;
$mergeGroups[] = 209;
$mergeGroups[] = 42;
$mergeGroups[] = 73;

foreach($mergeGroups as $group) {
	$occurrences = array();
	
	$data = new DataQuery(sprintf("SELECT Value_ID, Value FROM product_specification_value WHERE Group_ID=%d", $group));
	while($data->Row) {
		if(!isset($occurrences[strtolower($data->Row['Value'])])) {
			$occurrences[strtolower($data->Row['Value'])]['Values'] = array();
			$occurrences[strtolower($data->Row['Value'])]['Count'] = 0;
		}
		
		$occurrences[strtolower($data->Row['Value'])]['Count']++;
		$occurrences[strtolower($data->Row['Value'])]['Values'][] = $data->Row['Value_ID'];
		
		$data->Next();
	}
	$data->Disconnect();

	foreach($occurrences as $occurrence) {
		if($occurrence['Count'] > 1) {
			$firstId = -1;
			$mergeId = array();
			
			for($i=0; $i<count($occurrence['Values']); $i++) {
				if($i == 0) {
					$firstId = $occurrence['Values'][$i];
				} else {
					$mergeId[] = $occurrence['Values'][$i];
				}
			}
			
			foreach($mergeId as $id) {
				$data = new DataQuery(sprintf("UPDATE product_specification SET Value_ID=%d WHERE Value_ID=%d", $firstId, $id));
				$data->Disconnect();
				
				$data = new DataQuery(sprintf("DELETE FROM product_specification_value WHERE Value_ID=%d", $id));
				$data->Disconnect();
			}
		}		
	}
}

$GLOBALS['DBCONNECTION']->Close();
?>