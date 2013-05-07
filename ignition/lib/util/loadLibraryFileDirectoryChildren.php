<?php
require_once('../classes/ApplicationHeader.php');

$id = $_REQUEST['id'];
$type = $_REQUEST['type'];
$instance = $_REQUEST['instance'];

$data = new DataQuery(sprintf("SELECT * FROM library_file_directory WHERE Parent_ID=%d AND File_Type_ID=%d", mysql_real_escape_string($id), mysql_real_escape_string($type)));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Children FROM library_file_directory WHERE Parent_ID=%d AND File_Type_ID=%d", $data->Row['File_Directory_ID'], mysql_real_escape_string($type)));
	$children = $data2->Row['Children'] > 0 ? 'true' : 'false';
	echo sprintf("%s.addNode(%d, %d, '%s', 'default', %s, 'javascript:setNode(%d, \'%s\')');", $instance, $data->Row['File_Directory_ID'], $id, str_replace('\'', '', str_replace('"', '', $data->Row['Name'])), $children, $data->Row['File_Directory_ID'], str_replace('\'', '', str_replace('"', '', $data->Row['Name'])));
	$data2->Disconnect();

	$data->Next();
}
$data->Disconnect();

echo sprintf('%s.loaded(%d);', $instance, $id);

$GLOBALS['DBCONNECTION']->Close();