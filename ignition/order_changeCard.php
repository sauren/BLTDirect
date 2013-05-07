<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(3);

$order = new Order($_REQUEST['orderid']);
$order->Customer->Get();
$order->Customer->Contact->Get();
$order->Customer->Contact->Person->Get();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('title', 'Card Holder Title', 'select', $order->Card->Title, 'anything', 1, 20);
$form->AddOption('title', '', '');

$data = new DataQuery("SELECT * FROM person_title ORDER BY Person_Title ASC");
while($data->Row){
	$form->AddOption('title', $data->Row['Person_Title'], $data->Row['Person_Title']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('initial', 'Card Holder First Initial', 'text', $order->Card->Initial, 'alpha_numeric', 1, 1, true, 'size="2"');
$form->AddField('surname', 'Card Holder Last Name', 'text', $order->Card->Surname, 'anything', 1, 60);
$form->AddField('cardNumber', 'Card Number', 'text', $order->Card->GetNumber(), 'numeric_unsigned', 16, 19);
$form->AddField('cardType', 'Card Type', 'select', $order->Card->Type->ID, 'numeric_unsigned', 1, 11);
$form->AddOption('cardType', '', '');

$data = new DataQuery("SELECT * FROM card_type ORDER BY Card_Type ASC");
while($data->Row){
	$form->AddOption('cardType', $data->Row['Card_Type_ID'], $data->Row['Card_Type']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('cvn', 'Card Verification Number', 'text', '', 'numeric_unsigned', 3, 3, true, 'size="4"');
$form->AddField('starts', 'Starts (MMYY)', 'text', '', 'numeric_unsigned', 4, 4, false, 'size="5"');
$form->AddField('expires', 'Expires (MMYY)', 'text', '', 'numeric_unsigned', 4, 4, true, 'size="5"');
$form->AddField('issue', 'Issue Number', 'text', '', 'numeric_unsigned', 1, 3, false, 'size="3"');

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if($form->Validate()) {
		$order->Card->Type->Get($form->GetValue('cardType'));
		$order->Card->SetNumber($form->GetValue('cardNumber'));
		$order->Card->Expires = $form->GetValue('expires');
		$order->Card->Title = $form->GetValue('title');
		$order->Card->Initial = $form->GetValue('initial');
		$order->Card->Surname = $form->GetValue('surname');

		$gateway = new PaymentGateway();

		if($gateway->GetDefault()) {
			require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/' . $gateway->ClassFile);

			$addressData = array();

			if(!empty($order->Billing->Address->Line1)) {
				$addressData[] = $order->Billing->Address->Line1;
			}

			if(!empty($order->Billing->Address->Line2)) {
				$addressData[] = $order->Billing->Address->Line2;
			}

			if(!empty($order->Billing->Address->Line3)) {
				$addressData[] = $order->Billing->Address->Line3;
			}

			if(!empty($order->Billing->Address->City)) {
				$addressData[] = $order->Billing->Address->City;
			}

			if(!empty($order->Billing->Address->Region->Name)) {
				$addressData[] = $order->Billing->Address->Region->Name;
			}

			if(!empty($order->Billing->Address->Country->Name)) {
				$addressData[] = $order->Billing->Address->Country->Name;
			}

			$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);
			$paymentProcessor->Amount = $order->Total;
			$paymentProcessor->Description = $GLOBALS['COMPANY'] . ' Credit Card Pre-Authorisation';
			$paymentProcessor->BillingAddress = implode(', ', $addressData);
			$paymentProcessor->BillingPostcode = $order->Customer->Contact->Person->Address->Zip;
			$paymentProcessor->ContactNumber = $order->Customer->Contact->Person->Phone1;
			$paymentProcessor->CustomerEMail = $order->Customer->GetEmail();
			$paymentProcessor->CardHolder = sprintf('%s %s %s', $form->GetValue('title'), $form->GetValue('initial'), $form->GetValue('surname'));
			$paymentProcessor->CardNumber = $form->GetValue('cardNumber');
			$paymentProcessor->StartDate = $form->GetValue('starts');
			$paymentProcessor->ExpiryDate = $form->GetValue('expires');
			$paymentProcessor->CardType = $order->Card->Type->Reference;
			$paymentProcessor->ClientNumber = $order->Customer->ID;
			$paymentProcessor->IssueNumber = $form->GetValue('issue');
			$paymentProcessor->CV2 = $form->GetValue('cvn');
			$paymentProcessor->Payment->Gateway->ID = $gateway->ID;
			$paymentProcessor->setAccountType('M');

			if(!$paymentProcessor->PreAuthorise()){
				for($i=0; $i < count($paymentProcessor->Error); $i++){
					$form->AddError($paymentProcessor->Error[$i]);
				}
			}

			if($form->Valid){
				$order->IsDeclined = 'N';
				$order->IsFailed = 'N';
				$order->Update();

				$paymentProcessor->Payment->Order->ID = $order->ID;
				$paymentProcessor->Payment->Update();

				redirect(sprintf("Location: order_details.php?orderid=%d", $order->ID));
			}
		}
	}
}

$page = new Page(sprintf('<a href="order_details.php?orderid=%d">[#%s] Sales Order Details for %s</a> &gt; Change Card Details', $order->ID, $order->ID, $order->Customer->Contact->Person->GetFullName()), 'Please select your preferred payment method and your credit card information below.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

echo $form->Open();
echo $form->GetHtml('action');
echo $form->GetHtml('confirm');
echo $form->GetHtml('orderid');

$window = new StandardWindow('Payment by Credit/Debit Card');

echo $window->Open();
echo $window->AddHeader('Please complete the fields below. Required fields are marked with an asterisk (*).');
echo $window->OpenContent();
?>

<table cellspacing="0" class="form">
	<tr>
		<td align="right"><?php echo $form->GetLabel('title'); ?>:</td>
		<td><?php echo $form->GetHtml('title'); ?> <?php echo $form->GetIcon('title'); ?></td>
	</tr>
	<tr>
		<td align="right"><?php echo $form->GetLabel('initial'); ?>:</td>
		<td><?php echo $form->GetHtml('initial'); ?> <?php echo $form->GetIcon('initial'); ?></td>
	</tr>
	<tr>
		<td align="right"><?php echo $form->GetLabel('surname'); ?>:</td>
		<td><?php echo $form->GetHtml('surname'); ?> <?php echo $form->GetIcon('surname'); ?></td>
	</tr>
	<tr>
		<td align="right"><?php echo $form->GetLabel('cardNumber'); ?>:</td>
		<td><?php echo $form->GetHtml('cardNumber'); ?> <?php echo $form->GetIcon('cardNumber'); ?></td>
	</tr>
	<tr>
		<td align="right"><?php echo $form->GetLabel('cardType'); ?>:</td>
		<td><?php echo $form->GetHtml('cardType'); ?> <?php echo $form->GetIcon('cardType'); ?></td>
	</tr>
	<tr>
		<td align="right"><?php echo $form->GetLabel('starts'); ?>:</td>
		<td><?php echo $form->GetHtml('starts'); ?> <?php echo $form->GetIcon('starts'); ?></td>
	</tr>
	<tr>
		<td align="right"><?php echo $form->GetLabel('expires'); ?>:</td>
		<td><?php echo $form->GetHtml('expires'); ?> <?php echo $form->GetIcon('expires'); ?></td>
	</tr>
	<tr>
		<td align="right"><?php echo $form->GetLabel('issue'); ?>:</td>
		<td><?php echo $form->GetHtml('issue'); ?> <?php echo $form->GetIcon('issue'); ?></td>
	</tr>
	<tr>
		<td align="right"><?php echo $form->GetLabel('cvn'); ?>:</td>
		<td><?php echo $form->GetHtml('cvn'); ?> <img src="images/icon_cvn_1.gif" width="51" height="31" align="absmiddle" /><?php echo $form->GetIcon('cvn'); ?></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td><input type="submit" class="btn" name="update" value="update" /></td>
	</tr>
</table>

<?php
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');
?>