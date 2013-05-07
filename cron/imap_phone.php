
<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/AutomateReturn.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReturnReason.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'IMAP Phone';
$fileName = 'imap_phone.php';

## BEGIN SCRIPT
$sessionMax = 10;
$count = 0;

$mailbox = imap_open("{bltdirect.com/imap/novalidate-cert}INBOX", 'csphone@bltdirect.com', 'Teit7v') or die("Cannot connect: " . imap_last_error());
$check = imap_check($mailbox);

for($i=$check->Nmsgs; $i>0; $i--) {
	$header = imap_header($mailbox, $i);

	if(strcasecmp($header->fromaddress, 'csphone@bltdirect.com') == 0) {
		if(preg_match('/\[([\w\s]+)\] Order ([\d]+)/', $header->subject, $matches)) {
			$type = trim($matches[1]);
			$orderId = trim($matches[2]);

			$reason = new ReturnReason();

			if($reason->GetByTitle($type)) {
				$order = new Order();

				if($order->Get($orderId)) {
					$order->Customer->Get();
					$order->Customer->Contact->Get();

					AutomateReturn::processRequest($orderId, $reason->ID, $order->Customer->Contact->Person->Email);

					$log[] = sprintf("Processed Order: %s, Reason: %s", $orderId, $type);
				}
			}
		}
	}

	imap_delete($mailbox, $i);
}

imap_close($mailbox, CL_EXPUNGE);
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

echo implode('<br />', $log);

$GLOBALS['DBCONNECTION']->Close();

