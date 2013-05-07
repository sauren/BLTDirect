<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

$data = new DataQuery(sprintf("SELECT Person_ID, Name_First, Name_Last FROM person WHERE Name_First<>'' OR Name_Last<>''"));
while($data->Row) {
	new DataQuery(sprintf("UPDATE person SET Name_First_Search='%s', Name_Last_Search='%s' WHERE Person_ID=%d", preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Name_First']), preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Name_Last']), $data->Row['Person_ID']));
	
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Org_ID, Org_Name FROM organisation WHERE Org_Name<>''"));
while($data->Row) {
	new DataQuery(sprintf("UPDATE organisation SET Org_Name_Search='%s' WHERE Org_ID=%d", preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Org_Name']), $data->Row['Org_ID']));
	
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Address_ID, Zip FROM address WHERE Zip<>''"));
while($data->Row) {
	new DataQuery(sprintf("UPDATE address SET Zip_Search='%s' WHERE Address_ID=%d", preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Zip']), $data->Row['Address_ID']));
	
	$data->Next();	
}
$data->Disconnect();