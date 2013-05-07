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

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'send', 'alpha', 4, 4);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('email', 'Email Address', 'text', '', 'email', 1, 255);
$form->AddField('name', 'Name', 'text', '', 'anything', 1, 255);

$prompt = 'no';
if(!empty($cart->ShipTo) || !empty($cart->Customer->ID)){
	$prompt = 'yes';
	$cart->Customer->Get();
	$cart->Customer->Contact->Get();
}

if(isset($_REQUEST['cuid'])) {
	$prompt = 'no';

	$cart->Customer->Get($_REQUEST['cuid']);
	$cart->Customer->Contact->Get();
	$cart->Calculate();
	$cart->Update();
}

if($action == "refresh"){
	$session->Create($GLOBALS['SESSION_USER_ID']);

	redirect("Location: order_create.php");

} elseif($action == "removecustomer"){
	$cart->Customer->ID = 0;
	$cart->Update();

	redirect("Location: order_cart.php");

} elseif($action == "send"){
	$contact = new CouponContact();
	$contact->SendCoupon($form->GetValue('email'), $form->GetValue('name'));

	redirect("Location: order_create.php");
}

$page = new Page('Create a New Order Manually', '');
$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
$page->Display('header');
?>
<script language="javascript" type="text/javascript">
	var isPrompt  = '<?php echo $prompt; ?>';
	var refreshCart;
	if(isPrompt == 'yes'){
        refreshCart = confirm('This cart contains existing customer information for <?php echo $cart->Customer->Contact->Person->GetFullName(); ?>. Would you like to Refresh the Shopping cart for a new Order?');
		if(refreshCart) window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?action=refresh';
	}
</script>
<table width="100%" border="0">
  <tr>
    <td width="300" valign="top"><?php include('./order_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top">
    </strong>Before beginning a new order please check that the shopping cart to the left is empty. If items already exsit in your cart please click on the clear shopping cart in the toolbox. </p>
    <form id="form1" name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	  <input type="hidden" name="action" value="refresh" />
      <input type="submit" name="Submit" value="Refresh Cart" id="Submit" class="btn" />
    </form>

    <br /><br /><br />

    <p><strong>Email customer introduction coupon</strong><br />Enter the customers name and email address and click the submit button to send them the introduction coupon.</p>
	<?php
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo '<div style="float: left; padding: 0 5px 0 0;">';

	echo '<strong>'.$form->GetLabel('name').'</strong><br />';
	echo $form->GetHTML('name').'<br />';

	echo '</div><div style="float: left; padding: 0 5px 0 0;">';

	echo '<strong>'.$form->GetLabel('email').'</strong><br />';
	echo $form->GetHTML('email').'<br />';

	echo '</div><div style="float: left; padding: 0 5px 0 0;">';

	echo '&nbsp;<br /><input type="submit" class="btn" value="submit" name="submit" />';

	echo '</div><div style="clear: both;"></div>';

	echo $form->Close();
	?>

    </td>
  </tr>
</table>

<?php
$page->Display('footer');

require_once('lib/common/app_footer.php');