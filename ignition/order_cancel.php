<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');

$session->Secure(3);

$order = new Order($_REQUEST['orderid']);
$order->PaymentMethod->Get();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'cancel', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'hidden', $order->ID, 'numeric_unsigned', 1, 11);
$form->AddField('reason', 'Reason', 'text', '', 'paragraph', 1, 128, true, 'style="width: 300px;"');
$form->AddField('comment', 'Comment', 'textarea', '', 'paragraph', 1, 1024, false, 'style="width: 300px;" rows="5"');

if(isset($_REQUEST['confirm'])){
	if($form->Validate()) {
		$order->Cancel($form->GetValue('reason'), $form->GetValue('comment'));

		echo '<html>
				<script>
					function closeWindow(){
						window.opener.location.reload(true);
						window.self.close();
					}
				</script>
			  <body onload="closeWindow();">Closing Window...</body></html>';
		exit;
	}
}

$page = new Page(sprintf('[#%s%s] Cancel Order', $order->Prefix, $order->ID), 'This action will cancel the order and all of its items. Partially despatched orders will have the remainder of their undespatched lines cancelled.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('orderid');

echo sprintf('<strong>%s</strong><br />%s<br /><br />', $form->GetLabel('reason'), $form->GetHTML('reason'));
echo sprintf('<strong>%s</strong><br />%s<br /><br />', $form->GetLabel('comment'), $form->GetHTML('comment'));
echo sprintf('<input type="submit" name="cancel" value="cancel" class="btn" />');

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');