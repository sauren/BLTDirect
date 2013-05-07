<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ContactSchedule.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Schedule Orders';
$fileName = 'schedule_orders.php';

## BEGIN SCRIPT
new DataQuery(sprintf("LOCK TABLES contact AS c READ, customer AS cu READ, orders AS o READ"));
new DataQuery(sprintf("CREATE TEMPORARY TABLE `temp_orders` SELECT MAX(o.Created_On) AS Last_Ordered_On, c.Contact_ID, c.Account_Manager_ID FROM contact AS c INNER JOIN customer AS cu ON c.Contact_ID=cu.Contact_ID INNER JOIN orders AS o ON cu.Customer_ID=o.Customer_ID WHERE c.Account_Manager_ID>0 GROUP BY c.Contact_ID"));
new DataQuery(sprintf("UNLOCK TABLES"));

$data = new DataQuery(sprintf("SELECT o.Order_Prefix, o.Order_ID, t.Last_Ordered_On, t.Contact_ID, t.Account_Manager_ID FROM temp_orders AS t INNER JOIN customer AS cu ON cu.Contact_ID=t.Contact_ID INNER JOIN orders AS o ON o.Created_On=t.Last_Ordered_On AND o.Customer_ID=cu.Customer_ID WHERE t.Last_Ordered_On>=ADDDATE(ADDDATE(NOW(), INTERVAL -3 MONTH), INTERVAL -1 DAY) AND t.Last_Ordered_On<ADDDATE(NOW(), INTERVAL -3 MONTH) ORDER BY t.Last_Ordered_On ASC"));
while($data->Row) {
	$despatchedTime = strtotime($data->Row['Last_Ordered_On']);

	$schedule = new ContactSchedule();
	$schedule->ContactID = $data->Row['Contact_ID'];
	$schedule->Type->GetByReference('ordered');
	$schedule->ScheduledOn = date('Y-m-d H:i:s', mktime(date('H', $despatchedTime), date('i', $despatchedTime), date('s', $despatchedTime), date('m', $despatchedTime)+3, date('d', $despatchedTime), date('Y', $despatchedTime)));
	$schedule->Note = sprintf('This contacts last order (#<a href="order_details.php?orderid=%d">%s%s</a>) was ordered on %s and has not ordered since.', $data->Row['Order_ID'], $data->Row['Order_Prefix'], $data->Row['Order_ID'], cDatetime($data->Row['Last_Ordered_On'], 'shortdate'));
	$schedule->OwnedBy = $data->Row['Account_Manager_ID'];
	$schedule->Add();

	$log[] = sprintf("Scheduling Contact: %d, Order: #%s%s, Last Ordered Date: %s", $data->Row['Contact_ID'], $data->Row['Order_Prefix'], $data->Row['Order_ID'], $data->Row['Last_Ordered_On']);

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
?>