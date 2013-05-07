<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(3);

$contact = new Contact($_REQUEST['cid']);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('cid', 'Contact ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('isproformaaccount', 'Is Proforma Account Active?', 'checkbox', $contact->IsProformaAccount, 'boolean', 1, 1, false);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$contact->IsProformaAccount = $form->GetValue('isproformaaccount');
		$contact->Update();

		redirect(sprintf('Location: contact_profile.php?cid=%d', $contact->ID));
	}
}

$tempHeader = '';

if($contact->HasParent) {
	$tempHeader .= sprintf("<a href=\"contact_profile.php?cid=%d\">%s</a> &gt; ", $contact->Parent->ID, $contact->Parent->Organisation->Name);
}

$page = new Page(sprintf('%s<a href="contact_profile.php?cid=%d">%s %s</a> &gt; Credit Account Settings for %s', $tempHeader, $contact->ID, $contact->Person->Name, $contact->Person->LastName, $contact->Person->GetFullName()), sprintf('Allow %s to purchase from you using a credit account. No need for credit cards. And invoice will be sent with credit terms.', $contact->Person->GetFullName()));
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Update Proforma Account');
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('cid');

echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('isproformaaccount'), $form->GetHTML('isproformaaccount') . $form->GetIcon('isproformaaccount'));
echo $webForm->AddRow('', sprintf('<input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');