<?php
ini_set('max_execution_time', '1800');
ini_set('memory_limit', '512M');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cron.php');

$cron = new Cron();
$cron->scriptName = 'Cache Reports';
$cron->scriptFileName = 'cache_reports.php';
$cron->mailLogLevel = Cron::LOG_LEVEL_WARNING;

## BEGIN SCRIPT
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Report.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ReportCache.php');

function cacheReport($reportScript) {
	include($reportScript);
}

$data = new DataQuery(sprintf("SELECT r.ReportID, r.Script, r.`Interval`, r.Threshold, rc.ReportCacheID, MAX(rc.CreatedOn) AS LastCachedOn FROM report AS r LEFT JOIN report_cache AS rc ON rc.ReportID=r.ReportID AND rc.IsOnDemand='N' WHERE r.`Interval`>0 GROUP BY r.ReportID HAVING (DATE(NOW())>=ADDDATE(DATE(LastCachedOn), INTERVAL r.`Interval` DAY) OR rc.ReportCacheID IS NULL)"));
while($data->Row) {
	if(date('G') >= $data->Row['Threshold']) {
		$reportScript = sprintf('%slib/reports/%s', $GLOBALS['DIR_WS_ADMIN'], $data->Row['Script']);
		
		if(file_exists($reportScript)) {
			cacheReport($reportScript);
			
			$cron->log(sprintf('Cached Report: #%d, Script: %s, Interval: %d day(s)', $data->Row['ReportID'], $data->Row['Script'], $data->Row['Interval']), Cron::LOG_LEVEL_INFO);
		} else {
			$cron->log(sprintf('Report Not Found: #%d, Script: %s, Interval: %d day(s)', $data->Row['ReportID'], $data->Row['Script'], $data->Row['Interval']), Cron::LOG_LEVEL_ERROR);
		}
	}
	
	$data->Next();
}
$data->Disconnect();
## END SCRIPT

$cron->execute();
$cron->output();

$GLOBALS['DBCONNECTION']->Close();