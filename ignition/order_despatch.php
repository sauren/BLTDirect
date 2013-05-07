<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Courier.php');

$session->Secure(3);

switch($action) {
	case 'step2':
		step2();
		break;
	case 'step2b':
		step2b();
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

function step1() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');

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
			$count++;
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
					<td>
						<?php
						echo ($order->Line[$i]->Product->ID > 0) ? $order->Line[$i]->Product->ID : '';
						?>
					</td>

					<?php
					echo "</tr>";
				}
			}
			?>

		</table>
		<br />

		<?php
	}

	if(($order->IsFreeTextDespatched == 'N') && ($order->FreeTextValue > 0)) {
	?>

	<table border="0" cellpadding="4" cellspacing="0" class="orderDetails">
		<tr>
			<th width="15%">Free Text</th>
			<th width="85%">Description</th>
		</tr>
		<tr>
			<td><input type="checkbox" name="freeText" value="freeText" /></td>
			<td colspan="2"><?php print (empty($order->FreeText) ? '-' : $order->FreeText); ?></td>
		</tr>
	</table>
	<br />
	<?php
	}
	?>

	<p>
	  <input type="submit" name="continue" value="continue" class="btn">
	</p>
	</form>
	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function step2() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
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

	if(isset($_REQUEST['freeText'])) {
		$continue = true;
	}

	if(!$continue) {
		echo '<script language="javascript" type="text/javascript">window.close();</script>';
		exit;
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'step2', 'alpha_numeric', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('confirm2', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('orderid', 'Order ID', 'hidden', $order->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('warehouseid', 'Warehouse ID', 'hidden', '', 'numeric_unsigned', 1, 11);

	for($i=0; $i<count($order->Line); $i++){
		if(empty($order->Line[$i]->DespatchID) && (($order->Line[$i]->DespatchedFrom->ID == $_REQUEST['warehouseid']) || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $warehouse->ID))) && ($order->Line[$i]->Status != 'Cancelled')) {
			$order->Line[$i]->Product->Get();

			$form->AddField('line'.$order->Line[$i]->ID, 'OrderLine', 'hidden', $_REQUEST['line'.$order->Line[$i]->ID], 'numeric_unsigned', 1, 11);

			$totalWeight += $order->Line[$i]->Product->Weight * $_REQUEST['line'.$order->Line[$i]->ID];
		}
	}

	$form->AddField('weight', 'Weight', 'text', $totalWeight, 'float', 0, 11, true, 'style="text-align: right;" size="5"');
	$form->AddField('boxes', 'Boxes', 'text', '1', 'numeric_unsigned', 1, 11, true, 'style="text-align: right;" size="5"');

	$data = new DataQuery("SELECT Courier_ID FROM courier WHERE Is_Default='Y'");
	$default = ($data->TotalRows > 0) ? $data->Row['Courier_ID'] : 0;
	$data->Disconnect();

	$form->AddField('courier', 'Courier', 'select', $default, 'anything', 1, 11, true);
	$form->AddOption('courier', '', '');

	$data = new DataQuery(sprintf("SELECT Courier_ID, Courier_Name FROM courier%s ORDER BY Courier_Name ASC", ($warehouse->Type == 'B') ? sprintf(' WHERE Courier_ID IN (2, 3)') : ''));
	while($data->Row) {
		$form->AddOption('courier', $data->Row['Courier_ID'], $data->Row['Courier_Name']);

		$data->Next();
	}
	$data->Disconnect();

	$tracking = false;
	
	if(($order->Postage->Days == 1) && ($warehouse->IsNextDayTrackingRequired == 'Y')) {
		$data = new DataQuery(sprintf("SELECT Is_Tracking_Active FROM courier WHERE Courier_ID=%d", mysql_real_escape_string($form->GetValue('courier'))));
		if($data->Row['Is_Tracking_Active'] == 'Y') {
			$tracking = true;
		}
		$data->Disconnect();
	}

	if($warehouse->Type == 'B') {
		$cost = 0;
		
		for($i=0; $i<count($order->Line); $i++) {
			$cost += $order->Line[$i]->Cost * $order->Line[$i]->Quantity;
		}
		
		if($cost >= 100) {
			$tracking = true;
		}
	}

	$form->AddField('ref', 'Consigment Number/Reference', 'text', '', 'anything', 1, 32, $tracking, 'size="15"');
	
	if(isset($_REQUEST['confirm2']) && (strtolower($_REQUEST['confirm2']) == 'true')) {
		if($form->Validate()) {
			if(strlen($form->GetValue('ref')) > 0) {
				$courier = new Courier();
				$courier->ID = $form->GetValue('courier');
				
				if($courier->ID > 0) {
					$courier->Get();
					
					if(!empty($courier->TrackingValidation)) {
						if(!preg_match('/' . $courier->TrackingValidation . '/', str_replace(' ', '', $form->GetValue('ref')))) {
							$form->AddError(sprintf('Consigment Number/Reference does not match expression \'%s\'.', $courier->TrackingValidation), 'ref');
						}
					}
				}
			}
			
			if($form->Valid) {
				if(($order->IsFailed == 'Y') && ($order->PaymentMethod->Reference == 'card')) {
					unset($_REQUEST['confirm']);
					unset($_REQUEST['confirm2']);
					
					step2b();
					exit;
				} else {
					step3();
				}
			}
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
			echo $form->GetHTML('line'.$order->Line[$i]->ID);
		}
	}

	$count = 0;

	for($i=0; $i<count($order->Line); $i++){
		$order->Line[$i]->DespatchedFrom->Contact->Get();

		if(empty($order->Line[$i]->DespatchID) && (($order->Line[$i]->DespatchedFrom->ID == $_REQUEST['warehouseid']) || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $warehouse->ID))) && ($order->Line[$i]->Status != 'Cancelled')) {
			$count++;
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
				<td>
					<?php
					echo ($order->Line[$i]->Product->ID > 0) ? $order->Line[$i]->Product->ID : '';
					?>
				</td>

			<?php
			echo "</tr>";
		}
	}
	?>
		</table>
		<br />
		<?php
	}

	if(($order->IsFreeTextDespatched == 'N') && ($order->FreeTextValue > 0)) {
	?>

	<table border="0" cellpadding="4" cellspacing="0" class="orderDetails">
		<tr>
			<th width="15%">Free Text</th>
			<th width="85%">Description</th>
		</tr>
		<tr>
			<td><input type="hidden" name="freeText" value="<?php print (isset($_REQUEST['freeText']) ? 'Y' : 'N');?>" /><input type="checkbox" name="freeTextFake" value="freeTextFake" disabled="disabled" <?php print (isset($_REQUEST['freeText']) ? 'checked="checked"' : ''); ?> /></td>
			<td colspan="2"><?php print (empty($order->FreeText) ? '-' : $order->FreeText); ?></td>
		</tr>
	</table>
	<br />
	<?php
	}
	?>

	<strong>Consignment Information</strong><br /><br />
	
	<table class="invoicePaymentDetails" cellspacing="0" width="100%">
		<tr>
			<th width="50%">Consignment Weight:	</th>
			<td width="50%"><?php echo $form->GetHTML('weight'); ?> Kg</td>
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
	
	<?php	
	if($order->Sample == 'N') {
		?>
	  <p>Would you like to Generate an Invoice for non-invoiced items?
	    <label><input type="radio" name="invoice" value="Y" checked="checked" />Yes</label>
	    <label><input type="radio" name="invoice" value="N" />No</label>
	  </p>
	  <?php
  	}
  ?>
  <br />
  
	<?php
	if(($order->IsFailed == 'Y') && ($order->PaymentMethod->Reference == 'card')) {
		echo '<input type="submit" name="continue" value="continue" class="btn" />';
	} else {
		echo '<input type="submit" name="despatch" value="despatch" class="btn" />';
	}

	echo $form->Close();
}

function step2b() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
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

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'step2b', 'alpha_numeric', 6, 6);
	$form->SetValue('action', 'step2b');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('orderid', 'Order ID', 'hidden', $order->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('warehouseid', 'Warehouse ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('invoice', 'Generate Invoice', 'hidden', 'Y', 'alpha', 1, 1);
	$form->AddField('paymentbypass', 'Bypass Integration', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('paymentdate', 'Invoice Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

	for($i=0; $i<count($order->Line); $i++){
		if(empty($order->Line[$i]->DespatchID) && (($order->Line[$i]->DespatchedFrom->ID == $_REQUEST['warehouseid']) || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $warehouse->ID))) && ($order->Line[$i]->Status != 'Cancelled')) {
			$form->AddField('line' . $order->Line[$i]->ID, 'Order Line', 'hidden', '0', 'numeric_unsigned', 1, 11);
		}
	}
	
	if(isset($_REQUEST['confirm'])) {
		if($form->GetValue('paymentbypass') == 'Y') {
			$form->InputFields['paymentdate']->Required = true;
		}
		
		if($form->Validate()) {
			step3();
		}	
	}

	$page = new Page(sprintf('Despatch Order No.%s%s', $order->Prefix, $order->ID), 'Please confirm and enter your consignment information.');
	$page->LinkScript('js/scw.js');
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
	echo $form->GetHTML('orderid');
	echo $form->GetHTML('warehouseid');
	echo $form->GetHTML('invoice');
	
	for($i=0; $i<count($order->Line); $i++){
		if(empty($order->Line[$i]->DespatchID) && (($order->Line[$i]->DespatchedFrom->ID == $_REQUEST['warehouseid']) || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $warehouse->ID))) && ($order->Line[$i]->Status != 'Cancelled')) {
			echo $form->GetHTML('line'.$order->Line[$i]->ID);
		}
	}
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
			$order->Line[$i]->DespatchedFrom->Contact->Get();
			
			if($order->Line[$i]->DespatchedFrom->Type == 'S') {
				$order->Line[$i]->DespatchedFrom->Contact->Contact->Get();
			}
			
			if(empty($order->Line[$i]->DespatchID) && (($order->Line[$i]->DespatchedFrom->ID == $_REQUEST['warehouseid']) || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $warehouse->ID))) && ($order->Line[$i]->Status != 'Cancelled')) {
				$order->Line[$i]->Product->Get();
				?>
				
				<tr>
					<td><?php echo $_REQUEST['line'.$order->Line[$i]->ID]; ?>x</td>
					<td><?php echo $order->Line[$i]->Product->Name; ?></td>
					<td>
						<?php
						if($order->Line[$i]->DespatchedFrom->Type == 'B') {
							echo $order->Line[$i]->DespatchedFrom->Contact->Name;
						} elseif($order->Line[$i]->DespatchedFrom->Type == 'S') {
							echo $order->Line[$i]->DespatchedFrom->Contact->Contact->Parent->Organisation->Name;
						}
						?>
					</td>
					<td><?php echo ($order->Line[$i]->Product->ID > 0) ? $order->Line[$i]->Product->ID : ''; ?></td>
				</tr>
				
				<?php
			}
		}
		?>
		
	</table>
	<br />
	
	<strong>Payment Integration</strong><br /><br />
	
	<table class="invoicePaymentDetails" cellspacing="0" width="100%">
		<tr>
			<th width="50%">Bypass Integration:</th>
			<td width="50%"><?php echo $form->GetHTML('paymentbypass'); ?></td>
		</tr>
		<tr>
			<th>Invoice Date:</th>
			<td><?php echo $form->GetHTML('paymentdate'); ?></td>
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Payment.php");
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Log.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierShippingCalculator.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ShippingCostCalculator.php');

	$warehouse = new Warehouse($_REQUEST['warehouseid']);

	$redirect = $_SERVER['PHP_SELF'] . '?action=step4';

	$generateInvoice = (isset($_REQUEST['invoice']) && ($_REQUEST['invoice'] == 'Y')) ? true : false;
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

	if(isset($_REQUEST['freeText']) && ($_REQUEST['freeText'] == 'Y')) {
		$order->IsFreeTextDespatched = 'Y';
	} else {
		$order->IsFreeTextDespatched = 'N';
	}

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
		
		if(($order->IsFailed == 'Y') && ($order->PaymentMethod->Reference == 'card')) {
			if(isset($_REQUEST['paymentbypass']) && isset($_REQUEST['paymentdate'])) {
				$invoice->DueOn = sprintf('%s-%s-%s 00:00:00', substr($_REQUEST['paymentdate'], 6, 4), substr($_REQUEST['paymentdate'], 3, 2), substr($_REQUEST['paymentdate'], 0, 2));
			}
		}

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


	debug($despatch,1);
	
	$data = new DataQuery(sprintf("SELECT Type_Reference_ID FROM warehouse WHERE Warehouse_ID=%d AND `Type`='S'", mysql_real_escape_string($_REQUEST['warehouseid'])));
	if($data->TotalRows > 0){
		$generatePurchase = true;

		debug($data->TotalRows,1);

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
				// Is this line partially despatched?
				if($order->Line[$i]->Quantity > $_REQUEST['line' . $order->Line[$i]->ID]){
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

					$order->Line[] = $line;

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

				if($generateInvoice && empty($order->Line[$i]->InvoiceID)) {
					if($order->PaymentMethod->Reference != 'foc') {
						$invoice->SubTotal += $order->Line[$i]->Total;
						$invoice->Tax += $order->Line[$i]->Tax;
						$invoice->Discount += $order->Line[$i]->Discount;
					}

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

					if($purchaseLine->Product->ID > 0) {
						$data = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($_REQUEST['warehouseid']), mysql_real_escape_string($purchaseLine->Product->ID)));
						$purchaseLine->Location = $data->Row['Shelf_Location'];
						$data->Disconnect();

						$data = new DataQuery(sprintf("SELECT Cost, Supplier_SKU FROM supplier_product WHERE Product_ID=%d AND Supplier_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID), mysql_real_escape_string($supplier->ID)));
						$purchaseLine->Cost = ($data->TotalRows > 0) ? $data->Row['Cost'] : 0;
						$purchaseLine->SKU = $data->Row['Supplier_SKU'];
						$data->Disconnect();
					}

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

	// check if free text exists and if needs to be despatched
	if(($order->IsFreeTextDespatched == 'N') && ($order->FreeTextValue > 0)) {
		$isPartialDespatch = true;
	}

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
						if($order->Line[$i]->Product->ID > 0) {
							$items[] = $order->Line[$i]->Product->ID;
						}
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
			$taxToAdd = 0;
			
			if(isset($_REQUEST['freeText']) && ($_REQUEST['freeText'] =='Y')) {
				$taxToAdd = $order->CalculateCustomTax($order->FreeTextValue);
			}
			
			$amount = number_format($invoice->Total + ((isset($_REQUEST['freeText']) && ($_REQUEST['freeText'] =='Y')) ? ($order->FreeTextValue + $taxToAdd) : 0), 2, '.', '');

			if($amount > 0) {
				if(($order->IsFailed == 'Y') && isset($_REQUEST['paymentbypass'])) {
					$payment = new Payment();
					$payment->Type = 'AUTHORISE';
					$payment->Status = 'OK';
					$payment->StatusDetail = 'Payment bypassed due to failed transaction.';
					$payment->Amount = $amount;
					$payment->Order->ID = $order->ID;
				} else {
					$gateway = new PaymentGateway();
							
					if($gateway->GetDefault()){
						require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/' . $gateway->ClassFile);

						$paymentProcessor = new PaymentProcessor($gateway->VendorName, $gateway->IsTestMode);
						$paymentProcessor->Amount = $amount;
						$paymentProcessor->Description = $GLOBALS['COMPANY'] . ' Invoice #' . $invoice->ID;

						$paymentProcessor->Payment->Gateway->ID = $gateway->ID;
						$paymentProcessor->Payment->Order->ID = $order->ID;

						$payment = new Payment();

						$success = false;

						$data = new DataQuery(sprintf("SELECT Payment_ID FROM payment WHERE Transaction_Type LIKE 'AUTHENTICATE' AND (Status LIKE 'REGISTERED' OR Status LIKE '3DAUTH' OR Status LIKE 'AUTHENTICATED') AND Reference!='' AND Order_ID=%d ORDER BY Payment_ID DESC LIMIT 0, 1", mysql_real_escape_string($order->ID)));

						if($data->TotalRows > 0) {
							$data83 = new DataQuery(sprintf("SELECT Payment_ID FROM payment WHERE Transaction_Type LIKE 'CANCEL' AND Status LIKE 'OK' AND Order_ID=%d AND Payment_ID>%d", mysql_real_escape_string($order->ID), mysql_real_escape_string($data->Row['Payment_ID'])));
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
							require_once('lib/common/app_footer.php');
							exit;
						}
					}
				}
			}
		}
	}

	for($i=0; $i<count($order->Line); $i++){
		if(empty($order->Line[$i]->DespatchID) && ($order->Line[$i]->Status != 'Cancelled')){
			if($_REQUEST['line' . $order->Line[$i]->ID] > 0){
				if($order->Line[$i]->Product->ID > 0) {
					$order->Line[$i]->DespatchedFrom->ChangeQuantity($order->Line[$i]->Product->ID, $_REQUEST['line'.$order->Line[$i]->ID]);
				}
			}
		}
	}

	if($generateInvoice) {
		if(count($invoice->Line) > 0) {
			if($invoice->Total > 0) {
				$invoice->Add();
				
				if(($order->IsFailed == 'Y') && ($order->PaymentMethod->Reference == 'card')) {
					if(isset($_REQUEST['paymentbypass']) && isset($_REQUEST['paymentdate'])) {
						new DataQuery(sprintf("UPDATE invoice SET Created_On='%s' WHERE Invoice_ID=%d", mysql_real_escape_string($invoice->DueOn), mysql_real_escape_string($invoice->ID)));
					}
				}

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

					if($order->Line[$i]->Product->ID > 0) {
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
			if(($order->IsFailed == 'Y') && isset($_REQUEST['paymentbypass'])) {
				$payment->Invoice->ID = $invoice->ID;
				$payment->PaidOn = $invoice->DueOn;
				$payment->Add();
				
				new DataQuery(sprintf("UPDATE payment SET Created_On='%s' WHERE Payment_ID=%d", mysql_real_escape_string($payment->PaidOn), mysql_real_escape_string($payment->ID)));
			} else {
				$paymentProcessor->Payment->Invoice->ID = $invoice->ID;

				if(!empty($gateway->ID)){
					$invoice->Payment = $paymentProcessor->Payment->ID;
					$invoice->Paid = $invoice->Total;

					$paymentProcessor->Payment->Invoice->ID = $invoice->ID;
					$paymentProcessor->Payment->PaidOn = getDatetime();
					$paymentProcessor->Payment->Update();
				}
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
	
	$order->IsPaymentUnverified = 'N';
	$order->IsDeclined = 'N';
	$order->IsFailed = 'N';
	$order->Update();

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

	redirect(sprintf("Location: %s&freeText=%s", $redirect, (isset($_REQUEST['freeText']) && ($_REQUEST['freeText'] =='Y') ? 'show' : 'hide')));
}

function step4() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

	$order = new Order($_REQUEST['orderid']);

	$despatch = new Despatch($_REQUEST['despatchid']);
	$despatch->IsIgnition = true;
	$despatch->ShowCustom = (isset($_REQUEST['freeText']) && ($_REQUEST['freeText'] == 'hide')) ? false : true;

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

            if(isset($_REQUEST['emailPurchase']) && ($_REQUEST['emailPurchase'] == 'Y')) {
				$purchase->EmailSupplier();
			}
		}

		if(!empty($invoiceNo)) {
			if(($order->Sample == 'N') && ($invoiceNo > 0)) {
				$invoice = new Invoice($invoiceNo);

				if(isset($_REQUEST['emailInvoice']) && ($_REQUEST['emailInvoice'] == 'Y')) {
					$additionalEmail = $order->GetAdditioanlEmail($order->ID);
					$invoice->EmailCustomer();
					if(!empty($additionalEmail)){
						$invoice->EmailCustomer($additionalEmail);
					}
				}

				$invoice->ShowCustom = (isset($_REQUEST['freeText']) && ($_REQUEST['freeText'] == 'hide')) ? false : true;

				if(isset($_REQUEST['printInvoice'])) {
					if(!empty($printHtml)) $printHtml .= '<br style="page-break-after:always" />';
					$printHtml .= $invoice->GetDocument();
				}
			}
		}
		$jsPrint = (!empty($printHtml))?'window.self.print();' : '';

		if(isset($_REQUEST['printCoupons'])) {
			echo sprintf('<html>
						<script>
							function printDocs(){
								%s
								window.opener.location.reload(true);
								// interfers with alert boxes, should pass through to parent function to redirect with an action to disable popup.

								window.self.location.href = \'order_printCoupons.php?orderid=%s\';
							}
						</script>
					  <body onload="printDocs();">', $jsPrint, $order->ID);
		} else {
			echo sprintf('<html>
						<script>
							function printDocs(){
								%s
								window.opener.location.reload(true);
								window.self.close();
							}
						</script>
					  <body onload="printDocs();">', $jsPrint);
		}
		echo $printHtml;
		echo '</body></html>';
		exit;
	}

	$page = new Page(sprintf('Despatch No.%s', $despatch->ID),'Please select from one of the following options:');
	$page->Display('header');
	?>

	<form id="form1" name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	<input type="hidden" name="action" value="step4" />
	<input type="hidden" name="confirm" value="true" />
	<input type="hidden" name="orderid" value="<?php echo $_REQUEST['orderid']; ?>" />
	<input type="hidden" name="despatchid" value="<?php echo $_REQUEST['despatchid']; ?>" />
	<input type="hidden" name="invoiceid" value="<?php echo $invoiceNo; ?>" />
	<input type="hidden" name="purchaseid" value="<?php echo $_REQUEST['purchaseid']; ?>" />
	<input type="hidden" name="suppliermail" value="<?php echo $_REQUEST['suppliermail']; ?>" />
	<input type="hidden" name="warehouseid" value="<?php echo $_REQUEST['warehouseid']; ?>" />
	<input type="hidden" name="freeText" value="<?php echo $_REQUEST['freeText']; ?>" />

	<?php $Options = new DataQuery(sprintf("SELECT * FROM warehouse WHERE Warehouse_ID = %d", mysql_real_escape_string($_REQUEST['warehouseid'])));
	?>
	  <p><label>
	  <input name="printDespatch" type="checkbox" id="printDespatch" value="Y" <?php echo ($Options->Row['Despatch_Options'] == 'B' || $Options->Row['Despatch_Options'] == 'P') ? 'checked="checked"' : ''; ?> />
	  Print Despatch Note</label>
	  </p>
	  <p>
	    <label>
	    <input name="emailDespatch" type="checkbox" id="emailDespatch" value="Y" <?php echo ($Options->Row['Despatch_Options'] == 'B' || $Options->Row['Despatch_Options'] == 'E') ? 'checked="checked"' : ''; ?> />
	    Email Despatch Note to Customer</label>
	  </p>

	  <?php
	  if(!empty($purchaseNo)) {
  		?>

	    <p><label>
		  <input name="printPurchase" type="checkbox" id="printPurchase" value="Y" checked="checked" />
		  Print Purchase Note</label>
		  </p>
		  <p>
		    <label>
		    <input name="emailPurchase" type="checkbox" id="emailPurchase" value="Y" checked="checked" />
		    Email Purchase Note to Supplier</label>
		  </p>

  		<?php
	  }

	  if(($order->Sample == 'N') && ($invoiceNo > 0)) {
  		  ?>

	  <p>
	    <label>
		<input name="printInvoice" type="checkbox" id="printInvoice" value="N" />
	    Print Invoice No.</label>
	  </p>
	  <p>
	    <label>
	    <input name="emailInvoice" type="checkbox" id="emailInvoice" value="Y" <?php echo ($Options->Row['Invoice_Options'] == 'B' || $Options->Row['Invoice_Options'] == 'E') ? 'checked="checked"' : ''; ?> />
	    Email Invoice to Customer</label>
	  </p>
	  <?php } ?>
	  <p>
	    <label>
	    <input name="printCoupons" type="checkbox" id="printCoupons" value="Y" />
	    Print Coupons</label>
	  </p>
	  <p>
	    <label>
	    <input name="continue" type="submit" value="continue" class="btn" />
	    </label>
	  </p>
	</form>

	<?php
	$Options->Disconnect();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}