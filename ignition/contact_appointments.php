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
} else {
	$session->Secure(3);
	view();
	exit;
}

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactAppointment.php');

	if(isset($_REQUEST['id'])) {
		$appointment = new ContactAppointment();
		$appointment->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $_REQUEST['cid']));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactAppointment.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

	$contact = new Contact($_REQUEST['cid']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cid', 'Contact ID', 'hidden', $contact->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('date', 'Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('message', 'Message', 'textarea', '', 'anything', 1, 2000, true, 'style="width:100%; height:200px"');

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$appointment = new ContactAppointment();
			$appointment->ContactID = $form->GetValue('cid');
			$appointment->Message = $form->GetValue('message');
			$appointment->AppointmentOn = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('date'), 6, 4), substr($form->GetValue('date'), 3, 2), substr($form->GetValue('date'), 0, 2));
			$appointment->Add();

			redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $contact->ID));
		}
	}

	if($contact->Type == "O"){
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; Contact Appointments', $contact->ID, $contact->Organisation->Name), 'Add an appointment for this contact.');
		$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
		$page->Display('header');
	} else {
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; Contact Appointments', $contact->ID, $contact->Person->Name, $contact->Person->LastName), 'Add an appointment for this contact.');
		$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
		$page->Display('header');
	}

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add an appointment');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cid');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('date'), $form->GetHTML('date') . $form->GetIcon('date'));
	echo $webForm->AddRow($form->GetLabel('message'), $form->GetHTML('message') . $form->GetIcon('message'));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactAppointment.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

	$appointment = new ContactAppointment($_REQUEST['id']);
	$contact = new Contact($appointment->ContactID);

	$date = '';

	if($appointment->ScheduledOn != '0000-00-00 00:00:00') {
		$date = date('d/m/Y', strtotime($appointment->AppointmentOn));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Appointment ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('date', 'Date', 'text', $date, 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('message', 'Message', 'textarea', $appointment->Message, 'anything', 1, 2000, true, 'style="width:100%; height:200px"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			$appointment->Message = $form->GetValue('message');
			$appointment->AppointmentOn = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('date'), 6, 4), substr($form->GetValue('date'), 3, 2), substr($form->GetValue('date'), 0, 2));
			$appointment->Update();

			redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $contact->ID));
		}
	}

	if($contact->Type == "O"){
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; Contact Appointments', $contact->ID, $contact->Organisation->Name), 'Add a schedule for this contact.');
		$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
		$page->Display('header');
	} else {
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; Contact Appointments', $contact->ID, $contact->Person->Name, $contact->Person->LastName), 'Add a schedule for this contact.');
		$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
		$page->Display('header');
	}

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update an appointment');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('date'), $form->GetHTML('date') . $form->GetIcon('date'));
	echo $webForm->AddRow($form->GetLabel('message'), $form->GetHTML('message') . $form->GetIcon('message'));
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

	$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; Contact Schedules', mysql_real_escape_string($contact->ID), mysql_real_escape_string($contact->Person->Name), mysql_real_escape_string($contact->Person->LastName)), 'Below is a list of schedules for this contact.');
	$page->Display('header');

	$count = array();
	
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM orders WHERE Customer_ID=%d AND Status NOT IN ('Incomplete', 'Unauthenticated')", mysql_real_escape_string($customer->ID)));

	$count['Orders'] = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM quote WHERE Customer_ID=%d", mysql_real_escape_string($customer->ID)));
	$count['Quotes'] = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM invoice WHERE Customer_ID=%d", mysql_real_escape_string($customer->ID)));
	$count['Invoices'] = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM `return` WHERE Customer_ID=%d", mysql_real_escape_string($customer->ID)));
	$count['Returns'] = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM credit_note AS c INNER JOIN orders AS o ON o.Order_ID=c.Order_ID WHERE o.Customer_ID=%d", mysql_real_escape_string($customer->ID)));
	$count['Credits'] = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM customer_product AS cp INNER JOIN product AS p ON p.Product_ID=cp.Product_ID WHERE cp.Customer_ID=%d", mysql_real_escape_string($customer->ID)));
	$count['Products'] = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM contact_note WHERE Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
	$count['Notes'] = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM contact_schedule WHERE Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
	$count['Schedules'] = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM campaign_contact WHERE Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
	$count['Campaigns'] = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM enquiry WHERE Customer_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
	$count['Enquiries'] = $data->Row['Count'];
	$data->Disconnect();

	$customerOptions = sprintf('<p>Customer Options: <a href="customer_orders.php?customer=%1$d">View Order History (%3$d)</a> | <a href="customer_quotes.php?customer=%1$d">View Quote History (%4$d)</a> | <a href="customer_invoices.php?customer=%1$d">View Invoice History (%5$d)</a> | <a href="customer_returns.php?customer=%1$d">View Return History (%6$d)</a> | <a href="customer_credits.php?customer=%1$d">View Credit History (%7$d)</a> | <a href="customer_products.php?customer=%1$d">View Products (%8$d)</a> | <a href="contact_notes.php?cid=%2$d">View Notes (%9$d)</a> | <a href="contact_schedules.php?cid=%2$d">View Schedules (%10$d)</a> | <a href="contact_campaigns.php?cid=%2$d">View Campaigns (%11$d)</a> | <a href="customer_enquiries.php?customer=%1$d">View Enquiries (%12$d)</a> | <a href="customer_affiliate.php?customer=%1$d">View Affiliate Information</a> | <a href="discount_schema_customer.php?customer=%1$d">Discount Schema Options</a> | <a href="customer_credit.php?customer=%1$d">Credit Account Settings</a> | <a href="customer_security.php?id=%1$d&cid=%2$d&isCustomer=y">Security settings</a></p>', $customer->ID, $customer->Contact->ID, $count['Orders'], $count['Quotes'], $count['Invoices'], $count['Returns'], $count['Credits'], $count['Products'], $count['Notes'], $count['Schedules'], $count['Campaigns'], $count['Enquiries']);

	if($customer->Contact->IsSupplier=='Y') {
		$data = new DataQuery(sprintf("SELECT Supplier_ID FROM supplier WHERE Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$supplier = new Supplier($data->Row['Supplier_ID']);
		$data->Disconnect();

		$count = array();

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM supplier_product WHERE Supplier_ID=%d", mysql_real_escape_string($supplier->ID)));
		$count['Products'] = $data->Row['Count'];
		$data->Disconnect();

		$customerOptions = sprintf('<p>Supplier Options: <a href="customer_security.php?id=%1$d&cid=%2$d&isCustomer=n">Security settings</a> | <a href="products_supplier.php?sid=%1$d&cid=%2$d">Products Supplied (%3$d)</a> | <a href="supplier_settings.php?sid=%1$d&cid=%2$d">Supplier Settings</a>', mysql_real_escape_string($supplier->ID), mysql_real_escape_string($customer->Contact->ID), mysql_real_escape_string($count['Products']));

		if($supplier->IsComparable == 'Y') {
			$customerOptions .= sprintf('| <a href="supplier_categories.php?sid=%1$d&cid=%2$d">Supplier Categories</a>', $supplier->ID, $customer->Contact->ID);
		}

		$customerOptions .= '</p>';

		$products->Disconnect();
	}

	echo $customerOptions;

	$table = new DataTable("records");
	$table->SetSQL(sprintf("SELECT ca.* FROM contact_appointment AS ca WHERE ca.ContactID=%d", mysql_real_escape_string($contact->ID)));
	$table->AddField('ID#', 'ContactAppointmentID', 'left');
	$table->AddField('Appointment On', 'AppointmentOn', 'left');
	$table->AddField('Message', 'Message', 'left');
	$table->AddLink("?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Edit\" border=\"0\">", "ContactAppointmentID");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s&cid=" . $contact->ID . "', 'Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "ContactAppointmentID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("AppointmentOn");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input name="schedule" type="button" value="add new appointment" class="btn" onclick="window.self.location.href=\'?action=add&cid=%d\';" />', $contact->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}