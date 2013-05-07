<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CreditNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CreditNoteLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Payment.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleRequest.php');

$session->Secure(3);

if(isset($_REQUEST['paymentid'])){
	if(is_numeric($_REQUEST['paymentid'])){
		$payment = new Payment($_REQUEST['paymentid']);
	} else {
		$payment = 'onaccount';
	}
} else {
	echo "payment information not sent";
}

$order = new Order($_REQUEST['orderid']);
$order->PaymentMethod->Get();
$order->Customer->Get();
$order->Customer->Contact->Get();

$lines = array();

$data = new DataQuery(sprintf('SELECT il.Invoice_Line_ID, il.Product_ID, il.Quantity, il.Description AS Product_Title, Price-(Line_Discount/Quantity) AS Amount FROM invoice_line AS il INNER JOIN invoice AS i ON i.Invoice_ID=il.Invoice_ID WHERE i.Order_ID=%1$d', mysql_real_escape_string($order->ID)));
while($data->Row) {
	$lines[] = $data->Row;

	$data->Next();	
}
$data->Disconnect();

$creditNote = new CreditNote();
$totalSub = 0;
$totalShipping = 0;
$totalCustom = 0;
$totalNet = 0;
$totalTax = 0;
$total = 0;

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 1, 12);
$form->SetValue('action','add');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'hidden', $order->ID, 'numeric_unsigned', 1, 11);
$form->AddField('paymentid', 'Payment ID', 'hidden', ($payment != 'onaccount') ? $payment->ID : 'onaccount', 'numeric_unsigned', 1, 11);

if($order->PaymentMethod->Reference == 'google') {
	$form->AddField('reason', 'Reason', 'text', '', 'paragraph', 1, 128, false);
	$form->AddField('comment', 'Comment', 'textarea', '', 'paragraph', 1, 1024, false, 'rows="5"');
}

if($payment == 'onaccount') {
	$form->AddField('date', 'Date Credited', 'text', date('d/m/Y'), 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
}

$quantities = array();

foreach($_REQUEST as $key=>$value) {
	if(preg_match('/^product_[\\d]*$/', $key)) {
		$items = explode('_', $key);

		if(count($items) == 2) {
			if(is_numeric($items[1])) {
				$quantities[$items[1]] = $value;
			}
		}
	}
}

$form->AddField('customRefund', 'Custom Refund', 'text',  '0.00', 'float', 1, 11, true, 'size="6" style="text-align:right"');
$form->AddField('customRefundText', 'Custom Refund Text', 'text', $_REQUEST['customRefundText'], 'paragraph', 0, 100, false, 'style="width:100%;"');
$form->AddField('shippingRefund', 'Shipping Refund', 'text',  '0.00', 'float', 1, 11, true, 'size="6" style="text-align:right"');
$form->AddField('taxrate', 'Override Tax Rate', 'select',  '', 'float', 1, 11);
$form->AddOption('taxrate', '', '');
$form->AddOption('taxrate', '0', '0.0%');
$form->AddOption('taxrate', '15', '15.0%');
$form->AddOption('taxrate', '17.5', '17.5%');
$form->AddOption('taxrate', '20', '20.0%');

$taxRate = 0;

foreach($lines as $line) {
	$product = new Product($line['Product_ID']);
	
	$form->AddField('qty_' . $line['Invoice_Line_ID'], 'Quantity of ' . $line['Product_Title'], 'text', isset($quantities[$line['Product_ID']]) ? $quantities[$line['Product_ID']] : 0, 'numeric_unsigned', 1, 9, true, 'size="3"');
	$form->AddField('price_' . $line['Invoice_Line_ID'], 'Price of ' . $line['Product_Title'], 'text',  number_format($line['Amount'], 2, '.', ''), 'float', 1, 11, true, 'size="3" style="text-align: right;"');

	$qty = $form->GetValue('qty_' . $line['Invoice_Line_ID']);
	$price = $form->GetValue('price_' . $line['Invoice_Line_ID']);
	$lineTotal = $qty * $price;
	$totalSub += $lineTotal;

	if(is_numeric($form->GetValue('taxrate'))) {
		$lineTax = $lineTotal * ($form->GetValue('taxrate') / 100);
        	$taxRate = $form->GetValue('taxrate');
	} else {
		$lineTax = $order->CalculateCustomTax($lineTotal);
		$taxRate = $order->GetTaxRate();
	}

	$totalTax += round($lineTax, 2);

	$creditLine = new CreditNoteLine();
	$creditLine->Quantity = $qty;
	$creditLine->Description = $line['Product_Title'];
	$creditLine->Product->ID = $line['Product_ID'];
	$creditLine->Price = $price;
	$creditLine->TotalNet = $lineTotal;
	$creditLine->TotalTax = $lineTax;
	$creditLine->Total = $lineTotal + $lineTax;
	
	$creditNote->Line[] = $creditLine;
}

$totalShipping = $form->GetValue('shippingRefund');
$totalCustom = $form->GetValue('customRefund');

$totalTax += round($order->CalculateCustomTax($totalShipping), 2);
$totalTax += round($order->CalculateCustomTax($totalCustom), 2);

$totalNet = $totalShipping + $totalCustom + $totalSub;
$total = $totalNet + $totalTax;

$creditNote->Order->ID = $order->ID;

if($payment == 'onaccount'){
	$creditNote->CreditType = 'Account Credited';
	$creditNote->CreditStatus = 'Authorised';
} else {
	$creditNote->CreditType = 'Card Refund';
	$creditNote->CreditStatus = 'Pending';
}

$creditNote->TaxRate = $taxRate;
$creditNote->TotalShipping = $totalShipping;
$creditNote->TotalCustom = $totalCustom;
$creditNote->TotalNet = $totalNet;
$creditNote->TotalTax = $totalTax;
$creditNote->Total = $total;
$creditNote->NominalCode = $order->NominalCode;
$creditNote->CustomText = isset($_REQUEST['customRefundText']) ? $_REQUEST['customRefundText'] : '';

if($payment == 'onaccount') {
	$creditNote->CreditedOn = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('date'), 6, 4), substr($form->GetValue('date'), 3, 2), substr($form->GetValue('date'), 0, 2));
}
	
if($action == 'add' && isset($_REQUEST['confirm'])){
	if($order->PaymentMethod->Reference == 'google') {
		$googleRequest = new GoogleRequest();

		if(!$googleRequest->refundOrder($order->CustomID, $creditNote->Total, $form->GetValue('reason'), $form->GetValue('comment'))) {
			$creditNote->CreditStatus = 'Failed';

			$page = new Page('Refund Error','An error occured whilst trying to refund the credit card. Details below:');
			$page->Display('header');

			echo '<p>' . $googleRequest->ErrorMessage . '</p>';
			echo '<p>You may need to manually refund the customer through Google Checkout. Please contact a system administrator.</p>';

			$page->Display('footer');

			require_once('lib/common/app_footer.php');
			exit;
		} else {
			$creditNote->CreditStatus = 'Authorised';
		}
	} else {
		$gateway = new PaymentGateway();

		if($payment != 'onaccount' && $gateway->GetDefault()){
			require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/' . $gateway->ClassFile);
			$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);

			$paymentProcessor->Amount = number_format($creditNote->Total, 2, '.', '');
			$paymentProcessor->Description = $GLOBALS['COMPANY'] . ' Card Refund';

			$billing = &$order->Customer->Contact->Person->Address;
			$addressData = array();
			if(!empty($billing->Line1)) $addressData[] = $billing->Line1;
			if(!empty($billing->Line2)) $addressData[] = $billing->Line2;
			if(!empty($billing->Line3)) $addressData[] = $billing->Line3;
			if(!empty($billing->City)) $addressData[] = $billing->City;
			if(!empty($billing->Region->Name)) $addressData[] = $billing->Region->Name;
			if(!empty($billing->Country->Name)) $addressData[] = $billing->Country->Name;
			$addressString = implode(', ', $addressData);

			$paymentProcessor->BillingAddress = $addressString;
			$paymentProcessor->BillingPostcode = $billing->Zip;
			$paymentProcessor->ContactNumber = $order->Customer->Contact->Person->Phone1;
			$paymentProcessor->CustomerEMail = $order->Customer->GetEmail();

			$paymentProcessor->CardHolder = sprintf('%s %s %s', $order->Card->Title, $order->Card->Initial, $order->Card->Surname);
			$paymentProcessor->CardNumber = $order->Card->GetNumber();
			$paymentProcessor->ExpiryDate = $order->Card->Expires;
			$order->Card->Type->Get();
			$paymentProcessor->CardType = $order->Card->Type->Reference;
			$paymentProcessor->ClientNumber = $order->Customer->ID;
			$paymentProcessor->Payment->Gateway->ID = $gateway->ID;
			$paymentProcessor->Payment->Order->ID = $order->ID;

			if(!$paymentProcessor->RefundCard($payment)){
				$creditNote->CreditStatus = 'Failed';
				$page = new Page('Refund Error','An error occured whilst trying to refund the credit card. Details below:');
				$page->Display('header');
				for($i=0; $i < count($paymentProcessor->Error); $i++){
					echo '<p>' . $paymentProcessor->Error[$i] . '</p>';
				}
				echo '<p>You may need to change the card details for this order. Please contact the customer and try again later.</p>';
				$page->Display('footer');
				require_once('lib/common/app_footer.php');
				exit;
			} else {
				$creditNote->CreditStatus = 'Authorised';
			}
		}

		if($order->PaymentMethod->Reference == 'credit') {
			$data = new DataQuery(sprintf("SELECT SUM(Total) AS Total FROM credit_note WHERE Order_ID=%d", mysql_real_escape_string($order->ID)));
			$totalCredit = $data->Row['Total'];
			$data->Disconnect();

			if(round($totalCredit + $creditNote->Total, 2) > $order->Total) {
				$form->AddError('The total credit amount for this order cannot exceed the original order amount.');
			}
		}
	}

	if($form->Valid) {
		$creditNote->Add();

		for($i=0; $i <count($creditNote->Line); $i++){
			if($creditNote->Line[$i]->Total > 0){
				$creditNote->Line[$i]->CreditNoteID = $creditNote->ID;
				$creditNote->Line[$i]->Add();
			}
		}

		$creditNote->EmailCustomer();

		redirect("Location: credit_notes.php?oid=" . $order->ID);
	}
}

$page = new Page('Order Refund', 'Please edit the quantity and price to be refunded on this order.');
$page->LinkScript('js/scw.js');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('orderid');
echo $form->GetHTML('paymentid');

if($payment == 'onaccount') {
	$window = new StandardWindow("Date credited");
	$webForm = new StandardForm();

	echo $window->Open();
	echo $window->AddHeader('Select a credited date for this refund.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('date'), $form->GetHTML('date') . $form->GetIcon('date'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo '<br />';
}
?>

<table cellspacing="0" class="orderDetails">
	<tr>
		<th>Qty</th>
		<th>Product</th>
		<th>Quickfind</th>
		<th>Price</th>
	</tr>
	<?php
	foreach($lines as $line){
	?>
	<tr>
		<td><?php echo $form->GetHTML('qty_'. $line['Invoice_Line_ID']); ?> x</td>
		<td><?php echo $line['Product_Title']; ?></td>
		<td align="right"><?php echo $line['Product_ID']; ?></td>
		<td align="right" width="100">&pound;<?php echo $form->GetHTML('price_'. $line['Invoice_Line_ID']); ?></td>
	</tr>
	<?php
	}
	?>
	<tr>
		<td align="left">Custom Refund</td>
		<td colspan="2"><?php echo $form->GetHTML('customRefundText'); ?></td>
		<td width="100" align="right">&pound;
		<?php echo $form->GetHTML('customRefund'); ?></td>
	</tr>
</table>

<table border="0" cellspacing="0" class="orderDetails">
	<tr>
		<td align="right">Shipping Refund</td>
		<td width="100" align="right">&pound;
		<?php echo $form->GetHTML('shippingRefund'); ?></td>
	</tr>
	<tr>
		<td align="right">Net</td>
		<td width="100" align="right">&pound;<?php echo number_format($totalNet, 2, '.', ''); ?></td>
	</tr>
	<tr>
		<td align="right">Override Tax Rate</td>
		<td width="100" align="right"><?php echo $form->GetHTML('taxrate'); ?></td>
	</tr>
	<tr>
		<td align="right">Tax</td>
		<td width="100" align="right">&pound;<?php echo number_format($totalTax, 2, '.', ''); ?></td>
	</tr>
	<tr>
		<td align="right">Total Refund</td>
		<td width="100" align="right" style="background-color:#eeeeee; border:1px solid #FF6600"><strong>&pound;<?php echo number_format($total, 2, '.', ''); ?></strong></td>
	</tr>
</table>
<br />

<?php
if($order->PaymentMethod->Reference == 'google') {
	$window = new StandardWindow("Return reason");
	$webForm = new StandardForm();

	echo $window->Open();
	echo $window->AddHeader('The refund explaination you enter below will be visible to the customer.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('reason'), $form->GetHTML('reason') . $form->GetIcon('reason'));
	echo $webForm->AddRow($form->GetLabel('comment'), $form->GetHTML('comment') . $form->GetIcon('comment'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo '<br />';
}
?>

<input type="button" name="back" value="back to order details" class="btn" onclick="window.location.href='order_details.php?orderid=<?php echo $order->ID; ?>';" />
<input type="submit" name="action" value="calculate" class="btn" />
<input type="submit" name="generate" value="generate credit note" class="btn" />

<?php
echo $form->Close();

$page->Display('footer');

require_once('lib/common/app_footer.php');