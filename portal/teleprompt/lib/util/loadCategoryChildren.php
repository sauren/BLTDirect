<?php
require_once('../../../../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

$id = $_REQUEST['id'];
$instance = $_REQUEST['instance'];
$connection = isset($_REQUEST['connection']) ? $_REQUEST['connection'] : 0;
$connections = getSyncConnections();

$function = isset($_REQUEST['function']) ? $_REQUEST['function'] . '.' : '';

$cat = new Category($id, $connections[$connection]['Connection']);

if($id > 0){
	$sql = sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d ORDER BY %s ASC", mysql_real_escape_string($id), mysql_real_escape_string($cat->CategoryOrder));
} else {
	$sql = sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($id));
}

$data = new DataQuery($sql, $connections[$connection]['Connection']);
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Children FROM product_categories WHERE Category_Parent_ID=%d", $data->Row['Category_ID']), $connections[$connection]['Connection']);
	$children = ($data2->Row['Children'] > 0) ? true : false;
	echo sprintf("%s.addNode(%d, %d, '%s', 'default', %s, 'javascript:%ssetNode(%d, \'%s\')');", $instance, $data->Row['Category_ID'], $id, str_replace('\'', '\\\'', str_replace('"', '', $data->Row['Category_Title'])), (($children) ? 'true' : 'false'), $function, $data->Row['Category_ID'], str_replace('\'', '\\\'', str_replace('"', '', $data->Row['Category_Title'])));
	$data2->Disconnect();
	$data->Next();
}
$data->Disconnect();

echo sprintf('%s.loaded(%d);', $instance, $id);

$GLOBALS['DBCONNECTION']->Close();
?>

