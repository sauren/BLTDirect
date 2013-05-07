<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Email Queue';
$fileName = 'email_queue.php';

## BEGIN SCRIPT
$form = new Form($_SERVER['PHP_SELF']);
$emailQueue = new EmailQueue();
$emails = array();
$rate = Setting::GetValue('email_queue_rate');
$priority = array('H','N','L');
$load = getLoad();
$loadThreshold = 5;

if($load < $loadThreshold) {
	if($rate > 0) {
		new DataQuery(sprintf("LOCK TABLES email_queue WRITE"));
		
		foreach($priority as $priorityItem) {
			if(count($emails) < $rate) {
				$data = new DataQuery(sprintf("SELECT * FROM email_queue WHERE Is_Sent='N' AND Priority='%s' AND (Send_After='0000-00-00 00:00:00' OR Send_After<='%s') LIMIT 0, %d", mysql_real_escape_string($priorityItem), date('Y-m-d H:i:s'), $rate - count($emails)));
				while($data->Row) {
					$emails[] = $data->Row;

					$emailQueue->SetSent($data->Row['Email_Queue_ID']);

					$data->Next();
				}
				$data->Disconnect();
			}
		}

		new DataQuery(sprintf("UNLOCK TABLES"));

		foreach($emails as $email) {
			$mail = new htmlMimeMail5();
			$mail->setFrom($email['From_Address']);
			$mail->setSubject($email['Subject']);
			$mail->setReturnPath($email['Return_Path']);

			if($email['Receipt'] == 'Y') {
				$mail->setReceipt($email['From_Address']);
			}

			switch($email['Type']) {
				case 'T':
					$mail->setText($email['Body']);
					break;
				case 'H':
					$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
					$mail->setHTML($email['Body']);
					break;
			}

			$data = new DataQuery(sprintf("SELECT File_Path FROM email_queue_attachment WHERE Email_Queue_ID=%d", mysql_real_escape_string($email['Email_Queue_ID'])));
			while($data->Row) {
				if(!empty($data->Row['File_Path']) && file_exists($data->Row['File_Path'])) {
					$mail->addAttachment(new fileAttachment($data->Row['File_Path']));
				}

				$data->Next();
			}
			$data->Disconnect();

			if($email['Is_Bcc'] == 'N') {
				$emailAddresses = explode(';', $email['To_Address']);

				foreach($emailAddresses as $emailAddress) {
					$emailAddress = trim($emailAddress);

					if((strlen($emailAddress) > 0) && preg_match(sprintf("/%s/", $form->RegularExp['email']), $emailAddress)) {
						$mail->send(array($emailAddress), 'smtp');

						$log[] = sprintf("Emailing: %s (%s)", $emailAddress, $email['Subject']);
					}
				}
			} else {
				$tempEmailAddresses = array();
				$emailAddresses = explode(';', $email['To_Address']);

				foreach($emailAddresses as $emailAddress) {
					$emailAddress = trim($emailAddress);

					if((strlen($emailAddress) > 0) && preg_match(sprintf("/%s/", $form->RegularExp['email']), $emailAddress)) {
						$tempEmailAddresses[$emailAddress] = $emailAddress;
					}
				}

				if(count($tempEmailAddresses) > 0) {
					$bcc = implode('; ', $tempEmailAddresses);

					$mail->setBcc(str_replace(';', ',', $bcc));
					$mail->send(array(), 'smtp');

					$log[] = sprintf("Emailing: %s (%s)", $bcc, $email['Subject']);
				}
			}
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

if($mailLog) {
	$mail = new htmlMimeMail5();
	$mail->setFrom('root@bltdirect.com');
	$mail->setSubject(sprintf("Cron [%s] <root@bltdirect.com> php /var/www/vhosts/bltdirect.com/httpdocs/cron/%s", $script, $fileName));
	$mail->setText(implode("\n", $log));
	$mail->send(array('adam@azexis.com'));
}

echo implode("<br />", $log);

$GLOBALS['DBCONNECTION']->Close();