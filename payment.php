<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

//redirectTo('cart.php');

$session->Secure();

$unassociatedProducts = 0;

for($i=0;$i<count($cart->Line);$i++) {
	if($cart->Line[$i]->Product->ID == 0) {
		$unassociatedProducts++;
	}
}

if($unassociatedProducts > 0) {
	$session->Customer->AvailableDiscountReward = 0;
}

if($cart->TotalLines == 0) {
	redirect("Location: cart.php");
}

if($cart->Error) {
	redirect("Location: summary.php");
}

$form = new Form($_SERVER['PHP_SELF']);
$form->DisableAutocomplete = true;
$form->Icons['valid'] = '';
$form->AddField('action', 'Action', 'hidden', 'pay', 'alpha', 3, 3);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('isOnAccount', 'Pay on Account?', 'radio', 'N', 'boolean', NULL, NULL, false);
$form->AddOption('isOnAccount', 'Y', 'Pay Using My Credit Account');
$form->AddOption('isOnAccount', 'N', 'Pay By Credit/Debit Card');


if(empty($cart->Customer->Contact->ID)){
	$cart->Customer->Get();
	$cart->Customer->Contact->Get();
}
$cart->Customer->GetRemaingAllowance();

// calculate correct values when a discount reward is present
if($session->Customer->AvailableDiscountReward > 0){
	$discount = $session->Customer->AvailableDiscountReward;
	if(($cart->SubTotal-$cart->Discount) < $discount) {
		$discount = ($cart->SubTotal-$cart->Discount);
	}

	$subTotal = ($cart->SubTotal-$cart->Discount)-$session->Customer->AvailableDiscountReward;
	if($subTotal < 0) {
		$subTotal = 0;
	}

	$remaining = $session->Customer->AvailableDiscountReward-($cart->SubTotal-$cart->Discount);
	if($remaining < 0) {
		$remaining = 0;
	}

	$taxTotal = $cart->CalculateCustomTax($subTotal+$cart->ShippingTotal);

	$total = $subTotal+$cart->ShippingTotal+$taxTotal;
} else {
	$total = $cart->Total;
}

if(param('confirm')) {
    if($form->GetValue('isOnAccount') != 'Y') {
		redirect(sprintf("Location: paymentServer.php"));
	}

	if($form->Validate()) {
		$order = new Order();
		$order->Referrer = $session->Referrer;
		$order->AffiliateID = $session->AffiliateID;

        $data = new DataQuery(sprintf("SELECT Payment_Method_ID FROM payment_method WHERE Reference LIKE '%s'", ($form->GetValue('isOnAccount') == 'Y') ? 'credit' : 'card'));
        if($data->TotalRows > 0) {
			$order->PaymentMethod->ID = $data->Row['Payment_Method_ID'];
		} else {
			$order->PaymentMethod->GetByReference('card');
		}
		$data->Disconnect();

		if($order->PaymentMethod->ID == 0) {
			$form->AddError('Payment method could not be located.');
		} else {
			$order->PaymentMethod->Get();
		}

        if($order->PaymentMethod->Reference == 'card') {
			redirect(sprintf("Location: paymentServer.php"));
		}

		if($form->Valid) {
			$order->GenerateFromCart($cart);
			$order->SendEmail();
			$cipher = new Cipher($order->ID);
			$cipher->Encrypt();
			redirect(sprintf("Location: complete.php?o=%s", base64_encode($cipher->Value)));
		}
	}
}
//require_once('lib/mobile' . $_SERVER['PHP_SELF']);
require_once('lib/' . $renderer . $_SERVER['PHP_SELF']);
require_once('lib/common/appFooter.php');