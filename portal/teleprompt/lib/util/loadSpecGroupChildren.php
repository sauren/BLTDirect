<?php
require_once('../../../../ignition/lib/classes/ApplicationHeader.php');

$id = $_REQUEST['id'];
$instance = $_REQUEST['instance'];

$data = new DataQuery(sprintf("SELECT * FROM product_specification_group WHERE Parent_ID=%d ORDER BY Name ASC", mysql_real_escape_string($id)));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Children FROM product_specification_group WHERE Parent_ID=%d", $data->Row['Group_ID']));
	$children = $data2->Row['Children'] > 0 ? 'true' : 'false';
	echo sprintf("%s.addNode(%d, %d, '%s', 'default', %s, 'javascript:setNode(%d, \'%s\')');", $instance, $data->Row['Group_ID'], $id, str_replace('\'', '\\\'', str_replace('"', '', $data->Row['Reference'])), $children, $data->Row['Group_ID'], str_replace('\'', '\\\'', str_replace('"', '', $data->Row['Reference'])));

	$data2->Disconnect();
	$data->Next();
}
$data->Disconnect();

echo sprintf('%s.loaded(%d);', $instance, $id);

$GLOBALS['DBCONNECTION']->Close();
?>

