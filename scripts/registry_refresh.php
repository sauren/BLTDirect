<?php
require_once('../ignition/lib/classes/ApplicationHeader.php');

if($handle = opendir('../ignition')) {
	while (false !== ($file = readdir($handle))) {
		if(!is_dir($file)) {
			if((strlen($file) > 4) && (substr($file, -4) == '.php')) {
				$name = strrev($file);
				$pos = stripos($name, '.');
				$name = ucwords(str_replace('_', ' ', strrev(substr($name, $pos + 1, strlen($name)))));

				$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM registry WHERE Script_File LIKE '%s'", $file));
				if($data->Row['Count'] == 0) {
					new DataQuery(sprintf("INSERT INTO registry (Script_Name, Script_File) VALUES ('%s', '%s')", $name, $file));
				}
				$data->Disconnect();
			}
		}
	}

	closedir($handle);
}

$data = new DataQuery(sprintf("SELECT Registry_ID, Script_File FROM registry"));
while($data->Row) {
	$name = strrev($data->Row['Script_File']);
	$pos = stripos($name, '.');
	$name = ucwords(str_replace('_', ' ', strrev(substr($name, $pos + 1, strlen($name)))));

	$data2 = new DataQuery(sprintf("UPDATE registry SET Script_Name='%s' WHERE Registry_ID=%d", $name, $data->Row['Registry_ID']));
	$data2->Disconnect();

	$name = strrev($data->Row['Script_File']);
	$pos = stripos($name, '.');
	$name = strrev(substr($name, $pos + 1, strlen($name))) . '.php';

	if(!file_exists($GLOBALS["DIR_WS_ADMIN"].$name)) {
		new DataQuery(sprintf("DELETE FROM registry WHERE Registry_ID=%d", $data->Row['Registry_ID']));
		new DataQuery(sprintf("DELETE FROM registry_permissions WHERE Registry_ID=%d", $data->Row['Registry_ID']));
	}

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();