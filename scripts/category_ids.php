<?php
ini_set('max_execution_time', '90000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

function getCategories($categoryId) {
	$categories = array($categoryId);
	
	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", $categoryId));
	while($data->Row) {
		$categories = array_merge($categories, getCategories($data->Row['Category_ID']));
		
		$data->Next();
	}
	$data->Disconnect();
	
	return $categories;
}

$categories = getCategories(66);

echo implode(', ', $categories);