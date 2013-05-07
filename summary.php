<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProductLocation.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerLocation.php');

require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

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

if($cart->TotalLines == 0){
	redirect("Location: cart.php");
}

$shipping = NULL;
$billing = &$session->Customer->Contact->Person;

$locations = array();

$data = new DataQuery(sprintf("SELECT * FROM customer_location WHERE CustomerID=%d", mysql_real_escape_string($session->Customer->ID)));
while($data->Row) {
	$locations[strtolower(trim($data->Row['Name']))] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('coupon', 'Coupon Code', 'text', '', 'alpha_numeric', 1, 15, false);
$form->AddField('taxexemptcode', 'Tax Exempt Code', 'text', $cart->TaxExemptCode, 'paragraph', 0, 20, false);

$form->AddField('isOnAccount', 'Pay on Account?', 'radio', 'N', 'boolean', NULL, NULL, false);
$form->AddOption('isOnAccount', 'Y', 'Pay Using My Credit Account');
$form->AddOption('isOnAccount', 'N', 'Pay By Credit/Debit Card');


if(empty($cart->Customer->Contact->ID)){
	$cart->Customer->Get();
	$cart->Customer->Contact->Get();
}
$cart->Customer->GetRemaingAllowance();

for($i=0; $i < count($cart->Line); $i++) {
	$form->AddField(sprintf('location_%d', $cart->Line[$i]->ID), 'Product Location', 'text', '', 'anything', 1, 120, false, 'style="width:100%"');
}

switch(strtolower($action)){
	case 'removecoupon':
		removeCoupon();
		break;
}

if(param('shipTo')){
	$cart->ShipTo = param('shipTo');
	$cart->Update();

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

if(empty($cart->ShipTo)){
	redirect("Location: checkout.php?action=change");
}

if($action == 'save as quote') {
	$shippingIDNo = $cart->ShipTo;
	$shippingContact = new CustomerContact;
	if($cart->ShipTo != "billing"){
		$shippingContact->validateCustomerContact($shippingIDNo,'F');
	}
	if($unassociatedProducts == 0) {
		if(param('shipTo')){	// Quote has come from summary.php
			$cart->ShipTo = param('shipTo');
		}
		if(!empty($cart->ShipTo)){
			for($i=0; $i < count($cart->Line); $i++){
				$cp = new CustomerProduct();
				$cp->Product = $cart->Line[$i]->Product;
				$cp->Customer = $session->Customer;
				$cp->Add();

				$value = $form->GetValue(sprintf('location_%d', $cart->Line[$i]->ID));

				if(!empty($value)) {
					if(isset($locations[strtolower(trim($value))])) {
						$data = new DataQuery(sprintf("SELECT * FROM customer_product_location WHERE CustomerLocationID=%d AND CustomerProductID=%d", $locations[strtolower(trim($value))]['CustomerLocationID'], $cp->ID));
						if($data->TotalRows == 0) {
							$productLocation = new CustomerProductLocation();
							$productLocation->Product->ID = $cp->ID;
							$productLocation->Location->ID = $locations[strtolower(trim($value))]['CustomerLocationID'];
							$productLocation->Add();
						}
						$data->Disconnect();
					} else {
						$customerLocation = new CustomerLocation();
						$customerLocation->Customer->ID = $cp->Customer->ID;
						$customerLocation->Name = $value;
						$customerLocation->Add();

						$productLocation = new CustomerProductLocation();
						$productLocation->Product->ID = $cp->ID;
						$productLocation->Location->ID = $customerLocation->ID;
						$productLocation->Add();
					}
				}
			}

			$quote = new Quote();
			$quote->GenerateFromCart($cart);
			$quote->SendEmail();

			$cart->Delete();

			redirect(sprintf("Location: quote.php?quoteid=%d", $quote->ID));
		}
	}
} elseif($action == 'proceed to payment') {
	$shippingIDNo = $cart->ShipTo;
	$shippingContact = new CustomerContact;
	if($cart->ShipTo != "billing"){
		$shippingContact->validateCustomerContact($shippingIDNo,'F');
	}
	for($i=0; $i < count($cart->Line); $i++){
		$cp = new CustomerProduct();
		$cp->Product = $cart->Line[$i]->Product;
		$cp->Customer = $session->Customer;
		$cp->Add();

		$value = $form->GetValue(sprintf('location_%d', $cart->Line[$i]->ID));

		if(!empty($value)) {
			if(isset($locations[strtolower(trim($value))])) {
				$data = new DataQuery(sprintf("SELECT * FROM customer_product_location WHERE CustomerLocationID=%d AND CustomerProductID=%d", $locations[strtolower(trim($value))]['CustomerLocationID'], $cp->ID));
				if($data->TotalRows == 0) {
					$productLocation = new CustomerProductLocation();
					$productLocation->Product->ID = $cp->ID;
					$productLocation->Location->ID = $locations[strtolower(trim($value))]['CustomerLocationID'];
					$productLocation->Add();
				}
				$data->Disconnect();
			} else {
				$customerLocation = new CustomerLocation();
				$customerLocation->Customer->ID = $cp->Customer->ID;
				$customerLocation->Name = $value;
				$customerLocation->Add();

				$productLocation = new CustomerProductLocation();
				$productLocation->Product->ID = $cp->ID;
				$productLocation->Location->ID = $customerLocation->ID;
				$productLocation->Add();
			}
		}
	}

	redirect("Location: payment.php?shipTo=".param('shipTo'));

} elseif($action == 'update quote') {
	if(param('shipTo')) {
		$cart->ShipTo = param('shipTo');
	}
	$quote = new Quote($cart->QuoteID);
	$quote->GenerateFromCart($cart);

	$cart->Delete();

	redirect("Location: quote.php?&quoteid={$quote->ID}");

} elseif($action == "continue"){

	//debug($cart->ShipTo ,1);

	$shippingIDNo = $cart->ShipTo;
	$shippingContact = new CustomerContact;
	if($cart->ShipTo != "billing"){
		$shippingContact->validateCustomerContact($shippingIDNo,'F');
	}

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
			redirect(sprintf("Location: complete.php?o=%s&paymenttype=%s", base64_encode($cipher->Value),$order->PaymentMethod->Reference));
		}
	}

} elseif($action == "pay by card"){
	$shippingIDNo = $cart->ShipTo;
	$shippingContact = new CustomerContact;
	if($cart->ShipTo != "billing"){
		$shippingContact->validateCustomerContact($shippingIDNo,'F');
	}

	if($form->GetValue('isOnAccount') != 'Y') {
		redirect(sprintf("Location: paymentServer.php"));
	}
}








function removeCoupon(){
	global $cart;

	if(param('confirm')) {
		$cart->Coupon->ID = 0;
		$cart->Update();

		redirect("Location:summary.php?shipTo=" . param('shipTo'));
	}
}


if(id_param('changePostage')){
	$cart->Postage = id_param('changePostage');
	$cart->Update();
	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

if(strtolower($cart->ShipTo) == 'billing'){
	$shipping = &$session->Customer->Contact->Person;
} else {
	$shipping = new CustomerContact($cart->ShipTo);
}

if(($cart->ShippingCountry->ID != $shipping->Address->Country->ID) || ($cart->ShippingRegion->ID  != $shipping->Address->Region->ID)) {
	$cart->ShippingCountry->ID = $shipping->Address->Country->ID;
	$cart->ShippingRegion->ID = $shipping->Address->Region->ID;
	$cart->Update();
	$cart->Reset();
	$cart->Calculate();
}

if(param('confirm')) {
	if(param('addcoupon')) {
		if($form->Validate('coupon')) {
			$coupon = new Coupon();
			$coupon->Reference = $form->GetValue('coupon');
			
			if(!empty($coupon->Reference)) {
				if($coupon->Check($coupon->Reference, $cart->SubTotal, $cart->Customer->ID, true)) {
					$cart->Coupon->ID = $coupon->ID;
					$cart->Update();
				} else {
					foreach($coupon->Errors as $key=>$value){
						$form->AddError($value, 'coupon');
					}
				}
			}
		}

		if($form->Valid){
			redirect("Location: summary.php");
		}
	} elseif(param('updatetax')) {
		if($cart->BillingCountry->ID != $GLOBALS['SYSTEM_COUNTRY']) {
			$cart->TaxExemptCode = $form->GetValue('taxexemptcode');
			$cart->Calculate();
			$cart->Update();
		}
	}
}
//require_once('lib/mobile' . $_SERVER['PHP_SELF']);
require_once('lib/' . $renderer . $_SERVER['PHP_SELF']);
require_once('lib/common/appFooter.php');