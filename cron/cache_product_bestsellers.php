<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cron.php');

$cron = new Cron();
$cron->scriptName = 'Cache Product Best Sellers';
$cron->scriptFileName = 'cache_product_bestsellers.php';
$cron->mailLogLevel = Cron::LOG_LEVEL_WARNING;

## BEGIN SCRIPT
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cache.php');

$months = 1;
$limit = 100;
$cacheData = array();
$totalOrders = 0;
$totalProducts = 0;

$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Quantity) AS Products FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE o.Created_On>=ADDDATE(NOW(), INTERVAL -1 MONTH)"));
if($data->TotalRows > 0) {
	$totalOrders = $data->Row['Orders'];
	$totalProducts = $data->Row['Products'];
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT p.Product_ID AS ProductID, p.Product_Title AS Name, COUNT(DISTINCT o.Order_ID) AS Orders, (COUNT(DISTINCT o.Order_ID)/%d)*100 AS OrdersPercent, SUM(ol.Quantity) AS Products, (SUM(ol.Quantity)/%d)*100 AS ProductsPercent FROM product AS p INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE p.Is_Active='Y' AND p.Discontinued='N' AND p.Is_Demo_Product='N' AND p.Is_Complementary='N' AND ((p.Sales_Start<=NOW() AND p.Sales_End>NOW()) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) GROUP BY p.Product_ID ORDER BY Orders DESC LIMIT 0, %d", mysql_real_escape_string($totalOrders), mysql_real_escape_string($totalProducts), mysql_real_escape_string($months), mysql_real_escape_string($limit)));
while($data->Row) {
	$cacheData[] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$cache = new Cache();
$cache->setProperty('product.best_sellers');
$cache->setData($cacheData);
$cache->add();

$cron->log(sprintf('Cached: %d Best Sellers, Period: %d Month(s)', $limit, $months), Cron::LOG_LEVEL_INFO);
## END SCRIPT

$cron->execute();
$cron->output();

$GLOBALS['DBCONNECTION']->Close();