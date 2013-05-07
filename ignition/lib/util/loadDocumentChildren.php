<?php
require_once('../classes/ApplicationHeader.php');

$id = $_REQUEST['id'];
$instance = $_REQUEST['instance'];

$data = new DataQuery(sprintf("SELECT * FROM document WHERE Parent_ID=%d", $id));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Children FROM document WHERE Parent_ID=%d", $data->Row['Document_ID']));
	$children = $data2->Row['Children'] > 0 ? 'true' : 'false';
	echo sprintf("%s.addNode(%d, %d, '%s', 'default', %s, 'javascript:setNode(%d, \'%s\')');", $instance, $data->Row['Document_ID'], $id, str_replace('\'', '\\\'', str_replace('"', '', $data->Row['Title'])), $children, $data->Row['Document_ID'], str_replace('\'', '\\\'', str_replace('"', '', $data->Row['Title'])));

	$data2->Disconnect();
	$data->Next();
}
$data->Disconnect();

echo sprintf('%s.loaded(%d);', $instance, $id);

$GLOBALS['DBCONNECTION']->Close();
?>

