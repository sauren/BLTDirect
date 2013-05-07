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

$spec = new ProductSpec();
$specValue = new ProductSpecValue();

$data = new DataQuery(sprintf("SELECT Value_ID, Value FROM product_specification_value WHERE Group_ID=41"));
while($data->Row) {
	if(preg_match(sprintf('/^([\d.\s])*[\s]*(Kelvin)$/i', $matches[$i]), $data->Row['Value'])) {
	
		$data2 = new DataQuery(sprintf("SELECT Value_ID FROM product_specification_value WHERE Group_ID=42 AND Value LIKE '%s'", $data->Row['Value']));
		if($data2->TotalRows > 0) {
			$data3 = new DataQuery(sprintf("SELECT Product_ID FROM product_specification WHERE Value_ID=%d", $data->Row['Value_ID']));
			while($data3->Row) {
				$spec->Value->ID = $data2->Row['Value_ID'];
				$spec->Product->ID = $data3->Row['Product_ID'];
				$spec->Add();
				
				$data3->Next();
			}
			$data3->Disconnect();
			
			$data3 = new DataQuery(sprintf("SELECT Specification_ID FROM product_specification WHERE Value_ID=%d", $data->Row['Value_ID']));
			while($data3->Row) {
				$spec->Delete($data3->Row['Specification_ID']);
				
				$data3->Next();
			}
			$data3->Disconnect();
		} else {
			$specValue->Group->ID = 42;
			$specValue->Value = $data->Row['Value'];
			$specValue->Add();
			
			$data3 = new DataQuery(sprintf("SELECT Product_ID FROM product_specification WHERE Value_ID=%d", $data->Row['Value_ID']));
			while($data3->Row) {
				$spec->Value->ID = $specValue->ID;
				$spec->Product->ID = $data3->Row['Product_ID'];
				$spec->Add();
				
				$data3->Next();
			}
			$data3->Disconnect();
			
			$data3 = new DataQuery(sprintf("SELECT Specification_ID FROM product_specification WHERE Value_ID=%d", $data->Row['Value_ID']));
			while($data3->Row) {
				$spec->Delete($data3->Row['Specification_ID']);
				
				$data3->Next();
			}
			$data3->Disconnect();
		}
		$data2->Disconnect();		
	}
	
	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>