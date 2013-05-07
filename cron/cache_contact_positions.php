<?php
ini_set('max_execution_time', '86400');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cron.php');

$cron = new Cron();
$cron->scriptName = 'Cache Contact Position';
$cron->scriptFileName = 'cache_contact_positions.php';
$cron->mailLogLevel = Cron::LOG_LEVEL_WARNING;

## BEGIN SCRIPT
new DataQuery(sprintf("UPDATE contact SET Position_Orders=0, Position_Turnover=0"));

$positionOrders = array();
$positionTurnover = array();

$data = new DataQuery(sprintf("SELECT cu.Contact_ID, COUNT(DISTINCT o.Order_ID) AS Orders, SUM(o.Total-o.TotalTax) AS Turnover FROM customer AS cu INNER JOIN orders AS o ON o.Customer_ID=cu.Customer_ID WHERE o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY cu.Contact_ID"));
while($data->Row) {
	if(!isset($positionOrders[$data->Row['Orders']])) {
		$positionOrders[$data->Row['Orders']] = array();
	}
	
	if(!isset($positionQuantities[$data->Row['Turnover']])) {
		$positionQuantities[$data->Row['Turnover']] = array();
	}

	$positionOrders[$data->Row['Orders']][] = $data->Row['Contact_ID'];
	$positionTurnover[$data->Row['Turnover']][] = $data->Row['Contact_ID'];

	$data->Next();
}
$data->Disconnect();

krsort($positionOrders);
krsort($positionTurnover);

$index = 1;

foreach($positionOrders as $position) {
	$jointPosition = $index;

	foreach($position as $contactId) {
		new DataQuery(sprintf("UPDATE contact SET Position_Orders=%d WHERE Contact_ID=%d", mysql_real_escape_string($jointPosition), mysql_real_escape_string($contactId)));

		$index++;
	}
}

$cron->log(sprintf('Cached: %d Order Positions', $index - 1), Cron::LOG_LEVEL_INFO);

$index = 1;

foreach($positionTurnover as $position) {
	$jointPosition = $index;

	foreach($position as $contactId) {
		new DataQuery(sprintf("UPDATE contact SET Position_Turnover=%d WHERE Contact_ID=%d", mysql_real_escape_string($jointPosition), mysql_real_escape_string($contactId)));

		$index++;
	}
}

$cron->log(sprintf('Cached: %d Turnover Positions', $index - 1), Cron::LOG_LEVEL_INFO);
## END SCRIPT

$cron->execute();
$cron->output();

$GLOBALS['DBCONNECTION']->Close();