<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Archive Sessions';
$fileName = 'archive_session.php';

## BEGIN SCRIPT
$lockTimeStart = microtime(true);

new DataQuery(sprintf("LOCK TABLES customer_session_archive WRITE, customer_session_item_archive WRITE"));

$lockTimeEnd = microtime(true);
$lockTimeAchieved = $lockTimeEnd - $lockTimeStart;

new DataQuery(sprintf("UNLOCK TABLES"));

if($lockTimeAchieved < 10) {
    new DataQuery(sprintf("INSERT INTO `customer_session_archive` (`Session_ID`, `PHP_Session_ID`, `Is_Active`, `Referrer`, `Referrer_Search_Term`, `Created_On`, `Token`, `IP_Address`, `User_Agent_ID`, `Customer_ID`, `Affiliate_ID`) SELECT cs.`Session_ID`, cs.`PHP_Session_ID`, cs.`Is_Active`, cs.`Referrer`, cs.`Referrer_Search_Term`, cs.`Created_On`, cs.`Token`, cs.`IP_Address`, cs.`User_Agent_ID`, cs.`Customer_ID`, cs.`Affiliate_ID` FROM `customer_session` AS cs LEFT JOIN `customer_session_archive` AS csa ON cs.Session_ID=csa.Session_ID WHERE csa.Session_ID IS NULL"));
	new DataQuery(sprintf("INSERT INTO `customer_session_item_archive` (`Session_Item_ID`, `Session_ID`, `Customer_ID`, `Page_Request`, `Token`, `IP_Address`, `User_Agent_ID`, `Created_On`) SELECT csi.`Session_Item_ID`, csi.`Session_ID`, csi.`Customer_ID`, csi.`Page_Request`, csi.`Token`, csi.`IP_Address`, csi.`User_Agent_ID`, csi.`Created_On` FROM `customer_session_item` AS csi LEFT JOIN `customer_session_item_archive` AS csia ON csi.Session_Item_ID=csia.Session_Item_ID WHERE csia.Session_Item_ID IS NULL"));

	$log[] = sprintf("Archiving Session Items: Successful");
} else {
	$log[] = sprintf("Archiving Session Items: Unsuccessful");
}
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