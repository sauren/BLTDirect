<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/WarehouseStockHistory.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Archive Warehouse Stock';
$fileName = 'archive_warehouse_stock.php';

## BEGIN SCRIPT
$archive = array();

$data = new DataQuery(sprintf("SELECT id, warehouseStockId, quantityStocked FROM warehouse_stock_history AS wsh INNER JOIN (SELECT MAX(id) AS warehouseStockHistoryId FROM warehouse_stock_history GROUP BY warehouseStockId) AS wsh2 ON wsh2.warehouseStockHistoryId=wsh.id WHERE wsh.quantityStocked>0"));
while($data->Row) {
	$archive[$data->Row['warehouseStockId']] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Stock_ID, Quantity_In_Stock FROM warehouse_stock AS ws INNER JOIN product AS p ON p.Product_ID=ws.Product_ID"));
while($data->Row) {
	$insert = false;
	
	if(isset($archive[$data->Row['Stock_ID']])) {
		if($archive[$data->Row['Stock_ID']]['quantityStocked'] <> $data->Row['Quantity_In_Stock']) {
			$insert = true;
		}
	} else {
		if($data->Row['Quantity_In_Stock'] > 0) {
			$insert = true;	
		}
	}
	
	if($insert) {
		$history = new WarehouseStockHistory();
		$history->warehouseStockId = $data->Row['Stock_ID'];
		$history->quantityStocked = $data->Row['Quantity_In_Stock'];
		$history->add();
		
		$log[] = sprintf("Archiving Stock Quantity: %d, Stock Record: %d", $data->Row['Quantity_In_Stock'], $data->Row['Stock_ID']);
	}
	
	unset($archive[$data->Row['Stock_ID']]);
	
	$data->Next();
}
$data->Disconnect();

foreach($archive as $archiveKey=>$archiveItem) {
	$history = new WarehouseStockHistory();
	$history->warehouseStockId = $archiveKey;
	$history->quantityStocked = 0;
	$history->add();
	
	$log[] = sprintf("Archiving Stock Quantity: 0, Stock Record: %d", $archiveKey);
}
## END SCRIPT

$logHeader[] = sprintf("Script: %s", $script);
$logHeader[] = sprintf("File Name: %s", $fileName);
$logHeader[] = sprintf("Date Executed: %s", date('Y-m-d H:i:s'));
$logHeader[] = sprintf("Execution Time: %s seconds", number_format(microtime(true) - $timing, 4, '.', ''));
$logHeader[] = '';

$log = array_merge($logHeader, $log);

if ($mailLog) {
	$mail = new htmlMimeMail5();
	$mail->setFrom('root@bltdirect.com');
	$mail->setSubject(sprintf("Cron [%s] <root@bltdirect.com> php /var/www/vhosts/bltdirect.com/httpdocs/cron/%s", $script, $fileName));
	$mail->setText(implode("\n", $log));
	$mail->send(array('adam@azexis.com'));
}

echo implode("<br />", $log);

$GLOBALS['DBCONNECTION']->Close();