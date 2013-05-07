<?php
ini_set('max_execution_time', '90000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$data = new DataQuery();
$data->Execute(sprintf("SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA='%s' AND TABLE_NAME NOT LIKE 'customer_session%%' AND TABLE_NAME NOT LIKE 'email_queue' AND TABLE_NAME NOT LIKE 'session_item'", $data->DataConnection->DbName));

while($data->Row) {
	if($data->Row['TABLE_COLLATION'] != 'utf8_general_ci') {
		new DataQuery(sprintf("ALTER TABLE `%s` CHARACTER SET utf8 COLLATE utf8_general_ci", $data->Row['TABLE_NAME']));

		echo sprintf('UTF8 Encoding: %s<br />', $data->Row['TABLE_NAME']);
	}

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery();
$data->Execute(sprintf("SELECT TABLE_NAME, COLLATION_NAME, COLUMN_NAME, COLUMN_DEFAULT, COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='%s' AND COLLATION_NAME IS NOT NULL AND TABLE_NAME NOT LIKE 'customer_session%%' AND TABLE_NAME NOT LIKE 'email_queue' AND TABLE_NAME NOT LIKE 'session_item'", $data->DataConnection->DbName));

$tables = array();

while($data->Row) {
	if($data->Row['COLLATION_NAME'] != 'utf8_general_ci') {
		if(!isset($tables[$data->Row['TABLE_NAME']])) {
			$tables[$data->Row['TABLE_NAME']] = array();
		}
		
		$tables[$data->Row['TABLE_NAME']][] = $data->Row;
	}

	$data->Next();
}
$data->Disconnect();

foreach($tables as $tableName=>$columnData) {
	$sql = sprintf("ALTER TABLE `%s` ", $tableName);
	
	$columns = array();
	
	foreach($columnData as $column) {
		$columns[] = sprintf("MODIFY COLUMN `%s` %s CHARACTER SET utf8 COLLATE utf8_general_ci %s%s", $column['COLUMN_NAME'], $column['COLUMN_TYPE'], ($column['IS_NULLABLE'] == 'YES') ? 'NULL' : 'NOT NULL', !is_null($column['COLUMN_DEFAULT']) ? sprintf(' DEFAULT \'%s\'', $column['COLUMN_DEFAULT']) : '');
		
		echo sprintf('UTF8 Encoding: %s - %s<br />', $tableName, $column['COLUMN_NAME']);
	}
	
	$sql .= implode(', ', $columns);
	
	new DataQuery($sql);
}