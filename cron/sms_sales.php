<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/FastSMS.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'SMS Sales';
$fileName = 'sms_sales.php';

## BEGIN SCRIPT
if(Setting::GetValue('sms_sales_report') == 'true') {
	$smsProcessor = new SMSProcessor();

	if($smsProcessor->GetCredits()) {
		if($smsProcessor->Response['Response'][0] > 0) {
			$message = array();
			$connections = getSyncConnections();

			$total = array();
			$count = array();

			for($i = 0; $i < count($connections); $i++) {
				$data = new DataQuery(sprintf("SELECT Order_Prefix, COUNT(Order_ID) AS Count, SUM(Total) AS Total FROM orders WHERE Created_On BETWEEN '%s' AND '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND (Order_Prefix='T' OR Order_Prefix='W') GROUP BY Order_Prefix", date('Y-m-d 00-00-00', mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'))), date('Y-m-d 00-00-00', mktime(0, 0, 0, date('m'), date('d'), date('Y')))), $connections[$i]['Connection']);
				while ($data->Row) {
					$total[$data->Row['Order_Prefix']] += $data->Row['Total'];
					$count[$data->Row['Order_Prefix']] += $data->Row['Count'];

					$data->Next();
				}
				$data->Disconnect();
			}

			$message[] = sprintf('[YT] TO: %s', $count['T']);
			$message[] = sprintf('TT: £%s', number_format($total['T'], 2, '.', ''));
			$message[] = sprintf('WO: %s', $count['W']);
			$message[] = sprintf('WT: £%s', number_format($total['W'], 2, '.', ''));

			$total = array();
			$count = array();

			for($i = 0; $i < count($connections); $i++) {
				$data = new DataQuery(sprintf("SELECT Order_Prefix, COUNT(Order_ID) AS Count, SUM(Total) AS Total FROM orders WHERE Created_On BETWEEN '%s' AND NOW() AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND (Order_Prefix='T' OR Order_Prefix='W') GROUP BY Order_Prefix", date('Y-m-d 00-00-00', mktime(0, 0, 0, date('m'), date('d'), date('Y')))), $connections[$i]['Connection']);
				while ($data->Row) {
					$total[$data->Row['Order_Prefix']] += $data->Row['Total'];
					$count[$data->Row['Order_Prefix']] += $data->Row['Count'];

					$data->Next();
				}
				$data->Disconnect();
			}

			$message[] = sprintf('[TT] TO: %s', $count['T']);
			$message[] = sprintf('TT: £%s', number_format($total['T'], 2, '.', ''));
			$message[] = sprintf('WO: %s', $count['W']);
			$message[] = sprintf('WT: £%s', number_format($total['W'], 2, '.', ''));

			$smsProcessor->DestinationNumber = $GLOBALS['SMS_DESTINATION_NUMBER'];
			$smsProcessor->SourceNumber = $GLOBALS['SMS_DESTINATION_NUMBER'];
			$smsProcessor->Message = implode(', ', $message);

			if($smsProcessor->SendSMS()) {
				$log[] = sprintf("Sending - Destination: %s, Source: %s, Message: %s", $smsProcessor->DestinationNumber, $smsProcessor->SourceNumber, $smsProcessor->Message);
			} else {
				$log[] = sprintf("Failure - Destination: %s, Source: %s, Message: %s, Error: %s", $smsProcessor->DestinationNumber, $smsProcessor->SourceNumber, $smsProcessor->Message, $smsProcessor->Response['StatusDetail']);
			}

			if($smsProcessor->GetCredits()) {
				$log[] = sprintf("Status - Credits Remaining: %d", $smsProcessor->Response['Response'][0]);
			}
		} else {
			$mail = new htmlMimeMail5();
			$mail->setFrom($GLOBALS['EMAIL_FROM']);
			$mail->setSubject("SMS Alert");
			$mail->setText("You have zero SMS credits remaining. You must purchase additional credits to send messages.");
			$mail->send(array($GLOBALS['SMS_ALERT_EMAIL']));

			$log[] = sprintf("SMS Account Out Of Credits");
		}
	}
}
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