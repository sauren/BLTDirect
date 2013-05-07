<?php
ini_set('max_execution_time', '90000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');

$data = new DataQuery(sprintf("SELECT Order_ID, Created_On, TotalTax, Total, ROUND((TotalTax/(Total-TotalTax))*100, 2) AS TaxRate
FROM orders
WHERE Total>0 AND TotalTax>0
AND Tax_Rate=0
ORDER BY Order_ID ASC"));
while($data->Row) {
	if($data->Row['TaxRate'] < 19) {
		new DataQuery(sprintf("UPDATE orders SET Tax_Rate=17.5 WHERE Order_ID=%d", $data->Row['Order_ID']));
	} else {
		new DataQuery(sprintf("UPDATE orders SET Tax_Rate=20 WHERE Order_ID=%d", $data->Row['Order_ID']));
	}
	
	$data->Next();
}
$data->Disconnect();