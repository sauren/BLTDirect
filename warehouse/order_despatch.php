<?php
require_once('lib/appHeader.php');

switch($action) {
	case 'step2':
		step2();
		break;
	case 'step3':
		step3();
		break;
	case 'step4':
		step4();
		break;
	default:
		step1();
		break;
}
exit;

function step1(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
	require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Page.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');

	global $session;

	$warehouse = new Warehouse($_REQUEST['warehouseid']);

	$order = new Order($_REQUEST['orderid']);
	$order->GetLines();
	$order->Customer->Get();
	$order->Customer->Contact->Get();
	$order->RecalculateCost();

	$page = new Page(sprintf('Despatch Order No.%s%s', $order->Prefix, $order->ID),'If you are despatching this order in part please edit the relevant quantities. To ship this order in full please click submit.');
	$page->Display('header');
	?>

	<form name="despatch" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<input type="hidden" name="action" value="despatch" />
	<input type="hidden" name="confirm" value="true" />
	<input type="hidden" name="action" value="step2" />
	<input type="hidden" name="orderid" value="<?php echo $order->ID; ?>" />
	<input type="hidden" name="warehouseid" value="<?php echo $_REQUEST['warehouseid']; ?>" />

	<?php
	$count = 0;

	for($i=0; $i<count($order->Line); $i++){
		$order->Line[$i]->DespatchedFrom->Contact->Get();

		if(empty($order->Line[$i]->DespatchID) && (($order->Line[$i]->DespatchedFrom->ID == $_REQUEST['warehouseid']) || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $warehouse->ID))) && ($order->Line[$i]->Status != 'Cancelled')) {
			if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($order->Line[$i]->Cost > 0))) {
				$count++;
			}
		}
	}

	if($count > 0) {
		?>
		<table border="0" cellpadding="4" cellspacing="0" class="orderDetails">
			<tr>
				<th>Quantity</th>
				<th>Description</th>
				<th>Warehouse</th>
				<th>Quickfind</th>
			</tr>
		<?php
		for($i=0; $i<count($order->Line); $i++){
			if(empty($order->Line[$i]->DespatchID) && (($order->Line[$i]->DespatchedFrom->ID == $_REQUEST['warehouseid']) || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $warehouse->ID))) && ($order->Line[$i]->Status != 'Cancelled')) {
				if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($order->Line[$i]->Cost > 0))) {
					echo "<tr>";
					echo '<td><input type="text" name="line' . $order->Line[$i]->ID  . '" value="' . $order->Line[$i]->Quantity . '" size="3" /></td>';
					echo "<td>{$order->Line[$i]->Product->Name}</td>";
					?>

					<td>
						<?php
						if($order->Line[$i]->DespatchedFrom->Type == 'B') {
							echo $order->Line[$i]->DespatchedFrom->Contact->Name;
						} elseif($order->Line[$i]->DespatchedFrom->Type == 'S') {
							$order->Line[$i]->DespatchedFrom->Contact->Contact->Get();

							echo $order->Line[$i]->DespatchedFrom->Contact->Contact->Parent->Organisation->Name;
						}
						?>
					</td>

					<?php
					echo "<td>{$order->Line[$i]->Product->ID}</td>";
					echo "</tr>";
				}
			}
		}
	?>
		</table>
		<br />
	<?php
	}
	?>

	<p>
	  <input type="submit" name="continue" value="Continue" class="btn">
	</p>
	</form>
	<?php
	$page->Display('footer');
	//require_once('lib/common/app_footer.php');
}

function step2(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Page.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

	global $session;

	$warehouse = new Warehouse($_REQUEST['warehouseid']);

	$order = new Order($_REQUEST['orderid']);
	$order->PaymentMethod->Get();
	$order->GetLines();
	$order->Customer->Get();
	$order->Customer->Contact->Get();

	$continue = false;
	$totalWeight = 0;

	for($i=0; $i<count($order->Line); $i++){
		if(isset($_REQUEST['line' . $order->Line[$i]->ID]) && ($_REQUEST['line' . $order->Line[$i]->ID] > 0)) {
			$continue = true;
		}
	}

	if(!$continue) {
		echo '<script language="javascript" type="text/javascript">window.close();</script>';
		exit;
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'step2', 'alphanumeric', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('confirm2', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('orderid', 'Order ID', 'hidden', $order->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('warehouseid', 'Warehouse ID', 'hidden', '', 'numeric_unsigned', 1, 11);

	for($i=0; $i<count($order->Line); $i++){
		if(empty($order->Line[$i]->DespatchID) && (($order->Line[$i]->DespatchedFrom->ID == $_REQUEST['warehouseid']) || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $warehouse->ID))) && ($order->Line[$i]->Status != 'Cancelled')) {
			if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($order->Line[$i]->Cost > 0))) {
				$order->Line[$i]->Product->Get();

				$form->AddField('line'.$order->Line[$i]->ID, 'OrderLine', 'hidden', $_REQUEST['line'.$order->Line[$i]->ID], 'numeric_unsigned', 1, 11);

				$totalWeight += $order->Line[$i]->Product->Weight * $_REQUEST['line'.$order->Line[$i]->ID];
			}
		}
	}

	$form->AddField('weight', 'Weight', 'text', $totalWeight, 'float', 0, 11, true, 'style="text-align: right;" size="5"');
	$form->AddField('boxes', 'Boxes', 'text', '1', 'numeric_unsigned', 1, 11, true, 'style="text-align: right;" size="5"');

	$data = new DataQuery("SELECT Courier_ID FROM courier WHERE Is_Default='Y'");
	$default = ($data->TotalRows > 0) ? $data->Row['Courier_ID'] : 0;
	$data->Disconnect();

	$form->AddField('courier', 'Courier', 'select', $default, 'anything', 1, 11, true);
	$form->AddOption('courier', '', '');

	$data = new DataQuery("SELECT Courier_ID, Courier_Name FROM courier ORDER BY Courier_Name ASC");
	while($data->Row) {
		$form->AddOption('courier', $data->Row['Courier_ID'], $data->Row['Courier_Name']);

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT Is_Tracking_Active FROM courier WHERE Courier_ID=%d", mysql_real_escape_string($form->GetValue('courier'))));
	$tracking = ($data->TotalRows > 0) ? $data->Row['Is_Tracking_Active'] : 'N';
	$data->Disconnect();

	$form->AddField('ref', 'Consigment Number/Reference', 'text', '', 'anything', 1, 32, (($order->Postage->Days == 1) && ($tracking == 'Y') && ($warehouse->IsNextDayTrackingRequired == 'Y')) ? true : false, 'size="15"');

	if(isset($_REQUEST['confirm2']) && (strtolower($_REQUEST['confirm2']) == 'true')) {
		if($form->Validate()) {
			step3();
		}
	}

	$page = new Page(sprintf('Despatch Order No.%s%s', $order->Prefix, $order->ID), 'Please confirm and enter your consignment information.');
	$page->Display('header');

	if(($order->Postage->Days == 1) && ($order->Status != 'Despatched') && ($order->Status != 'Cancelled') && ($warehouse->IsNextDayTrackingRequired == 'Y')) {
		?>

		<table class="bubbleinfo" cellspacing="0">
		  <tr>
		    <td valign="top"><img src="images/icon_alert_2.gif" width="16" height="16" align="absmiddle" /> <strong>Next Day Delivery:</strong><br />This order may require a courier tracking number to proceed.
		    </td>
		  </tr>
		</table>
		<br />

		<?php
	}

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('confirm2');
	echo $form->GetHTML('orderid');
	echo $form->GetHTML('warehouseid');

	for($i=0; $i<count($order->Line); $i++){
		if(empty($order->Line[$i]->DespatchID) && (($order->Line[$i]->DespatchedFrom->ID == $_REQUEST['warehouseid']) || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $warehouse->ID))) && ($order->Line[$i]->Status != 'Cancelled')) {
			if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($order->Line[$i]->Cost > 0))) {
				echo $form->GetHTML('line'.$order->Line[$i]->ID);
			}
		}
	}

	$count = 0;

	for($i=0; $i<count($order->Line); $i++){
		$order->Line[$i]->DespatchedFrom->Contact->Get();

		if(empty($order->Line[$i]->DespatchID) && (($order->Line[$i]->DespatchedFrom->ID == $_REQUEST['warehouseid']) || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $warehouse->ID))) && ($order->Line[$i]->Status != 'Cancelled')) {
			if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($order->Line[$i]->Cost > 0))) {
				$count++;
			}
		}
	}

	if($count > 0) {
	?>

	<table border="0" cellpadding="4" cellspacing="0" class="orderDetails">
		<tr>
			<th>Quantity</th>
			<th>Description</th>
			<th>Warehouse</th>
			<th>Quickfind</th>
		</tr>

		<?php
		for($i=0; $i<count($order->Line); $i++){
			if(empty($order->Line[$i]->DespatchID) && (($order->Line[$i]->DespatchedFrom->ID == $_REQUEST['warehouseid']) || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $warehouse->ID))) && ($order->Line[$i]->Status != 'Cancelled')) {
				if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($order->Line[$i]->Cost > 0))) {
					echo "<tr>";
					echo '<td>'. $_REQUEST['line'.$order->Line[$i]->ID].'x</td>';
					echo "<td>{$order->Line[$i]->Product->Name}</td>";
					?>

						<td>
							<?php
							if($order->Line[$i]->DespatchedFrom->Type == 'B') {
								echo $order->Line[$i]->DespatchedFrom->Contact->Name;
							} elseif($order->Line[$i]->DespatchedFrom->Type == 'S') {
								$order->Line[$i]->DespatchedFrom->Contact->Contact->Get();

								echo $order->Line[$i]->DespatchedFrom->Contact->Contact->Parent->Organisation->Name;
							}
							?>
						</td>

					<?php
					echo "<td>{$order->Line[$i]->Product->ID}</td>";
					echo "</tr>";
				}
			}
		}
		?>
	</table>
	<br />
		<?php
	}
	?>
	<strong>Consignment Information</strong><br />
	<table class="invoicePaymentDetails" cellspacing="0" width="100%">
		<tr>
			<th>Consignment Weight:	</th>
			<td><?php echo $form->GetHTML('weight'); ?> Kg</td>
		</tr>
		<tr>
			<th>Total Boxes:</th>
			<td><?php echo $form->GetHTML('boxes'); ?></td>
		</tr>
		<tr>
			<th>Courier:</th>
			<td><?php echo $form->GetHTML('courier'); ?></td>
		</tr>
		<tr>
			<th>Consigment Number/Reference: (if applicable)</th>
			<td><?php echo $form->GetHTML('ref'); ?></td>
		</tr>
	</table>
	<br />
	
    <input type="submit" name="despatch" value="despatch" class="btn" />

	<?php
	echo $form->Close();
}

function step3() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DespatchLine.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/InvoiceLine.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountCollection.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PaymentGateway.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Page.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Payment.php");
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Log.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierShippingCalculator.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ShippingCostCalculator.php');

	$warehouse = new Warehouse($_REQUEST['warehouseid']);

	$redirect = $_SERVER['PHP_SELF'] . '?action=step4';

	$generateInvoice = true;
	$generatePurchase = false;

	$order = new Order($_REQUEST['orderid']);
	$order->GetLines();
	$order->Customer->Get();
	$order->Customer->Contact->Get();

	if($order->PaymentMethod->ID == 0) {
		$order->PaymentMethod->GetByReference('card');
		$order->Update();
	} else {
		$order->PaymentMethod->Get();
	}

	$chargeShipping = ($order->HasInvoices)?false:true;

	$invoice = new Invoice();

	if($generateInvoice) {
		$invoice->PaymentMethod->ID = $order->PaymentMethod->ID;
		$invoice->Order->ID = $order->ID;
		$invoice->Customer->ID = $order->Customer->ID;
		if(empty($order->Customer->Contact->ID)) $order->Customer->Get();

		$invoice->Payment = 0;
		$invoice->IsDespatched = 'Y';
		$invoice->Despatch = 0;
		$invoice->Organisation = $order->InvoiceOrg;
		$invoice->Person->Title = $order->Invoice->Title;
		$invoice->Person->Name = $order->Invoice->Name;
		$invoice->Person->Initial = $order->Invoice->Initial;
		$invoice->Person->LastName = $order->Invoice->LastName;
		$invoice->Person->Address->Line1 = $order->Invoice->Address->Line1;
		$invoice->Person->Address->Line2 = $order->Invoice->Address->Line2;
		$invoice->Person->Address->Line3 = $order->Invoice->Address->Line3;
		$invoice->Person->Address->City = $order->Invoice->Address->City;
		$invoice->Person->Address->Region->ID = $order->Invoice->Address->Region->ID;
		$invoice->Person->Address->Region->Get();
		$invoice->Person->Address->Country->ID = $order->Invoice->Address->Country->ID;
		$invoice->Person->Address->Country->Get();
		$invoice->Person->Address->Zip = $order->Invoice->Address->Zip;
		$invoice->NominalCode = $order->NominalCode;
		$invoice->IsPaid = 'N';
		$invoice->DueOn = date('Y-m-d H:i:s');
		
		if($order->PaymentMethod->Reference == 'credit') {
			$invoice->DueOn = date('Y-m-d H:i:s', time() + (86400 * $order->Customer->CreditPeriod));

		} elseif(($order->PaymentMethod->Reference == 'card') || ($order->PaymentMethod->Reference == 'google') || ($order->PaymentMethod->Reference == 'paypal')) {
			$invoice->IsPaid = 'Y';
		}
	}

	$despatch = new Despatch();
	$despatch->Order->ID = $order->ID;
	$despatch->DeliveryInstructions = $order->DeliveryInstructions;
	$despatch->Courier->ID = $_REQUEST['courier'];
	$despatch->Consignment = $_REQUEST['ref'];;
	$despatch->Weight = $_REQUEST['weight'];
	$despatch->Boxes = $_REQUEST['boxes'];
	$despatch->Postage->ID = $order->Postage->ID;
	$despatch->DespatchedOn = getDatetime();
	$despatch->DespatchedFrom->ID = $_REQUEST['warehouseid'];
	$despatch->Person->Title = $order->Shipping->Title;
	$despatch->Person->Name = $order->Shipping->Name;
	$despatch->Person->Initial = $order->Shipping->Initial;
	$despatch->Person->LastName = $order->Shipping->LastName;
	$despatch->Organisation = $order->ShippingOrg;
	$despatch->Person->Address->Line1 = $order->Shipping->Address->Line1;
	$despatch->Person->Address->Line2 = $order->Shipping->Address->Line2;
	$despatch->Person->Address->Line3 = $order->Shipping->Address->Line3;
	$despatch->Person->Address->City = $order->Shipping->Address->City;
	$despatch->Person->Address->Region->ID = $order->Shipping->Address->Region->ID;
	$despatch->Person->Address->Region->Get();
	$despatch->Person->Address->Country->ID = $order->Shipping->Address->Country->ID;
	$despatch->Person->Address->Country->Get();
	$despatch->Person->Address->Zip = $order->Shipping->Address->Zip;

	$data = new DataQuery(sprintf("SELECT Type_Reference_ID FROM warehouse WHERE Warehouse_ID=%d AND `Type`='S'", mysql_real_escape_string($_REQUEST['warehouseid'])));
	if($data->TotalRows > 0){
		$generatePurchase = true;

		$supplier = new Supplier($data->Row['Type_Reference_ID']);
		$supplier->Contact->Get();

		$purchase = new Purchase();
		$purchase->Type = 'Turnaround';
		$purchase->SupplierID = $supplier->ID;
		$purchase->Order->ID = $order->ID;
		$purchase->PurchasedOn = getDatetime();
		$purchase->Person->Title = $order->Shipping->Title;
		$purchase->Person->Name = $order->Shipping->Name;
		$purchase->Person->Initial = $order->Shipping->Initial;
		$purchase->Person->LastName = $order->Shipping->LastName;
		$purchase->Organisation = $order->ShippingOrg;
		$purchase->Person->Address->Line1 = $order->Shipping->Address->Line1;
		$purchase->Person->Address->Line2 = $order->Shipping->Address->Line2;
		$purchase->Person->Address->Line3 = $order->Shipping->Address->Line3;
		$purchase->Person->Address->City = $order->Shipping->Address->City;
		$purchase->Person->Address->Region->ID = $order->Shipping->Address->Region->ID;
		$purchase->Person->Address->Region->Get();
		$purchase->Person->Address->Country->ID = $order->Shipping->Address->Country->ID;
		$purchase->Person->Address->Country->Get();
		$purchase->Person->Address->Zip = $order->Shipping->Address->Zip;
		$purchase->Postage = $order->Postage->ID;
		$purchase->Supplier = $supplier->Contact->Person;
		$purchase->SupOrg = ($supplier->Contact->HasParent)? $supplier->Contact->Parent->Organisation->Name: '';
	}
	$data->Disconnect();

    $cost = 0;
	$weight = 0;

    $shippingProducts = array();

	/*
	1. Do we need to update the order lines?
	2. Create a new despatch
	3. Add lines to despatch
	*/
	$isPartialDespatch = false;
	$discountCollection = new DiscountCollection;
	$discountCollection->Get($order->Customer);

	for($i=0; $i<count($order->Line); $i++){
		if(empty($order->Line[$i]->DespatchID) && ($order->Line[$i]->Status != 'Cancelled')){
			if($_REQUEST['line' . $order->Line[$i]->ID] > 0){
				//$order->Line[$i]->DespatchedFrom->ChangeQuantity($order->Line[$i]->Product->ID, $_REQUEST['line'.$order->Line[$i]->ID]);
				// Is this line partially despatched?
				if($order->Line[$i]->Quantity > $_REQUEST['line' . $order->Line[$i]->ID]){
					// Yes it was
					// add a new line in the order for the remainder quantity

					// First Add a new Order Line for the quantity not being despatched
					// Remembering to split the discount in two where applicable.
					$remaindingQty = $order->Line[$i]->Quantity - $_REQUEST['line' . $order->Line[$i]->ID];

					$line = new OrderLine();
					$line->Order = $order->Line[$i]->Order;
					$line->Product->ID = $order->Line[$i]->Product->ID;
					$line->Product->Name = $order->Line[$i]->Product->Name;
					$line->Price = $order->Line[$i]->Price;
					$line->DespatchedFrom->Get($_REQUEST['warehouseid']);
					$line->Quantity = $remaindingQty;
					$line->Status = '';
					$line->Total = $remaindingQty * $line->Price;
					$line->Discount = round(($remaindingQty * ($order->Line[$i]->Discount/$order->Line[$i]->Quantity)), 2);
					$line->DiscountInformation = $order->Line[$i]->DiscountInformation;
					$line->Tax = round(($remaindingQty * ($order->Line[$i]->Tax/$order->Line[$i]->Quantity)), 2);
					$line->InvoiceID = $order->Line[$i]->InvoiceID;
					$line->Status = '';

					$order->Line[] = $line;

					// update this line being shipped.
					$order->Line[$i]->Quantity = $_REQUEST['line' . $order->Line[$i]->ID];
					$order->Line[$i]->Total = $order->Line[$i]->Quantity * $order->Line[$i]->Price;
					$order->Line[$i]->Discount = $order->Line[$i]->Discount - $line->Discount;
					$order->Line[$i]->Tax = $order->Line[$i]->Tax - $line->Tax;

					$isPartialDespatch = true;
				}

				$despatchedLine = new DespatchLine();
				$despatchedLine->Quantity = $order->Line[$i]->Quantity;
				$despatchedLine->Product->ID = $order->Line[$i]->Product->ID;
				$despatchedLine->Product->Name = $order->Line[$i]->Product->Name;
				$despatchedLine->IsComplementary = $order->Line[$i]->IsComplementary;

				$despatch->Line[] = $despatchedLine;

				$order->Line[$i]->Status = 'Despatched';

				// do we generate an invoice for this line?
				if($generateInvoice && empty($order->Line[$i]->InvoiceID)) {

					// Increment Running Totals
					if($order->PaymentMethod->Reference != 'foc') {
						$invoice->SubTotal += $order->Line[$i]->Total;
						$invoice->Tax += $order->Line[$i]->Tax;
						$invoice->Discount += $order->Line[$i]->Discount;
					}

					// add invoice line
					$invoiceLine = new InvoiceLine;
					$invoiceLine->Quantity = $order->Line[$i]->Quantity;
					$invoiceLine->Description = $order->Line[$i]->Product->Name;
					$invoiceLine->Product->ID = $order->Line[$i]->Product->ID;
					$invoiceLine->Price = $order->Line[$i]->Price;
					$invoiceLine->Total = ($order->PaymentMethod->Reference != 'foc') ? $order->Line[$i]->Total : 0;
					$invoiceLine->Discount = ($order->PaymentMethod->Reference != 'foc') ? $order->Line[$i]->Discount : 0;
					$invoiceLine->DiscountInformation = ($order->PaymentMethod->Reference != 'foc') ? $order->Line[$i]->DiscountInformation : '';
					$invoiceLine->Tax = ($order->PaymentMethod->Reference != 'foc') ? $order->Line[$i]->Tax : 0;

					$invoice->Line[] = $invoiceLine;
				}

                $cost += $order->Line[$i]->Cost * $order->Line[$i]->Quantity;
				$weight += $order->Line[$i]->Product->Weight * $order->Line[$i]->Quantity;

                $shippingProducts[] = array('Quantity' => $order->Line[$i]->Quantity, 'ShippingClassID' => $order->Line[$i]->Product->ShippingClass->ID);

				if($generatePurchase){
					$purchaseLine = new PurchaseLine();
					$purchaseLine->Quantity = $order->Line[$i]->Quantity;
					$purchaseLine->Product->Name = $order->Line[$i]->Product->Name;
					$purchaseLine->Product->ID = $order->Line[$i]->Product->ID;

					$data = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($_REQUEST['warehouseid']), mysql_real_escape_string($purchaseLine->Product->ID)));
					$purchaseLine->Location = $data->Row['Shelf_Location'];
					$data->Disconnect();

					$data = new DataQuery(sprintf("SELECT Cost, Supplier_SKU FROM supplier_product WHERE Product_ID=%d AND Supplier_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID), mysql_real_escape_string($supplier->ID)));
					$purchaseLine->Cost = ($data->TotalRows > 0) ? $data->Row['Cost'] : 0;
					$purchaseLine->SKU = $data->Row['Supplier_SKU'];
					$data->Disconnect();

					$purchase->Line[] = $purchaseLine;
				}
			} else {
				$isPartialDespatch = true;
			}
		}
	}

    if($warehouse->Type == 'S') {
		$calc = new SupplierShippingCalculator($despatch->Person->Address->Country->ID, $despatch->Person->Address->Region->ID, $cost, $weight, $despatch->Postage->ID, $warehouse->Contact->ID);
	} elseif($warehouse->Type == 'B') {
		$calc = new ShippingCostCalculator($despatch->Person->Address->Country->ID, $despatch->Person->Address->Region->ID, $cost, $weight, $despatch->Postage->ID);
	}
	
	foreach($shippingProducts as $item) {
		$calc->Add($item['Quantity'], $item['ShippingClassID']);
	}

	// Do we need to invoice?
	if($order->PaymentMethod->Reference != 'foc') {
		if($generateInvoice) {
			if(count($invoice->Line) > 0) {
				$invoice->TaxRate = $order->GetTaxRate();

				if($chargeShipping){
					$invoice->Shipping = $order->TotalShipping;
					$invoice->Tax += $order->CalculateCustomTax($order->TotalShipping);
				}

				$invoice->Tax = round($invoice->Tax, 2);
				$invoice->Total = $invoice->SubTotal + $invoice->Tax + $invoice->Shipping - $invoice->Discount;
			}
		}
	}

	/*
	Ok let's check if we need to take a payment here.
	If the credit card payment is unsuccessful we will have to delete generated invoice and despatch
	and then exit the process.
	*/
	if($order->Sample == 'N') {
		if($order->PaymentMethod->Reference == 'google') {
			$googleRequest = new GoogleRequest();

			$despatch->Courier->Get();

			$items = array();

			for($i=0; $i<count($order->Line); $i++){
				if(empty($order->Line[$i]->DespatchID) && ($order->Line[$i]->Status != 'Cancelled')){
					if($_REQUEST['line' . $order->Line[$i]->ID] > 0){
						$items[] = $order->Line[$i]->Product->ID;
					}
				}
			}

			if(!$googleRequest->shipItems($order->CustomID, $items, $despatch->Courier->Name, $_REQUEST['ref'])) {
				$page = new Page('Delivery Error','An error occured whilst trying to mark the order as delivered. Details below:');
				$page->Display('header');

				echo '<p>' . $googleRequest->ErrorMessage . '</p>';

				echo '<p>You may need to manually send delivery confirmation through Google Checkout. Please contact a system administrator.</p>';
				echo '<input type="button" name="close window" value="close window" onclick="window.self.close();" />';

				$page->Display('footer');

				require_once('lib/common/app_footer.php');
				exit;
			}
		} elseif($order->PaymentMethod->Reference == 'card') {
			$gateway = new PaymentGateway();

			if($gateway->GetDefault()){
				require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/' . $gateway->ClassFile);

				$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);

				$amount = number_format($invoice->Total, 2, '.', '');

				if($amount > 0) {
					$paymentProcessor->Amount = $amount;
					$paymentProcessor->Description = $GLOBALS['COMPANY'] . ' Invoice #' . $invoice->ID;

					$paymentProcessor->Payment->Gateway->ID = $gateway->ID;
					$paymentProcessor->Payment->Order->ID = $order->ID;

					$payment = new Payment();

					$success = false;

					$data = new DataQuery(sprintf("SELECT Payment_ID FROM payment WHERE Transaction_Type LIKE 'AUTHENTICATE' AND (Status LIKE 'REGISTERED' OR Status LIKE '3DAUTH' OR Status LIKE 'AUTHENTICATED') AND Reference!='' AND Order_ID=%d ORDER BY Payment_ID DESC LIMIT 0, 1", mysql_real_escape_string($order->ID)));
					if($data->TotalRows > 0) {
						$data83 = new DataQuery(sprintf("SELECT Payment_ID FROM payment WHERE Transaction_Type LIKE 'CANCEL' AND Status LIKE 'OK' AND Order_ID=%d AND Payment_ID>%d", mysql_real_escape_string($order->ID), $data->Row['Payment_ID']));
						if($data83->TotalRows == 0) {
							$payment->Get($data->Row['Payment_ID']);

							$success = $paymentProcessor->Authorise($payment);
						}
						$data83->Disconnect();
					}
					$data->Disconnect();

					if(!$success){
						$invoice->IsPaid = 'N';

						if($paymentProcessor->Payment->Status == 'FAIL') {
							$order->IsFailed = 'Y';
							$order->Update();
						} else {
							if($order->IsDeclined == 'N') {
								if((stristr($paymentProcessor->Payment->StatusDetail, 'authorisation') && stristr($paymentProcessor->Payment->StatusDetail, 'declined')) || (stristr($paymentProcessor->Payment->StatusDetail, 'validation') && stristr($paymentProcessor->Payment->StatusDetail, 'failure')) || (stristr($paymentProcessor->Payment->StatusDetail, 'does not match card number'))) {
									$order->SendDeclined();
								}
							}
							
							$order->IsDeclined = 'Y';
							$order->Update();
						}

						$page = new Page('Payment Error','An error occured whilst trying to charge the credit card. Details below:');
						$page->Display('header');

						for($i=0; $i < count($paymentProcessor->Error); $i++){
							echo '<p>' . $paymentProcessor->Error[$i] . '</p>';
						}

						echo '<p>You may need to change the card details for this order. Please contact the customer and try again later.</p>';
						echo '<input class="btn" type="button" name="close window" value="Close Window" onclick="window.self.close();" />';
						$page->Display('footer');
						//require_once('lib/common/app_footer.php');
						exit;
					} else {
						$order->IsPaymentUnverified = 'N';
						$order->IsDeclined = 'N';
						$order->IsFailed = 'N';
						$order->Update();
					}
				}
			}
		}
	}

	//Change the quantities of the product from each order
	for($i=0; $i<count($order->Line); $i++){
		if(empty($order->Line[$i]->DespatchID) && ($order->Line[$i]->Status != 'Cancelled')){
			if($_REQUEST['line' . $order->Line[$i]->ID] > 0){
				$order->Line[$i]->DespatchedFrom->ChangeQuantity($order->Line[$i]->Product->ID, $_REQUEST['line'.$order->Line[$i]->ID]);
			}
		}
	}

	if($generateInvoice) {
		if(count($invoice->Line) > 0) {
			if($invoice->Total > 0) {
				$invoice->Add();

				for($i=0; $i < count($invoice->Line); $i++){
					$invoice->Line[$i]->InvoiceID = $invoice->ID;
					$invoice->Line[$i]->Add();
				}
			}
		}
	}

	if($generatePurchase) {
		$purchase->Add();

		for($i=0; $i<count($purchase->Line); $i++) {
			$purchase->Line[$i]->Purchase = $purchase->ID;
			$purchase->Line[$i]->Add();
		}

		$despatch->Purchase->ID = $purchase->ID;
	}

	$despatch->PostageCost = $calc->GetTotal();
    $despatch->Add();

	for($i=0; $i < count($despatch->Line); $i++){
		$despatch->Line[$i]->Despatch = $despatch->ID;
		$despatch->Line[$i]->Add();
	}

	$order->Status = ($isPartialDespatch)?'Partially Despatched':'Despatched';

	if(!$isPartialDespatch) {
		$order->Despatch(false);
	}

	$order->Update();

	for($i=0; $i<count($order->Line); $i++){
		$order->Line[$i]->Product->Get();

		if($order->Line[$i]->Status != 'Cancelled') {
			if($generateInvoice) {
				if(empty($order->Line[$i]->InvoiceID)) {
					if($_REQUEST['line' . $order->Line[$i]->ID] > 0) {
						if($invoice->Total > 0) {
							$order->Line[$i]->InvoiceID = $invoice->ID;
                        	$order->Line[$i]->Status = 'Invoiced';
						}
					}
				}
			}

			if(empty($order->Line[$i]->DespatchID)) {
				if($_REQUEST['line' . $order->Line[$i]->ID] > 0){
					$order->Line[$i]->DespatchID = $despatch->ID;

					if($order->Line[$i]->Cost == 0) {
                        if($order->Line[$i]->Product->Type == 'S') {
							$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", mysql_real_escape_string($order->Line[$i]->Product->ID)));
							if($data->TotalRows > 0) {
                                $order->Line[$i]->Cost = $data->Row['Cost'];
								$order->Line[$i]->Update();
							}
							$data->Disconnect();
						} elseif($order->Line[$i]->Product->Type == 'G') {
							$data = new DataQuery(sprintf("SELECT Product_ID, Component_Quantity FROM product_components WHERE Component_Of_Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
							while($data->Row) {
						        $data2 = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Cost>0 ORDER BY Preferred_Supplier ASC LIMIT 0, 1", $data->Row['Product_ID']));
								if($data2->TotalRows > 0) {
                                    $order->Line[$i]->Cost = $data2->Row['Cost'] * $data->Row['Component_Quantity'];
								}
								$data2->Disconnect();

								$data->Next();
							}
							$data->Disconnect();

							$order->Line[$i]->Update();
						}
					}
				}
			}

			if(empty($order->Line[$i]->ID)){
				$order->Line[$i]->Add();
			} else {
				$order->Line[$i]->Update();
			}
		}
	}

	if($generateInvoice) {
		if($order->PaymentMethod->Reference == 'card') {
			$paymentProcessor->Payment->Invoice->ID = $invoice->ID;

			if(!empty($gateway->ID)){
				$invoice->Payment = $paymentProcessor->Payment->ID;
				$invoice->Paid = $invoice->Total;

				$paymentProcessor->Payment->Invoice->ID = $invoice->ID;
				$paymentProcessor->Payment->PaidOn = getDatetime();
				$paymentProcessor->Payment->Update();
			}
		}

		if($invoice->Total > 0) {
			$invoice->Despatch = $despatch->ID;
			$invoice->Update();
		}
	} else {
		if(!empty($gateway->ID)){
			$paymentProcessor->Payment->PaidOn = getDatetime();
			$paymentProcessor->Payment->Update();
		}
	}

	$redirect .= '&despatchid=' . $despatch->ID;

	if($generateInvoice) {
		if($invoice->Total > 0) {
			$redirect .= '&invoiceid=' . $invoice->ID;
		}
	}

	$redirect .= '&orderid=' . $order->ID;

	if($generatePurchase) {
		$redirect .= '&purchaseid=' . $purchase->ID;
	}

	$redirect .= '&warehouseid='.$_REQUEST['warehouseid'];

	redirect("Location: " . $redirect);
}

function step4() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Page.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

	$order = new Order($_REQUEST['orderid']);

	$despatch = new Despatch($_REQUEST['despatchid']);

	global $session;

	if($session->Warehouse->Type == 'B') {
		$despatch->IsIgnition = true;
	}

	$invoiceNo = (isset($_REQUEST['invoiceid']))?$_REQUEST['invoiceid']:0;
	$purchaseNo = (isset($_REQUEST['purchaseid']))?$_REQUEST['purchaseid']:0;

	if(isset($_REQUEST['confirm'])) {
		$printHtml = '';

		if(isset($_REQUEST['printDespatch']) && ($_REQUEST['printDespatch'] == 'Y')) {
			$printHtml .= $despatch->GetDocument(($order->IsPlainLabel == 'N') ? true : false);
		}

		if(isset($_REQUEST['emailDespatch']) && ($_REQUEST['emailDespatch'] == 'Y')) {
			$despatch->EmailCustomer();
		}

		if(!empty($purchaseNo)) {
			$purchase = new Purchase($purchaseNo);

			if(isset($_REQUEST['printPurchase']) && ($_REQUEST['printPurchase'] == 'Y')) {
				if(!empty($printHtml)) {
					$printHtml .= '<br style="page-break-after:always" />';
				}

				$printHtml .= $purchase->GetDocument();
			}
		}

		if(!empty($invoiceNo)) {
			if(($order->Sample == 'N') && ($invoiceNo > 0)) {
				$invoice = new Invoice($invoiceNo);

				if(isset($_REQUEST['emailInvoice']) && ($_REQUEST['emailInvoice'] == 'Y')) {
					$invoice->EmailCustomer();
				}
			}
		}

		$jsPrint = (!empty($printHtml)) ? 'window.self.print();' : '';

		echo sprintf('<html>
					<script>
						function printDocs(){
							%s
							window.opener.location.reload(true);
							window.self.close();
						}
					</script>
				  <body onload="printDocs();">', $jsPrint);
		echo $printHtml;
		echo '</body></html>';
		exit;
	}

	$page = new Page(sprintf('Processing Despatch No. %s', $despatch->ID), 'Select order finalisation options.');
	$page->Display('header');

	$Options = new DataQuery(sprintf("SELECT * FROM warehouse WHERE Warehouse_ID=%d", mysql_real_escape_string($_REQUEST['warehouseid'])));
	?>

	<form method="post" id="printOptions" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="hidden" name="action" value="step4" />
		<input type="hidden" name="confirm" value="true" />
		<input type="hidden" name="orderid" value="<?php echo $_REQUEST['orderid']; ?>" />
		<input type="hidden" name="despatchid" value="<?php echo $_REQUEST['despatchid']; ?>" />
		<input type="hidden" name="invoiceid" value="<?php echo $invoiceNo; ?>" />
		<input type="hidden" name="purchaseid" value="<?php echo $_REQUEST['purchaseid']; ?>" />
		<input type="hidden" name="warehouseid" value="<?php echo $_REQUEST['warehouseid']; ?>" />
		<input type="hidden" name="emailPurchase" value="<?php echo (($Options->Row['Purchase_Options'] == 'B' || $Options->Row['Purchase_Options'] == 'P') && $Options->Row['Type'] == 'S') ? 'Y' : 'N'; ?>" />
		<input type="hidden" name="printPurchase" value="<?php echo (($Options->Row['Purchase_Options'] == 'B' || $Options->Row['Purchase_Options'] == 'P') && $Options->Row['Type'] == 'S') ? 'Y' : 'N'; ?>" />
		<input type="hidden" name="printInvoice" value="<?php echo ($Options->Row['Invoice_Options'] == 'B' || $Options->Row['Invoice_Options'] == 'P') ? 'Y' : 'N'; ?>" />
		<input type="hidden" name="emailInvoice" value="<?php echo ($Options->Row['Invoice_Options'] == 'B' || $Options->Row['Invoice_Options'] == 'E') ? 'Y' : 'N'; ?>" />
		<input type="hidden" name="emailDespatch" value="<?php echo ($Options->Row['Despatch_Options'] == 'B' || $Options->Row['Despatch_Options'] == 'E') ? 'Y' : 'N'; ?>" />

		<input type="checkbox" name="printDespatch" value="<?php echo ($Options->Row['Despatch_Options'] == 'B' || $Options->Row['Despatch_Options'] == 'P') ? 'Y' : 'N'; ?>" <?php echo ($Options->Row['Despatch_Options'] == 'B' || $Options->Row['Despatch_Options'] == 'P') ? 'checked="checked"' : ''; ?> /> Print Despatch Note<br /><br />
		<input type="submit" name="continue" value="continue" class="btn" />
	</form>

	<script type="text/javascript" language="Javascript">
		var form = document.getElementById('printOptions');
		form.submit();
	</script>

	<?php
	$Options->Disconnect();

	$page->Display('footer');
	//require_once('lib/common/app_footer.php');
}
?>