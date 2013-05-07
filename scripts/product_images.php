<?php
ini_set('max_execution_time', '1800');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT DISTINCT Product_ID FROM product_images ORDER BY Product_ID ASC"));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product_images WHERE Product_ID=%d AND Is_Primary='Y'", $data->Row['Product_ID']));
	if($data2->Row['Count'] == 0) {
		$data3 = new DataQuery(sprintf("SELECT Product_Image_ID FROM product_images WHERE Product_ID=%d LIMIT 0, 1", $data->Row['Product_ID']));
		if($data3->TotalRows > 0) {
			$data4 = new DataQuery(sprintf("UPDATE product_images SET Is_Primary='Y' WHERE Product_Image_ID=%d", $data3->Row['Product_Image_ID']));
			$data4->Disconnect();
		}
		$data3->Disconnect();
		
		echo $data->Row['Product_ID'].'<br />';	
	}
	$data2->Disconnect();	
	
	$data->Next();
}
$data->Disconnect();

include('lib/common/appFooter.php');
?>