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

$data = new DataQuery(sprintf("SELECT Product_ID FROM product WHERE Integration_ID<>''"));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT Specification_ID FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID WHERE ps.Product_ID=%d", $data->Row['Product_ID']));
	while($data2->Row) {
		new DataQuery(sprintf("DELETE FROM product_specification WHERE Specification_ID=%d", $data2->Row['Specification_ID']));

		$data2->Next();
	}
	$data2->Disconnect();

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();