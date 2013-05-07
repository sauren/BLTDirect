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
$matches[] = 'mm';

$data = new DataQuery(sprintf("SELECT Value_ID, Value FROM product_specification_value WHERE (Group_ID=121 OR Group_ID=54 OR Group_ID=89 OR Group_ID=214) ORDER BY Value ASC"));
while($data->Row) {
	for($i=0; $i<count($matches); $i++) {
		if(preg_match(sprintf('/^[\d.]*[\s]*%s$/', $matches[$i]), $data->Row['Value'])) {
			$value = $data->Row['Value'];

			for($j=0; $j<count($matches); $j++) {
				$value = str_replace(strtolower($matches[$j]), '', $value);
				$value = str_replace($matches[$j], '', $value);
			}

			$value = trim($value);

			new DataQuery(sprintf("UPDATE product_specification_value SET Value='%s' WHERE Value_ID=%d", $value, $data->Row['Value_ID']));

			break;
		}
	}

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();