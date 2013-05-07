<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');



if(trim(param('payment', '')) == 'google') {
	for($i=0; $i < count($cart->Line); $i++){
		$cart->Line[$i]->Remove();
	}
	$cart->Coupon->ID = 0;
	$cart->Update();
}

if(strlen(param('o', '')) > 0) {
    $session->Secure();

	$o = base64_decode(param('o'));
	$orderNum = new Cipher($o);
	$orderNum->Decrypt();

	$order = new Order($orderNum->Value);
	$order->PaymentMethod->Get();
	$order->GetLines();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('o', 'Order ID', 'hidden', $o, 'paragraph', 1, 100);

	if($order->PaymentMethod->Reference != 'google') {
		$form->AddField('custom', 'Custom Reference Number', 'text', '', 'alpha_numeric', 1, 32, false);
	}

	$form->AddField('message', 'Order Note', 'textarea', '', 'paragraph', 1, 2000, false, 'style="width:90%; height:100px"');
	$form->AddField('delivery', 'Delivery Instructions', 'textarea', '', 'paragraph', 1, 2000, false, 'style="width:90%; height:100px"');

	if(strtolower(param('confirm', '')) == "true"){
		if($form->Validate()){
			$note = new OrderNote();
			$note->Message = $form->GetValue('message');
			$note->OrderID = $order->ID;
			$note->IsPublic = 'Y';

			if(!empty($note->Message)){
				$order->Customer->Get();
				$order->Customer->Contact->Get();

				$note->Add();
				$note->SendToAdmin($order->Customer->Contact->Person->GetFullName(), $order->Customer->GetEmail());
			}

			if($order->PaymentMethod->Reference != 'google') {
				$order->CustomID = $form->GetValue('custom');
			}

			$order->DeliveryInstructions = $form->GetValue('delivery');
			$order->IsNotesUnread = 'Y';
			$order->Update();

			redirect(sprintf("Location: ordersconfirmation.php?orderid=%d", $order->ID));
		}
	}
}

require_once('lib/' . $renderer . $_SERVER['PHP_SELF']);
require_once('lib/common/appFooter.php');