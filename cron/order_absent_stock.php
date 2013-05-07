<?php
ini_set('max_execution_time', '1800');
ini_set('memory_limit', '512M');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cron.php');

$cron = new Cron();
$cron->scriptName = 'Order Absent Stock Profile';
$cron->scriptFileName = 'order_absent_stock.php';
$cron->mailLogLevel = Cron::LOG_LEVEL_WARNING;

## BEGIN SCRIPT
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');

new DataQuery(sprintf("UPDATE orders SET Is_Absent_Stock_Profile='N'"));

$data = new DataQuery(sprintf("SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN (SELECT p.Product_ID, SUM(ws.Quantity_In_Stock) AS Quantity FROM product AS p INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID AND w.`Type`='B' WHERE p.Is_Stocked='Y' GROUP BY p.Product_ID HAVING Quantity<=0) AS p ON p.Product_ID=ol.Product_ID WHERE o.Status NOT LIKE 'Despatched' AND o.Status NOT LIKE 'Cancelled' GROUP BY o.Order_ID"));
while($data->Row) {
	new DataQuery(sprintf("UPDATE orders SET Is_Absent_Stock_Profile='Y' WHERE Order_ID=%d", $data->Row['Order_ID']));
      		
	$data->Next();
}
$data->Disconnect();
## END SCRIPT

$cron->execute();
$cron->output();

$GLOBALS['DBCONNECTION']->Close();