<?php
ini_set('max_execution_time', '300');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT Contact_ID, Org_ID, Person_ID FROM contact WHERE Is_Temporary='Y'"));
while($data->Row) {
	if($data->Row['Person_ID'] > 0) {
		$data2 = new DataQuery(sprintf("SELECT Address_ID FROM person WHERE Person_ID=%d", $data->Row['Person_ID']));
		new DataQuery(sprintf("DELETE FROM address WHERE Address_ID=%d", $data2->Row['Address_ID']));
		$data2->Disconnect();

		new DataQuery(sprintf("DELETE FROM person WHERE Person_ID=%d", $data->Row['Person_ID']));

	} elseif($data->Row['Org_ID'] > 0) {
		$data2 = new DataQuery(sprintf("SELECT Address_ID FROM organisation WHERE Org_ID=%d", $data->Row['Org_ID']));
		new DataQuery(sprintf("DELETE FROM address WHERE Address_ID=%d", $data2->Row['Address_ID']));
		$data2->Disconnect();

		new DataQuery(sprintf("DELETE FROM organisation WHERE Org_ID=%d", $data->Row['Org_ID']));
	}

	new DataQuery(sprintf("DELETE FROM contact WHERE Contact_ID=%d", $data->Row['Contact_ID']));

	$data->Next();
}
$data->Disconnect();

include('lib/common/appFooter.php');
?>