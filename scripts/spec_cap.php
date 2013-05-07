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

$screwIn = array('GES', 'SES', 'MES', 'E11', 'E12', 'E10', 'E17', 'ES', 'E27');
$bayonet = array('SBC', 'Ba', 'BC');
$twoPin = array('GU', 'GX', 'G4', '2 Pin');
$fourPin = array('4 Pin');
$doubleEnded = array('R7', 'R7X', 'RX7', 'R7S');

$spec = new ProductSpec();

$data = new DataQuery(sprintf("SELECT Value_ID, Value FROM product_specification_value WHERE Group_ID=30 ORDER BY Value ASC"));
while($data->Row) {
	$found = false;
	
	if(!$found) {
		for($i=0; $i<count($screwIn); $i++) {
			if(stristr($data->Row['Value'], $screwIn[$i])) {
				echo 'Screw In ['.$screwIn[$i].']: '.$data->Row['Value'].'<br />';
				
				$data2 = new DataQuery(sprintf("SELECT Product_ID FROM product_specification WHERE Value_ID=%d", $data->Row['Value_ID']));
				while($data2->Row) {
					$spec->Product->ID = $data2->Row['Product_ID'];
					$spec->Value->ID = 4597;
					$spec->Add();
						
					$data2->Next();
				}
				$data2->Disconnect();
				
				$found = false;
				break;
			}
		}
	}
	
	if(!$found) {
		for($i=0; $i<count($bayonet); $i++) {
			if(stristr($data->Row['Value'], $bayonet[$i])) {
				if(($data->Row['Value'] != 'Ceramic Base') && ($data->Row['Value'] != 'Wedge Base') && ($data->Row['Value'] != 'WBC')) {
					echo 'Bayonet ['.$bayonet[$i].']: '.$data->Row['Value'].'<br />';
					
					$data2 = new DataQuery(sprintf("SELECT Product_ID FROM product_specification WHERE Value_ID=%d", $data->Row['Value_ID']));
					while($data2->Row) {
						$spec->Product->ID = $data2->Row['Product_ID'];
						$spec->Value->ID = 4598;
						$spec->Add();
							
						$data2->Next();
					}
					$data2->Disconnect();
				
					$found = false;
					break;
				}
			}
		}
	}
	
	if(!$found) {
		for($i=0; $i<count($twoPin); $i++) {
			if(stristr($data->Row['Value'], $twoPin[$i])) {
				if($data->Row['Value'] != 'Mogul End Prong') {
					echo '2 Pin ['.$twoPin[$i].']: '.$data->Row['Value'].'<br />';
					
					$data2 = new DataQuery(sprintf("SELECT Product_ID FROM product_specification WHERE Value_ID=%d", $data->Row['Value_ID']));
					while($data2->Row) {
						$spec->Product->ID = $data2->Row['Product_ID'];
						$spec->Value->ID = 4595;
						$spec->Add();
							
						$data2->Next();
					}
					$data2->Disconnect();
					
					$found = false;
					break;
				}
			}
		}
	}
	
	if(!$found) {
		for($i=0; $i<count($fourPin); $i++) {
			if(stristr($data->Row['Value'], $fourPin[$i])) {
				echo '4 Pin ['.$fourPin[$i].']: '.$data->Row['Value'].'<br />';
				
				$data2 = new DataQuery(sprintf("SELECT Product_ID FROM product_specification WHERE Value_ID=%d", $data->Row['Value_ID']));
				while($data2->Row) {
					$spec->Product->ID = $data2->Row['Product_ID'];
					$spec->Value->ID = 4596;
					$spec->Add();
						
					$data2->Next();
				}
				$data2->Disconnect();
					
				$found = false;
				break;
			}
		}
	}
	
	if(!$found) {
		for($i=0; $i<count($doubleEnded); $i++) {
			if(stristr($data->Row['Value'], $doubleEnded[$i])) {
				echo 'Double Ended ['.$doubleEnded[$i].']: '.$data->Row['Value'].'<br />';
				
				$data2 = new DataQuery(sprintf("SELECT Product_ID FROM product_specification WHERE Value_ID=%d", $data->Row['Value_ID']));
				while($data2->Row) {
					$spec->Product->ID = $data2->Row['Product_ID'];
					$spec->Value->ID = 4599;
					$spec->Add();
						
					$data2->Next();
				}
				$data2->Disconnect();
					
				$found = false;
				break;
			}
		}
	}
	
	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>