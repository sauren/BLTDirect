<?php
ini_set('max_execution_time', '1800');
ini_set('memory_limit', '128M');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Enquiry.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/EnquiryLine.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/User.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'IMAP No-Reply';
$fileName = 'imap_noreply.php';

## BEGIN SCRIPT
function getMessagePart($mbox,$mid,$p,$partno) {
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
            getMessagePart($mbox,$mid,$p2,$partno.'.'.($partno0+1));
    }
}

$htmlmsg = '';
$plainmsg = '';
$charset = '';
$attachments = '';
		
global $htmlmsg,$plainmsg,$charset,$attachments;

$data12 = new DataQuery(sprintf("SELECT User_ID FROM users WHERE Secondary_Mailbox<>''"));
while($data12->Row) {
	$user = new User($data12->Row['User_ID']);
	
	$mailbox = imap_open("{localhost:993/imap/ssl/novalidate-cert}INBOX", $user->SecondaryMailbox, $user->GetSecondaryMailboxPassword()) or die("Cannot connect: " . imap_last_error());
	$check = imap_check($mailbox);

	for($i = $check->Nmsgs; $i > 0; $i--) {
		$header = imap_header($mailbox, $i);

		$email = strtolower(trim(sprintf('%s@%s', $header->from[0]->mailbox, $header->from[0]->host)));
		$subject = trim($header->subject);
		$matchSubject = (stripos($subject, $GLOBALS['COMPANY']) !== false) ? substr($subject, stripos($subject, $GLOBALS['COMPANY'])) : $subject;

		$pattern = '/^([A-Za-z0-9\s:-_]*)\[#([A-Za-z]*)([0-9]*)\]$/';

		if (preg_match($pattern, $matchSubject, $matches)) {
			$sql = '';
			$typeId = 0;
			$customerArr = array();
			$enquiryReply = false;

			$htmlmsg = '';
			$plainmsg = '';
			$charset = '';
			$attachments = '';

			$s = imap_fetchstructure($mailbox, $i);

			if(!$s->parts) {
				getMessagePart($mailbox, $i, $s, 0);
			} else {
			    foreach($s->parts as $partNo=>$p) {
			        getMessagePart($mailbox, $i, $p, $partNo+1);
				}
			}

			$message = !empty($htmlmsg) ? $htmlmsg : $plainmsg;

			if (strlen(trim($message)) > 0) {
				$data = new DataQuery(sprintf("SELECT Enquiry_Type_ID FROM enquiry_type WHERE Developer_Key LIKE 'customerservices'"));
				if ($data->TotalRows > 0) {
					$typeId = $data->Row['Enquiry_Type_ID'];
				}
				$data->Disconnect();

				if ($typeId == 0) {
					$data = new DataQuery(sprintf("SELECT Enquiry_Type_ID FROM enquiry_type ORDER BY Enquiry_Type_ID ASC LIMIT 0, 1"));
					if ($data->TotalRows > 0) {
						$typeId = $data->Row['Enquiry_Type_ID'];
					}
					$data->Disconnect();
				}

				$data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE Username LIKE '%s'", mysql_real_escape_string($email)));
				while ($data->Row) {
					$customerArr[] = $data->Row['Customer_ID'];

					$data->Next();
				}
				$data->Disconnect();

				if (count($customerArr) > 0) {
					$customerStr = sprintf('(%s)', implode(' OR Customer_ID=', $customerArr));
					$subjectLine = trim(strtolower($matches[1]));

					if ((stristr($subjectLine, 'payment declined')) || (stristr($subjectLine, 'order confirmation')) || (stristr($subjectLine, 'sample confirmation')) || (stristr($subjectLine, 'order despatch'))) {
						$sql = sprintf("SELECT Customer_ID FROM orders WHERE Order_ID=%d AND %s LIMIT 0, 1", mysql_real_escape_string($matches[3]), mysql_real_escape_string($customerStr));

					} elseif ((stristr($subjectLine, 'enquiry closed')) || (stristr($subjectLine, 'enquiry confirmation')) || (stristr($subjectLine, 'enquiry requesting closure')) || (stristr($subjectLine, 'enquiry notification')) || (stristr($subjectLine, 'enquiry response'))) {
						$sql = sprintf("SELECT Customer_ID FROM enquiry WHERE Enquiry_ID=%d AND %s LIMIT 0, 1", mysql_real_escape_string($matches[3]), mysql_real_escape_string($customerStr));
						$enquiryReply = true;

					} elseif ((stristr($subjectLine, 'return refused')) || (stristr($subjectLine, 'return confirmation')) || (stristr($subjectLine, 'return received')) || (stristr($subjectLine, 'return resolved'))) {
						$sql = sprintf("SELECT Customer_ID FROM `return` WHERE Return_ID=%d AND %s LIMIT 0, 1", mysql_real_escape_string($matches[3]), mysql_real_escape_string($customerStr));

					} elseif ((stristr($subjectLine, 'proforma confirmation'))) {
						$sql = sprintf("SELECT Customer_ID FROM proforma WHERE ProForma_ID=%d AND %s LIMIT 0, 1", mysql_real_escape_string($matches[3]), mysql_real_escape_string($customerStr));

					} elseif ((stristr($subjectLine, 'quote confirmation'))) {
						$sql = sprintf("SELECT Customer_ID FROM quote WHERE Quote_ID=%d AND %s LIMIT 0, 1", mysql_real_escape_string($matches[3]), mysql_real_escape_string($customerStr));

					} elseif ((stristr($subjectLine, 'invoice'))) {
						$sql = sprintf("SELECT Customer_ID FROM invoice WHERE Invoice_ID=%d AND %s LIMIT 0, 1", mysql_real_escape_string($matches[3]), mysql_real_escape_string($customerStr));

					} elseif ((stristr($subjectLine, 'credit note'))) {
						$sql = sprintf("SELECT o.Customer_ID FROM credit_note AS cn INNER JOIN orders AS o ON o.Order_ID=cn.Order_ID WHERE cn.Credit_Note_ID=%d AND %s LIMIT 0, 1", mysql_real_escape_string($matches[3]), mysql_real_escape_string($customerStr));
					}

					if (strlen($sql) > 0) {
						$data = new DataQuery($sql);
						if ($data->TotalRows > 0) {
							if ($enquiryReply) {
								$enquiry = new Enquiry($matches[3]);
								$enquiry->IsPendingAction = 'Y';
								$enquiry->IsRequestingClosure = 'N';
								$enquiry->Status = 'Open';
								$enquiry->Update();

								$log[] = sprintf("Updating Enquiry: #%s%s, Subject: %s, E-mail Address: %s", $enquiry->Prefix, $enquiry->ID, $enquiry->Subject, $email);
							} else {
								$enquiry = new Enquiry();
								$enquiry->Prefix = 'E';
								$enquiry->Subject = $subject;
								$enquiry->Status = 'Unread';
								$enquiry->Type->ID = $typeId;
								$enquiry->Customer->ID = $data->Row['Customer_ID'];
								$enquiry->Add();

								$log[] = sprintf("Adding Enquiry: #%s%s, Subject: %s, E-mail Address: %s", $enquiry->Prefix, $enquiry->ID, $enquiry->Subject, $email);
							}

							$enquiryLine = new EnquiryLine();
							$enquiryLine->IsCustomerMessage = 'Y';
							$enquiryLine->Enquiry->ID = $enquiry->ID;
							$enquiryLine->Message = $message;
							$enquiryLine->Add();
						}
						$data->Disconnect();
					}
				}
			}
		}

		imap_delete($mailbox, $i);
	}

	imap_close($mailbox, CL_EXPUNGE);
	
	$data12->Next();	
}
$data12->Disconnect();
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