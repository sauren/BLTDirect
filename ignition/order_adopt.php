<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Coupon.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CouponContact.php");

$session->Secure(2);

global $cart;

$cart = new Cart($session, true);
$cart->Calculate();

if(param('adopt') && id_param('ref')){
	$success = $cart->Adopt(id_param('ref'));
	if(!$success){
		$page = new Page('Sorry...', '');
		$page->Display('header');
		echo '<p>I was unable to adopt the shopping cart with reference ' . id_param('ref') . '</p>';
		$page->Display('footer');
	} else {
		redirect("Location: order_cart.php");
	}
} else if(param('release') && id_param('ref')){
	$cart->Release(id_param('ref'));
	redirect("Location: order_cart.php");
}

require_once('lib/common/app_footer.php');