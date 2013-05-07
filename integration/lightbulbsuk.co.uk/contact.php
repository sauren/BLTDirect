<?php
require_once('../../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

$form = new Form('');
$form->AddField('title', '', 'hidden', '', 'anything', 1, 20, false);
$form->AddField('fname', '', 'hidden', '', 'anything', 1, 255, false);
$form->AddField('lname', '', 'hidden', '', 'anything', 1, 255, false);
$form->AddField('email', '', 'hidden', '', 'anything', 1, 255, false);
$form->AddField('phone', '', 'hidden', '', 'anything', 1, 255, false);
$form->AddField('subject', '', 'hidden', '', 'anything', 1, 255, false);
$form->AddField('message', '', 'hidden', '', 'anything', 1, 2000, false);

$customer = new Customer();
$customerFound = false;

$data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE Username LIKE '%s'", trim(strtolower($form->GetValue('email')))));
if($data->TotalRows > 0) {
	if($customer->Get($data->Row['Customer_ID'])) {
		$customerFound = true;
	}
}
$data->Disconnect();

if(!$customerFound) {
	$customer->Username = trim(strtolower($form->GetValue('email')));
	$customer->Contact->Type = 'I';
	$customer->Contact->IsCustomer = 'Y';
	$customer->Contact->Person->Title = $form->GetValue('title');
	$customer->Contact->Person->Name = $form->GetValue('fname');
	$customer->Contact->Person->LastName = $form->GetValue('lname');
	$customer->Contact->Person->Phone1 = $form->GetValue('phone');
	$customer->Contact->Person->Email  = $form->GetValue('email');
	$customer->Contact->OnMailingList = 'H';
	$customer->Contact->Add();
	$customer->Add();
}

$typeId = 0;

$data = new DataQuery(sprintf("SELECT Enquiry_Type_ID FROM enquiry_type WHERE Developer_Key LIKE 'salesenquiries'"));
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

$message = trim($form->GetValue('message'));

if(!empty($message)) {
	$enquiry = new Enquiry();
	$enquiry->Type->ID = $typeId;
	$enquiry->Customer->ID = $customer->ID;
	$enquiry->Status = 'Unread';
	$enquiry->Subject = $form->GetValue('subject');
	$enquiry->Add();

	$enquiryLine = new EnquiryLine();
	$enquiryLine->Enquiry->ID = $enquiry->ID;
	$enquiryLine->IsCustomerMessage = 'Y';
	$enquiryLine->Message = sprintf("Enquiry from lightbulbsuk.co.uk\n\n%s", $message);
	$enquiryLine->Add();
}

redirect(sprintf("Location: http://www.lightbulbsuk.co.uk/contact.php?status=sent"));