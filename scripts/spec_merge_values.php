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

$data = new DataQuery(sprintf("SELECT Group_ID, Value, COUNT(*) AS Count FROM product_specification_value GROUP BY Group_ID, Value HAVING Count>1 ORDER BY Count DESC"));
while($data->Row) {
	$values = array();

	$data2 = new DataQuery(sprintf("SELECT Value_ID FROM product_specification_value WHERE Group_ID=%d AND Value LIKE '%s'", $data->Row['Group_ID'], $data->Row['Value']));
	while($data2->Row) {
		$values[] = $data2->Row['Value_ID'];

		$data2->Next();
	}
	$data2->Disconnect();

	if(count($values) >= 2) {
		for($i=1; $i<count($values); $i++) {
			new DataQuery(sprintf("UPDATE product_specification SET Value_ID=%d WHERE Value_ID=%d", $values[0], $values[$i]));

			$value = new ProductSpecValue();
			$value->Delete($values[$i]);
		}
	}

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();