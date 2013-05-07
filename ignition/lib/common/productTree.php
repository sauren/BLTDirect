<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Category.php');

$catId = 0;
if(isset($_REQUEST['cat'])) $catId = $_REQUEST['cat'];
$function = isset($_REQUEST['function']) ? $_REQUEST['function'] . '.' : (strlen($function) > 0) ? $function .'.' : '';

function getNodes($id, $function){
	$data = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_ID=%d and Category_Parent_ID!=0", mysql_real_escape_string($id)));
	if($data->TotalRows > 0){
		getNodes($data->Row['Category_Parent_ID'], $function);

		$cat = new Category($data->Row['Category_Parent_ID']);

		$data2 = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d ORDER BY %s ASC", $data->Row['Category_Parent_ID'], $cat->CategoryOrder));
		while($data2->Row){
			$data3 = new DataQuery(sprintf("SELECT COUNT(*) AS Children FROM product_categories WHERE Category_Parent_ID=%d", $data2->Row['Category_ID']));
			$children = ($data3->Row['Children'] > 0) ? true : false;
			$itemClass = 'default';
			if($data2->Row['Category_ID'] == $id) $itemClass = 'selected';
			echo sprintf("myTree.addNode(%d, %d, '%s', '%s', %s, 'javascript:%ssetNode(%d, \'%s\');');\n",
			$data2->Row['Category_ID'],
			$data2->Row['Category_Parent_ID'],
			str_replace('\'', '\\\'', str_replace('"', '', $data2->Row['Category_Title'])),
			$itemClass,
			(($children) ? 'true' : 'false'),
			$function,
			$data2->Row['Category_ID'],
			str_replace('\'', '\\\'', str_replace('"', '', $data2->Row['Category_Title'])));
			$data3->Disconnect();
			$data2->Next();
		}
		$data2->Disconnect();
	}
	$data->Disconnect();
}

$data = new DataQuery("SELECT * FROM product_categories WHERE Category_Parent_ID=0 ORDER BY Category_Title");
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Children FROM product_categories WHERE Category_Parent_ID=%d", $data->Row['Category_ID']));
	$children = ($data2->Row['Children'] > 0) ? true : false;
	$itemClass = 'default';
	if($data->Row['Category_ID'] == $catId) $itemClass = 'selected';
	echo sprintf("myTree.addNode(%d, 0, '%s', '%s', %s, 'javascript:%ssetNode(%d, \'%s\');');\n",
	$data->Row['Category_ID'],
	str_replace('\'', '\\\'', str_replace('"', '', $data->Row['Category_Title'])),
	$itemClass,
	(($children) ? 'true' : 'false'),
	$function,
	$data->Row['Category_ID'],
	str_replace('\'', '\\\'', str_replace('"', '', $data->Row['Category_Title'])));
	$data2->Disconnect();
	$data->Next();
}
$data->Disconnect();

if($catId > 0){
	getNodes($catId, $function);
}
?>