<?php
ini_set('max_execution_time', '86400');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

$data = new DataQuery(sprintf("SELECT * FROM orders"));
while($data->Row) {
	new DataQuery(sprintf("UPDATE orders SET
	Custom_Order_No_Search='%s',
	Billing_First_Name_Search='%s', Billing_Last_Name_Search='%s', Billing_Organisation_Name_Search='%s', Billing_Zip_Search='%s',
	Shipping_First_Name_Search='%s', Shipping_Last_Name_Search='%s', Shipping_Organisation_Name_Search='%s', Shipping_Zip_Search='%s',
	Invoice_First_Name_Search='%s', Invoice_Last_Name_Search='%s', Invoice_Organisation_Name_Search='%s', Invoice_Zip_Search='%s'
	
	
	WHERE Order_ID=%d", mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Custom_Order_No'])),
	
	mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Billing_First_Name'])), mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Billing_Last_Name'])), mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Billing_Organisation_Name'])), mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Billing_Zip'])),
	mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Shipping_First_Name'])), mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Shipping_Last_Name'])), mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Shipping_Organisation_Name'])), mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Shipping_Zip'])),
	mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Invoice_First_Name'])), mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Invoice_Last_Name'])), mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Invoice_Organisation_Name'])), mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Invoice_Zip'])),
	
	$data->Row['Order_ID']));
	
	$data->Next();	
}
$data->Disconnect();