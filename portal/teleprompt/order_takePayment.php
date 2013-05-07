<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Template.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TelePrompt.php');

$session->Secure(2);

$changeOrder = false;
$order = new Order();

if(isset($_REQUEST['orderid'])){
	$changeOrder = true;
	$order->Get($_REQUEST['orderid']);
	$order->Customer->Get();
	$order->Customer->Contact->Get();
	$order->Customer->Contact->Person->Get();
}

if(!$changeOrder){
	$cart = new Cart($session, true);
	$cart->Customer->Get();
	$cart->Customer->Contact->Get();
	$cart->Customer->Contact->Person->Get();
	$cart->Calculate();
	$order->Customer = $cart->Customer;
	if($cart->TotalLines == 0){
		redirect("Location: order_cart.php");
	}
	$order->Prefix = 'M';
	$order->Referrer = 'None (Manual Order)';
	$order->Total = $cart->Total;
}

$selectPayment = ($changeOrder && ($order->ProformaID > 0)) ? true : false;

$form = new Form($_SERVER['PHP_SELF']);
$form->DisableAutocomplete = true;
$form->Icons['valid'] = '';
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

if($changeOrder){
	$form->AddField('orderid', 'Order ID', 'hidden', $_REQUEST['orderid'], 'numeric_unsigned', 1, 11);
}

if(!$selectPayment) {
	$form->AddField('isOnAccount', 'Pay on Account?', 'radio', 'N', 'boolean', NULL, NULL, false);
	$form->AddOption('isOnAccount', 'Y', 'Pay Using My Credit Account');
	$form->AddOption('isOnAccount', 'N', 'Pay By Credit/Debit Card');

	if(empty($order->Customer->Contact->ID)) $order->Customer->Get();
	$order->Customer->GetRemaingAllowance($order->ID);
} else {
	$form->AddField('paymentdate', 'Payment Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('payment', 'Payment Method', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('payment', '', '');
	
	$data = new DataQuery(sprintf("SELECT Payment_Method_ID, Method, Reference FROM payment_method WHERE Reference NOT IN ('google', 'paypal') ORDER BY Method ASC"));
	while($data->Row) {
		$form->AddOption('payment', $data->Row['Payment_Method_ID'], $data->Row['Method']);

		$data->Next();
	}
	$data->Disconnect();
}

if(isset($_REQUEST['confirm'])) {
	if(!$selectPayment) {
	    if($form->GetValue('isOnAccount') != 'Y') {
			if($changeOrder) $q = '?action=change&orderid=' . $order->ID;
			redirect(sprintf("Location: paymentServer.php" . $q));
		}

		if($form->Validate()) {
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
				if($changeOrder) $q = '?action=change&orderid=' . $order->ID;
				redirect(sprintf("Location: paymentServer.php" . $q));
			}

			if($form->Valid) {
				if(!$changeOrder) {
					$order->GenerateFromCart($cart);
					$order->SendEmail();
				} else {
					$order->Update();
				}

				if(!$changeOrder) {
					$cipher = new Cipher($order->ID);
					$cipher->Encrypt();
					redirect(sprintf("Location: order_complete.php?o=%s", base64_encode($cipher->Value)));
				} else {
					redirect(sprintf("Location: order_payment.php?orderid=%d", $order->ID));
				}
			}
		}
	} else {
		if($form->Validate()) {
			$order->Customer->Contact->IsProformaAccount = 'Y';
			$order->PaymentMethod->ID = $form->GetValue('payment');
			$order->PaymentReceivedOn = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('paymentdate'), 6, 4), substr($form->GetValue('paymentdate'), 3, 2), substr($form->GetValue('paymentdate'), 0, 2));
			$order->Update();

			$findReplace = new FindReplace();
			$findReplace->Add('/\[ORDER_ID\]/', $order->ID);
			$findReplace->Add('/\[ORDER_REFERENCE\]/', $order->Prefix . $order->ID);
			$findReplace->Add('/\[ORDER_DATE\]/', cDatetime($order->OrderedOn, 'longdate'));
			$findReplace->Add('/\[ORDER_BILLING_ADDRESS\]/', $order->GetBillingAddress());
			$findReplace->Add('/\[ORDER_SHIPPING_ADDRESS\]/', $order->GetShippingAddress());
			$findReplace->Add('/\[ORDER_TOTAL\]/', $order->Total);
			$findReplace->Add('/\[ORDER_PAYMENT_DATE\]/', cDatetime($order->PaymentReceivedOn, 'longdate'));
			$findReplace->Add('/\[CUSTOMER_ID\]/', $order->Customer->Contact->ID);
			$findReplace->Add('/\[CUSTOMER_NAME\]/', $order->Customer->Contact->Person->GetFullName());
			
			$html = $findReplace->Execute(Template::GetContent('email_order_payment_received'));

			$findReplace = new FindReplace();
			$findReplace->Add('/\[BODY\]/', $html);
			$findReplace->Add('/\[NAME\]/', 'Accountant');

			$templateEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
			$templateHtml = '';

			for($i=0; $i<count($templateEmail); $i++) {
				$templateHtml .= $findReplace->Execute($templateEmail[$i]);
			}

			$queue = new EmailQueue();
			$queue->GetModuleID('orders');
			$queue->Subject = sprintf("%s Order Payment Received [%s%s]", $GLOBALS['COMPANY'], $order->Prefix, $order->ID);
			$queue->Body = $templateHtml;
			$queue->ToAddress = 'accounts@bltdirect.com';
			$queue->Priority = 'H';
			$queue->Add();
			
			redirect(sprintf("Location: order_payment.php?orderid=%d", $order->ID));
		}
	}
}

if($changeOrder) {
	$page = new Page('Change Order Payment Details', 'Please select your preferred payment method and your credit card information below.');
} else {
	$page = new Page('Create New Order Manually', 'Finally, please select your preferred payment method and your credit card information below.');
}

$page->LinkScript('js/scw.js');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHtml('confirm');

if($changeOrder){
	echo $form->GetHtml('orderid');
}

$prompt = new TelePrompt();
$prompt->Output('ordertakepayment');

echo $prompt->Body;

if(!$selectPayment) {
	$displayRadio = false;
	if(strtoupper($order->Customer->IsCreditActive) == 'Y' && $order->Customer->CreditRemaining > 0 && $order->Customer->CreditRemaining >= $order->Total){
		$displayRadio = true;
		$window = new StandardWindow('Payment on Credit Account Available');
		echo $window->Open();
		echo $window->AddHeader(sprintf('%s <strong>%s</strong>', $form->GetHTML('isOnAccount', 1), $form->GetLabel('isOnAccount', 1)));
		echo $window->OpenContent();

				?>
				<table cellspacing="0" class="form">
					<tr>
						<td align="right" width="50%">Charge My Credit Account for:</td>
						<td><strong>&pound;<?php echo number_format($order->Total, 2, '.', ','); ?></strong></td>
					</tr>
					<tr>
						<td align="right" width="50%">Monthly Credit Allowance:</td>
						<td>&pound;<?php echo number_format($order->Customer->CreditLimit, 2, '.', ','); ?></td>
					</tr>
					<tr>
						<td align="right" width="50%">Remaining Credit Before Spend:</td>
						<td>&pound;<?php echo number_format($order->Customer->CreditRemaining, 2, '.', ','); ?></td>
					</tr>
					<tr>
						<td align="right" width="50%">Remaining Credit After Spend:</td>
						<td>&pound;<?php echo number_format($order->Customer->CreditRemaining-$order->Total, 2, '.', ','); ?></td>
					</tr>
					<tr>
						<td align="right" width="50%">My Credit Terms:</td>
						<td><?php echo $order->Customer->CreditPeriod; ?> Days</td>
					</tr>
				</table>

				<br />
				<?php
				echo $window->CloseContent();
				echo $window->Close();
	} elseif(strtoupper($order->Customer->IsCreditActive) == 'Y' && ($order->Customer->CreditRemaining <= 0 || $order->Customer->CreditRemaining < $order->Total)){
		$window = new StandardWindow('Payment on Credit Account Unavailable');
		echo $window->Open();
		$tempStr = sprintf('?orderid=%d', $order->ID);
		echo $window->AddHeader(sprintf('<span class="alert"><img src="./images/icon_alert_2.gif" align="absmiddle" />
						Your Credit Account has insufficient funds remaining this month to purchase on credit (See Details Below). You may continue with purchase via Credit/Debit Card. To Edit this Customer\'s Credit Account Settings <a href="customer_credit.php?customer=%d&direct=%s%s">click here</a>.</span>', $order->Customer->ID, $_SERVER['PHP_SELF'], $tempStr));
		echo $window->OpenContent();
				?>
				<table cellspacing="0" class="form">
					<tr>
						<td colspan="2"><strong>Credit Account Customer</strong></td>
					</tr>
					<tr>
						<td align="right" width="50%">Charge My Credit Account for:</td>
						<td><strong>&pound;<?php echo number_format($order->Total, 2, '.', ','); ?></strong></td>
					</tr>
					<tr>
						<td align="right" width="50%">My Monthly Credit Allowance:</td>
						<td>&pound;<?php echo number_format($order->Customer->CreditLimit, 2, '.', ','); ?></td>
					</tr>
					<tr>
						<td align="right" width="50%">Remaining Credit Before Spend:</td>
						<td>&pound;<?php echo number_format($order->Customer->CreditRemaining, 2, '.', ','); ?></td>
					</tr>
					<tr>
						<td align="right" width="50%">Remaining Credit After Spend:</td>
						<td>&pound;<?php echo number_format($order->Customer->CreditRemaining-$order->Total, 2, '.', ','); ?></td>
					</tr>
					<tr>
						<td align="right" width="50%">My Credit Terms:</td>
						<td><?php echo $order->Customer->CreditPeriod; ?> Days</td>
					</tr>
				</table>
				<br />
				<?php
				echo $window->CloseContent();
				echo $window->Close();
	} else {
		$window = new StandardWindow('Payment by Credit Account Unavailable');
		echo $window->Open();
		echo $window->AddHeader('This Customer does not have a Credit Account.');
		echo $window->OpenContent();
				?>
				<table cellspacing="0" class="form">
					<tr>
						<td>You cannot place this order on credit account.</td>
					</tr>
				</table>
				<br />
				<?php
				echo $window->CloseContent();
				echo $window->Close();
	}
	echo "<br />";
	$window = new StandardWindow('Payment by Credit Card');
	echo $window->Open();
	if($displayRadio){
		echo $window->AddHeader(sprintf('%s <strong>%s</strong>', $form->GetHTML('isOnAccount', 2), $form->GetLabel('isOnAccount', 2)));
	} else {
		echo $window->AddHeader('Please complete the fields below. Required fields are marked with an asterisk (*).');
	}
	echo $window->OpenContent();
				?>
				<table cellspacing="0" class="form">
					<tr>
						<td align="right" width="50%">Charge My Credit Card for:</td>
						<td><strong>&pound;<?php echo number_format($order->Total, 2, '.', ','); ?></strong></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					<td>
					<?php if(!$changeOrder){ ?>
						<input type="submit" class="btn" name="Place Order" value="Place Order" id="placeOrder" />
					<?php } else { ?>
						<input type="submit" class="btn" name="Update Details" value="Update Details" id="updateDetails" />
					<?php } ?>
					</td>
					</tr>
				</table>
	<?php
	echo $window->CloseContent();
	echo $window->Close();
} else {
	$window = new StandardWindow('Payment on Credit Account Available');
		
	echo $window->Open();
	echo $window->AddHeader('Specify the payment details this proforma order.');
	echo $window->OpenContent();
	?>
		
	<table cellspacing="0" class="form">
		<tr>
			<td align="right" width="50%">Payment Method:</td>
			<td><?php echo $form->GetHTML('payment');?></td>
		</tr>
		<tr>
			<td align="right">Payment Date:</td>
			<td><?php echo $form->GetHTML('paymentdate');?></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><input type="submit" class="btn" name="continue" value="continue" /></td>
		</tr>
	</table>
	<br />
	
	<?php
	echo $window->CloseContent();
	echo $window->Close();
}

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');