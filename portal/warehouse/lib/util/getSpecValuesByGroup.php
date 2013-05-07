<?php
require_once('../../../../ignition/lib/classes/ApplicationHeader.php');

$data = new DataQuery(sprintf("SELECT Value_ID, Value FROM product_specification_value WHERE Group_ID=%d ORDER BY Value ASC", mysql_real_escape_string($_REQUEST['id'])));
while($data->Row) {
	echo sprintf("%s{br}\n", $data->Row['Value_ID']);
	echo sprintf("%s{br}\n", $data->Row['Value']);
	echo "{br}{br}\n";

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>