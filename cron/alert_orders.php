<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Alert Orders';
$fileName = 'alert_orders.php';

## BEGIN SCRIPT
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Setting.php');

$hours = Setting::GetValue('alert_order_not_placed');

if(is_numeric($hours)) {
	$start = mktime(8, 30, 0, date('m'), date('d'), date('Y'));
	$end = mktime(22, 0, 0, date('m'), date('d'), date('Y'));

	if(($start < $end) && ($start <= time()) && (time() < $end)) {

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND (pm.Reference LIKE 'card' OR pm.Reference LIKE 'credit') WHERE (o.Order_Prefix='W' OR o.Order_Prefix='U') AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%f HOUR)", mysql_real_escape_string($hours)));

		while($data->Row) {
			if($data->Row['Count'] == 0) {
				$message = sprintf('There have been 0 website orders placed within the last %s hour(s) on a Credit/Debit Card or Credit Account on %s.', number_format($hours, 2, '.', ''), date('d/m/Y H:i'));
				
				$mail = new htmlMimeMail5();
				$mail->setFrom('root@bltdirect.com');
				$mail->setSubject(sprintf("Alert [%s]", $script));
				$mail->setText($message);
				$mail->send(array('adam@azexis.com', 'steve@bltdirect.com'));
				
				$smsProcessor = new SMSProcessor();

				if($smsProcessor->GetCredits()) {
					if($smsProcessor->Response['Response'][0] > 0) {
						$smsProcessor->DestinationNumber = $GLOBALS['SMS_DESTINATION_NUMBER'];
						$smsProcessor->SourceNumber = $GLOBALS['SMS_DESTINATION_NUMBER'];
						$smsProcessor->Message = $message;
						$smsProcessor->SendSMS();
					}
				}
			}
				
			$data->Next();
		}
		$data->Disconnect();
	}
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