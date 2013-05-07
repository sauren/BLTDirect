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
$script = 'Schedule Campaigns';
$fileName = 'schedule_campaigns.php';

## BEGIN SCRIPT
$data = new DataQuery(sprintf("SELECT c.Campaign_ID, c.Title, ce.Scheduled, ce.Is_Dated, ce.Campaign_Event_ID, ce.Owned_By FROM campaign_event AS ce INNER JOIN campaign AS c ON c.Campaign_ID=ce.Campaign_ID WHERE ce.Type='P' AND ce.Owned_By>0"));
while($data->Row) {
	$scheduled = $data->Row['Scheduled'];

	if($data->Row['Is_Dated'] == 'Y') {
		if($scheduled < time()) {
			$contacts = array();

			$data2 = new DataQuery(sprintf("SELECT cce.Campaign_Contact_Event_ID, cc.Contact_ID, cc.Created_On FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID INNER JOIN contact AS c ON c.Contact_ID=cc.Contact_ID WHERE cce.Campaign_Event_ID=%d AND cce.Is_Active='Y' AND cce.Is_Complete='N' AND cce.Is_Phone_Scheduled='N'", $data->Row['Campaign_Event_ID']));
			while($data2->Row) {
				$contacts[] = $data2->Row;

				$data2->Next();
			}
			$data2->Disconnect();

			foreach($contacts as $contactItem) {
				$schedule = new ContactSchedule();
				$schedule->ContactID = $contactItem['Contact_ID'];
				$schedule->Type->GetByReference('campaign');
				$schedule->ScheduledOn = date('Y-m-d H:i:s', mktime(date('H', $data->Row['Scheduled']), date('i', $data->Row['Scheduled']), date('s', $data->Row['Scheduled']), date('m', $data->Row['Scheduled']), date('d', $data->Row['Scheduled']), date('Y', $data->Row['Scheduled'])));
				$schedule->Note = sprintf('Scheduled event (<a href="campaign_profile.php?id=%d">%s</a>).', $data->Row['Campaign_ID'], $data->Row['Title']);
				$schedule->OwnedBy = $data->Row['Owned_By'];
				$schedule->Add();

				$log[] = sprintf("Scheduling Contact: %d, Campaign: #%s (%s), Scheduled Date: %s", $contactItem['Contact_ID'], $data->Row['Campaign_ID'], $data->Row['Title'], date('Y-m-d H:i:s', $data->Row['Scheduled']));

				new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Phone_Scheduled='Y' WHERE Campaign_Contact_Event_ID=%d", mysql_real_escape_string($contactItem['Campaign_Contact_Event_ID'])));
			}
		}
	} else {
		$contacts = array();

		$data2 = new DataQuery(sprintf("SELECT cce.Campaign_Contact_Event_ID, cc.Contact_ID, cc.Created_On FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID INNER JOIN contact AS c ON c.Contact_ID=cc.Contact_ID WHERE cce.Campaign_Event_ID=%d AND cce.Is_Active='Y' AND cce.Is_Complete='N' AND cce.Is_Phone_Scheduled='N' ORDER BY cc.Created_On ASC, cc.Campaign_Contact_ID ASC", $data->Row['Campaign_Event_ID']));
		while($data2->Row) {
			$contacts[] = $data2->Row;

			$data2->Next();
		}
		$data2->Disconnect();

		foreach($contacts as $contactItem) {
			$period = periodToArray($scheduled);
			$year = substr($contactItem['Created_On'], 0, 4);
			$month = substr($contactItem['Created_On'], 5, 2);
			$day = substr($contactItem['Created_On'], 8, 2);

			if(strtotime(date('Y-m-d 00:00:00', mktime(0, 0, 0, $month+$period['month'], $day+$period['day'], $year))) < time()) {
				$scheduledTime = strtotime($contactItem['Created_On']) + $data->Row['Scheduled'];

				$schedule = new ContactSchedule();
				$schedule->ContactID = $contactItem['Contact_ID'];
				$schedule->ScheduledOn = date('Y-m-d H:i:s', mktime(date('H', $scheduledTime), date('i', $scheduledTime), date('s', $scheduledTime), date('m', $scheduledTime), date('d', $scheduledTime), date('Y', $scheduledTime)));
				$schedule->Note = sprintf('This contacts campaign event (<a href="campaign_profile.php?id=%d">%s</a>) has been scheduled for processing on %s and requires completion.', $data->Row['Campaign_ID'], $data->Row['Title'], cDatetime(date('Y-m-d H:i:s', $scheduledTime), 'shortdate'));
				$schedule->OwnedBy = $data->Row['Owned_By'];
				$schedule->Add();

				$log[] = sprintf("Scheduling Contact: %d, Campaign: #%s (%s), Scheduled Date: %s", $contactItem['Contact_ID'], $data->Row['Campaign_ID'], $data->Row['Title'], date('Y-m-d H:i:s', $scheduledTime));

				new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Phone_Scheduled='Y' WHERE Campaign_Contact_Event_ID=%d", mysql_real_escape_string($contactItem['Campaign_Contact_Event_ID'])));
			}
		}
	}

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