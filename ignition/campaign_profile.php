<?php
ini_set('max_execution_time', '7200');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Campaign.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignEvent.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContactEvent.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

$session->Secure(3);

$limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 50;
$offset = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : 0;

$campaign = new Campaign();

if($action == 'requestcatalogue' && isset($_REQUEST['cid'])){
    $contact = new Contact();

    if($contact->Get($_REQUEST['cid'])) {
	    $contact->IsCatalogueRequested = 'Y';
	    $contact->Update();
    }
    redirectTo('?id=' . $_REQUEST['id'] . '&limit='.$limit.'&offset='.$offset);
}

if($action == 'cancelrequestcatalogue' && isset($_REQUEST['cid'])){
    $contact = new Contact();
	
    if($contact->Get($_REQUEST['cid'])) {
	    $contact->IsCatalogueRequested = 'N';
	    $contact->Update();
    }

    redirectTo('?id=' . $_REQUEST['id'] . '&limit='.$limit.'&offset='.$offset);
}


if(!$campaign->Get($_REQUEST['id'])) {
	redirect(sprintf("Location: campaigns.php"));
}

UserRecent::Record(sprintf('[#%d] Campaign Profile (%s)', $campaign->ID, $campaign->Title), sprintf('campaign_profile.php?id=%d', $campaign->ID));

$data = new DataQuery(sprintf("SELECT cc.Contact_ID, cc.Campaign_Contact_ID FROM campaign_contact AS cc LEFT JOIN contact AS c ON c.Contact_ID=cc.Contact_ID WHERE c.Contact_ID IS NULL AND cc.Campaign_ID=%d", mysql_real_escape_string($campaign->ID)));
while($data->Row) {
	$data2 = new CampaignContact();
	$data2->DeleteByContact($data->Row['Contact_ID']);

	$data3 = new CampaignContactEvent();
	$data3->DeleteByCampaignContact($data->Row['Campaign_Contact_ID']);

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT COUNT(*) AS Recipients FROM campaign_contact AS cc INNER JOIN contact AS c ON c.Contact_ID=cc.Contact_ID WHERE cc.Campaign_ID=%d", mysql_real_escape_string($campaign->ID)));
$totalRecipients = $data->Row['Recipients'];
$data->Disconnect();

if($offset < 0) {
	redirect(sprintf("Location: %s?id=%d&limit=%d", $_SERVER['PHP_SELF'], $campaign->ID, $limit));
}

$totalPages = floor($totalRecipients / $limit) - ((($totalRecipients % $limit) == 0) ? 1 : 0) + 1;

if($totalRecipients > 0) {
	if(($totalRecipients % $limit) == 0) {
		if($offset >= floor($totalRecipients / $limit)) {
			redirect(sprintf("Location: %s?id=%d&limit=%d", $_SERVER['PHP_SELF'], $campaign->ID, $limit));
		}
	} else {
		if($offset > floor($totalRecipients / $limit)) {
			redirect(sprintf("Location: %s?id=%d&limit=%d", $_SERVER['PHP_SELF'], $campaign->ID, $limit));
		}
	}
}

$user = new User($campaign->CreatedBy);
$userStr = trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName));

if(strlen($userStr) == 0) {
	$userStr = '<em>Unknown</em>';
}

$events = array();
$datedEvents = array();
$timedEvents = array();
$recipients = array();

$data = new DataQuery(sprintf("SELECT * FROM campaign_event WHERE Campaign_ID=%d ORDER BY Is_Dated, Scheduled ASC", mysql_real_escape_string($campaign->ID)));
while($data->Row) {
	$event = new CampaignEvent();
	$event->Campaign->ID = $campaign->ID;
	$event->CreatedOn = $data->Row['Created_On'];
	$event->ID = $data->Row['Campaign_Event_ID'];
	$event->IsAutomatic = $data->Row['Is_Automatic'];
	$event->IsAutomaticDisabling = $data->Row['Is_Automatic_Disabling'];
	$event->IsDefault = $data->Row['Is_Default'];
	$event->IsDated = $data->Row['Is_Dated'];
	$event->Scheduled = $data->Row['Scheduled'];
	$event->Subject = $data->Row['Subject'];
	$event->Template = $data->Row['Template'];
	$event->Title = $data->Row['Title'];
	$event->Type = $data->Row['Type'];

	if($event->IsDated == 'Y') {
		$datedEvents[] = $event;
	} else {
		$timedEvents[] = $event;
	}

	$events[] = $event;

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT cc.Campaign_Contact_ID, cc.Created_On, cc.Owned_By, c.Contact_ID, c.On_Mailing_List, c.Is_Email_Invalid, p.Name_First, p.Name_Last, p.Email, o.Org_Name FROM campaign_contact AS cc INNER JOIN contact AS c ON c.Contact_ID=cc.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID WHERE cc.Campaign_ID=%d ORDER BY cc.Created_On DESC, cc.Campaign_Contact_ID ASC LIMIT %d, %d", mysql_real_escape_string($campaign->ID), $offset*$limit, mysql_real_escape_string($limit)));
while($data->Row) {
	$contact = new CampaignContact();
	$contact->ID = $data->Row['Campaign_Contact_ID'];
	$contact->CreatedOn = $data->Row['Created_On'];
	$contact->OwnedBy = $data->Row['Owned_By'];
	$contact->Campaign = $campaign->ID;
	$contact->Contact->ID = $data->Row['Contact_ID'];
	$contact->Contact->IsEmailInvalid = $data->Row['Is_Email_Invalid'];
	$contact->Contact->OnMailingList = $data->Row['On_Mailing_List'];
	$contact->Contact->Person->Name = $data->Row['Name_First'];
	$contact->Contact->Person->LastName = $data->Row['Name_Last'];
	$contact->Contact->Person->Email = $data->Row['Email'];
	$contact->Contact->Parent = new Contact();
	$contact->Contact->Parent->Organisation->Name = $data->Row['Org_Name'];
	$contact->CampaignContactEvent = array();
	$contact->Data = $data->Row;

	$data2 = new DataQuery(sprintf("SELECT * FROM campaign_contact_event WHERE Campaign_Contact_ID=%d", $contact->ID));
	while($data2->Row) {
		$contactEvent = new CampaignContactEvent();
		$contactEvent->ID = $data2->Row['Campaign_Contact_Event_ID'];
		$contactEvent->CampaignContact->ID = $data2->Row['Campaign_Contact_ID'];
		$contactEvent->CampaignEvent->ID = $data2->Row['Campaign_Event_ID'];
		$contactEvent->IsComplete = $data2->Row['Is_Complete'];
		$contactEvent->IsActive = $data2->Row['Is_Active'];
		$contactEvent->IsEmailSent = $data2->Row['Is_Email_Sent'];
		$contactEvent->IsEmailFailed = $data2->Row['Is_Email_Failed'];
		$contactEvent->IsEmailViewed = $data2->Row['Is_Email_Viewed'];
		$contactEvent->IsEmailFollowed = $data2->Row['Is_Email_Followed'];
		$contactEvent->IsPhoneScheduled = $data2->Row['Is_Phone_Scheduled'];
		$contactEvent->CreatedOn = $data2->Row['Created_On'];
		$contactEvent->CreatedBy = $data2->Row['Created_By'];
		$contactEvent->ModifiedOn = $data2->Row['Modified_On'];
		$contactEvent->ModifiedBy = $data2->Row['Modified_By'];

		$contact->CampaignContactEvent[$contactEvent->CampaignEvent->ID] = $contactEvent;

		$data2->Next();
	}
	$data2->Disconnect();

	$recipients[] = $contact;

	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Campaign ID', 'hidden', $campaign->ID, 'numeric_unsigned', 1, 11);
$form->AddField('limit', 'Limit', 'hidden', $limit, 'numeric_unsigned', 1, 11);
$form->AddField('offset', 'Offset', 'hidden', $offset, 'numeric_unsigned', 1, 11);
$form->AddField('refine', 'Refine', 'select', '', 'anything', 1, 128, false, 'onchange="checkAdditionalFields(this);"');
$form->AddOption('refine', '', '');
$form->AddOption('refine', 'PendingQuotes', 'by Pending Quotes');
$form->AddOption('refine', 'SuccessfulQuotes', 'by Successful Quotes');
$form->AddOption('refine', 'Orders', 'by Orders');
$form->AddOption('refine', 'NoEmailAddress', 'by No Email Address');
$form->AddOption('refine', 'DuplicateEmailAddress', 'by Duplicate Email Address');
$form->AddOption('refine', 'LastContacted', 'by Last Contacted');
$form->AddField('refinestart', 'Start Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this,this);" onfocus="scwShow(this,this);"');
$form->AddField('refineend', 'End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this,this);" onfocus="scwShow(this,this);"');
$form->AddField('refinetitle', 'New Title', 'text', 'Copy of ' . $campaign->Title, 'anything', 1, 255, true, 'disabled="disabled"');
$form->AddField('refinecopy', 'Copy Campaign', 'checkbox', 'N', 'boolean', 1, 1, false, 'onclick="toggleTitle(this);"');

for($i=0; $i<count($recipients); $i++) {
	$recipient = $recipients[$i];

	foreach($events as $event) {
		if(!isset($recipient->CampaignContactEvent[$event->ID])) {
			$contactEvent = new CampaignContactEvent();
			$contactEvent->CampaignEvent->ID = $event->ID;
			$contactEvent->CampaignContact->ID = $recipient->ID;
			$contactEvent->IsActive = $event->IsDefault;
			$contactEvent->Add();

			$recipients[$i]->CampaignContactEvent[$event->ID] = $contactEvent;
		}

		$form->AddField(sprintf('active_%d_%d', $recipient->ID, $event->ID), 'Active', 'checkbox', $recipient->CampaignContactEvent[$event->ID]->IsActive, 'boolean', 1, 1, false);
	}
}

foreach($events as $event) {
	$eventStats = array();
	$eventStats['Y'] = 0;
	$eventStats['N'] = 0;

	foreach($recipients as $recipient) {
		$eventStats[$recipient->CampaignContactEvent[$event->ID]->IsActive]++;
	}

	if($eventStats['Y'] == 0) {
		$default = 'N';
	} elseif($eventStats['N'] == 0) {
		$default = 'Y';
	} else {
		$default = $event->IsDefault;
	}

	$form->AddField(sprintf('activate_%d', $event->ID), 'Activate', 'checkbox', $default, 'boolean', 1, 1, false, sprintf('onclick="checkUncheckEvent(this, \'%d\');"', $event->ID));
}

if($action == 'complete') {
	$qs = '';

	if(isset($_REQUEST['event']) && isset($_REQUEST['contact'])) {
		$data = new DataQuery(sprintf("SELECT cce.Campaign_Contact_Event_ID, cc.Campaign_Contact_ID, cc.Contact_ID, cc.Owned_By FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID WHERE cce.Campaign_Event_ID=%d AND cc.Contact_ID=%d", mysql_real_escape_string($_REQUEST['event']), mysql_real_escape_string($_REQUEST['contact'])));
		if($data->TotalRows > 0) {
			$campaignEvent = new CampaignEvent($_REQUEST['event']);

			$user = new User($session->UserID);

			$contact = new Contact($data->Row['Contact_ID']);

			$data2 = new DataQuery(sprintf("SELECT Password FROM customer WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
			if($data2->TotalRows > 0) {
				$cipher = new Cipher($data2->Row['Password']);
				$cipher->Decrypt();

				$passwordText = $cipher->Value;
			} else {
				$passwordText = 'Not a customer';
			}
			$data2->Disconnect();

			$ownerFound = false;

			$owner = new User();
			$owner->ID = $data->Row['Owned_By'];

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
			$findReplace->Add('/\[PHONE\]/', $contact->Person->Phone);
			$findReplace->Add('/\[ADDRESS\]/', $contact->Person->Address->GetLongString());
			$findReplace->Add('/\[USERNAME\]/', sprintf("%s %s", $user->Person->Name, $user->Person->LastName));
			$findReplace->Add('/\[USEREMAIL\]/', $user->Username);
			$findReplace->Add('/\[USERPHONE\]/', sprintf('%s', (strlen(trim($user->Person->Phone1)) > 0) ? $user->Person->Phone1 : $user->Person->Phone2));
			$findReplace->Add('/\[EMAIL\]/', $email);
			$findReplace->Add('/\[EMAILENCRYPTED\]/', urlencode(base64_encode($cypher->Value)));
			$findReplace->Add('/\[PASSWORD\]/', $passwordText);
			$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $user->Person->Name, $user->Person->LastName), $user->Person->Phone1, $user->Username));

			if($ownerFound) {
				$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName), $owner->Person->Phone1, $owner->Username));
			} else {
				$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
			}

			if($campaignEvent->Type == 'E') {
				$subject = (strlen(trim($campaignEvent->Subject)) > 0) ? trim($campaignEvent->Subject) : Setting::GetValue('campaign_default_subject');
				$entity = serialize(array('Type' => 'Campaign', 'CampaignEvent' => $campaignEvent->ID, 'CampaignContact' => $data->Row['Campaign_Contact_ID']));

				$cypher = new Cipher($entity);
				$cypher->Encrypt();

				$entity = base64_encode($cypher->Value);

				$findReplace->Add('/\[SUBJECT\]/', $subject);
				$findReplace->Add('/\[ENTITY\]/', $entity);
			}

			$html = $findReplace->Execute($campaignEvent->Template);

			switch($campaignEvent->Type) {
				case 'E':
					$returnPath = explode('@', $GLOBALS['EMAIL_RETURN']);
					$returnPath = (count($returnPath) == 2) ? sprintf('%s.event.%d@%s', $returnPath[0], $campaignEvent->ID, $returnPath[1]) : $GLOBALS['EMAIL_RETURN'];

					$campaignContactEvent = new CampaignContactEvent($data->Row['Campaign_Contact_Event_ID']);

					if((strlen($contact->Person->Email) > 0) && preg_match(sprintf("/%s/", $form->RegularExp['email']), $contact->Person->Email)) {
						if($contact->OnMailingList != 'N') {
							$queue = new EmailQueue();

							$data8 = new DataQuery(sprintf("SELECT Email_Queue_Module_ID FROM email_queue_module WHERE Reference LIKE 'campaigns' LIMIT 0, 1"));
							$queue->ModuleID = ($data8->TotalRows > 0) ? $data8->Row['Email_Queue_Module_ID'] : 0;
							$data8->Disconnect();

							if(!empty($campaignEvent->FromAddress)) {
								$queue->FromAddress = $campaignEvent->FromAddress;
							}

							$queue->ReturnPath = $returnPath;
							$queue->Subject = (strlen(trim($campaignEvent->Subject)) > 0) ? trim($campaignEvent->Subject) : Setting::GetValue('campaign_default_subject');
							$queue->Body = sprintf('<html><body>%s<img src="%strack.gif?entity=%s" width="1" height="1" border="0" /></body></html>', $html, $GLOBALS['HTTP_SERVER'], $entity);
							$queue->Priority = 'H';
							$queue->Type = 'H';
							$queue->ToAddress = $contact->Person->Email;
							$queue->Add();

							if($contact->IsEmailInvalid == 'Y') {
								new DataQuery(sprintf("UPDATE contact SET Is_Email_Invalid='N' WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
							}

							$campaignContactEvent->IsEmailSent = 'Y';
						}
					}

					$campaignContactEvent->IsComplete = 'Y';
					$campaignContactEvent->Update();

					break;
				case 'L':
				case 'F':
				case 'P':
					$pdf = new CampaignPDF();
					$pdf->HasLetterHead = ($campaignEvent->Type == 'P') ? false : true;
					$pdf->AddPage();
					$pdf->WriteHTML(sprintf("&#012;%s", $html));

					$fileName = $pdf->Output(sprintf('campaign_%d_%d_%d.pdf', $campaign->ID, $_REQUEST['event'], $_REQUEST['contact']), 'F', false, $GLOBALS['CAMPAIGN_DOCUMENT_DIR_FS']);

					$qs .= sprintf('&popup=%s', $fileName);

					$campaignContactEvent = new CampaignContactEvent($data->Row['Campaign_Contact_Event_ID']);
					$campaignContactEvent->IsComplete = 'Y';
					$campaignContactEvent->Update();
					break;
			}
		}
		$data->Disconnect();

	} elseif(isset($_REQUEST['event']) && isset($_REQUEST['repeat'])) {

		$campaignEvent = new CampaignEvent($_REQUEST['event']);
		$user = new User($session->UserID);
		$subject = (strlen(trim($campaignEvent->Subject)) > 0) ? trim($campaignEvent->Subject) : Setting::GetValue('campaign_default_subject');

		switch($campaignEvent->Type) {
			case 'E':
				$returnPath = explode('@', $GLOBALS['EMAIL_RETURN']);
				$returnPath = (count($returnPath) == 2) ? sprintf('%s.event.%d@%s', $returnPath[0], $campaignEvent->ID, $returnPath[1]) : $GLOBALS['EMAIL_RETURN'];

				if($campaignEvent->IsBcc == 'Y') {
					$emailAddresses = array();

					$data = new DataQuery(sprintf("SELECT cce.Campaign_Contact_Event_ID, cc.Contact_ID, cc.Owned_By FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID WHERE cce.Campaign_Event_ID=%d AND cce.Is_Complete='%s'", mysql_real_escape_string($_REQUEST['event']), ($_REQUEST['repeat'] == 'true') ? 'Y' : 'N'));
					while($data->Row) {
						$contact = new Contact($data->Row['Contact_ID']);

						$campaignContactEvent = new CampaignContactEvent($data->Row['Campaign_Contact_Event_ID']);

						if((strlen($contact->Person->Email) > 0) && preg_match(sprintf("/%s/", $form->RegularExp['email']), $contact->Person->Email)) {
							if($contact->OnMailingList != 'N') {
								$emailAddresses[$contact->Person->Email] = $contact->Person->Email;

								if($contact->IsEmailInvalid == 'Y') {
									new DataQuery(sprintf("UPDATE contact SET Is_Email_Invalid='N' WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
								}

								$campaignContactEvent->IsEmailSent = 'Y';
							}
						}

						$campaignContactEvent->IsComplete = 'Y';
						$campaignContactEvent->Update();

						$data->Next();
					}
					$data->Disconnect();

					if(count($emailAddresses) > 0) {
						$data8 = new DataQuery(sprintf("SELECT Email_Queue_Module_ID FROM email_queue_module WHERE Reference LIKE 'campaigns' LIMIT 0, 1"));
						$moduleID = ($data8->TotalRows > 0) ? $data8->Row['Email_Queue_Module_ID'] : 0;
						$data8->Disconnect();

						$findReplace = new FindReplace();
						$findReplace->Add('/\[USERNAME\]/', Setting::GetValue('default_username'));
						$findReplace->Add('/\[USEREMAIL\]/', Setting::GetValue('default_useremail'));
						$findReplace->Add('/\[USERPHONE\]/', Setting::GetValue('default_userphone'));
						$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
						$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));

						$html = $findReplace->Execute($campaignEvent->Template);

						foreach($emailAddresses as $emailAddress) {
							$emailAddresses[] = $emailAddress;
							unset($emailAddresses[$emailAddress]);
						}

						for($i=0; $i<ceil(count($emailAddresses) / $campaignEvent->MaximumBccCount); $i++) {
							$tempAddresses = array();

							for($j=0; $j<$campaignEvent->MaximumBccCount; $j++) {
								if(isset($emailAddresses[$j+($i*$campaignEvent->MaximumBccCount)])) {
									$tempAddresses[] = $emailAddresses[$j+($i*$campaignEvent->MaximumBccCount)];
								}
							}

							if(count($tempAddresses) > 0) {
								$emailAddress = trim(implode('; ', $tempAddresses));

								$queue = new EmailQueue();

								if(!empty($campaignEvent->FromAddress)) {
									$queue->FromAddress = $campaignEvent->FromAddress;
								}

								$queue->ModuleID = $moduleID;
								$queue->ReturnPath = $returnPath;
								$queue->Body = sprintf("<html><body>%s</body></html>", $html);
								$queue->ToAddress = $emailAddress;
								$queue->Priority = 'N';
								$queue->Subject = (strlen(trim($campaignEvent->Subject)) > 0) ? trim($campaignEvent->Subject) : Setting::GetValue('campaign_default_subject');
								$queue->Type = 'H';
								$queue->IsBcc = 'Y';
								$queue->Add();
							}
						}
					}
				} else {
					$options = array();

					if(isset($_REQUEST['single']) && ($_REQUEST['single'] == 'true')) {
						foreach($recipients as $recipient) {
							if($recipient->CampaignContactEvent[$_REQUEST['event']]->IsComplete == (($_REQUEST['repeat'] == 'true') ? 'Y' : 'N')) {
								$option = $recipient->Data;
								$option['Campaign_Contact_Event_ID'] = $recipient->CampaignContactEvent[$_REQUEST['event']]->ID;

								$options[] = $option;
							}
						}
					} else {
						$data = new DataQuery(sprintf("SELECT cce.Campaign_Contact_Event_ID, cc.Campaign_Contact_ID, cc.Contact_ID, cc.Owned_By FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID WHERE cce.Campaign_Event_ID=%d AND cce.Is_Complete='%s'", mysql_real_escape_string($_REQUEST['event']), ($_REQUEST['repeat'] == 'true') ? 'Y' : 'N'));
						while($data->Row) {
							$options[] = $data->Row;

							$data->Next();
						}
						$data->Disconnect();
					}

					foreach($options as $option) {
						$contact = new Contact($option['Contact_ID']);

						$campaignContactEvent = new CampaignContactEvent($option['Campaign_Contact_Event_ID']);

						if((strlen($contact->Person->Email) > 0) && preg_match(sprintf("/%s/", $form->RegularExp['email']), $contact->Person->Email)) {
							$data2 = new DataQuery(sprintf("SELECT Password FROM customer WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
							if($data2->TotalRows > 0) {
								$cipher = new Cipher($data2->Row['Password']);
								$cipher->Decrypt();

								$passwordText = $cipher->Value;
							} else {
								$passwordText = 'Not a customer';
							}
							$data2->Disconnect();

							$ownerFound = false;

							$owner = new User();
							$owner->ID = $option['Owned_By'];

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
							$findReplace->Add('/\[USERNAME\]/', sprintf("%s %s", $user->Person->Name, $user->Person->LastName));
							$findReplace->Add('/\[USEREMAIL\]/', $user->Username);
							$findReplace->Add('/\[USERPHONE\]/', sprintf('%s', (strlen(trim($user->Person->Phone1)) > 0) ? $user->Person->Phone1 : $user->Person->Phone2));
							$findReplace->Add('/\[EMAIL\]/', $email);
							$findReplace->Add('/\[EMAILENCRYPTED\]/', urlencode(base64_encode($cypher->Value)));
							$findReplace->Add('/\[PASSWORD\]/', $passwordText);
							$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $user->Person->Name, $user->Person->LastName), $user->Person->Phone1, $user->Username));

							if($ownerFound) {
								$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName), $owner->Person->Phone1, $owner->Username));
							} else {
								$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
							}

							if($campaignEvent->Type == 'E') {
								$entity = serialize(array('Type' => 'Campaign', 'CampaignEvent' => $campaignEvent->ID, 'CampaignContact' => $option['Campaign_Contact_ID']));

								$cypher = new Cipher($entity);
								$cypher->Encrypt();

								$entity = base64_encode($cypher->Value);

								$findReplace->Add('/\[SUBJECT\]/', $subject);
								$findReplace->Add('/\[ENTITY\]/', $entity);
							}

							$html = $findReplace->Execute($campaignEvent->Template);

							if($contact->OnMailingList != 'N') {
								$queue = new EmailQueue();

								$data8 = new DataQuery(sprintf("SELECT Email_Queue_Module_ID FROM email_queue_module WHERE Reference LIKE 'campaigns' LIMIT 0, 1"));
								$queue->ModuleID = ($data8->TotalRows > 0) ? $data8->Row['Email_Queue_Module_ID'] : 0;
								$data8->Disconnect();

								if(!empty($campaignEvent->FromAddress)) {
									$queue->FromAddress = $campaignEvent->FromAddress;
								}

								$queue->ReturnPath = $returnPath;
								$queue->Subject = $subject;
								$queue->Body = sprintf('<html><body>%s<img src="%strack.gif?entity=%s" width="1" height="1" border="0" /></body></html>', $html, $GLOBALS['HTTP_SERVER'], $entity);
								$queue->Priority = 'N';
								$queue->Type = 'H';
								$queue->ToAddress = $contact->Person->Email;
								$queue->Add();

								if($contact->IsEmailInvalid == 'Y') {
									new DataQuery(sprintf("UPDATE contact SET Is_Email_Invalid='N' WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
								}

								$campaignContactEvent->IsEmailSent = 'Y';
							}
						}

						$campaignContactEvent->IsComplete = 'Y';
						$campaignContactEvent->Update();
					}
				}
				break;

			case 'L':
			case 'F':
			case 'P':
				$contactStr = '';
				$filter = ($_REQUEST['repeat'] == 'true') ? 'Y' : 'N';

				$pdf = new CampaignPDF();
				$pdf->HasLetterHead = ($campaignEvent->Type == 'P') ? false : true;

				foreach($recipients as $recipient) {
					if($filter == $recipient->CampaignContactEvent[$campaignEvent->ID]->IsComplete) {
						$contact = $recipient->Contact;
						$contact->Get();

						$contactStr .= $contact->ID;

						$data2 = new DataQuery(sprintf("SELECT Password FROM customer WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
						if($data2->TotalRows > 0) {
							$cipher = new Cipher($data2->Row['Password']);
							$cipher->Decrypt();

							$passwordText = $cipher->Value;
						} else {
							$passwordText = 'Not a customer';
						}
						$data2->Disconnect();

						$ownerFound = false;

						$data2 = new DataQuery(sprintf("SELECT Owned_By FROM campaign_contact WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
						if($data2->TotalRows > 0) {
							$owner = new User();
							$owner->ID = $data->Row['Owned_By'];

							if($owner->Get()) {
								$ownerFound = true;
							}
						}
						$data2->Disconnect();

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
						$findReplace->Add('/\[USERNAME\]/', sprintf("%s %s", $user->Person->Name, $user->Person->LastName));
						$findReplace->Add('/\[USEREMAIL\]/', $user->Username);
						$findReplace->Add('/\[USERPHONE\]/', sprintf('%s', (strlen(trim($user->Person->Phone1)) > 0) ? $user->Person->Phone1 : $user->Person->Phone2));
						$findReplace->Add('/\[EMAIL\]/', (strlen($contact->Person->Email) > 0) ? $contact->Person->Email : (($contact->Parent->ID > 0) ? $contact->Parent->Organisation->Email : ''));
						$findReplace->Add('/\[PASSWORD\]/', $passwordText);
						$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $user->Person->Name, $user->Person->LastName), $user->Person->Phone1, $user->Username));

						if($ownerFound) {
							$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName), $owner->Person->Phone1, $owner->Username));
						} else {
							$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
						}

						$html = $findReplace->Execute($campaignEvent->Template);

						$pdf->NewLetter = true;
						$pdf->AddPage();
						$pdf->WriteHTML(sprintf("&#012;%s", $html));

						$recipient->CampaignContactEvent[$campaignEvent->ID]->IsComplete = 'Y';
						$recipient->CampaignContactEvent[$campaignEvent->ID]->Update();
					}
				}

				$fileName = $pdf->Output(sprintf('campaign_%d_%d_%s.pdf', $campaign->ID, $_REQUEST['event'], md5($contactStr)), 'F', false, $GLOBALS['CAMPAIGN_DOCUMENT_DIR_FS']);

				$qs .= sprintf('&popup=%s', $fileName);
				break;
		}
	}

	redirect(sprintf("Location: %s?id=%d&limit=%d&offset=%d%s", $_SERVER['PHP_SELF'], $campaign->ID, $limit, $offset, $qs));

} elseif($action == 'update') {
	if(isset($_REQUEST['update'])) {
		if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
			for($i=0; $i<count($recipients); $i++) {
				$recipient = $recipients[$i];

				foreach($events as $event) {
					$key = sprintf('active_%d_%d', $recipient->ID, $event->ID);
					$value = (isset($_REQUEST[$key]) && ($_REQUEST[$key] === 'Y')) ? 'Y' : 'N';
					$contactEvent = $recipients[$i]->CampaignContactEvent[$event->ID];

					if($value != $contactEvent->IsActive) {
						$contactEvent->IsActive = $value;
						$contactEvent->Update();
					}
				}
			}

			redirect(sprintf("Location: %s?id=%d&limit=%d&offset=%d#Recipients", $_SERVER['PHP_SELF'], $campaign->ID, $limit, $offset));
		}
	} elseif(isset($_REQUEST['filter'])) {
		$start = (isset($_REQUEST['refinestart']) && !empty($_REQUEST['refinestart'])) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('refinestart'), 6, 4), substr($form->GetValue('refinestart'), 3, 2), substr($form->GetValue('refinestart'), 0, 2)) : '0000-00-00 00:00:00';
		$end = (isset($_REQUEST['refineend']) && !empty($_REQUEST['refineend'])) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('refineend'), 6, 4), substr($form->GetValue('refineend'), 3, 2), substr($form->GetValue('refineend'), 0, 2)) : '0000-00-00 00:00:00';

		if($form->GetValue('refinecopy') == 'Y') {
			$campaign->Copy();
			$campaign->Title = $form->GetValue('refinetitle');
			$campaign->Update();
		}

		$campaignContact = new CampaignContact();
		$sqlWhere = '';

		switch($_REQUEST['refine']) {
			case 'PendingQuotes':
				$sqlWhere .= sprintf("AND q.Status LIKE 'Pending' ", $end);

				if(($start != '0000-00-00 00:00:00') && ($end != '0000-00-00 00:00:00')) {
					$sqlWhere .= sprintf("AND q.Created_On BETWEEN '%s' AND '%s' ", $start, $end);
				} elseif($start != '0000-00-00 00:00:00') {
					$sqlWhere .= sprintf("AND q.Created_On>'%s' ", $start);
				} elseif($end != '0000-00-00 00:00:00') {
					$sqlWhere .= sprintf("AND q.Created_On<'%s' ", $end);
				}

				$data = new DataQuery(sprintf("SELECT cc.Campaign_Contact_ID, COUNT(DISTINCT q.Quote_ID) AS Count FROM campaign_contact AS cc INNER JOIN contact AS n ON n.Contact_ID=cc.Contact_ID LEFT JOIN contact AS n2 ON n.Parent_Contact_ID=n2.Contact_ID LEFT JOIN contact AS n3 ON n3.Parent_Contact_ID=n2.Contact_ID INNER JOIN customer AS c ON (c.Contact_ID=n.Contact_ID OR c.Contact_ID=n3.Contact_ID) INNER JOIN quotes AS q ON q.Customer_ID=c.Customer_ID WHERE cc.Campaign_ID=%d %s GROUP BY cc.Campaign_Contact_ID", mysql_real_escape_string($campaign->ID), mysql_real_escape_string($sqlWhere)));
				while($data->Row) {
					if($data->Row['Count'] > 0) {
						$campaignContact->Delete($data->Row['Campaign_Contact_ID']);
					}

					$data->Next();
				}
				$data->Disconnect();

				break;

			case 'SuccessfulQuotes':
				$sqlWhere .= sprintf("AND o.Quote_ID>0 ", $end);

			case 'Orders':
				if(($start != '0000-00-00 00:00:00') && ($end != '0000-00-00 00:00:00')) {
					$sqlWhere .= sprintf("AND o.Created_On BETWEEN '%s' AND '%s' ", $start, $end);
				} elseif($start != '0000-00-00 00:00:00') {
					$sqlWhere .= sprintf("AND o.Created_On>'%s' ", $start);
				} elseif($end != '0000-00-00 00:00:00') {
					$sqlWhere .= sprintf("AND o.Created_On<'%s' ", $end);
				}

				$data = new DataQuery(sprintf("SELECT cc.Campaign_Contact_ID, COUNT(DISTINCT o.Order_ID) AS Count FROM campaign_contact AS cc INNER JOIN contact AS n ON n.Contact_ID=cc.Contact_ID LEFT JOIN contact AS n2 ON n.Parent_Contact_ID=n2.Contact_ID LEFT JOIN contact AS n3 ON n3.Parent_Contact_ID=n2.Contact_ID INNER JOIN customer AS c ON (c.Contact_ID=n.Contact_ID OR c.Contact_ID=n3.Contact_ID) INNER JOIN orders AS o ON o.Customer_ID=c.Customer_ID WHERE cc.Campaign_ID=%d %s GROUP BY cc.Campaign_Contact_ID", mysql_real_escape_string($campaign->ID), mysql_real_escape_string($sqlWhere)));
				while($data->Row) {
					if($data->Row['Count'] > 0) {
						$campaignContact->Delete($data->Row['Campaign_Contact_ID']);
					}

					$data->Next();
				}
				$data->Disconnect();

				break;

			case 'NoEmailAddress':
				$data = new DataQuery(sprintf("SELECT cc.Campaign_Contact_ID, p.Email FROM campaign_contact AS cc INNER JOIN contact AS c ON c.Contact_ID=cc.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE cc.Campaign_ID=%d", mysql_real_escape_string($campaign->ID)));
				while($data->Row) {
					if(preg_match('/^(.*?)@no-email.co.uk$/', $data->Row['Email'])) {
						$campaignContact->Delete($data->Row['Campaign_Contact_ID']);
					} elseif(strlen(trim($data->Row['Email'])) == 0) {
						$campaignContact->Delete($data->Row['Campaign_Contact_ID']);
					}

					$data->Next();
				}
				$data->Disconnect();

				break;

			case 'DuplicateEmailAddress':
				$data = new DataQuery(sprintf("SELECT COUNT(cc.Campaign_Contact_ID) AS Count, p.Email FROM campaign_contact AS cc INNER JOIN contact AS c ON c.Contact_ID=cc.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE cc.Campaign_ID=%d AND p.Email<>'' GROUP BY p.Email", mysql_real_escape_string($campaign->ID)));
				while($data->Row) {
					if($data->Row['Count'] > 1) {
						$data2 = new DataQuery(sprintf("SELECT cc.Campaign_Contact_ID FROM campaign_contact AS cc INNER JOIN contact AS c ON c.Contact_ID=cc.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE cc.Campaign_ID=%d AND p.Email LIKE '%s' LIMIT 0, %d", mysql_real_escape_string($campaign->ID), mysql_real_escape_string($data->Row['Email']), $data->Row['Count'] - 1));
						while($data2->Row) {
							$campaignContact->Delete($data2->Row['Campaign_Contact_ID']);

							$data2->Next();
						}
						$data2->Disconnect();
					}

					$data->Next();
				}
				$data->Disconnect();

				break;

			case 'LastContacted':
				if(($start != '0000-00-00 00:00:00') && ($end != '0000-00-00 00:00:00')) {
					$sqlWhere .= sprintf("AND cs.Completed_On BETWEEN '%s' AND '%s' ", $start, $end);
				} elseif($start != '0000-00-00 00:00:00') {
					$sqlWhere .= sprintf("AND cs.Completed_On>'%s' ", $start);
				} elseif($end != '0000-00-00 00:00:00') {
					$sqlWhere .= sprintf("AND cs.Completed_On<'%s' ", $end);
				}

				$data = new DataQuery(sprintf("SELECT cc.Campaign_Contact_ID, COUNT(DISTINCT cs.Contact_Schedule_ID) AS Count FROM campaign_contact AS cc INNER JOIN contact AS n ON n.Contact_ID=cc.Contact_ID LEFT JOIN contact AS n2 ON n.Parent_Contact_ID=n2.Contact_ID LEFT JOIN contact AS n3 ON n3.Parent_Contact_ID=n2.Contact_ID INNER JOIN contact_schedule AS cs ON (cs.Contact_ID=n.Contact_ID OR cs.Contact_ID=n3.Contact_ID) AND cs.Is_Complete='Y' WHERE cc.Campaign_ID=%d %s GROUP BY cc.Campaign_Contact_ID", mysql_real_escape_string($campaign->ID), mysql_real_escape_string($sqlWhere)));
				while($data->Row) {
					if($data->Row['Count'] > 0) {
						$campaignContact->Delete($data->Row['Campaign_Contact_ID']);
					}

					$data->Next();
				}
				$data->Disconnect();

				break;
		}

		redirect(sprintf("Location: %s?id=%d&limit=%d&offset=%d#Recipients", $_SERVER['PHP_SELF'], $campaign->ID, $limit, $offset));
	}
}

$script = sprintf('<script language="javascript" type="text/javascript">
	function checkUncheckEvent(element, eventId) {
		var form = element.form;
		var items = null;

		for(var z=0; z<form.length; z++){
			if((form[z].type == "checkbox") && (form[z].name.substr(0, 8) != "activate")) {
				items = form[z].name.split("_");

				if(items[2] && (items[2] == eventId)) {
					form[z].checked = element.checked;
				}
			}
		}
	}

	function confirmComplete(msg) {
		return confirm(msg);
	}

	function checkAdditionalFields(obj) {
		var e = null;

		e = document.getElementById(\'refineDates\');
		if(e) {
			e.style.display = \'none\';

			switch(obj.value) {
				case \'PendingQuotes\':
				case \'SuccessfulQuotes\':
				case \'Orders\':
				case \'LastContacted\':
					e.style.display = \'\';
					break;
			}
		}
	}
	</script>');

if(isset($_REQUEST['popup'])) {
	$script .= sprintf('<script language="javascript" type="text/javascript">
		popUrl(\'campaign_download.php?filename=%s\', 600, 500);
		</script>', $_REQUEST['popup']);
}

$script .= sprintf('<script language="javascript" type="text/javascript">
	var toggleTitle = function(obj) {
		var e = document.getElementById(\'refinetitle\');

		if(e) {
			if(obj.checked) {
				e.removeAttribute(\'disabled\');
			} else {
				e.setAttribute(\'disabled\', \'disabled\');
			}
		}
	}
	</script>');

$page = new Page('Campaign Profile','Here you can change your campaign information.');
$page->AddToHead('<script language="javascript" type="text/javascript" src="js/scw.js"></script>');
$page->AddToHead($script);
$page->Display('header');

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');
echo $form->GetHTML('limit');
echo $form->GetHTML('offset');
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
 <thead>
 	<tr>
		<th colspan="5">Campaign Information</th>
	</tr>
 </thead>
 <tbody>
   <tr>
     <td>Title:</td>
     <td><strong><?php echo $campaign->Title; ?></strong></td>
   </tr>
   <tr>
	 <td>Description:</td>
	 <td><?php echo $campaign->Description; ?>&nbsp;</td>
   </tr>
   <tr>
     <td>Recipients:</td>
     <td><?php print $totalRecipients; ?></td>
   </tr>
   <tr>
	 <td>Created On:</td>
	 <td><?php echo cDatetime($campaign->CreatedOn, 'shortdatetime'); ?></td>
   </tr>
   <tr>
	 <td>Created By:</td>
	 <td><?php echo $userStr; ?></td>
   </tr>
 </tbody>
</table><br />

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
 <thead>
 	<tr>
		<th colspan="3">Campaign Links</th>
	</tr>
 </thead>
 <tbody>
   <tr>
   	 <td><a href="campaign_description.php?action=update&id=<?php echo $campaign->ID; ?>">1. Edit Description</a></td>
   	 <td><a href="campaign_recipients.php?id=<?php echo $campaign->ID; ?>">2. Edit Recipients</a></td>
   	 <td><a href="campaign_events.php?id=<?php echo $campaign->ID; ?>">3. Edit Events</a></td>
   </tr>
 </tbody>
</table><br />

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
 <thead>
 	<tr>
		<th>Events</th>
	</tr>
 </thead>
 <tbody>
 	<tr>
 		<td>

 			<p><strong>Dated Events</strong><br />Dated events apply to all contacts of the campaign at specific fixed dates.</p>

			 <table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
			 <thead>
				<tr>
					<th width="10%">Event No.</th>
					<th width="10%">Type</th>
					<th width="10%">Scheduled</th>
					<th>Title</th>
					<th width="15%">Process</th>
					<th width="1%" colspan="2"></th>
				</tr>
			 </thead>
			 <tbody>

				<?php
				$index = 1;

				if(count($datedEvents) > 0) {
					foreach($datedEvents as $event) {
						$type = ($event->Type == 'E') ? 'Email' : (($event->Type == 'L') ? 'Letter' : (($event->Type == 'F') ? 'Fax' : (($event->Type == 'P') ? 'Phone' : sprintf('<em>%s</em>', $event->Type))));
						$scheduled = cDatetime(date('Y-m-d 00:00:00', $event->Scheduled), 'shortdate');
						?>

						<tr>
							<td nowrap="nowrap"><?php echo $index; ?></td>
							<td nowrap="nowrap"><?php echo $type; ?></td>
							<td nowrap="nowrap"><?php echo $scheduled; ?></td>
							<td><a href="campaign_events.php?action=update&id=<?php print $campaign->ID; ?>&eid=<?php print $event->ID; ?>&direct=campaign_profile.php"><?php echo $event->Title; ?></a></td>
							<td nowrap="nowrap"><?php echo ($event->IsAutomatic == 'Y') ? sprintf('Automatic (%s)', ($event->IsAutomaticDisabling == 'Y') ? 'Disable On Complete' : 'Continuous') : 'Manual'; ?></td>
							<td nowrap="nowrap">
								<?php
								if($event->Type == 'E') {
									?>
									<a href="campaign_email.php?eventid=<?php echo $event->ID; ?>"><img src="../images/icons/mail.png" alt="Email" border="0" /></a>
									<?php
								}
								?>
							</td>
							<td nowrap="nowrap"><a href="javascript:popUrl('campaign_print.php?eventid=<?php echo $event->ID; ?>', 800, 600);"><img src="../images/icons/printer.png" alt="Print" border="0" /></a></td>
						</tr>

						<?php
						$index++;
					}
				} else {
					?>

					<tr>
						<td colspan="7">No Scheduled Events</td>
					</tr>

					<?php
				}
				?>

			 </tbody>
			 </table><br />

			 <p><strong>Timed Events</strong><br />Timed events apply to all contacts of the campaign at specific periods after being associated with a campaign.</p>

			 <table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
			 <thead>
				<tr>
					<th width="10%">Event No.</th>
					<th width="10%">Type</th>
					<th width="10%">Scheduled</th>
					<th>Title</th>
					<th width="15%">Process</th>
					<th width="1%" colspan="2"></th>
				</tr>
			 </thead>
			 <tbody>
			   <?php
			   if(count($timedEvents) > 0) {
			   	foreach($timedEvents as $event) {
			   		$type = ($event->Type == 'E') ? 'Email' : (($event->Type == 'L') ? 'Letter' : (($event->Type == 'F') ? 'Fax' : (($event->Type == 'P') ? 'Phone' : sprintf('<em>%s</em>', $event->Type))));
			   		$scheduled = ucwords(periodToString($event->Scheduled));
						?>

						<tr>
							<td nowrap="nowrap"><?php echo $index; ?></td>
							<td nowrap="nowrap"><?php echo $type; ?></td>
							<td nowrap="nowrap"><?php echo $scheduled; ?></td>
							<td><a href="campaign_events.php?action=update&id=<?php print $campaign->ID; ?>&eid=<?php print $event->ID; ?>&direct=campaign_profile.php"><?php echo $event->Title; ?></a></td>
							<td nowrap="nowrap"><?php echo ($event->IsAutomatic == 'Y') ? sprintf('Automatic (%s)', ($event->IsAutomaticDisabling == 'Y') ? 'Disable On Complete' : 'Continuous') : 'Manual'; ?></td>
							<td nowrap="nowrap">
								<?php
								if($event->Type == 'E') {
									?>
									<a href="campaign_email.php?eventid=<?php echo $event->ID; ?>"><img src="../images/icons/mail.png" alt="Email" border="0" /></a>
									<?php
								}
								?>
							</td>
							<td nowrap="nowrap"><a href="javascript:popUrl('campaign_print.php?eventid=<?php echo $event->ID; ?>', 800, 600);"><img src="../images/icons/printer.png" alt="Print" border="0" /></a></td>
						</tr>

						<?php
						$index++;
			   	}
			   } else {
					?>

					<tr>
						<td colspan="7">No Scheduled Events</td>
					</tr>

					<?php
			   }
				?>
			 </tbody>
			 </table>

 		</td>
 	</tr>
 </tbody>
</table><br />

<a name="Recipients"></a>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
 <thead>
 	<tr>
		<th colspan="6">Recipients</th>

		<?php
		if(count($recipients) > 0) {
			if(count($events) > 0) {
				if(count($datedEvents) > 0) {
					?>

					<th nowrap="nowrap" colspan="<?php echo count($datedEvents); ?>">Dated Events</th>

				<?php
				}

				if(count($timedEvents) > 0) {
					?>

					<th nowrap="nowrap" colspan="<?php echo count($timedEvents); ?>">Timed Events</th>

					<?php
				}
			}
		}
		?>

	</tr>
 </thead>
 <tbody>

 	<?php
 	if(count($recipients) > 0) {
 		$columns = 6;

 		foreach($events as $event) {
 			$columns++;
 		}

 		$index = 1;
 		?>

 		<tr>
			<td valign="bottom"><strong>Index</strong></td>
			<td valign="bottom" colspan="2"><strong>Name / Organisation</strong></td>
			<td valign="bottom"><strong>Email Address</strong></td>
			<td valign="bottom" align="center"><strong>On Mailing List</strong></td>
			<td valign="bottom" align="center"><strong>Catalogue Sent</strong></td>
 			<?php
 			if(count($events) > 0) {
 				foreach($datedEvents as $event) {
 					$datedStyle = '';

 					$eventStats = array();
 					$eventStats['Y'] = 0;
 					$eventStats['N'] = 0;

 					$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter, Is_Complete FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID INNER JOIN contact AS c ON c.Contact_ID=cc.Contact_ID WHERE Campaign_Event_ID=%d GROUP BY Is_Complete", $event->ID));
 					while($data->Row) {
 						$eventStats[$data->Row['Is_Complete']] = $data->Row['Counter'];
 						$data->Next();
 					}
 					$data->Disconnect();

 					if(date('Y-m-d 00:00:00') >= date('Y-m-d 00:00:00', $event->Scheduled)) {
 						if($eventStats['N'] == 0) {
 							$datedStyle = 'background-color: #B7F297;';
 						} else {
 							$datedStyle = 'background-color: #F1746E;';
 						}
 					}

 					$onclick = ($eventStats['N'] == 0) ? sprintf('onclick="return confirmComplete(\'Are you sure you wish to reprocess this event for %s?\');"', ($event->Type == 'E') ? 'all campaign recipients' : 'all listed campaign recipients') : '';
					?>

					<td align="center" style="<?php echo $datedStyle; ?>">
						<strong><em><?php echo $index; ?></em></strong><br />
						<?php echo $form->GetHTML(sprintf('activate_%d', $event->ID)); ?>
						<a <?php print $onclick; ?> href="<?php print $_SERVER['PHP_SELF'];?>?id=<?php print $campaign->ID; ?>&action=complete&event=<?php print $event->ID; ?>&limit=<?php print $limit; ?>&offset=<?php print $offset; ?>&repeat=<?php print ($eventStats['N'] == 0) ? 'true' : 'false'; ?>#Recipients"><img border="0" src="images/<?php echo ($eventStats['N'] == 0) ? 'icon_tick_3.gif' : 'icon_cross_3.gif'; ?>" /></a>
						<a <?php print $onclick; ?> href="<?php print $_SERVER['PHP_SELF'];?>?id=<?php print $campaign->ID; ?>&action=complete&event=<?php print $event->ID; ?>&limit=<?php print $limit; ?>&offset=<?php print $offset; ?>&repeat=<?php print ($eventStats['N'] == 0) ? 'true' : 'false'; ?>&single=true#Recipients"><img border="0" src="images/<?php echo ($eventStats['N'] == 0) ? 'icon_tick_4.gif' : 'icon_cross_4.gif'; ?>" /></a>
					</td>

					<?php
					$index++;
 				}

 				foreach($timedEvents as $event) {
 					$timedStyle = '';
 					$period = periodToArray($event->Scheduled);

 					$eventStats = array();
 					$eventStats['Y']['Y'] = 0;
 					$eventStats['Y']['N'] = 0;
 					$eventStats['N']['Y'] = 0;
 					$eventStats['N']['N'] = 0;

 					$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter, cc.Created_On, cce.Is_Complete FROM campaign_contact AS cc INNER JOIN campaign_contact_event AS cce ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID WHERE cc.Campaign_ID=%d AND cce.Campaign_Event_ID=%d GROUP BY cc.Created_On, cce.Is_Complete", mysql_real_escape_string($campaign->ID), mysql_real_escape_string($event->ID)));
 					while($data->Row) {
 						$year = substr($data->Row['Created_On'], 0, 4);
 						$month = substr($data->Row['Created_On'], 5, 2);
 						$day = substr($data->Row['Created_On'], 8, 2);

 						if(date('Y-m-d 00:00:00') >= date('Y-m-d 00:00:00', mktime(0, 0, 0, $month+$period['month'], $day+$period['day'], $year))) {
 							$eventStats['Y'][$data->Row['Is_Complete']] += $data->Row['Counter'];
 						} else {
 							$eventStats['N'][$data->Row['Is_Complete']] += $data->Row['Counter'];
 						}

 						$data->Next();
 					}
 					$data->Disconnect();

 					if(($eventStats['N']['N'] + $eventStats['N']['Y']) == 0) {
 						if($eventStats['Y']['N'] == 0) {
 							$timedStyle = 'background-color: #B7F297;';
 						} else {
 							$timedStyle = 'background-color: #F1746E;';
 						}
 					}

 					$onclick = (($eventStats['N']['N'] + $eventStats['Y']['N']) == 0) ? sprintf('onclick="return confirmComplete(\'Are you sure you wish to reprocess this event for %s?\');"', ($event->Type == 'E') ? 'all campaign recipients' : 'all listed campaign recipients') : '';
					?>

					<td align="center" style="<?php echo $timedStyle; ?>">
						<strong><em><?php echo $index; ?></em></strong><br />
						<?php echo $form->GetHTML(sprintf('activate_%d', $event->ID)); ?>
						<a <?php print $onclick; ?> href="<?php print $_SERVER['PHP_SELF'];?>?id=<?php print $campaign->ID; ?>&action=complete&event=<?php print $event->ID; ?>&limit=<?php print $limit; ?>&offset=<?php print $offset; ?>&repeat=<?php print (($eventStats['N']['N'] + $eventStats['Y']['N']) == 0) ? 'true' : 'false'; ?>#Recipients"><img border="0" src="images/<?php echo (($eventStats['N']['N'] + $eventStats['Y']['N']) == 0) ? 'icon_tick_3.gif' : 'icon_cross_3.gif'; ?>" /></a>
						<a <?php print $onclick; ?> href="<?php print $_SERVER['PHP_SELF'];?>?id=<?php print $campaign->ID; ?>&action=complete&event=<?php print $event->ID; ?>&limit=<?php print $limit; ?>&offset=<?php print $offset; ?>&repeat=<?php print (($eventStats['N']['N'] + $eventStats['Y']['N']) == 0) ? 'true' : 'false'; ?>&single=true#Recipients"><img border="0" src="images/<?php echo ($eventStats['N'] == 0) ? 'icon_tick_4.gif' : 'icon_cross_4.gif'; ?>" /></a>
					</td>

					<?php
					$index++;
 				}
 			}
			?>
 		</tr>

 		<?php
 		$index = $offset * $limit;

 		foreach($recipients as $recipient) {
 			$index++;
			?>

			<tr>
				<td valign="top"><?php print $index; ?></td>
				<td valign="top"><a href="contact_profile.php?cid=<?php echo $recipient->Contact->ID; ?>"><?php print trim(sprintf('%s %s', $recipient->Contact->Person->Name, $recipient->Contact->Person->LastName)); ?></a>&nbsp;</td>
				<td valign="top"><?php print trim(sprintf('%s', $recipient->Contact->Parent->Organisation->Name)); ?>&nbsp;</td>
				<td valign="top"><?php print $recipient->Contact->Person->Email; ?>&nbsp;</td>
				<td valign="top" align="center"><?php echo ($recipient->Contact->OnMailingList == 'H') ? 'HTML' : (($recipient->Contact->OnMailingList == 'P') ? 'Plain' : 'None'); ?>&nbsp;</td>
				<td valign="top" align="center"><?php
				    $recipient->Contact->Get();
				    if($recipient->Contact->CatalogueSentOn != '0000-00-00 00:00:00'){
					echo "Catalogue sent on " . cDatetime($recipient->Contact->CatalogueSentOn, 'shortdate');
					if($recipient->Contact->IsCatalogueRequested == "Y"){
					    echo " (New One Requested - <span>". sprintf('<a href="?action=cancelrequestcatalogue&cid=%d&id=%d&limit=%d&offset=%d">Cancel Request</a>', $recipient->Contact->ID,$campaign->ID,$limit,$offset) . "</span>)";
					}else{
					    echo sprintf(' - <span><a href="?action=requestcatalogue&cid=%d&id=%d&limit=%d&offset=%d">Request New Catalogue</a>', $recipient->Contact->ID,$campaign->ID,$limit,$offset) . "</span>";
					}
				    }else if($recipient->Contact->IsCatalogueRequested == "Y"){
					echo "Never sent but requested &nbsp;<span> " . sprintf('<a href="?action=cancelrequestcatalogue&cid=%d&id=%d&limit=%d&offset=%d">Cancel Request</a>', $recipient->Contact->ID,$campaign->ID,$limit,$offset) . "</span>";
				    }else{

					echo '<em>&lt;Never&gt;</em>&nbsp;<span>' . sprintf('<a href="?action=requestcatalogue&cid=%d&id=%d&limit=%d&offset=%d">Request Catalogue</a>', $recipient->Contact->ID,$campaign->ID,$limit,$offset) . "</span>";
				    }
				    ?>
				</td>
				<?php
				foreach($datedEvents as $event) {
					$datedStyle = '';

					if(date('Y-m-d 00:00:00') >= date('Y-m-d 00:00:00', $event->Scheduled)) {
						if($recipient->CampaignContactEvent[$event->ID]->IsComplete == 'Y') {
							$datedStyle = 'background-color: #B7F297;';
						} else {
							$datedStyle = 'background-color: #F1746E;';
						}
					}

					if($event->Type == 'E') {
						if($recipient->CampaignContactEvent[$event->ID]->IsEmailFailed == 'Y') {
							$datedStyle = 'background-color: #F6F399;';
						}
					}

					$onclick = ($recipient->CampaignContactEvent[$event->ID]->IsComplete == 'Y') ? sprintf('onclick="return confirmComplete(\'Are you sure you wish to reprocess this event for %s?\');"', trim((strlen(trim(sprintf('%s %s', $recipient->Contact->Person->Name, $recipient->Contact->Person->LastName))) > 0) ? sprintf('%s %s', $recipient->Contact->Person->Name, $recipient->Contact->Person->LastName) : 'this recipient')) : '';
					?>

					<td align="center" style="<?php echo $datedStyle; ?>">
						<?php echo $form->GetHTML(sprintf('active_%d_%d', $recipient->ID, $event->ID)); ?>
						<a <?php print $onclick; ?> href="<?php print $_SERVER['PHP_SELF'];?>?id=<?php print $campaign->ID; ?>&action=complete&event=<?php print $event->ID; ?>&contact=<?php print $recipient->Contact->ID; ?>&limit=<?php print $limit; ?>&offset=<?php print $offset; ?>#Recipients"><img border="0" src="images/<?php echo ($recipient->CampaignContactEvent[$event->ID]->IsComplete == 'Y') ? 'icon_tick_3.gif' : 'icon_cross_3.gif'; ?>" /></a>
					</td>

					<?php
				}

				foreach($timedEvents as $event) {
					$timedStyle = '';
					$period = periodToArray($event->Scheduled);
					$year = substr($recipient->CreatedOn, 0, 4);
					$month = substr($recipient->CreatedOn, 5, 2);
					$day = substr($recipient->CreatedOn, 8, 2);

					if(date('Y-m-d 00:00:00') >= date('Y-m-d 00:00:00', mktime(0, 0, 0, $month+$period['month'], $day+$period['day'], $year))) {
						if($recipient->CampaignContactEvent[$event->ID]->IsComplete == 'Y') {
							$timedStyle = 'background-color: #B7F297;';
						} else {
							$timedStyle = 'background-color: #F1746E;';
						}
					}

					if($event->Type == 'E') {
						if($recipient->CampaignContactEvent[$event->ID]->IsEmailFailed == 'Y') {
							$timedStyle = 'background-color: #F6F399;';
						}
					}

					$onclick = ($recipient->CampaignContactEvent[$event->ID]->IsComplete == 'Y') ? sprintf('onclick="return confirmComplete(\'Are you sure you wish to reprocess this event for %s?\');"', trim((strlen(trim(sprintf('%s %s', $recipient->Contact->Person->Name, $recipient->Contact->Person->LastName))) > 0) ? sprintf('%s %s', $recipient->Contact->Person->Name, $recipient->Contact->Person->LastName) : 'this contact')) : '';
					?>

					<td align="center" style="<?php echo $timedStyle; ?>">
						<?php echo $form->GetHTML(sprintf('active_%d_%d', $recipient->ID, $event->ID)); ?>
						<a <?php print $onclick; ?> href="<?php print $_SERVER['PHP_SELF'];?>?id=<?php print $campaign->ID; ?>&action=complete&event=<?php print $event->ID; ?>&contact=<?php print $recipient->Contact->ID; ?>&limit=<?php print $limit; ?>&offset=<?php print $offset; ?>#Recipients"><img border="0" src="images/<?php echo ($recipient->CampaignContactEvent[$event->ID]->IsComplete == 'Y') ? 'icon_tick_3.gif' : 'icon_cross_3.gif'; ?>" /></a>
					</td>

					<?php
				}
				?>

			</tr>

			<?php
 		}
		?>

		<tr>
			<td colspan="<?php print $columns; ?>">

				<table width="100%" cellspacing="0" cellpadding="0">
					<tr>

						<?php
						echo sprintf('<td style="border: none;">Page %d of %d</td>', $offset+1, $totalPages);

						if($offset > 0) {
							echo sprintf('<td style="border: none;" align="left" width="75"><a href="%s?id=%d&offset=%d&limit=%d#Recipients">&laquo; First</a></td>', $_SERVER['PHP_SELF'], $campaign->ID, 0, $limit);
							echo sprintf('<td style="border: none;" align="left" width="75"><a href="%s?id=%d&offset=%d&limit=%d#Recipients">&lt; Previous</a></td>', $_SERVER['PHP_SELF'], $campaign->ID, $offset-1, $limit);
						} else {
							echo sprintf('<td style="border: none;" align="left" width="75">&laquo; First</td>');
							echo sprintf('<td style="border: none;" align="left" width="75">&lt; Previous</td>');
						}

						if($offset < ($totalPages - 1)) {
							echo sprintf('<td style="border: none;" align="right" width="75"><a href="%s?id=%d&offset=%d&limit=%d#Recipients">Next &gt;</a></td>', $_SERVER['PHP_SELF'], $campaign->ID, $offset+1, $limit);
							echo sprintf('<td style="border: none;" align="right" width="75"><a href="%s?id=%d&offset=%d&limit=%d#Recipients">Last &raquo;</a></td>', $_SERVER['PHP_SELF'], $campaign->ID, $totalPages-1, $limit);
						} else {
							echo sprintf('<td style="border: none;" align="right" width="75">Next &gt;</td>');
							echo sprintf('<td style="border: none;" align="right" width="75">Last &raquo;</td>');
						}
						?>

					</tr>
				</table>

			</td>
		</tr>

		<?php
 	} else {
		?>

		<tr>
			<td colspan="5">No Associated Recipients</td>
		</tr>

		<?php
 	}
 	?>

		<tr>
			<td colspan="<?php print $columns; ?>" style="text-align: right;"><input class="btn" type="submit" name="update" value="update" /></td>
		</tr>
	</tbody>
</table><br />

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
 <thead>
 	<tr>
		<th colspan="3">Refine Recipients</th>
	</tr>
 </thead>
 <tbody>
   <tr>
     <td valign="top" width="33.3%">
     	<p>Filter out recipients who match the following criteria.</p>
		<p><strong><?php echo $form->GetLabel('refine'); ?></strong><br /><?php echo $form->GetHTML('refine'); ?></p>

		<div id="refineDates" style="display: none;">
			<p><strong><?php echo $form->GetLabel('refinestart'); ?></strong><br /><?php echo $form->GetHTML('refinestart'); ?></p>
			<p><strong><?php echo $form->GetLabel('refineend'); ?></strong><br /><?php echo $form->GetHTML('refineend'); ?></p>
		</div>
     </td>
     <td valign="top" width="33.3%">
     	<p>Enter the new titles for your refined campaign.</p>
		<p><strong><?php echo $form->GetLabel('refinecopy'); ?></strong><br /><?php echo $form->GetHTML('refinecopy'); ?></p>
		<p><strong><?php echo $form->GetLabel('refinetitle'); ?></strong><br /><?php echo $form->GetHTML('refinetitle'); ?></p>
     </td>
     <td valign="top" width="33.3%">
     	<p>Submit your refinment for processing.</p>
     	<br />

		<input class="btn" type="submit" name="filter" value="submit" />
     </td>
   </tr>
 </tbody>
</table>

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');
?>