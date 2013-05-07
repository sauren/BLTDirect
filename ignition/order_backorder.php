<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Backorder.php');

$session->Secure(3);

$order = new Order($_REQUEST['orderid']);
$order->PaymentMethod->Get();
$order->Customer->Get();
$order->Customer->Contact->Get();

$ol = new OrderLine($_REQUEST['orderlineid']);

$date = '';

if($ol->BackorderExpectedOn > '0000-00-00 00:00:00') {
	$date = date('d/m/Y', strtotime($ol->BackorderExpectedOn));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('orderlineid', 'Order Line ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('date', 'Expected Arrival Date', 'text', $date, 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('redirect', 'Redirect', 'hidden', 'order_details.php', 'anything', 0, 255, false);

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	$form->Validate();

	if($form->Valid) {
		$expected = strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('date'), 6, 4), substr($form->GetValue('date'), 3, 2), substr($form->GetValue('date'), 0, 2)));
		$now = strtotime(date('Y-m-d 00:00:00'));

		if($expected < $now) {
			$form->AddError('Expected Arrival Date cannot be in the past.', 'date');
		}
	}

	if($form->Valid) {
		$delay = $expected - $now;
		$days = $delay / 86400;

		if($ol->BackorderExpectedOn > '0000-00-00 00:00:00') {
			$note = new OrderNote();
			$note->Message = sprintf('<strong>%s</strong><br />Quickfind Code: %d<br /><br />', $ol->Product->Name, $ol->Product->ID);
			$note->Message .= sprintf('This product is currently out of stock, delivery on this product will now be %d days.  Please visit your <a href="https://www.bltdirect.com/orders.php" target="_blank">orders</a> within your account centre should you wish to cancel this order.', $days);
			$note->TypeID = 7;
			$note->OrderID = $order->ID;
			$note->IsPublic = 'Y';
			$note->IsAlert = 'N';
			$note->Add();
		} else {
			$note = new OrderNote();
			$note->Message = sprintf('<strong>%s</strong><br />Quickfind Code: %d<br /><br />', $ol->Product->Name, $ol->Product->ID);
			$note->Message .= sprintf('This product is currently out of stock, delivery on this product will be %d days.  Please visit your <a href="https://www.bltdirect.com/orders.php" target="_blank">orders</a> within your account centre should you wish to cancel this order.', $days);
			$note->TypeID = 7;
			$note->OrderID = $order->ID;
			$note->IsPublic = 'Y';
			$note->IsAlert = 'N';
			$note->Add();

			if($order->PaymentMethod->Reference == 'google') {
				$googleRequest = new GoogleRequest();
				$googleRequest->backorderItems($order->CustomID, array($ol->Product->ID));
			}
		}

		$order->Backorder();

		$ol->Status = 'Backordered';
		$ol->BackorderExpectedOn = date('Y-m-d 00:00:00', $expected);
		$ol->Update();

		$backorder = new Backorder();

		if($backorder->GetByOrderLineID($ol->ID)) {
			$backorder->ExpectedOn = $ol->BackorderExpectedOn;
			$backorder->Update();
		} else {
			$backorder->Product->ID = $ol->Product->ID;
			$backorder->Supplier->ID = 0;
			$backorder->Quantity = $ol->Quantity;
			$backorder->ExpectedOn = $ol->BackorderExpectedOn;
			$backorder->OrderLine->ID = $ol->ID;
			$backorder->Add();
		}

		redirect(sprintf("Location: %s?orderid=%d", $form->GetValue('redirect'), $order->ID));
	}
}

$page = new Page(sprintf('<a href="order_details.php?orderid=%s">Order</a> &gt; Backorder %s', $order->ID, $ol->Product->Name), 'Backordering will mark your order lines as awaiting stock.');
$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

$window = new StandardWindow('Backorder Details');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('orderid');
echo $form->GetHTML('orderlineid');
echo $form->GetHTML('redirect');
echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('date'), $form->GetHTML('date') . $form->GetIcon('date'));
echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location.href=\'order_details.php?orderid=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $order->ID, $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$page->Display('footer');

require_once('lib/common/app_footer.php');