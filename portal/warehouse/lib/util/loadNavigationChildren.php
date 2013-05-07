<?php
require_once('../../../../ignition/lib/classes/ApplicationHeader.php');

$id = $_REQUEST['id'];
$instance = $_REQUEST['instance'];

$function = isset($_REQUEST['function']) ? $_REQUEST['function'] . '.' : '';

$data = new DataQuery(sprintf("SELECT * FROM treemenu WHERE Parent_ID=%d ORDER BY Caption", mysql_real_escape_string($id)));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Children FROM treemenu WHERE Parent_ID=%d", $data->Row['Node_ID']));
	$children = ($data2->Row['Children'] > 0) ? true : false;
	echo sprintf("%s.addNode(%d, %d, '%s', 'default', %s, 'javascript:%ssetNode(%d, \'%s\')');", $instance, $data->Row['Node_ID'], $id, str_replace('\'', '\\\'', str_replace('"', '', $data->Row['Caption'])), (($children) ? 'true' : 'false'), $function, $data->Row['Node_ID'], str_replace('\'', '\\\'', str_replace('"', '', $data->Row['Caption'])));
	$data2->Disconnect();
	$data->Next();
}
$data->Disconnect();

echo sprintf('%s.loaded(%d);', $instance, $id);

$GLOBALS['DBCONNECTION']->Close();
?>

