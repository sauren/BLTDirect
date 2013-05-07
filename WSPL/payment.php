<?php
require_once('../lib/common/appHeadermobile.php');
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
include("ui/nav.php");
include("ui/search.php");?>
<script type="text/javascript">
	function disableSubmit(){
		var placeOrder = document.getElementById('placeOrder');
		placeOrder.disabled = true;
	}

	function toggleType(obj) {
		var e = document.getElementById('issue');

		if(e) {
			switch(obj.value) {
				case '5':
				case '6':
					e.removeAttribute('disabled');
					break;
				default:
					e.setAttribute('disabled', 'disabled');
					break;
			}
		}
	}

	var disableIssue = <?php echo (($form->GetValue('cardType') == 5) || ($form->GetValue('cardType') == 6) || ($form->GetValue('cardType') == 7)) ? 'false' : 'true'; ?>

	window.onload = function() {
		if(disableIssue) {
			var e = document.getElementById('issue');

			if(e) {
				e.setAttribute('disabled', 'disabled');
			}
		}
	}
	</script>
    <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Payment</span></div>
<div class="maincontent">
<div class="maincontent1">
			<p>Finally, please select your preferred payment method and your credit card information below.</p>
			<?php
			if(!$form->Valid){
				echo $form->GetError();
				echo "<br />";
			}
			$form->OnSubmit('disableSubmit();');

			echo $form->Open();
			echo $form->GetHtml('action');
			echo $form->GetHtml('confirm');

			$displayRadio = false;
			if(strtoupper($cart->Customer->IsCreditActive) == 'Y' && $cart->Customer->CreditRemaining > 0 && $cart->Customer->CreditRemaining >= $total){
				$displayRadio = true;
			?>
			<table cellspacing="0" class="checkoutPayment">
				<tr>
					<td colspan="2"><?php echo $form->GetHTML('isOnAccount', 1); ?><strong><?php echo $form->GetLabel('isOnAccount', 1); ?></strong></td>
				</tr>
				<tr>
					<td align="right" width="50%">Charge My Credit Account for:</td>
					<td><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
				</tr>
				<tr>
					<td align="right" width="50%">My Monthly Credit Allowance:</td>
					<td>&pound;<?php echo number_format($cart->Customer->CreditLimit, 2, '.', ','); ?></td>
				</tr>
				<tr>
					<td align="right" width="50%">Remaining Credit Before Spend:</td>
					<td>&pound;<?php echo number_format($cart->Customer->CreditRemaining, 2, '.', ','); ?></td>
				</tr>
				<tr>
					<td align="right" width="50%">Remaining Credit After Spend:</td>
					<td>&pound;<?php echo number_format($cart->Customer->CreditRemaining-$total, 2, '.', ','); ?></td>
				</tr>
				<tr>
					<td align="right" width="50%">My Credit Terms:</td>
					<td><?php echo$cart->Customer->CreditPeriod; ?> Days</td>
				</tr>
			</table>

			<br />
			<?php } elseif(strtoupper($cart->Customer->IsCreditActive) == 'Y' && ($cart->Customer->CreditRemaining <= 0 || $cart->Customer->CreditRemaining < $total)){ ?>
			<table cellspacing="0" class="checkoutPayment">
				<tr>
					<td colspan="2"><strong>Credit Account Customer</strong></td>
				</tr>
				<tr>
					<td colspan="2"><span class="alert"><img src="../ignition/images/icon_alert_2.gif" align="absmiddle" />
					Your Credit Account has insufficient funds remaining this month to purchase on credit (See Details Below). You may continue with purchase via Credit/Debit Card.</span></td>
				</tr>
				<tr>
					<td align="right" width="50%">Charge My Credit Account for:</td>
					<td><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
				</tr>
				<tr>
					<td align="right" width="50%">My Monthly Credit Allowance:</td>
					<td>&pound;<?php echo number_format($cart->Customer->CreditLimit, 2, '.', ','); ?></td>
				</tr>
				<tr>
					<td align="right" width="50%">Remaining Credit Before Spend:</td>
					<td>&pound;<?php echo number_format($cart->Customer->CreditRemaining, 2, '.', ','); ?></td>
				</tr>
				<tr>
					<td align="right" width="50%">Remaining Credit After Spend:</td>
					<td>&pound;<?php echo number_format($cart->Customer->CreditRemaining-$total, 2, '.', ','); ?></td>
				</tr>
				<tr>
					<td align="right" width="50%">My Credit Terms:</td>
					<td><?php echo$cart->Customer->CreditPeriod; ?> Days</td>
				</tr>
			</table>
			<br />
			<?php } ?>

			<table cellspacing="0" class="checkoutPayment">
				<tr>
					<td colspan="2"><?php echo ($displayRadio)?$form->GetHTML('isOnAccount', 2):''; ?><strong><?php echo $form->GetLabel('isOnAccount', 2); ?></strong></td>
				</tr>
				<tr>
					<td align="right" width="50%">Charge My Credit Card for:</td>
					<td><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
				    <td><input type="submit" class="submit" name="Place Order" value="Place Order" id="placeOrder" on="on" /></td>
				</tr>
			</table>
			<?php echo $form->Close(); ?>
</div>
</div>
<?php
include("ui/footer.php");
require_once('../lib/common/appFooter.php');