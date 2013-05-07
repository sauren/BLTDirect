<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/FindReplace.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Template.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'IMAP Failure';
$fileName = 'imap_failure.php';

## BEGIN SCRIPT
$invalidEmailAddresses = array();
$sessionMax = 50;
$count = 0;
$returnPath = 'bltdirect.com';

$mailbox = imap_open(sprintf("{%s/imap/novalidate-cert}INBOX", $GLOBALS['SMTP_HOST']), $GLOBALS['EMAIL_RETURN'], 'Teit7v') or die("Cannot connect: " . imap_last_error());
$check = imap_check($mailbox);

for($i=$check->Nmsgs; $i>0; $i--) {
	if($count < $sessionMax) {
		$header = imap_header($mailbox, $i);
		
		if(!empty($header->to[0]->host)) {
			if(stristr($GLOBALS['EMAIL_RETURN'], $header->to[0]->host)) {
				$mailboxItem = explode('.', strtolower($header->to[0]->mailbox));
				$invalidEmailAddress = array();

				if(isset($mailboxItem[0])) {
					if((strtolower(trim(sprintf('%s@%s', $mailboxItem[0], $header->to[0]->host))) == $GLOBALS['EMAIL_RETURN']) && (strtolower(trim(sprintf('%s@%s', $header->from[0]->mailbox, $header->from[0]->host))) == $GLOBALS['EMAIL_DAEMON']) && (stristr($header->subject, 'Undelivered Mail'))) {
						preg_match_all("/[\._a-zA-Z0-9-\']+@[\._a-zA-Z0-9-]+/i", imap_body($mailbox, $i), $matches);

						foreach($matches[0] as $emailAddress) {
							$domain = substr($emailAddress, (strpos($emailAddress, '@') + 1));

							if((stristr($domain, 'localhost') === false) && (stristr($domain, $returnPath) === false)) {
								$invalidEmailAddresses[$emailAddress] = $emailAddress;
								$invalidEmailAddress[$emailAddress] = $emailAddress;
							}
						}
					}
				}

				if(isset($mailboxItem[1])) {
					global $htmlmsg, $plainmsg, $charset, $attachments;
					
					$htmlmsg = '';
					$plainmsg = '';
					$charset = '';
					$attachments = '';
						
					$s = imap_fetchstructure($mailbox, $i);

					if(!isset($s->parts) || !$s->parts) {
						getMailPart($mailbox, $i, $s, 0);
					} else {
					    foreach($s->parts as $partNo=>$p) {
					        getMailPart($mailbox, $i, $p, $partNo+1);
						}
					}

					switch(strtolower($mailboxItem[1])) {
						case 'event':
							$id = isset($mailboxItem[2]) ? $mailboxItem[2] : 0;

							if($id > 0) {
								foreach($invalidEmailAddress as $invalidEmailAddress) {
									$data = new DataQuery(sprintf("SELECT j.Campaign_Contact_Event_ID FROM (SELECT cce.Campaign_Contact_Event_ID, p.Email FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID INNER JOIN contact AS c ON c.Contact_ID=cc.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE cce.Campaign_Event_ID=%d) j WHERE j.Email LIKE '%s'", $id, mysql_real_escape_string($invalidEmailAddress)));
									while($data->Row) {
										new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Email_Failed='Y' WHERE Campaign_Contact_Event_ID=%d AND Is_Email_Sent='Y'", $data->Row['Campaign_Contact_Event_ID']));

										$data->Next();
									}
									$data->Disconnect();
								}
							}

							break;

						case 'enquiry':
							break;

						case 'enquiry-line':
							break;
							
						case 'quote':
							$id = isset($mailboxItem[2]) ? $mailboxItem[2] : 0;

							if($id > 0) {
								foreach($invalidEmailAddress as $invalidEmailAddress) {
									$data = new DataQuery(sprintf("SELECT q.Quote_ID, q.Quote_Prefix, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name, p.Email FROM quote AS q INNER JOIN users AS u ON u.User_ID=q.Created_By INNER JOIN person AS p ON p.Person_ID=u.Person_ID WHERE q.Quote_ID=%d", $id));
									while($data->Row) {
										$findReplace = new FindReplace();
										$findReplace->Add('/\[QUOTE_ID\]/', $data->Row['Quote_ID']);
										$findReplace->Add('/\[EMAIL_ADDRESS\]/', $invalidEmailAddress);
										
										$html = $findReplace->Execute(Template::GetContent('email_quote_failure'));

										$findReplace = new FindReplace();
										$findReplace->Add('/\[BODY\]/', $html);
										$findReplace->Add('/\[NAME\]/', $data->Row['Name']);
										
										$emailBody = $findReplace->Execute(Template::GetContent('email_template_standard'));

										$queue = new EmailQueue();
										$queue->GetModuleID('quotes');
										$queue->Subject = sprintf("%s - Quote Failure [#%s%s]", $GLOBALS['COMPANY'], $data->Row['Quote_Prefix'], $data->Row['Quote_ID']);
										$queue->Body = $emailBody;
										$queue->ToAddress = implode(';', array($data->Row['Email'], 'adam@azexis.com'));
										$queue->Priority = 'H';
										$queue->Add();
										
										if(!empty($htmlmsg)) {
											$fileName = sprintf('failure_%d.html', $queue->ID);
											
											$fh = fopen($GLOBALS['TEMP_DIR_FS'].$fileName, 'w');
											
											if($fh) {
												fwrite($fh, $htmlmsg);
												fclose($fh);
												
												$queue->AddAttachment($GLOBALS['TEMP_DIR_FS'].$fileName, $GLOBALS['TEMP_DIR_WS'].$fileName);
											}
										}
										
										if(!empty($plainmsg)) {
											$fileName = sprintf('failure_%d.txt', $queue->ID);
											
											$fh = fopen($GLOBALS['TEMP_DIR_FS'].$fileName, 'w');
											
											if($fh) {
												fwrite($fh, $plainmsg);
												fclose($fh);
												
												$queue->AddAttachment($GLOBALS['TEMP_DIR_FS'].$fileName, $GLOBALS['TEMP_DIR_WS'].$fileName);
											}
										}

										$data->Next();
									}
									$data->Disconnect();
								}
							}

							break;
							
						case 'return':
							$id = isset($mailboxItem[2]) ? $mailboxItem[2] : 0;

							if($id > 0) {
								foreach($invalidEmailAddress as $invalidEmailAddress) {
									$data = new DataQuery(sprintf("SELECT r.Return_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name, p.Email FROM `return` AS r INNER JOIN users AS u ON u.User_ID=r.Created_By INNER JOIN person AS p ON p.Person_ID=u.Person_ID WHERE r.Return_ID=%d", $id));
									while($data->Row) {
										$findReplace = new FindReplace();
										$findReplace->Add('/\[RETURN_ID\]/', $data->Row['Return_ID']);
										$findReplace->Add('/\[EMAIL_ADDRESS\]/', $invalidEmailAddress);
										
										$html = $findReplace->Execute(Template::GetContent('email_return_failure'));

										$findReplace = new FindReplace();
										$findReplace->Add('/\[BODY\]/', $html);
										$findReplace->Add('/\[NAME\]/', $data->Row['Name']);
										
										$emailBody = $findReplace->Execute(Template::GetContent('email_template_standard'));

										$queue = new EmailQueue();
										$queue->GetModuleID('returns');
										$queue->Subject = sprintf("%s - Return Failure [#%s]", $GLOBALS['COMPANY'], $data->Row['Return_ID']);
										$queue->Body = $emailBody;
										$queue->ToAddress = implode(';', array($data->Row['Email'], 'adam@azexis.com'));
										$queue->Priority = 'H';
										$queue->Add();
										
										if(!empty($htmlmsg)) {
											$fileName = sprintf('failure_%d.html', $queue->ID);
											
											$fh = fopen($GLOBALS['TEMP_DIR_FS'].$fileName, 'w');
											
											if($fh) {
												fwrite($fh, $htmlmsg);
												fclose($fh);
												
												$queue->AddAttachment($GLOBALS['TEMP_DIR_FS'].$fileName, $GLOBALS['TEMP_DIR_WS'].$fileName);
											}
										}
										
										if(!empty($plainmsg)) {
											$fileName = sprintf('failure_%d.txt', $queue->ID);
											
											$fh = fopen($GLOBALS['TEMP_DIR_FS'].$fileName, 'w');
											
											if($fh) {
												fwrite($fh, $plainmsg);
												fclose($fh);
												
												$queue->AddAttachment($GLOBALS['TEMP_DIR_FS'].$fileName, $GLOBALS['TEMP_DIR_WS'].$fileName);
											}
										}

										$data->Next();
									}
									$data->Disconnect();
								}
							}

							break;
					}
				}
			}
		}
			
		imap_delete($mailbox, $i);

		$count++;
	} else {
		break;
	}
}

imap_close($mailbox, CL_EXPUNGE);

if(count($invalidEmailAddresses) > 0) {
	foreach($invalidEmailAddresses as $invalidEmailAddress) {
		$data = new DataQuery(sprintf("SELECT c.Contact_ID FROM contact AS c INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE c.Is_Email_Invalid='N' AND p.Email LIKE '%s'", mysql_real_escape_string($invalidEmailAddress)));
		while($data->Row) {
			new DataQuery(sprintf("UPDATE contact SET Is_Email_Invalid='Y', Modified_On=NOW(), Modified_By=0 WHERE Contact_ID=%d", $data->Row['Contact_ID']));

			$log[] = sprintf("Invalidating Email: %s", $invalidEmailAddress);

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