<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Campaign Email';
$fileName = 'campaign_email.php';

## BEGIN SCRIPT
$startTime = Setting::GetValue('campaign_email_start');
$endTime = Setting::GetValue('campaign_email_end');
$load = getLoad();
$loadThreshold = 5.0;

if($load < $loadThreshold) {
	if(!is_null($startTime) && !is_null($endTime)) {
	    $start = strtotime(sprintf('%s %s:00', date('Y-m-d'), $startTime));
		$end = strtotime(sprintf('%s %s:00', date('Y-m-d', ($endTime == '00:00') ? mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) : mktime(0, 0, 0, date('m'), date('d'), date('Y'))), $endTime));

		if((($start > $end) && (($start <= time()) || (time() < $end))) || (($start < $end) && (($start <= time()) && (time() < $end)))) {
			$queue = new EmailQueue();

			$data = new DataQuery(sprintf("SELECT Email_Queue_Module_ID FROM email_queue_module WHERE Reference LIKE 'campaigns' LIMIT 0, 1"));
			$queue->ModuleID = ($data->TotalRows > 0) ? $data->Row['Email_Queue_Module_ID'] : 0;
			$data->Disconnect();

			$contact = new Contact();
			$form = new Form($_SERVER['PHP_SELF']);
			$subject = Setting::GetValue('campaign_default_subject');

			$data = new DataQuery(sprintf("SELECT ce.Title, ce.Is_Bcc, ce.Maximum_Bcc_Count, ce.Scheduled, ce.Is_Dated, ce.Campaign_Event_ID, ce.Template, ce.Subject, ce.From_Address, ce.Queue_Rate FROM campaign_event AS ce WHERE ce.Type='E' AND ce.Is_Automatic='Y' AND ce.Queue_Rate>0"));
			while($data->Row) {
				$returnPath = explode('@', $GLOBALS['EMAIL_RETURN']);
				$returnPath = (count($returnPath) == 2) ? sprintf('%s.event.%d@%s', $returnPath[0], $data->Row['Campaign_Event_ID'], $returnPath[1]) : $GLOBALS['EMAIL_RETURN'];

				$scheduled = $data->Row['Scheduled'];

				if($data->Row['Is_Dated'] == 'Y') {
					if($scheduled <= time()) {
						$contacts = array();

						$data2 = new DataQuery(sprintf("SELECT cce.Campaign_Contact_Event_ID, cce.Campaign_Event_ID, cc.Campaign_Contact_ID, cc.Contact_ID, cc.Created_On, cc.Owned_By FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID INNER JOIN contact AS c ON c.Contact_ID=cc.Contact_ID WHERE cce.Campaign_Event_ID=%d AND cce.Is_Active='Y' AND cce.Is_Complete='N' LIMIT 0, %d", mysql_real_escape_string($data->Row['Campaign_Event_ID']), mysql_real_escape_string($data->Row['Queue_Rate'])));
						while($data2->Row) {
							$contacts[] = $data2->Row;

							$data2->Next();
						}
						$data2->Disconnect();

						if($data->Row['Is_Bcc'] == 'Y') {
							$emailAddresses = array();

							foreach($contacts as $contactItem) {
								$contact = new Contact();

								if($contact->Get($contactItem['Contact_ID'])) {
									if((strlen($contact->Person->Email) > 0) && preg_match(sprintf("/%s/", $form->RegularExp['email']), $contact->Person->Email)) {
										if($contact->OnMailingList != 'N') {
											$emailAddresses[$contact->Person->Email] = $contact->Person->Email;

											if($contact->IsEmailInvalid == 'Y') {
												new DataQuery(sprintf("UPDATE contact SET Is_Email_Invalid='N', Modified_On=NOW(), Modified_By=0 WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
											}

											new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Email_Sent='Y', Modified_On=NOW(), Modified_By=0 WHERE Campaign_Contact_Event_ID=%d", mysql_real_escape_string($contactItem['Campaign_Contact_Event_ID'])));
										}
									}
								}

								new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Complete='Y', Modified_On=NOW(), Modified_By=0 WHERE Campaign_Contact_Event_ID=%d", mysql_real_escape_string($contactItem['Campaign_Contact_Event_ID'])));
							}

							if(count($emailAddresses) > 0) {
								$findReplace = new FindReplace();
								$findReplace->Add('/\[USERNAME\]/', Setting::GetValue('default_username'));
								$findReplace->Add('/\[USEREMAIL\]/', Setting::GetValue('default_useremail'));
								$findReplace->Add('/\[USERPHONE\]/', Setting::GetValue('default_userphone'));
								$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
								$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));

								$html = $findReplace->Execute($data->Row['Template']);

								foreach($emailAddresses as $emailAddress) {
									$emailAddresses[] = $emailAddress;
									unset($emailAddresses[$emailAddress]);
								}

								for($i=0; $i<ceil(count($emailAddresses) / $data->Row['Maximum_Bcc_Count']); $i++) {
									$tempAddresses = array();

									for($j=0; $j<$data->Row['Maximum_Bcc_Count']; $j++) {
										if(isset($emailAddresses[$j+($i*2)])) {
											$tempAddresses[] = $emailAddresses[$j+($i*2)];
										}
									}

									if(count($tempAddresses) > 0) {
										$emailAddress = trim(implode('; ', $tempAddresses));

										if(!empty($data->Row['From_Address'])) {
											$queue->FromAddress = $data->Row['From_Address'];
										}

										$queue->ReturnPath = $returnPath;
										$queue->Subject = (strlen(trim($data->Row['Subject'])) > 0) ? trim($data->Row['Subject']) : $subject;
										$queue->Body = sprintf("<html><body>%s</body></html>", $html);
										$queue->ToAddress = $emailAddress;
										$queue->Priority = 'L';
										$queue->Type = 'H';
										$queue->IsBcc = 'Y';
										$queue->Add();

										$log[] = sprintf("Mass Emailing: %s (%s)", $emailAddress, $queue->Subject);
									}
								}
							}

						} else {
							foreach($contacts as $contactItem) {
								$contact = new Contact();

								if($contact->Get($contactItem['Contact_ID'])) {
									if((strlen($contact->Person->Email) > 0) && preg_match(sprintf("/%s/", $form->RegularExp['email']), $contact->Person->Email)) {
										if($contact->OnMailingList != 'N') {
											$data3 = new DataQuery(sprintf("SELECT Password FROM customer WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
											if($data3->TotalRows > 0) {
												$cipher = new Cipher($data3->Row['Password']);
												$cipher->Decrypt();

												$passwordText = $cipher->Value;
											} else {
												$passwordText = 'Not a customer';
											}
											$data3->Disconnect();

											$ownerFound = false;

											$owner = new User();
											$owner->ID = $contactItem['Owned_By'];

											if($owner->Get()) {
												$ownerFound = true;
											}

											$email = (strlen($contact->Person->Email) > 0) ? $contact->Person->Email : (($contact->Parent->ID > 0) ? $contact->Parent->Organisation->Email : '');

											$cypher = new Cipher($email);
											$cypher->Encrypt();

											$window = '<p>&nbsp;</p>';
											$window .= '<table width="100%">';
											$window .= '<tr>';
											$window .= '<td width="3%">&nbsp;</td>';
											$window .= sprintf('<td width="97%%">%s<br />%s</td>', sprintf("%s%s %s %s", ($contact->Parent->ID > 0) ? sprintf('%s<br />', $contact->Parent->Organisation->Name) : '', $contact->Person->Title, $contact->Person->Name, $contact->Person->LastName), $contact->Person->Address->GetLongString());
											$window .= '</tr>';
											$window .= '</table>';

											$windowAddress = '<p>&nbsp;</p>';
											$windowAddress .= '<table width="100%">';
											$windowAddress .= '<tr>';
											$windowAddress .= '<td width="3%">&nbsp;</td>';
											$windowAddress .= sprintf('<td width="97%%">%s%s</td>', sprintf($contact->Parent->ID > 0) ? sprintf('%s<br />', $contact->Parent->Organisation->Name) : '', $contact->Person->Address->GetLongString());
											$windowAddress .= '</tr>';
											$windowAddress .= '</table>';

											$windowDate = '<table width="100%">';
											$windowDate .= '<tr>';
											$windowDate .= '<td width="3%">&nbsp;</td>';
											$windowDate .= sprintf('<td width="97%%">%s</td>', date('jS F Y'));
											$windowDate .= '</tr>';
											$windowDate .= '</table>';
											$windowDate .= '<br />';
											$windowDate .= '<table width="100%">';
											$windowDate .= '<tr>';
											$windowDate .= '<td width="3%">&nbsp;</td>';
											$windowDate .= sprintf('<td width="97%%">%s<br />%s</td>', sprintf("%s%s %s %s", ($contact->Parent->ID > 0) ? sprintf('%s<br />', $contact->Parent->Organisation->Name) : '', $contact->Person->Title, $contact->Person->Name, $contact->Person->LastName), $contact->Person->Address->GetLongString());
											$windowDate .= '</tr>';
											$windowDate .= '</table>';

											$findReplace = new FindReplace();
											$findReplace->Add('/\[WINDOW\]/', $window);
											$findReplace->Add('/\[WINDOWADDRESS\]/', $windowAddress);
											$findReplace->Add('/\[WINDOWDATE\]/', $windowDate);
											$findReplace->Add('/\[COMPANY\]/', ($contact->Parent->ID > 0) ? $contact->Parent->Organisation->Name : trim(sprintf('%s %s %s', $contact->Person->Title, $contact->Person->Name, $contact->Person->LastName)));
											$findReplace->Add('/\[CUSTOMER\]/', sprintf("%s%s %s %s", ($contact->Parent->ID > 0) ? sprintf('%s<br />', $contact->Parent->Organisation->Name) : '', $contact->Person->Title, $contact->Person->Name, $contact->Person->LastName));
											$findReplace->Add('/\[TITLE\]/', $contact->Person->Title);
											$findReplace->Add('/\[FIRSTNAME\]/', $contact->Person->Name);
											$findReplace->Add('/\[LASTNAME\]/', $contact->Person->LastName);
											$findReplace->Add('/\[FULLNAME\]/', trim(str_replace("   ", " ", str_replace("  ", " ", sprintf("%s %s %s", $contact->Person->Title, $contact->Person->Name, $contact->Person->LastName)))));
											$findReplace->Add('/\[FAX\]/', $contact->Person->Fax);
											$findReplace->Add('/\[PHONE\]/', sprintf('%s', (strlen(trim($contact->Person->Phone1)) > 0) ? $contact->Person->Phone1 : $contact->Person->Phone2));
											$findReplace->Add('/\[ADDRESS\]/', $contact->Person->Address->GetLongString());
											$findReplace->Add('/\[EMAIL\]/', $email);
											$findReplace->Add('/\[EMAILENCRYPTED\]/', urlencode(base64_encode($cypher->Value)));
											$findReplace->Add('/\[PASSWORD\]/', $passwordText);

											if($ownerFound) {
												$findReplace->Add('/\[USERNAME\]/', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName));
												$findReplace->Add('/\[USEREMAIL\]/', $owner->Username);
												$findReplace->Add('/\[USERPHONE\]/', sprintf('%s', (strlen(trim($owner->Person->Phone1)) > 0) ? $owner->Person->Phone1 : $owner->Person->Phone2));
												$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName), $owner->Person->Phone1, $owner->Username));
												$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName), $owner->Person->Phone1, $owner->Username));
											} else {
												$findReplace->Add('/\[USERNAME\]/', Setting::GetValue('default_username'));
												$findReplace->Add('/\[USEREMAIL\]/', Setting::GetValue('default_useremail'));
												$findReplace->Add('/\[USERPHONE\]/', Setting::GetValue('default_userphone'));
												$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
												$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
											}

											$entity = serialize(array('Type' => 'Campaign', 'CampaignEvent' => $contactItem['Campaign_Event_ID'], 'CampaignContact' => $contactItem['Campaign_Contact_ID']));

											$cypher = new Cipher($entity);
											$cypher->Encrypt();

											$entity = base64_encode($cypher->Value);

											$findReplace->Add('/\[SUBJECT\]/', (strlen(trim($data->Row['Subject'])) > 0) ? trim($data->Row['Subject']) : $subject);
											$findReplace->Add('/\[ENTITY\]/', $entity);

											$html = $findReplace->Execute($data->Row['Template']);

											if(!empty($data->Row['From_Address'])) {
												$queue->FromAddress = $data->Row['From_Address'];
											}

											$queue->ReturnPath = $returnPath;
											$queue->Subject = (strlen(trim($data->Row['Subject'])) > 0) ? trim($data->Row['Subject']) : $subject;
											$queue->Body = sprintf('<html><body>%s<img src="%strack.gif?entity=%s" width="1" height="1" border="0" /></body></html>', $html, $GLOBALS['HTTP_SERVER'], $entity);
											$queue->ToAddress = $contact->Person->Email;
											$queue->Priority = 'L';
											$queue->Type = 'H';
											$queue->Add();

											if($contact->IsEmailInvalid == 'Y') {
												new DataQuery(sprintf("UPDATE contact SET Is_Email_Invalid='N', Modified_On=NOW(), Modified_By=0 WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
											}

											$log[] = sprintf("Individual Emailing: %s (%s)", $queue->ToAddress, $queue->Subject);

											new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Email_Sent='Y', Modified_On=NOW(), Modified_By=0 WHERE Campaign_Contact_Event_ID=%d", mysql_real_escape_string($contactItem['Campaign_Contact_Event_ID'])));
										}
									}
								}

								new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Complete='Y', Modified_On=NOW(), Modified_By=0 WHERE Campaign_Contact_Event_ID=%d", mysql_real_escape_string($contactItem['Campaign_Contact_Event_ID'])));
							}
						}
					}
				} else {
					$contacts = array();

					$data2 = new DataQuery(sprintf("SELECT cce.Campaign_Contact_Event_ID, cce.Campaign_Event_ID, cc.Campaign_Contact_ID, cc.Contact_ID, cc.Created_On, cc.Owned_By FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID INNER JOIN contact AS c ON c.Contact_ID=cc.Contact_ID WHERE cce.Campaign_Event_ID=%d AND cce.Is_Active='Y' AND cce.Is_Complete='N' ORDER BY cc.Created_On ASC, cc.Campaign_Contact_ID ASC LIMIT 0, %d", $data->Row['Campaign_Event_ID'], $data->Row['Queue_Rate']));
					while($data2->Row) {
						$contacts[] = $data2->Row;

						$data2->Next();
					}
					$data2->Disconnect();

					if($data->Row['Is_Bcc'] == 'Y') {
						$emailAddresses = array();

						foreach($contacts as $contactItem) {
							$period = periodToArray($data->Row['Scheduled']);
							$year = substr($contactItem['Created_On'], 0, 4);
							$month = substr($contactItem['Created_On'], 5, 2);
							$day = substr($contactItem['Created_On'], 8, 2);

							if(date('Y-m-d 00:00:00') >= date('Y-m-d 00:00:00', mktime(0, 0, 0, $month+$period['month'], $day+$period['day'], $year))) {
								$contact = new Contact();

								if($contact->Get($contactItem['Contact_ID'])) {
									if($contact->OnMailingList != 'N') {
										$emailAddresses[$contact->Person->Email] = $contact->Person->Email;

										if($contact->IsEmailInvalid == 'Y') {
											new DataQuery(sprintf("UPDATE contact SET Is_Email_Invalid='N', Modified_On=NOW(), Modified_By=0 WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
										}

										new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Email_Sent='Y', Modified_On=NOW(), Modified_By=0 WHERE Campaign_Contact_Event_ID=%d", mysql_real_escape_string($contactItem['Campaign_Contact_Event_ID'])));
									}
								}

								new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Complete='Y', Modified_On=NOW(), Modified_By=0 WHERE Campaign_Contact_Event_ID=%d", mysql_real_escape_string($contactItem['Campaign_Contact_Event_ID'])));
							}
						}

						if(count($emailAddresses) > 0) {
							$findReplace = new FindReplace();
							$findReplace->Add('/\[USERNAME\]/', Setting::GetValue('default_username'));
							$findReplace->Add('/\[USEREMAIL\]/', Setting::GetValue('default_useremail'));
							$findReplace->Add('/\[USERPHONE\]/', Setting::GetValue('default_userphone'));
							$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
							$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));

							$html = $findReplace->Execute($data->Row['Template']);

							foreach($emailAddresses as $emailAddress) {
								$emailAddresses[] = $emailAddress;
								unset($emailAddresses[$emailAddress]);
							}

							for($i=0; $i<ceil(count($emailAddresses) / $data->Row['Maximum_Bcc_Count']); $i++) {
								$tempAddresses = array();

								for($j=0; $j<$data->Row['Maximum_Bcc_Count']; $j++) {
									if(isset($emailAddresses[$j+($i*2)])) {
										$tempAddresses[] = $emailAddresses[$j+($i*2)];
									}
								}

								if(count($tempAddresses) > 0) {
									$emailAddress = trim(implode('; ', $tempAddresses));

									if(!empty($data->Row['From_Address'])) {
										$queue->FromAddress = $data->Row['From_Address'];
									}

									$queue->ReturnPath = $returnPath;
									$queue->Subject = (strlen(trim($data->Row['Subject'])) > 0) ? trim($data->Row['Subject']) : $subject;
									$queue->Body = sprintf("<html><body>%s</body></html>", $html);
									$queue->ToAddress = $emailAddress;
									$queue->Priority = 'N';
									$queue->Type = 'H';
									$queue->IsBcc = 'Y';
									$queue->Add();

									$log[] = sprintf("Mass Emailing: %s (%s)", $emailAddress, $queue->Subject);
								}
							}
						}
					} else {
						foreach($contacts as $contactItem) {
							$period = periodToArray($data->Row['Scheduled']);
							$year = substr($contactItem['Created_On'], 0, 4);
							$month = substr($contactItem['Created_On'], 5, 2);
							$day = substr($contactItem['Created_On'], 8, 2);

							if(date('Y-m-d 00:00:00') >= date('Y-m-d 00:00:00', mktime(0, 0, 0, $month+$period['month'], $day+$period['day'], $year))) {
								$contact = new Contact();

								if($contact->Get($contactItem['Contact_ID'])) {
									if((strlen($contact->Person->Email) > 0) && preg_match(sprintf("/%s/", $form->RegularExp['email']), mysql_real_escape_string($contact->Person->Email))) {
										if($contact->OnMailingList != 'N') {
											$data3 = new DataQuery(sprintf("SELECT Password FROM customer WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
											if($data3->TotalRows > 0) {
												$cipher = new Cipher($data3->Row['Password']);
												$cipher->Decrypt();

												$passwordText = $cipher->Value;
											} else {
												$passwordText = 'Not a customer';
											}
											$data3->Disconnect();

											$ownerFound = false;

											$owner = new User();
											$owner->ID = $contactItem['Owned_By'];

											if($owner->Get()) {
												$ownerFound = true;
											}

											$email = (strlen($contact->Person->Email) > 0) ? $contact->Person->Email : (($contact->Parent->ID > 0) ? $contact->Parent->Organisation->Email : '');

											$cypher = new Cipher($email);
											$cypher->Encrypt();

											$window = '<p>&nbsp;</p>';
											$window .= '<table width="100%">';
											$window .= '<tr>';
											$window .= '<td width="3%">&nbsp;</td>';
											$window .= sprintf('<td width="97%%">%s<br />%s</td>', sprintf("%s%s %s %s", ($contact->Parent->ID > 0) ? sprintf('%s<br />', $contact->Parent->Organisation->Name) : '', $contact->Person->Title, $contact->Person->Name, $contact->Person->LastName), $contact->Person->Address->GetLongString());
											$window .= '</tr>';
											$window .= '</table>';

											$windowAddress = '<p>&nbsp;</p>';
											$windowAddress .= '<table width="100%">';
											$windowAddress .= '<tr>';
											$windowAddress .= '<td width="3%">&nbsp;</td>';
											$windowAddress .= sprintf('<td width="97%%">%s%s</td>', sprintf($contact->Parent->ID > 0) ? sprintf('%s<br />', $contact->Parent->Organisation->Name) : '', $contact->Person->Address->GetLongString());
											$windowAddress .= '</tr>';
											$windowAddress .= '</table>';

											$windowDate = '<table width="100%">';
											$windowDate .= '<tr>';
											$windowDate .= '<td width="3%">&nbsp;</td>';
											$windowDate .= sprintf('<td width="97%%">%s</td>', date('jS F Y'));
											$windowDate .= '</tr>';
											$windowDate .= '</table>';
											$windowDate .= '<br />';
											$windowDate .= '<table width="100%">';
											$windowDate .= '<tr>';
											$windowDate .= '<td width="3%">&nbsp;</td>';
											$windowDate .= sprintf('<td width="97%%">%s<br />%s</td>', sprintf("%s%s %s %s", ($contact->Parent->ID > 0) ? sprintf('%s<br />', $contact->Parent->Organisation->Name) : '', $contact->Person->Title, $contact->Person->Name, $contact->Person->LastName), $contact->Person->Address->GetLongString());
											$windowDate .= '</tr>';
											$windowDate .= '</table>';

											$findReplace = new FindReplace();
											$findReplace->Add('/\[WINDOW\]/', $window);
											$findReplace->Add('/\[WINDOWADDRESS\]/', $windowAddress);
											$findReplace->Add('/\[WINDOWDATE\]/', $windowDate);
											$findReplace->Add('/\[COMPANY\]/', ($contact->Parent->ID > 0) ? $contact->Parent->Organisation->Name : trim(sprintf('%s %s %s', $contact->Person->Title, $contact->Person->Name, $contact->Person->LastName)));
											$findReplace->Add('/\[CUSTOMER\]/', sprintf("%s%s %s %s", ($contact->Parent->ID > 0) ? sprintf('%s<br />', $contact->Parent->Organisation->Name) : '', $contact->Person->Title, $contact->Person->Name, $contact->Person->LastName));
											$findReplace->Add('/\[TITLE\]/', $contact->Person->Title);
											$findReplace->Add('/\[FIRSTNAME\]/', $contact->Person->Name);
											$findReplace->Add('/\[LASTNAME\]/', $contact->Person->LastName);
											$findReplace->Add('/\[FULLNAME\]/', trim(str_replace("   ", " ", str_replace("  ", " ", sprintf("%s %s %s", $contact->Person->Title, $contact->Person->Name, $contact->Person->LastName)))));
											$findReplace->Add('/\[FAX\]/', $contact->Person->Fax);
											$findReplace->Add('/\[PHONE\]/', sprintf('%s', (strlen(trim($contact->Person->Phone1)) > 0) ? $contact->Person->Phone1 : $contact->Person->Phone2));
											$findReplace->Add('/\[ADDRESS\]/', $contact->Person->Address->GetLongString());
											$findReplace->Add('/\[EMAIL\]/', $email);
											$findReplace->Add('/\[EMAILENCRYPTED\]/', urlencode(base64_encode($cypher->Value)));
											$findReplace->Add('/\[PASSWORD\]/', $passwordText);

											if($ownerFound) {
												$findReplace->Add('/\[USERNAME\]/', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName));
												$findReplace->Add('/\[USEREMAIL\]/', $owner->Username);
												$findReplace->Add('/\[USERPHONE\]/', sprintf('%s', (strlen(trim($owner->Person->Phone1)) > 0) ? $owner->Person->Phone1 : $owner->Person->Phone2));
												$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName), $owner->Person->Phone1, $owner->Username));
												$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName), $owner->Person->Phone1, $owner->Username));
											} else {
												$findReplace->Add('/\[USERNAME\]/', Setting::GetValue('default_username'));
												$findReplace->Add('/\[USEREMAIL\]/', Setting::GetValue('default_useremail'));
												$findReplace->Add('/\[USERPHONE\]/', Setting::GetValue('default_userphone'));
												$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
												$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
											}

											$entity = serialize(array('Type' => 'Campaign', 'CampaignEvent' => $contactItem['Campaign_Event_ID'], 'CampaignContact' => $contactItem['Campaign_Contact_ID']));

											$cypher = new Cipher($entity);
											$cypher->Encrypt();

											$entity = base64_encode($cypher->Value);

											$findReplace->Add('/\[SUBJECT\]/', (strlen(trim($data->Row['Subject'])) > 0) ? trim($data->Row['Subject']) : $subject);
											$findReplace->Add('/\[ENTITY\]/', $entity);

											$html = $findReplace->Execute($data->Row['Template']);

											if(!empty($data->Row['From_Address'])) {
												$queue->FromAddress = $data->Row['From_Address'];
											}

											$queue->ReturnPath = $returnPath;
											$queue->Subject = (strlen(trim($data->Row['Subject'])) > 0) ? trim($data->Row['Subject']) : $subject;
											$queue->Body = sprintf('<html><body>%s<img src="%strack.gif?entity=%s" width="1" height="1" border="0" /></body></html>', $html, $GLOBALS['HTTP_SERVER'], $entity);
											$queue->ToAddress = $contact->Person->Email;
											$queue->Priority = 'N';
											$queue->Type = 'H';
											$queue->Add();

											if($contact->IsEmailInvalid == 'Y') {
												new DataQuery(sprintf("UPDATE contact SET Is_Email_Invalid='N', Modified_On=NOW(), Modified_By=0 WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
											}

											$log[] = sprintf("Individual Emailing: %s (%s)", $queue->ToAddress, $queue->Subject);

											new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Email_Sent='Y', Modified_On=NOW(), Modified_By=0 WHERE Campaign_Contact_Event_ID=%d", mysql_real_escape_string($contactItem['Campaign_Contact_Event_ID'])));
										}
									}
								}

								new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Complete='Y', Modified_On=NOW(), Modified_By=0 WHERE Campaign_Contact_Event_ID=%d", mysql_real_escape_string($contactItem['Campaign_Contact_Event_ID'])));
							}
						}
					}
				}

				$data2 = new DataQuery(sprintf("SELECT COUNT(cce.Campaign_Contact_Event_ID) AS Count FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID INNER JOIN contact AS c ON c.Contact_ID=cc.Contact_ID WHERE cce.Campaign_Event_ID=%d AND cce.Is_Active='Y' AND cce.Is_Complete='N'", $data->Row['Campaign_Event_ID']));
				if($data2->Row['Count'] == 0) {
					new DataQuery(sprintf("UPDATE campaign_event SET Is_Automatic='N' WHERE Campaign_Event_ID=%d", $data->Row['Campaign_Event_ID']));

					$log[] = sprintf("Disabling Automatic Event: %s [#%d]", $data->Row['Title'], $data->Row['Campaign_Event_ID']);
				}
				$data2->Disconnect();

				$data->Next();
			}
			$data->Disconnect();
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