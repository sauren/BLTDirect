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

$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter, Product_ID, Group_ID FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID GROUP BY Product_ID, Group_ID ORDER BY Counter DESC"));
while($data->Row) {
	if($data->Row['Counter'] > 1) {
		print_r($data->Row);
		echo '<br />';

		$keep = true;

		$data2 = new DataQuery(sprintf("SELECT ps.Specification_ID FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID WHERE ps.Product_ID=%d AND psv.Group_ID=%d ORDER BY ps.Specification_ID DESC", $data->Row['Product_ID'], $data->Row['Group_ID']));
		while($data2->Row) {
			echo $data2->Row['Specification_ID'].'<br />';

			if(!$keep) {
				$data3 = new DataQuery(sprintf("DELETE FROM product_specification WHERE Specification_ID=%d", $data2->Row['Specification_ID']));
				$data3->Disconnect();
			}

			$keep = false;

			$data2->Next();
		}
		$data2->Disconnect();

		echo '<br />';
	}

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();