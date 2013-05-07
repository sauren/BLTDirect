<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/WorkTaskSchedule.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Schedule Work Tasks';
$fileName = 'schedule_work_tasks.php';

## BEGIN SCRIPT
$data = new DataQuery(sprintf("SELECT wt.id, wtu.userId, wt.period, MAX(wts.completedOn) AS completedOn, MAX(wts2.scheduledOn) AS scheduledOn FROM work_task AS wt INNER JOIN work_task_user AS wtu ON wtu.workTaskId=wt.id LEFT JOIN work_task_schedule AS wts ON wts.workTaskId=wt.id AND wts.userId=wtu.userId AND wts.isComplete='Y' LEFT JOIN work_task_schedule AS wts2 ON wts2.workTaskId=wt.id AND wts2.userId=wtu.userId AND wts2.isComplete='N' WHERE wt.startedOn<=NOW() AND wts2.id IS NULL GROUP BY wtu.id"));
while($data->Row) {
	$schedule = new WorkTaskSchedule();
	$schedule->workTaskId = $data->Row['id'];
	$schedule->user->ID = $data->Row['userId'];
	
	if(empty($data->Row['completedOn'])) {
		$schedule->scheduledOn = date('Y-m-d 00:00:00');
	} else {
		$completedOn = strtotime($data->Row['completedOn']);
		$period = $data->Row['period'] * 86400;
		
		$schedule->scheduledOn = date('Y-m-d 00:00:00', $completedOn + $period);
	}
	
	$schedule->add();
	
	$log[] = sprintf("Scheduling Work Task: %d, User: %d, Scheduled Date: %s", $schedule->workTaskId, $schedule->user->ID, $schedule->scheduledOn);
	
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