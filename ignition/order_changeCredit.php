<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

$session->Secure(3);

$order = new Order($_REQUEST['orderid']);
$order->Customer->Get();
$order->Customer->Contact->Get();
$order->Customer->Contact->Person->Get();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('active', 'Credit Active?', 'checkbox', $order->Customer->IsCreditActive, 'boolean', 1, 1, false);
$form->AddField('limit', 'Credit Limit (&pound;)', 'text', $order->Customer->CreditLimit, 'float', 1, 32);

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
	if($form->Validate()) {
		$order->Customer->IsCreditActive = $form->GetValue('active');
		$order->Customer->CreditLimit = $form->GetValue('limit');
		$order->Customer->Update();

		redirect(sprintf("Location: order_details.php?orderid=%d", $order->ID));
	}
}

$page = new Page(sprintf('<a href="order_details.php?orderid=%d">[#%s] Sales Order Details for %s</a> &gt; Change Credit Details', $order->ID, $order->ID, $order->Customer->Contact->Person->GetFullName()), 'Please select your credit account information below.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHtml('action');
echo $form->GetHtml('confirm');
echo $form->GetHtml('orderid');

$window = new StandardWindow('Update Credit Setting');
$webForm = new StandardForm();

echo $window->Open();
echo $window->AddHeader('Please complete the fields below. Required fields are marked with an asterisk (*).');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
echo $webForm->AddRow($form->GetLabel('limit'), $form->GetHTML('limit') . $form->GetIcon('limit'));
echo $webForm->AddRow("&nbsp;", sprintf('<input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');
?>