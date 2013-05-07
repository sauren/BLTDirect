<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cron.php');

$cron = new Cron();
$cron->scriptName = 'Google Base API';
$cron->scriptFileName = 'google_base_api.php';
$cron->mailLogLevel = Cron::LOG_LEVEL_ERROR;

## BEGIN SCRIPT
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-base/classes/GoogleBaseRequest.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Setting.php');

$request = new GoogleBaseRequest();
$request->login();

if($request->isAuthenticated()) {
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product"));
	$productCount = $data->Row['Count'];
	$data->Disconnect();

	$start = Setting::GetValue('google_base_start');
	$limit = Setting::GetValue('google_base_limit');

	if(!is_null($start) && !is_null($limit)) {
		if($start == 0) {
			$start = $productCount;
		}

		$start -= $limit;

		if($start < 0) {
			$limit += $start;
			$start = 0;
		}

		$setting = new Setting();

		if($setting->GetByProperty('google_base_start')) {
			$setting->Value = $start;
			$setting->Update();
		}

		if($request->insertBatchItems($start, $limit)) {
			$cron->log(sprintf('Inserted product batch %s-%s.', $start, $start + $limit), Cron::LOG_LEVEL_INFO);
		} else {
			$cron->log(sprintf('Failed inserting product batch %s-%s.', $start, $start + $limit), Cron::LOG_LEVEL_ERROR);
		}
	} else {
		$cron->log('Could not find Google Base setting information.', Cron::LOG_LEVEL_WARNING);
	}
}
## END SCRIPT

$cron->execute();
$cron->output();

$GLOBALS['DBCONNECTION']->Close();