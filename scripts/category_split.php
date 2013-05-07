<?php
ini_set('max_execution_time', '90000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$data = new DataQuery(sprintf("SELECT Category_ID, Category_Description FROM product_categories WHERE Category_Description LIKE '%%[SPLIT]%%'"));
while($data->Row) {
	$description = explode('[SPLIT]', $data->Row['Category_Description']);
	
	new DataQuery(sprintf("UPDATE product_categories SET Category_Description='%s', Category_Description_Secondary='%s' WHERE Category_ID=%d", isset($description[0]) ? mysql_real_escape_string($description[0]) : '', isset($description[1]) ? mysql_real_escape_string($description[1]) : '', $data->Row['Category_ID']));
	
	$data->Next();
}
$data->Disconnect();