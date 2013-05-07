<?php
/* 
	Ignition version of paymentServer
*/
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(2);
// unlike the old system we'll create the order first
$orderId = id_param('orderid');
$action = param('action');
if($action != 'change') $action = 'new';

$_SESSION['PAYMENT_LAST_ACTION'] = $action;
// Ignition specific
$cart = new Cart($session, true);
$cart->Customer->Get();
$cart->Customer->Contact->Get();
$cart->Customer->Contact->Person->Get();
$cart->GetLines();
$cart->Calculate();


$order = new Order();
if(!$order->Get($orderId)){
	$order->Customer = $cart->Customer;
	if($cart->TotalLines == 0){
		redirect("Location: order_cart.php");
	}
	$order->Prefix = 'M';
	$order->Referrer = 'None (Manual Order)';
	$order->Total = $cart->Total;
	$order->PaymentMethod->GetByReference('card');
	$order->GenerateFromCart($cart, 'Incomplete');
	redirect("Location: {$_SERVER['PHP_SELF']}?action={$action}&orderid=" . $order->ID);
}

$total = $order->Total;

if($action == 'new'){
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
		redirect("Location: order_cart.php");
	}
	if($cart->Error) {
		redirect("Location: order_summary.php");
	}
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
}

// this is the new payment processing requirements
$gateway = new PaymentGateway();
$hasGateway = $gateway->GetDefault();
$isVerifyingPayments = ($hasGateway && ($gateway->HasPreAuth == 'Y')) ? true : false;
$nextUrl = null;

if($isVerifyingPayments) {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/SagePay.php');
	$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);
	$paymentProcessor->setAccountType('M'); // always MOTO account for back-end
	$paymentProcessor->setAmount($total, 'GBP');
	$paymentProcessor->setDescription($GLOBALS['COMPANY'] . ' Credit Card Authentication');
	if($action == 'new') {
		$paymentProcessor->setCart($cart);
	}
	$paymentProcessor->setOrder($order);
	$paymentProcessor->setGateway($gateway);
	$nextUrl = $paymentProcessor->getAuthenticateUrl('ignition/');
}
$page = new Page('Payment Details', 'Please enter the card details below.');
$page->Display('header');

$window = new StandardWindow('Payment on Credit Account Available');
echo $window->Open();
echo $window->AddHeader('<strong>Credit Card Payment</strong>');
echo $window->OpenContent();

if($nextUrl){ 
?>
	<iframe src="<?php echo $nextUrl; ?>" width="100%" height="900"></iframe>
<?php } else { ?>
	<h1>Oops...</h1>
	<p>It looks like we're having trouble connecting to our payment provider.</p>
	<p>If you would like to continue with your order please call <strong>01473 716 418</strong> quoting <strong>Order Reference <?php echo $order->Prefix.$order->ID; ?></strong></p>
	<p>Or, if you'd like you can try again...</p>
	
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<input type="hidden" name="orderid" value="<?php echo $order->ID; ?>" />
		<input type="submit" name="Retry" value="Retry" class="submit" title="Checkout Your Shopping Cart">
	</form>
<?php } 
echo $window->CloseContent();
echo $window->Close();
$page->Display('footer');
require_once('lib/common/app_footer.php');