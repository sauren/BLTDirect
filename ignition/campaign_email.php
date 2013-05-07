<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignEvent.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$event = new CampaignEvent();

if(!$event->Get($_REQUEST['eventid'])) {
	redirect(sprintf("Location: campaigns.php"));
}

$user = new User($GLOBALS['SESSION_USER_ID']);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('eventid', 'Event ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('email', 'Preview Email Address', 'text', $user->Person->Email, 'anything', 1, 255);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$ownerFound = false;

		$owner = new User();
		$owner->ID = $event->OwnedBy;

		if($owner->Get()) {
			$ownerFound = true;
		}

		$window = '<p>&nbsp;</p>';
		$window .= '<table width="100%">';
		$window .= '<tr>';
		$window .= '<td width="3%">&nbsp;</td>';
		$window .= sprintf('<td width="97%%">%s<br />%s</td>', sprintf('%s %s %s', $user->Person->Title, $user->Person->Name, $user->Person->LastName), $user->Person->Address->GetLongString());
		$window .= '</tr>';
		$window .= '</table>';

		$windowAddress = '<p>&nbsp;</p>';
		$windowAddress .= '<table width="100%">';
		$windowAddress .= '<tr>';
		$windowAddress .= '<td width="3%">&nbsp;</td>';
		$windowAddress .= sprintf('<td width="97%%">%s</td>', $user->Person->Address->GetLongString());
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
		$windowDate .= sprintf('<td width="97%%">%s<br />%s</td>', sprintf("%s %s %s", $user->Person->Title, $user->Person->Name, $user->Person->LastName), $user->Person->Address->GetLongString());
		$windowDate .= '</tr>';
		$windowDate .= '</table>';

		$findReplace = new FindReplace();
		$findReplace->Add('/\[WINDOW\]/', $window);
		$findReplace->Add('/\[WINDOWADDRESS\]/', $windowAddress);
		$findReplace->Add('/\[WINDOWDATE\]/', $windowDate);
		$findReplace->Add('/\[COMPANY\]/', trim(sprintf('%s %s %s', $user->Person->Title, $user->Person->Name, $user->Person->LastName)));
		$findReplace->Add('/\[CUSTOMER\]/', sprintf("%s %s %s", $user->Person->Title, $user->Person->Name, $user->Person->LastName));
		$findReplace->Add('/\[TITLE\]/', $user->Person->Title);
		$findReplace->Add('/\[FIRSTNAME\]/', $user->Person->Name);
		$findReplace->Add('/\[LASTNAME\]/', $user->Person->LastName);
		$findReplace->Add('/\[FULLNAME\]/', trim(str_replace("   ", " ", str_replace("  ", " ", sprintf("%s %s %s", $user->Person->Title, $user->Person->Name, $user->Person->LastName)))));
		$findReplace->Add('/\[FAX\]/', $user->Person->Fax);
		$findReplace->Add('/\[PHONE\]/', $user->Person->Phone1);
		$findReplace->Add('/\[ADDRESS\]/', $user->Person->Address->GetLongString());
		$findReplace->Add('/\[USERNAME\]/', sprintf("%s %s", $user->Person->Name, $user->Person->LastName));
		$findReplace->Add('/\[USEREMAIL\]/', $user->Person->Email);
		$findReplace->Add('/\[USERPHONE\]/', sprintf('%s', (strlen(trim($user->Person->Phone1)) > 0) ? $user->Person->Phone1 : $user->Person->Phone2));
		$findReplace->Add('/\[EMAIL\]/', $form->GetValue('email'));
		$findReplace->Add('/\[EMAILENCRYPTED\]/', '');
		$findReplace->Add('/\[PASSWORD\]/', 'Preview event');
		$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $user->Person->Name, $user->Person->LastName), $user->Person->Phone1, $user->Username));

		if($ownerFound) {
			$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName), $owner->Person->Phone1, $owner->Username));
		} else {
			$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
		}

		$subject = (strlen(trim($event->Subject)) > 0) ? trim($event->Subject) : Setting::GetValue('campaign_default_subject');

		$findReplace->Add('/\[SUBJECT\]/', $subject);
		$findReplace->Add('/\[ENTITY\]/', '');

		$html = $findReplace->Execute($event->Template);

		$returnPath = explode('@', $GLOBALS['EMAIL_RETURN']);
		$returnPath = (count($returnPath) == 2) ? sprintf('%s.event.%d@%s', $returnPath[0], $event->ID, $returnPath[1]) : $GLOBALS['EMAIL_RETURN'];

		if((strlen($form->GetValue('email')) > 0) && preg_match(sprintf("/%s/", $form->RegularExp['email']), $form->GetValue('email'))) {
			$queue = new EmailQueue();

			$data8 = new DataQuery(sprintf("SELECT Email_Queue_Module_ID FROM email_queue_module WHERE Reference LIKE 'campaigns' LIMIT 0, 1"));
			$queue->ModuleID = ($data8->TotalRows > 0) ? $data8->Row['Email_Queue_Module_ID'] : 0;
			$data8->Disconnect();

			if(!empty($event->FromAddress)) {
				$queue->FromAddress = $event->FromAddress;
			}

			$queue->ReturnPath = $returnPath;
			$queue->Subject = (strlen(trim($event->Subject)) > 0) ? trim($event->Subject) : Setting::GetValue('campaign_default_subject');
			$queue->Body = sprintf('<html><body>%s</body></html>', $html);
			$queue->Priority = 'H';
			$queue->Type = 'H';
			$queue->ToAddress = $form->GetValue('email');
			$queue->Add();
		}

		redirect(sprintf("Location: campaign_profile.php?id=%d", $event->Campaign->ID));
	}
}

$page = new Page(sprintf('<a href="campaign_profile.php?id=%d">Campaign Profile</a> &gt; Edit Description', $event->Campaign->ID), 'Email a preview example of this event to the below email address.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

$window = new StandardWindow('Event Preview Email');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('eventid');
echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('email'), $form->GetHTML('email') . $form->GetIcon('email'));
echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'campaign_profile.php?id=%d\';" /> <input type="submit" name="send" value="send" class="btn" tabindex="%s" />', $event->Campaign->ID, $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');