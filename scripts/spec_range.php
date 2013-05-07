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

/*$ranges = array();
$unit = 'V';

$data = new DataQuery(sprintf("SELECT Value_ID, Value FROM product_specification_value WHERE Group_ID=224 ORDER BY Value ASC"));
while($data->Row) {
	if(preg_match(sprintf('/^[\d]*[-][\d]*%s$/', $unit), $data->Row['Value'])) {
		$value = trim(str_replace($unit, '', $data->Row['Value']));
		
		$items = explode('-', $value);
		
		$item = array();
		$item['Low'] = $items[0];
		$item['High'] = $items[1];
		$item['Value_ID'] = $data->Row['Value_ID'];
		
		$ranges[] = $item;
	} elseif(preg_match(sprintf('/^[\d]*%s[+]$/', $unit), $data->Row['Value'])) {
		$value = trim(str_replace($unit.'+', '', $data->Row['Value']));
		
		if(is_numeric($value)) {
			$item = array();
			$item['Low'] = $value;
			$item['High'] = 99999999999;
			$item['Value_ID'] = $data->Row['Value_ID'];
			
			$ranges[] = $item;
		}
	}
	
	$data->Next();
}
$data->Disconnect();

$spec = new ProductSpec();

$data = new DataQuery(sprintf("SELECT * FROM product_specification_value WHERE Group_ID=209 ORDER BY Value ASC"));
while($data->Row) {
	if(preg_match(sprintf('/^([\d.\s])*[\s]*(%s)$/i', $unit), $data->Row['Value'])) {
		$value = trim(str_replace($unit, '', $data->Row['Value']));
		
		foreach($ranges as $range) {
			if(($range['Low'] <= $value) && ($range['High'] >= floor($value))) {
				//echo $range['Low'] . ' - ' . $range['High'] .': ' .$value.'<br />';
				
				$data2 = new DataQuery(sprintf("SELECT Product_ID FROM product_specification WHERE Value_ID=%d", $data->Row['Value_ID']));
				while($data2->Row) {
					$spec->Product->ID = $data2->Row['Product_ID'];
					$spec->Value->ID = $range['Value_ID'];
					$spec->Add();
						
					$data2->Next();
				}
				$data2->Disconnect();
				
				break;
			}
		}
	}
	$data->Next();
}
$data->Disconnect();




$ranges = array();
$unit = 'W';

$data = new DataQuery(sprintf("SELECT Value_ID, Value FROM product_specification_value WHERE Group_ID=223 ORDER BY Value ASC"));
while($data->Row) {
	if(preg_match(sprintf('/^[\d]*[-][\d]*%s$/', $unit), $data->Row['Value'])) {
		$value = trim(str_replace($unit, '', $data->Row['Value']));
		
		$items = explode('-', $value);
		
		$item = array();
		$item['Low'] = $items[0];
		$item['High'] = $items[1];
		$item['Value_ID'] = $data->Row['Value_ID'];
		
		$ranges[] = $item;
	} elseif(preg_match(sprintf('/^[\d]*%s[+]$/', $unit), $data->Row['Value'])) {
		$value = trim(str_replace($unit.'+', '', $data->Row['Value']));
		
		if(is_numeric($value)) {
			$item = array();
			$item['Low'] = $value;
			$item['High'] = 99999999999;
			$item['Value_ID'] = $data->Row['Value_ID'];
			
			$ranges[] = $item;
		}
	}
	
	$data->Next();
}
$data->Disconnect();

$spec = new ProductSpec();

$data = new DataQuery(sprintf("SELECT * FROM product_specification_value WHERE Group_ID=211 ORDER BY Value ASC"));
while($data->Row) {
	if(preg_match(sprintf('/^([\d.\s])*[\s]*(%s)$/i', $unit), $data->Row['Value'])) {
		$value = trim(str_replace($unit, '', $data->Row['Value']));
		
		foreach($ranges as $range) {
			if(($range['Low'] <= $value) && ($range['High'] >= floor($value))) {
				//echo $range['Low'] . ' - ' . $range['High'] .': ' .$value.'<br />';
				
				$data2 = new DataQuery(sprintf("SELECT Product_ID FROM product_specification WHERE Value_ID=%d", $data->Row['Value_ID']));
				while($data2->Row) {
					$spec->Product->ID = $data2->Row['Product_ID'];
					$spec->Value->ID = $range['Value_ID'];
					$spec->Add();
					
					$data2->Next();
				}
				$data2->Disconnect();
				
				break;
			}
		}
	}
	$data->Next();
}
$data->Disconnect();*/

$group = new ProductSpecGroup();
$group->Name = 'Length Range';
$group->Reference = 'Length Range';
$group->IsFilterable = 'N';
$group->Add();

$ranges = array();
$unit = 'mm';

$data = new DataQuery(sprintf("SELECT Value_ID, Value FROM product_specification_value WHERE Group_ID=121 ORDER BY Value ASC"));
while($data->Row) {
	if(preg_match(sprintf('/^([\d.]*)%s$/', $unit), $data->Row['Value'], $matches)) {
		$ranges[] = (float) $matches[1];
	}
	
	$data->Next();
}
$data->Disconnect();

sort($ranges);

$minValue = 0;
$maxValue = $ranges[count($ranges) - 1];

$steps = array();
$step = 10;

for($i=$minValue; $i<=$maxValue; $i=$i+$step) {
	$steps[] = array('Min' => $i, 'Max' => $i + $step, 'Value' => array(), 'LengthRangeID' => 0);
}

$data = new DataQuery(sprintf("SELECT Value_ID, Value FROM product_specification_value WHERE Group_ID=121 ORDER BY Value ASC"));
while($data->Row) {
	if(preg_match(sprintf('/^([\d.]*)%s$/', $unit), $data->Row['Value'], $matches)) {
		$value = (float) $matches[1];
		
		for($i=0; $i<count($steps); $i++) {
			if(($steps[$i]['Min'] <= $value) && ($value < $steps[$i]['Max'])) {
				$steps[$i]['Value'][] = $data->Row['Value_ID'];
				break;
			}
		}
	}
	
	$data->Next();
}
$data->Disconnect();

for($i=0; $i<count($steps); $i++) {
	if(count($steps[$i]['Value']) > 0) {
		$value = new ProductSpecValue();
		$value->Group->ID = $group->ID;
		$value->Value = sprintf('%s-%smm', $steps[$i]['Min'], $steps[$i]['Max'] - 1);
		$value->Add();
			
		$steps[$i]['LengthRangeID'] = $value->ID;
	}
}

$spec = new ProductSpec();

for($i=0; $i<count($steps); $i++) {
	for($j=0; $j<count($steps[$i]['Value']); $j++) {
		$data = new DataQuery(sprintf("SELECT Product_ID FROM product_specification WHERE Value_ID=%d", $steps[$i]['Value'][$j]));
		while($data->Row) {
			$spec->Product->ID = $data->Row['Product_ID'];
			$spec->Value->ID = $steps[$i]['LengthRangeID'];
			$spec->Add();
				
			$data->Next();
		}
		$data->Disconnect();
	}
}

$GLOBALS['DBCONNECTION']->Close();
?>