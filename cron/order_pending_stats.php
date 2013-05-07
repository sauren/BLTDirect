<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Order Pending Stats';
$fileName = 'order_pending_stats.php';

## BEGIN SCRIPT
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/OrderPendingStat.php');

$countPackable = 0;
$countUnpackable = 0;
$stock = array();

$data = new DataQuery(sprintf('SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Despatch_ID=0 INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'B\' WHERE o.Status IN (\'Unread\', \'Pending\', \'Packing\', \'Partially Despatched\') GROUP BY o.Order_ID'));

$countUnpackable = $data->TotalRows;

while($data->Row) {
	$isStocked = true;
	$products = array();

	$data2 = new DataQuery(sprintf('SELECT ol.Product_ID, SUM(ol.Quantity) AS Quantity FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'B\' WHERE ol.Despatch_ID=0 AND ol.Order_ID=%d GROUP BY ol.Product_ID', $data->Row['Order_ID']));
	while($data2->Row) {
		$products[$data2->Row['Product_ID']] = $data2->Row['Quantity'];

		$data2->Next();
	}
	$data2->Disconnect();

	foreach($products as $productId=>$quantity) {
		if(!isset($stock[$productId])) {
			$data2 = new DataQuery(sprintf('SELECT SUM(ws.Quantity_In_Stock) AS Quantity FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type=\'B\' WHERE ws.Product_ID=%d', $productId));
			$stock[$productId] = ($data2->TotalRows > 0) ? $data2->Row['Quantity'] : 0;
			$data2->Disconnect();
		}

		if($quantity > $stock[$productId]) {
			$isStocked = false;
			break;
		}
	}

	if($isStocked) {
		$countPackable++;

		foreach($products as $productId=>$quantity) {
			$stock[$productId] -= $quantity;
		}
	}

	$data->Next();
}
$data->Disconnect();

$stat = new OrderPendingStat();
$stat->ordersPackable = $countPackable;
$stat->ordersUnpackable = $countUnpackable;
$stat->add();
## END SCRIPT

$logHeader[] = sprintf("Script: %s", $script);
$logHeader[] = sprintf("File Name: %s", $fileName);
$logHeader[] = sprintf("Date Executed: %s", date('Y-m-d H:i:s'));
$logHeader[] = sprintf("Execution Time: %s seconds", number_format(microtime(true) - $timing, 4, '.', ''));
$logHeader[] = '';

$log = array_merge($logHeader, $log);

if($mailLog) {
	$mail = new htmlMimeMail5();
	$mail->setFrom('root@bltdirect.com');
	$mail->setSubject(sprintf("Cron [%s] <root@bltdirect.com> php /var/www/vhosts/bltdirect.com/httpdocs/cron/%s", $script, $fileName));
	$mail->setText(implode("\n", $log));
	$mail->send(array('adam@azexis.com'));
}

echo implode("<br />", $log);

$GLOBALS['DBCONNECTION']->Close();