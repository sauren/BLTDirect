<?php
ini_set('max_execution_time', '1800');
ini_set('memory_limit', '512M');
//chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleResponse.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Google Checkout';
$fileName = 'google_checkout.php';

## BEGIN SCRIPT
error_fatal(0);

$googleResponse = new GoogleResponse();

$data = new DataQuery(sprintf("SELECT Google_Checkout_ID, Data FROM google_checkout WHERE Is_Processed='N' ORDER BY Google_Checkout_ID ASC LIMIT 0, 20"));
while($data->Row) {
	new DataQuery(sprintf("UPDATE google_checkout SET Is_Processed='Y' WHERE Google_Checkout_ID=%d", $data->Row['Google_Checkout_ID']));

	if($googleResponse->ParseXml($data->Row['Data'])){
		$googleResponse->Execute();

		$log[] = sprintf("Parsing - Request: %s, Google ID: %s", $googleResponse->_Root, $googleResponse->_Data[$googleResponse->_Root]['google-order-number']['VALUE']);
	} else {
		$googleResponse->Error('Unable to Parse Google Response XML.');

		$log[] = sprintf("Parsing - Error: %s", $data->Row['Data']);
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

if($mailLog) {
	$mail = new htmlMimeMail5();
	$mail->setFrom('root@bltdirect.com');
	$mail->setSubject(sprintf("Cron [%s] <root@bltdirect.com> php /var/www/vhosts/bltdirect.com/httpdocs/cron/%s", $script, $fileName));
	$mail->setText(implode("\n", $log));
	$mail->send(array('adam@azexis.com'));
}

echo implode("<br />", $log);

$GLOBALS['DBCONNECTION']->Close();
?>