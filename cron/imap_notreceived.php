<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/OrderNote.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'IMAP Not Received';
$fileName = 'imap_notreceived.php';
exit;
## BEGIN SCRIPT
function getpart($mbox,$mid,$p,$partno) {
    global $htmlmsg,$plainmsg,$charset,$attachments;

    $data = ($partno) ? imap_fetchbody($mbox,$mid,$partno) : imap_body($mbox,$mid);

    if ($p->encoding==4)
        $data = quoted_printable_decode($data);
    elseif ($p->encoding==3)
        $data = base64_decode($data);

    $params = array();

    if ($p->parameters)
        foreach ($p->parameters as $x)
            $params[ strtolower( $x->attribute ) ] = $x->value;
    if ($p->dparameters)
        foreach ($p->dparameters as $x)
            $params[ strtolower( $x->attribute ) ] = $x->value;

    if ($params['filename'] || $params['name']) {
        $filename = ($params['filename'])? $params['filename'] : $params['name'];
        $attachments[$filename] = $data;
    }

    elseif ($p->type==0 && $data) {
        if (strtolower($p->subtype)=='plain')
            $plainmsg .= trim($data) ."\n\n";
        else
            $htmlmsg .= $data . '<br /><br />';
        $charset = $params['charset'];
    }

    elseif ($p->type==2 && $data) {
        $plainmsg .= trim($data) ."\n\n";
    }

    if ($p->parts) {
        foreach ($p->parts as $partno0=>$p2)
            getpart($mbox,$mid,$p2,$partno.'.'.($partno0+1));
    }
}

$htmlmsg = '';
$plainmsg = '';
$charset = '';
$attachments = '';
		
global $htmlmsg,$plainmsg,$charset,$attachments;

$mailbox = imap_open("{localhost:993/imap/ssl/novalidate-cert}INBOX", "not-received@bltdirect.com", "Teit7v") or die("Cannot connect: " . imap_last_error());
$check = imap_check($mailbox);

for($i = $check->Nmsgs; $i > 0; $i--) {
	$header = imap_header($mailbox, $i);

	$email = strtolower(trim(sprintf('%s@%s', $header->from[0]->mailbox, $header->from[0]->host)));
	$subject = trim($header->subject);
	$matchSubject = (stripos($subject, $GLOBALS['COMPANY']) !== false) ? substr($subject, stripos($subject, $GLOBALS['COMPANY'])) : $subject;

	$pattern = '/^([A-Za-z0-9\s:-_]*)\[#([A-Za-z]*)([0-9]*)\]$/';

	if(preg_match($pattern, $matchSubject, $matches)) {
		$customerArr = array();

		$htmlmsg = '';
		$plainmsg = '';
		$charset = '';
		$attachments = '';
			
        $s = imap_fetchstructure($mailbox, $i);

		if(!$s->parts) {
			getpart($mailbox, $i, $s, 0);
		} else {
        	foreach($s->parts as $partNo=>$p) {
            	getpart($mailbox, $i, $p, $partNo+1);
			}
		}

		$message = $htmlmsg;

		if(strlen(trim($message)) > 0) {
			$data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE Username LIKE '%s'", mysql_real_escape_string($email)));
			while($data->Row) {
				$customerArr[] = $data->Row['Customer_ID'];

				$data->Next();
			}
			$data->Disconnect();

			if(count($customerArr) > 0) {
				$customerStr = implode(', ', $customerArr);
				$subjectLine = trim(strtolower($matches[1]));

				$data = new DataQuery(sprintf("SELECT Order_ID FROM orders WHERE Order_ID=%d AND Is_Not_Received='Y' AND Customer_ID IN (%s)", mysql_real_escape_string($matches[3]), mysql_real_escape_string($customerStr)));
				if($data->TotalRows > 0) {
					$order = new Order($data->Row['Order_ID']);
                    $order->PaymentMethod->Get();
					$order->Customer->Get();
					$order->Customer->Contact->Get();

					$order->IsNotReceived = 'N';
					$order->Update();

                    if($order->PaymentMethod->Reference == 'google') {
						$order->Card = new Card();
						$order->CustomID = '';
					}

					$order->IsCustomShipping = 'Y';
					$order->TotalShipping = 0;
					$order->OrderedOn = date('Y-m-d H:i:s');
					$order->CustomID = '';
					$order->Status = 'Unread';
					$order->Prefix = 'N';
					$order->Referrer = '';
					$order->PaymentMethod->GetByReference('foc');
					$order->ParentID = $order->ID;
					$order->Add();

					$order->Recalculate();

					$log[] = sprintf("Adding Order: #%s%s", $order->Prefix, $order->ID);

					$note = new OrderNote();
					$note->OrderID = $order->ID;
					$note->Subject = 'Not Received Email Confirmation';
					$note->Message = $message;
					$note->IsPublic = 'N';
					$note->IsAlert = 'Y';
					$note->Add();
				}
				$data->Disconnect();
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

if ($mailLog) {
	$mail = new htmlMimeMail5();
	$mail->setFrom('root@bltdirect.com');
	$mail->setSubject(sprintf("Cron [%s] <root@bltdirect.com> php /var/www/vhosts/bltdirect.com/httpdocs/cron/%s", $script, $fileName));
	$mail->setText(implode("\n", $log));
	$mail->send(array('adam@azexis.com'));
}

echo implode("<br />", $log);

$GLOBALS['DBCONNECTION']->Close();