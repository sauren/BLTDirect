<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'LBUK Sync';
$fileName = 'lbuk_sync.php';

## BEGIN SCRIPT
$connection = new MysqlConnection('217.174.252.254', 'lightbulbsuk', 'blt', 'VaGihAtaSu68');

$data = new DataQuery(sprintf("SELECT localId, remoteId FROM lbuk_linked"));
while($data->Row) {
	new DataQuery(sprintf("UPDATE product SET Integration_ID=%d WHERE Product_ID=%d", $data->Row['localId'], $data->Row['remoteId']), $connection);

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT p.Product_ID, ppc.Quantity, ppc.Price_Base_Our, ppc.Price_Base_RRP FROM product AS p INNER JOIN product_prices_current AS ppc ON ppc.Product_ID=p.Product_ID WHERE ppc.Modified_On>ADDDATE(NOW(), INTERVAL -1 DAY)"));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT ppc.Price_Base_Our FROM product AS p INNER JOIN product_prices_current AS ppc ON ppc.Product_ID=p.Product_ID WHERE p.Product_ID=%d AND ppc.Quantity=%d", $data->Row['Product_ID'], $data->Row['Quantity']), $connection);
	if($data2->TotalRows > 0) {
		if(bccomp($data->Row['Price_Base_Our'], $data2->Row['Price_Base_Our'], 2) <> 0) {
			$log[] = sprintf("Updating Product: %d, Price: %s, Old Price: %s, Quantity: %d", $data->Row['Product_ID'], $data->Row['Price_Base_Our'], $data2->Row['Price_Base_Our'], $data->Row['Quantity']);
						
			new DataQuery(sprintf("INSERT INTO product_prices (Product_ID, Price_Base_Our, Price_Base_RRP, Price_Starts_On, Quantity) VALUES (%d, %f, %f, NOW(), %d)", $data->Row['Product_ID'], $data->Row['Price_Base_Our'], $data->Row['Price_Base_RRP'], $data->Row['Quantity']), $connection);
		}
	}
	$data2->Disconnect();

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT ll.remoteId AS Product_ID, ppc.Quantity, ppc.Price_Base_Our FROM lbuk_linked AS ll INNER JOIN product AS p ON p.Product_ID=ll.localId INNER JOIN product_prices_current AS ppc ON ppc.Product_ID=p.Product_ID WHERE ppc.Modified_On>ADDDATE(NOW(), INTERVAL -1 DAY)"));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT ppc.Price_Base_Our FROM product AS p INNER JOIN product_prices_current AS ppc ON ppc.Product_ID=p.Product_ID WHERE p.Product_ID=%d AND ppc.Quantity=%d", $data->Row['Product_ID'], $data->Row['Quantity']), $connection);
	if($data2->TotalRows > 0) {
		if(bccomp($data->Row['Price_Base_Our'], $data2->Row['Price_Base_Our'], 2) <> 0) {
			$log[] = sprintf("Updating Product: %d, Price: %s, Old Price: %s, Quantity: %d", $data->Row['Product_ID'], $data->Row['Price_Base_Our'], $data2->Row['Price_Base_Our'], $data->Row['Quantity']);
						
			new DataQuery(sprintf("INSERT INTO product_prices (Product_ID, Price_Base_Our, Price_Base_RRP, Price_Starts_On, Quantity) VALUES (%d, %f, %f, NOW(), %d)", $data->Row['Product_ID'], $data->Row['Price_Base_Our'], $data->Row['Price_Base_RRP'], $data->Row['Quantity']), $connection);
		}
	}
	$data2->Disconnect();

	$data->Next();
}
$data->Disconnect();
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