<?php
require_once('lib/common/app_header.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'complete') {
	$session->Secure(3);
	complete();
	exit;
} elseif($action == 'open') {
	$session->Secure(3);
	open();
	exit;
} elseif($action == 'cold') {
	$session->Secure(3);
	cold();
	exit;
} else {
	$session->Secure(3);
	view();
	exit;
}

function cold() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');

	if(isset($_REQUEST['id'])) {
		$data = new DataQuery(sprintf("SELECT Contact_Status_ID FROM contact_status WHERE Name LIKE 'Cold'"));
		if($data->TotalRows > 0) {
			$contact = new Contact($_REQUEST['id']);
			$contact->Status->ID = $data->Row['Contact_Status_ID'];
			$contact->AccountManager->ID = 0;
			$contact->Update();
			$contact->UpdateAccountManager();

			new DataQuery(sprintf("DELETE FROM contact_schedule WHERE Is_Complete='N' AND Contact_ID=%d", mysql_real_escape_string($contact->ID)));
		}
		$data->Disconnect();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function complete() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactSchedule.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/htmlMimeMail5.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactNote.php');

	$schedule = new ContactSchedule($_REQUEST['id']);
	$contact = new Contact($schedule->ContactID);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'complete', 'alpha', 8, 8);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Schedule ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('status', 'Status', 'select', '', 'anything', 1, 120, false);
	$form->AddOption('status', '', '');
	$form->AddOption('status', 'Customer not present', 'Customer not present');
	$form->AddOption('status', 'Ordered elsewhere', 'Ordered elsewhere');
	$form->AddOption('status', 'Does not require anything', 'Does not require anything');
	$form->AddOption('status', 'Call customer back', 'Call customer back');
	$form->AddOption('status', 'Customer ordered', 'Customer ordered');
	$form->AddField('note', 'Note', 'textarea', '', 'anything', 1, 2000, true, 'style="width:100%; height:200px"');
	$form->AddField('reschedule', 'Reschedule?', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('coupon', 'Send Predefined Coupon?', 'select', '', 'alpha', 1, 2, false);
	$form->AddOption('coupon', '', '');
	$form->AddOption('coupon', 'N', 'Not Contacted');
	$form->AddOption('coupon', 'Y', 'Contacted');
	$form->AddField('custom', 'Custom Email', 'checkbox', 'N', 'boolean', 1, 11, false, 'onclick="toggleCustomEmail(this);"');
	$form->AddField('customsubject', 'Custom Subject', 'text', '', 'anything', 1, 60, false, 'disabled="disabled" style="width: 100%;"');
	$form->AddField('customtext', 'Custom Text', 'textarea', '', 'anything', 1, 2048, false, 'disabled="disabled" rows="5" style="width: 100%;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			$schedule->Status = $form->GetValue('status');
			$schedule->Message = $form->GetValue('note');
			$schedule->Complete();

			$note = new ContactNote();
			$note->ContactID = $schedule->ContactID;
			$note->Description = $form->GetValue('note');
			$note->Add();

			$date = $form->GetValue('reschedule');

			if(!empty($date)) {
				$reschedule = new ContactSchedule();
				$reschedule->ParentID = $schedule->ID;
				$reschedule->ContactID = $schedule->ContactID;
				$reschedule->Type->ID = $schedule->Type->ID;
				$reschedule->Note = $schedule->Note;
				$reschedule->ScheduledOn = sprintf('%s-%s-%s 00:00:00', substr($date, 6, 4), substr($date, 3, 2), substr($date, 0, 2));
				$reschedule->OwnedBy = $schedule->OwnedBy;
				$reschedule->Add();
			}

			if($form->GetValue('custom') == 'N') {
				$coupon = $form->GetValue('coupon');

				if(!empty($coupon)) {
					$data = new DataQuery(sprintf("SELECT Coupon_ID, Coupon_Ref, Discount_Amount FROM coupon WHERE Coupon_Title LIKE 'Introduction Coupon'"));
					if($data->TotalRows > 0) {
						$findReplace = new FindReplace();
						$findReplace->Add('/\[DISCOUNT\]/', $data->Row['Discount_Amount']);
						$findReplace->Add('/\[COUPON\]/', $data->Row['Coupon_Ref']);

						$couponTemplate = '';

						switch($coupon) {
							case 'Y':
								$couponTemplate = 'schedule_coupon_contacted.tpl';
								break;
							case 'N':
								$couponTemplate = 'schedule_coupon_notcontacted.tpl';
								break;
						}

						if(!empty($couponTemplate)) {
							$scheduleEmail = file(sprintf("%slib/templates/email/%s", $GLOBALS["DIR_WS_ADMIN"], $couponTemplate));
							$scheduleHtml = '';

							for($i=0; $i < count($scheduleEmail); $i++){
								$scheduleHtml .= $findReplace->Execute($scheduleEmail[$i]);
							}

							$findReplace = new FindReplace();
							$findReplace->Add('/\[BODY\]/', $scheduleHtml);
							$findReplace->Add('/\[NAME\]/', $contact->Person->GetFullName());

							$templateEmail = file(sprintf("%slib/templates/email/template_standard.tpl", $GLOBALS["DIR_WS_ADMIN"]));
							$templateHtml = '';

							for($i=0; $i < count($templateEmail); $i++){
								$templateHtml .= $findReplace->Execute($templateEmail[$i]);
							}

							$mail = new htmlMimeMail5();
							$mail->setFrom($GLOBALS['EMAIL_FROM']);
							$mail->setSubject(sprintf("Introduction Coupon from %s", $GLOBALS['COMPANY']));
							$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
							$mail->setHTML($templateHtml);
							$mail->send(array($contact->Person->Email));
						}
					}
					$data->Disconnect();
				}
			} else {
				$findReplace = new FindReplace();
				$findReplace->Add('/\[BODY\]/', sprintf('<p>%s</p>', nl2br($form->GetValue('customtext'))));
				$findReplace->Add('/\[NAME\]/', $contact->Person->GetFullName());

				$templateEmail = file(sprintf("%slib/templates/email/template_standard.tpl", $GLOBALS["DIR_WS_ADMIN"]));
				$templateHtml = '';

				for($i=0; $i < count($templateEmail); $i++){
					$templateHtml .= $findReplace->Execute($templateEmail[$i]);
				}

				$mail = new htmlMimeMail5();
				$mail->setFrom($GLOBALS['EMAIL_FROM']);
				$mail->setSubject(sprintf("%s %s", $GLOBALS['COMPANY'], $form->GetValue('customsubjectt')));
				$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
				$mail->setHTML($templateHtml);
				$mail->send(array($contact->Person->Email));
			}

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var toggleCustomEmail = function(obj) {
			var customSubject = document.getElementById(\'customsubject\');
			var customText = document.getElementById(\'customtext\');
			var predefinedCoupon = document.getElementById(\'coupon\');

			if(obj.checked) {
				customSubject.removeAttribute(\'disabled\');
				customText.removeAttribute(\'disabled\');
				predefinedCoupon.setAttribute(\'disabled\', \'disabled\');
			} else {
				customSubject.setAttribute(\'disabled\', \'disabled\');
				customText.setAttribute(\'disabled\', \'disabled\');
				predefinedCoupon.removeAttribute(\'disabled\');
			}
		}
		</script>');

	$page = new Page('<a href="account_schedules.php">Account Schedules</a> &gt; Complete Schedule', 'Complete schedule for this contact.');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Complete schedule');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('Contact', sprintf('%s %s %s', $contact->Person->Name, $contact->Person->LastName, ($contact->Parent->ID > 0) ? sprintf('(%s)', $contact->Parent->Organisation->Name) : ''));
	echo $webForm->AddRow('Note', nl2br($schedule->Note));
	echo $webForm->AddRow($form->GetLabel('status'), $form->GetHTML('status') . $form->GetIcon('status'));
	echo $webForm->AddRow($form->GetLabel('note'), $form->GetHTML('note') . $form->GetIcon('note'));
	echo $webForm->AddRow($form->GetLabel('reschedule'), $form->GetHTML('reschedule') . $form->GetIcon('reschedule'));
	echo $webForm->AddRow($form->GetLabel('custom'), $form->GetHTML('custom') . $form->GetIcon('custom'));
	echo $webForm->AddRow($form->GetLabel('coupon'), $form->GetHTML('coupon') . $form->GetIcon('coupon'));
	echo $webForm->AddRow($form->GetLabel('customsubject'), $form->GetHTML('customsubject') . $form->GetIcon('customsubject'));
	echo $webForm->AddRow($form->GetLabel('customtext'), $form->GetHTML('customtext') . $form->GetIcon('customtext'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'account_schedules.php\';"> <input type="submit" name="complete" value="complete" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactSchedule.php');

	if(isset($_REQUEST['id'])) {
		$schedule = new ContactSchedule();
		$schedule->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function open() {
	if(isset($_REQUEST['cid'])) {
		$_SESSION['data']['account_schedules']['last_contact'] = $_REQUEST['cid'];
		
		redirectTo(sprintf('contact_profile.php?cid=%d', $_REQUEST['cid']));
	}
	
	redirectTo('?action=view');
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

	if(!isset($_SESSION['preferences']['account_schedules']['type'])) {
		$_SESSION['preferences']['account_schedules']['type'] = 2;
	}

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'view', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('type', 'Type', 'select', isset($_SESSION['preferences']['account_schedules']['type']) ? $_SESSION['preferences']['account_schedules']['type'] : '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('type', '0', '');
	
	UserRecent::Record('Account Schedules', 'account_schedules.php');

	$data = new DataQuery(sprintf("SELECT * FROM contact_schedule_type ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('type', $data->Row['Contact_Schedule_Type_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if(isset($_REQUEST['purge'])) {
			new DataQuery(sprintf("DELETE FROM contact_schedule WHERE Contact_Schedule_Type_ID=%d AND Is_Complete='N' AND Owned_By=%d", mysql_real_escape_string($form->GetValue('type')), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		} else {
			$_SESSION['preferences']['account_schedules']['type'] = $form->GetValue('type');
		}
		
		redirectTo('?action=view');
	}

	$page = new Page('Account Schedules', 'Below is a list of active schedules for your accounts.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Filter schedules');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Filter the schedules by the following type.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type'));
	echo $webForm->AddRow('', sprintf('<input type="submit" name="filter" value="filter" class="btn" tabindex="%s" /> %s', $form->GetTabIndex(), ($form->GetValue('type') == 4) ? sprintf('<input type="submit" name="purge" value="purge schedules" class="btn" tabindex="%s" onclick="return confirmText(\'Are you sure you wish to purge all schedules?\');" />', $form->GetTabIndex()) : ''));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT cs.Contact_Schedule_ID) AS Contact_Schedules FROM contact_schedule AS cs WHERE cs.Owned_By=%d AND cs.Is_Complete='Y' AND cs.Completed_On>'%s'", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), date('Y-m-d 00:00:00')));
	echo sprintf('<br /><p>Schedules completed today: <strong>%d</strong></p>', $data->Row['Contact_Schedules']);
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(cs.Contact_Schedule_ID) AS Count, cst.Name AS Type FROM contact_schedule AS cs LEFT JOIN contact_schedule_type AS cst ON cs.Contact_Schedule_Type_ID=cst.Contact_Schedule_Type_ID WHERE cs.Owned_By=%d AND cs.Is_Complete='Y' AND cs.Completed_On>'%s' GROUP BY cs.Contact_Schedule_Type_ID", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), date('Y-m-d 00:00:00')));
	if($data->TotalRows > 0) {
		echo '<p><strong>Completion Breakdown</strong><br />';

		while($data->Row) {
			echo sprintf('%s: <strong>%d</strong><br />', !empty($data->Row['Type']) ? $data->Row['Type'] : 'Other', $data->Row['Count']);

			$data->Next();
		}

		echo '</p>';
	}
	$data->Disconnect();

	$table = new DataTable("schedules");
	$table->SetSQL(sprintf("SELECT cu.Customer_ID, cs2.Contact_Schedule_ID, cs2.Scheduled_On, cst.Name AS Type, ADDDATE(NOW(), INTERVAL -5 DAY) AS Scheduled_Red, ADDDATE(NOW(), INTERVAL -4 DAY) AS Scheduled_Yellow, cs2.Note, c.Contact_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Contact_Name, cs.Completed_On AS Last_Contacted_On, COUNT(o2.Order_ID) AS Orders, FORMAT(AVG(o2.Total), 2) AS OrderAverage FROM contact_schedule AS cs2 INNER JOIN contact AS c ON c.Contact_ID=cs2.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID LEFT JOIN (SELECT Contact_ID, MAX(Completed_On) AS Completed_On FROM contact_schedule WHERE Is_Complete='Y' GROUP BY Contact_ID) AS cs ON c.Contact_ID=cs.Contact_ID LEFT JOIN contact_schedule_type AS cst ON cs2.Contact_Schedule_Type_ID=cst.Contact_Schedule_Type_ID LEFT JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID LEFT JOIN orders AS o2 ON o2.Customer_ID=cu.Customer_ID AND o2.Status<>'Unauthenticated' WHERE cs2.Owned_By=%d AND cs2.Is_Complete='N' AND cs2.Scheduled_On<ADDTIME(NOW(), '02:00:00') %s GROUP BY cs2.Contact_Schedule_ID", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), ($form->GetValue('type') > 0) ? sprintf("AND cs2.Contact_Schedule_Type_ID=%d", mysql_real_escape_string($form->GetValue('type'))) : ''));
	$table->SetExtractVars();
	
	if(isset($_SESSION['data']['account_schedules']['last_contact'])) {
		$table->AddBackgroundCondition('Contact_ID', $_SESSION['data']['account_schedules']['last_contact'], '==', '#99C5FF', '#77B0EE');
	}
	
	$table->AddBackgroundCondition('Scheduled_On', 'Scheduled_Red', '<', '#FF9999', '#EE7777');
	$table->AddBackgroundCondition('Scheduled_On', 'Scheduled_Yellow', '<', '#FFF499', '#EEE177');
	$table->AddBackgroundCondition('Scheduled_On', '0000-00-00 00:00:00', '!=', '#99FF99', '#77EE77');
	$table->AddField('', 'Contact_ID', 'hidden');
	$table->AddField('ID#', 'Contact_Schedule_ID', 'left');
	$table->AddField('Scheduled', 'Scheduled_On', 'left');
	$table->AddField('Contact', 'Contact_Name', 'left');
	$table->AddField('Last Contacted', 'Last_Contacted_On', 'left');
	
	if($form->GetValue('type') == 0) {
		$table->AddField('Type', 'Type', 'left');
	}
	
	$table->AddField('Note', 'Note', 'left');
	$table->AddField('Orders', 'Orders', 'right');
	$table->AddField('Average Order', 'OrderAverage', 'right');
	$table->AddLink("enquiry_summary.php?customerid=%s", "<img src=\"images/icon_help_1.gif\" alt=\"New Enquiry\" border=\"0\">", "Customer_ID");
	$table->AddLink("?action=open&cid=%s", "<img src=\"images/folderopen.gif\" alt=\"View Profile\" border=\"0\">", "Contact_ID");
	$table->AddLink("contact_schedules.php?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Edit\" border=\"0\">", "Contact_Schedule_ID");
	$table->AddLink("javascript:confirmRequest('account_schedules.php?action=cold&id=%s', 'Are you sure you want to mark this contact as cold?');", "<img src=\"./images/icon_cold_1.gif\" alt=\"Mark as Cold\" border=\"0\">", "Contact_ID");
	$table->AddLink("account_schedules.php?action=complete&id=%s", "<img src=\"./images/aztector_5.gif\" alt=\"Complete\" border=\"0\">", "Contact_Schedule_ID");
	$table->AddLink("javascript:confirmRequest('account_schedules.php?action=remove&id=%s', 'Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Contact_Schedule_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Scheduled_On");
	$table->Order = "ASC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}