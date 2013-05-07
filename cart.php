<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountBanding.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountBandingBasket.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountBandingBasketLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Postage.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductCart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductCookie.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/GoogleCheckout.php');

$cart->GetShippingLines();

$bandingBasket = new DiscountBandingBasket($session);
$bandingBasket->Banding->Get();

if($bandingBasket->Total > 0) {
	$cart->DiscountBandingID = ($cart->SubTotal >= $bandingBasket->Banding->Threshold) ? $bandingBasket->Banding->ID : 0;
	$cart->Calculate();
	$cart->Update();
}

$unassociatedProducts = 0;

for($i=0;$i<count($cart->Line);$i++) {
	if($cart->Line[$i]->Product->ID == 0) {
		$unassociatedProducts++;
	}
}

echo $session->ID;

if($unassociatedProducts > 0) {
	$session->Customer->AvailableDiscountReward = 0;
}

if($action == 'remove') {
	if(id_param('line')) {
		$line = new CartLine();
		$line->Remove(id_param('line'));
		redirect("Location: cart.php");
	}
} elseif($action == 'removecoupon') {
	$cart->Coupon->ID = 0;
	$cart->Update();
	redirect("Location: cart.php");
} elseif($action == 'switch') {
	if(id_param('line')) {
		if(id_param('product')) {
			$cart->ChangeLine(id_param('line'), id_param('product'));
			$cart->Calculate();
		}
	}
	redirect("Location: cart.php");
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('coupon', 'Coupon Code', 'text', '', 'alpha_numeric', 1, 15, false);

for($i=0; $i < count($cart->Line); $i++){
	$form->AddField('qty_' . $cart->Line[$i]->ID, 'Quantity of ' . $cart->Line[$i]->Product->Name, 'text',  $cart->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="3"');
}

if(id_param('changePostage') && (id_param('changePostage') > 0)) {
	$cart->Postage = id_param('changePostage');
	$cart->Update();
	redirect('Location: cart.php');
	
} elseif(param('continueshopping')) {
	redirect('Location: products.php');
}

if($action == 'update' && param('confirm')) {
	if($form->Validate()){
		$quantitiesUpdated = false;
		for($i=0; $i < count($cart->Line); $i++){

			if(is_numeric($form->GetValue('qty_' . $cart->Line[$i]->ID))
			&& ($cart->Line[$i]->Quantity
			!= $form->GetValue('qty_' . $cart->Line[$i]->ID))
			&& $form->GetValue('qty_' . $cart->Line[$i]->ID) > 0)
			{
				$cart->Line[$i]->Quantity = $form->GetValue('qty_' . $cart->Line[$i]->ID);
				$cart->Line[$i]->Update();
				$quantitiesUpdated = true;
			}
		}

		$tmpCoupon = $form->GetValue('coupon');
		if(!empty($tmpCoupon)){
			if($quantitiesUpdated){
				$cart->Calculate();
			}
			$coupon = new Coupon();

			if($coupon->Check($form->GetValue('coupon'), $cart->SubTotal, $cart->Customer->ID, true)){
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
		redirect("Location: cart.php");
	}
}

if($action == 'checkout'){
	if($bandingBasket->Total == 0) {
		$data = new DataQuery(sprintf("SELECT Discount_Banding_ID FROM discount_banding WHERE Trigger_Low<=%f AND Trigger_High>%f LIMIT 0, 1", mysql_real_escape_string($cart->SubTotal), mysql_real_escape_string($cart->SubTotal)));
		if($data->TotalRows > 0) {
			$bandingBasket->GenerateFromCart($cart, $data->Row['Discount_Banding_ID']);
			$cart->DiscountBandingOffered = 'Y';
			$cart->Update();
			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
		$data->Disconnect();
	}
	redirect("Location: checkout.php");
}

$groupsType = array();
$groupsEquivalentWattage = array();
$groupsWattage = array();
$groupsLampLife = array();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE 'type'"));
while($data->Row) {
	$groupsType[] = $data->Row['Group_ID'];
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE '%%equivalent%%' AND Reference LIKE '%%wattage%%'"));
while($data->Row) {
	$groupsEquivalentWattage[] = $data->Row['Group_ID'];
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE 'wattage'"));
while($data->Row) {
	$groupsWattage[] = $data->Row['Group_ID'];
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE '%%lamp%%' AND Reference LIKE '%%life%%'"));
while($data->Row) {
	$groupsLampLife[] = $data->Row['Group_ID'];
	$data->Next();	
}
$data->Disconnect();

//require_once('lib/mobile' . $_SERVER['PHP_SELF']);
require_once('lib/' . $renderer . $_SERVER['PHP_SELF']);
require_once('lib/common/appFooter.php');