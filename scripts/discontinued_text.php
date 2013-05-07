<?php
ini_set('max_execution_time', '90000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$data = new DataQuery("SELECT * FROM product WHERE Product_Description LIKE '%discontinued on%'");
while($data->Row) {
	$items = explode('discontinued on', strtolower($data->Row['Product_Description']));
	
	if(count($items) > 2) {
		echo $data->Row['Product_ID'] . '<br />';
		
		new DataQuery(sprintf("UPDATE product SET Product_Description='%s' WHERE Product_ID=%d", mysql_real_escape_string(preg_replace('/\<p\>\<strong\>Discontinued on ([^\:]+):\<\/strong\>\<br \/\>([^\<]*)\<\/p\>/', '', $data->Row['Product_Description'])), $data->Row['Product_ID']));
	}
	
	$data->Next();
}
$data->Disconnect();