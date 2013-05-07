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
$script = 'Schedule Enquiries';
$fileName = 'schedule_enquiries.php';

## BEGIN SCRIPT
$data = new DataQuery(sprintf("SELECT e.Prefix, e.Enquiry_ID, e.Review_On, e.Owned_By, c.Contact_ID FROM enquiry AS e INNER JOIN customer AS cu ON cu.Customer_ID=e.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID WHERE e.Review_On>=NOW() AND e.Review_On<ADDDATE(NOW(), INTERVAL 1 DAY) AND e.Owned_By>0 ORDER BY e.Review_On ASC"));
while($data->Row) {
	$despatchedTime = strtotime($data->Row['Review_On']);

	$schedule = new ContactSchedule();
	$schedule->ContactID = $data->Row['Contact_ID'];
	$schedule->Type->GetByReference('enquiry');
	$schedule->ScheduledOn = date('Y-m-d H:i:s', mktime(date('H', $despatchedTime), date('i', $despatchedTime), date('s', $despatchedTime), date('m', $despatchedTime), date('d', $despatchedTime), date('Y', $despatchedTime)));
	$schedule->Note = sprintf('This contacts recent enquiry (#<a href="enquiry_details.php?enquiryid=%d">E%s%s</a>) has been scheduled for reviewing on %s and requires following up.', $data->Row['Enquiry_ID'], $data->Row['Prefix'], $data->Row['Enquiry_ID'], cDatetime($data->Row['Review_On'], 'shortdate'));
	$schedule->OwnedBy = $data->Row['Owned_By'];
	$schedule->Add();

	$log[] = sprintf("Scheduling Contact: %d, Enquiry: #E%s%s, Review Date: %s", $data->Row['Contact_ID'], $data->Row['Prefix'], $data->Row['Enquiry_ID'], $data->Row['Review_On']);

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