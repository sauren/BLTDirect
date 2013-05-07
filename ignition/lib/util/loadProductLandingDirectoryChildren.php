<?php
require_once('../classes/ApplicationHeader.php');

$id = $_REQUEST['id'];
$type = $_REQUEST['type'];
$instance = $_REQUEST['instance'];

$data = new DataQuery(sprintf("SELECT * FROM product_landing_directory WHERE parentId=%d", mysql_real_escape_string($id)));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Children FROM product_landing_directory WHERE parentId=%d", $data->Row['id']));
	$children = $data2->Row['Children'] > 0 ? 'true' : 'false';
	echo sprintf("%s.addNode(%d, %d, '%s', 'default', %s, 'javascript:setNode(%d, \'%s\')');", $instance, $data->Row['id'], $id, str_replace('\'', '', str_replace('"', '', $data->Row['name'])), $children, $data->Row['id'], str_replace('\'', '', str_replace('"', '', $data->Row['name'])));
	$data2->Disconnect();

	$data->Next();
}
$data->Disconnect();

echo sprintf('%s.loaded(%d);', $instance, $id);

$GLOBALS['DBCONNECTION']->Close();