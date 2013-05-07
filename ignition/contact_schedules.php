<?php
require_once('lib/common/app_header.php');

if($action == 'add') {
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'update') {
	$session->Secure(3);
	update();
	exit;
} elseif($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'complete') {
	$session->Secure(3);
	complete();
	exit;
} else {
	$session->Secure(3);
	view();
	exit;
}

function complete() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactSchedule.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

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
	$form->AddField('message', 'Message', 'textarea', '', 'anything', 1, 2000, true, 'style="width:100%; height:200px"');
	$form->AddField('reschedule', 'Reschedule', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			$schedule->Status = $form->GetValue('status');
			$schedule->Message = $form->GetValue('message');
			$schedule->Complete();

			$date = $form->GetValue('reschedule');

			if(!empty($date)) {
				$message = $form->GetValue('message');

				$reschedule = new ContactSchedule();
				$reschedule->ContactID = $schedule->ContactID;
				$reschedule->Type->ID = $schedule->Type->ID;
				$reschedule->Note = sprintf('%s%s', $schedule->Note, !empty($message) ? sprintf("\n\n%s", $message) : '');
				$reschedule->ScheduledOn = sprintf('%s-%s-%s 00:00:00', substr($date, 6, 4), substr($date, 3, 2), substr($date, 0, 2));
				$reschedule->OwnedBy = $schedule->OwnedBy;
				$reschedule->Add();
			}

			redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $schedule->ContactID));
		}
	}

	$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; <a href="contact_schedules.php?cid=%d">Contact Schedules</a> &gt; Complete Schedule', $contact->ID, $contact->Person->Name, $contact->Person->LastName, $contact->ID), 'Complete schedule for this contact.');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
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
	echo $webForm->AddRow($form->GetLabel('message'), $form->GetHTML('message') . $form->GetIcon('message'));
	echo $webForm->AddRow($form->GetLabel('reschedule'), $form->GetHTML('reschedule') . $form->GetIcon('reschedule'));
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

	redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $_REQUEST['cid']));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactSchedule.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

	$contact = new Contact($_REQUEST['cid']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cid', 'Contact ID', 'hidden', $contact->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('scheduled', 'Scheduled', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('note', 'Note', 'textarea', '', 'anything', 1, 2000, true, 'style="width:100%; height:200px"');
	$form->AddField('type', 'Type', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('type', '0', '');

	$data = new DataQuery(sprintf("SELECT * FROM contact_schedule_type ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('type', $data->Row['Contact_Schedule_Type_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$schedule = new ContactSchedule();
			$schedule->ScheduledOn = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('scheduled'), 6, 4), substr($form->GetValue('scheduled'), 3, 2), substr($form->GetValue('scheduled'), 0, 2));
			$schedule->ContactID = $form->GetValue('cid');
			$schedule->Type->ID = $form->GetValue('type');
			$schedule->Note = $form->GetValue('note');
			$schedule->OwnedBy = $GLOBALS['SESSION_USER_ID'];
			$schedule->Add();

			redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $contact->ID));
		}
	}

	if($contact->Type == "O"){
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; Contact Schedules', $contact->ID, $contact->Organisation->Name), 'Add a schedule for this contact.');
		$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
		$page->Display('header');
	} else {
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; Contact Schedules', $contact->ID, $contact->Person->Name, $contact->Person->LastName), 'Add a schedule for this contact.');
		$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
		$page->Display('header');
	}

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add a schedule');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cid');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type') . $form->GetIcon('type'));
	echo $webForm->AddRow($form->GetLabel('scheduled'), $form->GetHTML('scheduled') . $form->GetIcon('scheduled'));
	echo $webForm->AddRow($form->GetLabel('note'), $form->GetHTML('note') . $form->GetIcon('note'));
	echo $webForm->AddRow('', sprintf('<input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactSchedule.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

	$schedule = new ContactSchedule($_REQUEST['id']);
	$contact = new Contact($schedule->ContactID);

	$date = '';

	if($schedule->ScheduledOn != '0000-00-00 00:00:00') {
		$date = date('d/m/Y', strtotime($schedule->ScheduledOn));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Schedule ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('scheduled', 'Scheduled', 'text', $date, 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('note', 'Note', 'textarea', $schedule->Note, 'anything', 1, 2000, true, 'style="width:100%; height:200px"');
	$form->AddField('type', 'Type', 'select', $schedule->Type->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('type', '0', '');

	$data = new DataQuery(sprintf("SELECT * FROM contact_schedule_type ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('type', $data->Row['Contact_Schedule_Type_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			$schedule->ScheduledOn = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('scheduled'), 6, 4), substr($form->GetValue('scheduled'), 3, 2), substr($form->GetValue('scheduled'), 0, 2));
			$schedule->Note = $form->GetValue('note');
			$schedule->Update();

			redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $contact->ID));
		}
	}

	if($contact->Type == "O"){
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; Contact Schedules', $contact->ID, $contact->Organisation->Name), 'Add a schedule for this contact.');
		$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
		$page->Display('header');
	} else {
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; Contact Schedules', $contact->ID, $contact->Person->Name, $contact->Person->LastName), 'Add a schedule for this contact.');
		$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
		$page->Display('header');
	}

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update a schedule');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type') . $form->GetIcon('type'));
	echo $webForm->AddRow($form->GetLabel('scheduled'), $form->GetHTML('scheduled') . $form->GetIcon('scheduled'));
	echo $webForm->AddRow($form->GetLabel('note'), $form->GetHTML('note') . $form->GetIcon('note'));
	echo $webForm->AddRow('', sprintf('<input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

	$contact = new Contact($_REQUEST['cid']);

	$data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));

	$customer = new Customer($data->Row['Customer_ID']);
	$customer->Contact->Get();

	$data->Disconnect();

	$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; Contact Schedules', $contact->ID, $contact->Person->Name, $contact->Person->LastName), 'Below is a list of schedules for this contact.');
	$page->Display('header');

	$table = new DataTable("schedules");
	$table->SetSQL(sprintf("SELECT cs.*, cst.Name AS Type FROM contact_schedule AS cs LEFT JOIN contact_schedule_type AS cst ON cs.Contact_Schedule_Type_ID=cst.Contact_Schedule_Type_ID WHERE cs.Contact_ID=%d", $contact->ID));
	$table->AddField('ID#', 'Contact_Schedule_ID', 'left');
	$table->AddField('Scheduled On', 'Scheduled_On', 'left');
	$table->AddField('Created On', 'Created_On', 'left');
	$table->AddField('Type', 'Type', 'left');
	$table->AddField('Note', 'Note', 'left');
	$table->AddField('Is Complete', 'Is_Complete', 'center');
	$table->AddField('Completed On', 'Completed_On', 'left');
	$table->AddLink("contact_schedules.php?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Edit\" border=\"0\">", "Contact_Schedule_ID");
	$table->AddLink("contact_schedules.php?action=complete&id=%s", "<img src=\"./images/aztector_5.gif\" alt=\"Complete\" border=\"0\">", "Contact_Schedule_ID", true, false, array('Is_Complete', '==', 'N'));
	$table->AddLink("javascript:confirmRequest('contact_schedules.php?action=remove&id=%s&cid=" . $contact->ID . "', 'Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Contact_Schedule_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Scheduled_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input name="schedule" type="button" value="add new schedule" class="btn" onclick="window.self.location.href=\'contact_schedules.php?action=add&cid=%d\';" />', $contact->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
