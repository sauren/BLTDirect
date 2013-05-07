<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(2);

if($action == "update"){
	$session->Secure(3);
}

$direct = (isset($_REQUEST['direct'])) ? $direct = $_REQUEST['direct'] : '';

$customer = new Customer($_REQUEST['customer']);
$customer->Contact->Get();

$tempHeader = '';

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('customer', 'Customer ID', 'hidden', $customer->ID, 'numeric_unsigned', 1, 11);
$form->AddField('direct', 'Direct URL', 'hidden', $direct, 'paragraph', 1, 255, false);
$form->AddField('limit', 'Credit Limit (&pound;)', 'text', $customer->CreditLimit, 'float', 1, 32);
$form->AddField('period', 'Credit Invoice Terms (Days)', 'text', $customer->CreditPeriod, 'numeric_unsigned', 1, 4);
$form->AddField('active', 'Is Credit Account Active?', 'checkbox', $customer->IsCreditActive, 'boolean', NULL, NULL, false);
$form->AddField('dectivated', 'Is Credit Account Deactivated?', 'checkbox', $customer->IsCreditDeactivated, 'boolean', NULL, NULL, false);

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if($form->Validate()) {
		$customer->CreditLimit = $form->GetValue('limit');
		$customer->CreditPeriod = $form->GetValue('period');
		$customer->IsCreditDeactivated = $form->GetValue('dectivated');
		$customer->IsCreditActive = ($customer->IsCreditDeactivated == 'Y') ? 'N' : $form->GetValue('active');
		$customer->Update();

		if(!empty($direct)){
			redirect("Location:" . $direct);
		} else {
			redirect("Location: customer_credit.php?customer=".$customer->ID);
		}
	}
}

if($customer->Contact->HasParent){
	$tempHeader .= sprintf("<a href=\"contact_profile.php?cid=%d\">%s</a> &gt; ", $customer->Contact->Parent->ID, $customer->Contact->Parent->Organisation->Name);
}
$tempHeader .= sprintf("<a href=\"contact_profile.php?cid=%d\">%s %s</a> &gt;", $customer->Contact->ID, $customer->Contact->Person->Name, $customer->Contact->Person->LastName);

$page = new Page(sprintf('%s Credit Account Settings for %s', $tempHeader, $customer->Contact->Person->GetFullName()), sprintf('Allow %s to purchase from you using a credit account. No need for credit cards. And invoice will be sent with credit terms.', $customer->Contact->Person->GetFullName()));
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

$window = new StandardWindow('Update Credit Account');
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('customer');
echo $form->GetHTML('direct');
echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('limit'), $form->GetHTML('limit') . $form->GetIcon('limit'));
echo $webForm->AddRow($form->GetLabel('period'), $form->GetHTML('period') . $form->GetIcon('period'));
echo $webForm->AddRow($form->GetHTML('active'), $form->GetLabel('active') . $form->GetIcon('active'));
echo $webForm->AddRow($form->GetHTML('dectivated'), $form->GetLabel('dectivated') . $form->GetIcon('dectivated'));
echo $webForm->AddRow("&nbsp;", sprintf('<input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');