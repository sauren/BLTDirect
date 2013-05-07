<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Setting.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Purge Shopping Cart';
$fileName = 'purge_cart.php';
$rate = Setting::GetValue('purge_cart_rate');

if(!isset($rate) || $rate <= 0){
	$rate = $GLOBALS['DEFAULT_PURGE_CART_RATE'];
}

## BEGIN SCRIPT
$data = new DataQuery(sprintf("SELECT Basket_ID FROM customer_basket WHERE Created_On<ADDDATE(NOW(), INTERVAL -%d DAY)", $rate));
while($data->Row) {
	new DataQuery(sprintf("DELETE FROM customer_basket WHERE Basket_ID=%d", $data->Row['Basket_ID']));
	new DataQuery(sprintf("DELETE FROM customer_basket_line WHERE Basket_ID=%d", $data->Row['Basket_ID']));
	new DataQuery(sprintf("DELETE FROM customer_basket_shipping WHERE CustomerBasketID=%d", $data->Row['Basket_ID']));

	$data->Next();
}
$data->Disconnect();

new DataQuery(sprintf("OPTIMIZE TABLE customer_basket"));
new DataQuery(sprintf("OPTIMIZE TABLE customer_basket_line"));
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
	$mail->send(array('support@azexis.com'));
}

echo implode("<br />", $log);

$GLOBALS['DBCONNECTION']->Close();
?>