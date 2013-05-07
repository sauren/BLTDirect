<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderWarehouseNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierShippingCalculator.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Package.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Return.php');

$session->Secure(3);

$order = new Order();

if(!isset($_REQUEST['orderid']) || !$order->Get($_REQUEST['orderid'])) {
	redirect("Location: supplier_orders_pending.php");
}

$order->GetLines();
$order->Customer->Get();
$order->Customer->Contact->Get();
$order->GetTransactions();

if(($order->IsDeclined == 'Y') || ($order->IsFailed == 'Y')) {
	redirect(sprintf("Location: supplier_orders_pending.php"));
}

$session->Supplier->Contact->Get();

$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse WHERE Type='S' AND Type_Reference_ID=%d", $session->Supplier->ID));
if($data->TotalRows > 0) {
	$warehouseId = $data->Row['Warehouse_ID'];
} else {
	redirect(sprintf("Location: supplier_orders_pending.php"));
}
$data->Disconnect();

$isWarehouseEditable = ((strtolower($order->Status) == 'packing') || (strtolower($order->Status) == 'partially despatched')) ? true : false;

if($order->ReceivedOn == '0000-00-00 00:00:00'){
	$order->Received();
}

if($order->ParentID > 0) {
	$parent = new Order($order->ParentID);
	$parent->GetLines();
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'hidden', $order->ID, 'numeric_unsigned', 1, 11);
$form->AddField('type', 'Type', 'select', '0', 'numeric_unsigned', 1, 11, false);
$form->AddOption('type', '0', '');
$form->AddField('note', 'Note', 'textarea', '', 'anything', 1, 2048, false, 'style="width: 300px;" rows="7"');

$disabled = '';

if($order->Coupon->ID > 0) {
	$order->Coupon->Get();
	if($order->Coupon->IsInvisible == 'Y') {
		$disabled = ' disabled="disabled"';
	}
}

$form->AddField('freeText', 'Free Text', 'text', $order->FreeText, 'paragraph', 0, 255, false, 'style="width:100%;"'.$disabled);
$form->AddField('freeTextValue', 'Free Text Value', 'text', (isset($order->FreeTextValue) ? $order->FreeTextValue : '0.00'), 'float', 0, 11, false, 'size="4"'.$disabled);

$data = new DataQuery(sprintf("SELECT * FROM order_warehouse_note_type ORDER BY Name ASC"));
while($data->Row) {
	$form->AddOption('type', $data->Row['Order_Warehouse_Note_Type_ID'], $data->Row['Name']);

	$data->Next();
}
$data->Disconnect();

$cost = 0;
$weight = 0;

$supplierProducts = array();
$shippingProducts = array();

for($i=0; $i < count($order->Line); $i++){
	if($order->Line[$i]->Product->ID > 0) {
		$order->Line[$i]->Product->Get();
	}
	
	$order->Line[$i]->DespatchedFrom->Contact->Get();

	if(($warehouseId == $order->Line[$i]->DespatchedFrom->ID) || ((($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $session->Supplier->ID)))) {
		$cost += $order->Line[$i]->Cost * $order->Line[$i]->Quantity;
		$weight += $order->Line[$i]->Product->Weight * $order->Line[$i]->Quantity;

		$shippingProducts[] = array('Quantity' => $order->Line[$i]->Quantity, 'ShippingClassID' => $order->Line[$i]->Product->ShippingClass->ID);

		$form->AddField('days_' . $order->Line[$i]->ID, 'Arrival Days for ' . $order->Line[$i]->Product->Name, 'text', '', 'numeric_unsigned', 1, 11, false, 'size="3"');

		$data = new DataQuery(sprintf("SELECT Supplier_Product_ID, Supplier_Product_Number, Supplier_SKU, Cost FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($session->Supplier->ID), mysql_real_escape_string($order->Line[$i]->Product->ID)));
		if($data->TotalRows > 0) {
			$supplierProducts[$i] = $data->Row;
		}
		$data->Disconnect();

		if($isWarehouseEditable) {
			if(($order->Line[$i]->Status != 'Cancelled') && ($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Despatched')) {
				$form->AddField('sku_' . $order->Line[$i]->ID, 'SKU of ' . $order->Line[$i]->Product->Name, 'text', $supplierProducts[$i]['Supplier_SKU'], 'anything', 1, 64, false);
				$form->AddField('cost_' . $order->Line[$i]->ID, 'Cost Price of ' . $order->Line[$i]->Product->Name, 'text', (strlen($supplierProducts[$i]['Cost']) > 0) ? $supplierProducts[$i]['Cost'] : '0.00', 'float', 1, 7, true, 'size="5"');

				if($session->Supplier->ShowProduct == 'Y') {
					$form->AddField('productnumber_' . $order->Line[$i]->ID, 'Product Number of ' . $order->Line[$i]->Product->Name, 'text', $supplierProducts[$i]['Supplier_Product_Number'], 'numeric_unsigned', 1, 11, false, 'size="10"');
				}
			}
		}
	}
}

$calc = new SupplierShippingCalculator($order->Billing->Address->Country->ID, $order->Billing->Address->Region->ID, $cost, $weight, $order->Postage->ID, $session->Supplier->ID);

foreach($shippingProducts as $item) {
	$calc->Add($item['Quantity'], $item['ShippingClassID']);
}

$shippingTotal = number_format(round($calc->GetTotal(), 2), 2, '.', ',');

$form->AddField('shipping_cost', 'Shipping Cost', 'text', $shippingTotal, 'float', 1, 11, false, 'size="5"');

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['addnote'])) {
		$form->InputFields['type']->Required = true;
		$form->InputFields['note']->Required = true;

		if($form->GetValue('type') == 0) {
			$form->AddError('Type must have a selected value.', 'type');
		}

		if($form->Validate()) {
			$order->IsWarehouseDeclined = 'Y';
			$order->IsWarehouseDeclinedRead = 'N';
			$order->Update();

			$warehouseNote = new OrderWarehouseNote();
			$warehouseNote->IsAlert = 'Y';
			$warehouseNote->Note = $form->GetValue('note');
			$warehouseNote->Order->ID = $order->ID;
			$warehouseNote->Type->ID = $form->GetValue('type');
			$warehouseNote->Warehouse->ID = $warehouseId;
			$warehouseNote->Add();

			redirect(sprintf("Location: %s?orderid=%d", $_SERVER['PHP_SELF'], $order->ID));
		}
	} elseif(isset($_REQUEST['alteration'])) {
		$form->InputFields['shipping_cost']->Required = true;

		if($form->Validate()) {
			$order->IsWarehouseDeclined = 'Y';
			$order->IsWarehouseDeclinedRead = 'N';
			$order->Update();

			$warehouseNote = new OrderWarehouseNote();
			$warehouseNote->IsAlert = 'Y';
			$warehouseNote->Note = sprintf('Shipping cost alteration request from %s to %s.', $shippingTotal, number_format(round($form->GetValue('shipping_cost'), 2), 2, '.', ','));
			$warehouseNote->Order->ID = $order->ID;
			$warehouseNote->Type->ID = 3;
			$warehouseNote->Warehouse->ID = $warehouseId;
			$warehouseNote->Add();

			redirect(sprintf("Location: %s?orderid=%d", $_SERVER['PHP_SELF'], $order->ID));
		}
	} elseif(isset($_REQUEST['update'])) {
		if($form->Validate()) {
			$order->Recalculate();

			$stock = array();

			for($i=0; $i < count($order->Line); $i++){
				if($warehouseId == $order->Line[$i]->DespatchedFrom->ID) {
					if($isWarehouseEditable) {
						$days = $form->GetValue('days_' . $order->Line[$i]->ID);

						if(!empty($days)) {
							$stock[$order->Line[$i]->ID] = $days;
						}

						if(($order->Line[$i]->Status != 'Cancelled') && ($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Despatched')) {
							if((strlen($supplierProducts[$i]['Supplier_Product_ID']) > 0) && (is_numeric($supplierProducts[$i]['Supplier_Product_ID'])) && ($supplierProducts[$i]['Supplier_Product_ID'] > 0)) {
								$product = new SupplierProduct($supplierProducts[$i]['Supplier_Product_ID']);
								$product->SKU = trim($form->GetValue('sku_' . $order->Line[$i]->ID));

								if($session->Supplier->ShowProduct == 'Y') {
									$product->SupplierProductNumber = trim($form->GetValue('productnumber_' . $order->Line[$i]->ID));
								}

								$product->Update();
							} else {
								$product = new SupplierProduct();
								$product->Cost = '0.00';
								$product->SKU = trim($form->GetValue('sku_' . $order->Line[$i]->ID));

                                if($session->Supplier->ShowProduct == 'Y') {
									$product->SupplierProductNumber = trim($form->GetValue('productnumber_' . $order->Line[$i]->ID));
								}

								$product->Product->ID = $order->Line[$i]->Product->ID;
								$product->Supplier->ID = $session->Supplier->ID;
								$product->Add();
							}

							$newCost = number_format($form->GetValue('cost_' . $order->Line[$i]->ID), 2, '.', '');
							if($newCost > 0) {
								if($newCost != $order->Line[$i]->Cost) {
									$order->Line[$i]->Product->Get();

									$order->IsWarehouseDeclined = 'Y';
									$order->IsWarehouseDeclinedRead = 'N';

									$warehouseNote = new OrderWarehouseNote();
									$warehouseNote->IsAlert = 'Y';
									$warehouseNote->Note = sprintf('%s has requested that the cost price of %s (#%d) be changed from &pound;%s to &pound;%s within the Warehouse Portal.', $session->Supplier->Contact->Person->GetFullName(), $order->Line[$i]->Product->Name, $order->Line[$i]->Product->ID, $supplierProducts[$i]['Cost'], $newCost);
									$warehouseNote->Order->ID = $order->ID;
									$warehouseNote->Type->ID = 5;
									$warehouseNote->Warehouse->ID = $warehouseId;
									$warehouseNote->Add();
								}
							}
						}
					}
				}
			}

			if(!empty($stock)) {
				$note = array();

				for($i=0; $i<count($order->Line); $i++) {
					if(isset($stock[$order->Line[$i]->ID])) {
						$note[] = sprintf('Stock for product \'%s\' (quickfind #%d) will arrive in stock in %d days.', $order->Line[$i]->Product->Name, $order->Line[$i]->Product->ID, $stock[$order->Line[$i]->ID]);
					}
				}

				$warehouseNote = new OrderWarehouseNote();
				$warehouseNote->IsAlert = 'Y';
				$warehouseNote->Note = implode('<br /><br />', $note);;
				$warehouseNote->Order->ID = $order->ID;
				$warehouseNote->Type->ID = 1;
				$warehouseNote->Warehouse->ID = $warehouseId;
				$warehouseNote->Add();

				$order->IsWarehouseDeclined = 'Y';
				$order->IsWarehouseDeclinedRead = 'N';
			}

			$order->Update();

			redirect("Location: supplier_order_details.php?orderid=". $order->ID);
		}
	}
}

$script = sprintf('<script language="javascript" type="text/javascript">
	var toggleLine = function(lineId) {
		var e = null;

		e = document.getElementById(\'line_\' + lineId);
		if(e) {
			if(e.style.display == \'none\') {
				e.style.display = \'\';

				e = document.getElementById(\'toggle_line_\' + lineId);
				if(e) {
					e.src = \'images/aztector_3.gif\';
					e.alt = \'Collapse\';
				}
			} else {
				e.style.display = \'none\';

				e = document.getElementById(\'toggle_line_\' + lineId);
				if(e) {
					e.src = \'images/aztector_4.gif\';
					e.alt = \'Expand\';
				}
			}
		}
	}
	</script>');

$page = new Page(sprintf('[#%s] Order Details', $order->ID), 'Manage this order here.');
$page->AddToHead($script);
$page->Display('header');

$zeroCost = 0;

for($i=0; $i < count($order->Line); $i++){
	if($warehouseId == $order->Line[$i]->DespatchedFrom->ID) {
		if(($order->Line[$i]->Status != 'Cancelled') && ($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Despatched')) {
			if($order->Line[$i]->Cost == 0) {
				$zeroCost++;
			}
		}
	}
}

if($zeroCost > 0) {
	$bubble = new Bubble('Warning!', 'You must specify a cost price before you can despatch an order line.');

	echo $bubble->GetHTML();
	echo '<br />';
}

if(!$form->Valid){
	echo $form->GetError();
	echo "<br />";
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('orderid');
?>
<script language="javascript" type="text/javascript">
if(<?php echo ($order->HasAlerts($warehouseId)) ? 'true' : 'false'; ?>){
	popUrl('supplier_order_alerts.php?oid=<?php echo $order->ID; ?>', 500, 400);
}

function changeDelivery(num){
if(num == ''){
	alert('Please Select a Delivery Option');
} else {
	window.location.href = 'supplier_order_details.php?orderid=<?php echo $order->ID; ?>&changePostage=' + num;
}
}
</script>

<?php
if(isset($_REQUEST['postage']) && $_REQUEST['postage'] == 'error'){
$order->CalculateShipping();

if($order->Error){
?>
<table class="error" cellspacing="0">
<tr>
<td valign="top"><img src="images/icon_alert_2.gif" width="16"
	height="16" align="absmiddle"> <strong>Shipping Information Not
Found:</strong><br>
Sorry could not find any shipping settings for this location. Please
contact your system administrator for changing shipping location.</td>
</tr>
</table>
<br />
<?php
} else {
?>
<table class="error" cellspacing="0">
<tr>
<td valign="top"><img src="images/icon_alert_2.gif" width="16"
	height="16" align="absmiddle"> <strong>Shipping Information Needed:</strong><br>
Please select an Appropriate Shipping Option: <?php echo $order->PostageOptions; ?>
</td>
</tr>
</table>
<br />
<?php
}
}

if(Setting::GetValue('sage_pay_active') == 'false') {
$bubble = new Bubble('Payment Gateway Unavailable', 'You will be unable to process orders as our third party payment gateway &quot;Sage Pay&quot; is experiencing technical difficulties.<br />Please check back soon.');

	echo $bubble->GetHTML();
	echo '<br />';
}

if($order->Invoice->Address->Country->ID == 0) {
	$bubble = new Bubble('Missing Invoice Details', 'This order cannot be invoiced and despatched until at least an invoice country value is provided.');

	echo $bubble->GetHTML();
	echo '<br />';
}

if($order->IsWarehouseDeclined == 'Y') {
	$bubble = new Bubble('Warehouse Declined', 'This order is currently warehouse declined and cannot be despatched until BLT Direct have resolved this status.');

	echo $bubble->GetHTML();
	echo '<br />';
}
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<td valign="top">


<table cellpadding="0" cellspacing="0" border="0"
	class="invoiceAddresses">
	<tr>
		<td valign="top" class="shipping">
		<p><strong>Shipping Address:</strong><br />
            			<?php echo sprintf('%s%s', (empty($order->ShippingOrg)) ? '' : sprintf('%s<br />', $order->ShippingOrg), $order->Shipping->GetFullName()); ?><br />
                    	<?php echo $order->Shipping->Address->GetFormatted('<br />'); ?><br />
		<br />
                    	<?php echo $order->Customer->Contact->Person->GetPhone('<br />'); ?>
                    </p>
		</td>
	</tr>
</table>
<br />

<?php
if($order->IsWarehouseDeclined == 'N') {
	$data = new DataQuery(sprintf("SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID INNER JOIN warehouse AS w ON ol.Despatch_From_ID=w.Warehouse_ID AND w.Warehouse_ID=%d INNER JOIN postage AS p ON o.Postage_ID=p.Postage_ID WHERE ol.Despatch_ID=0 AND o.Status IN ('Packing', 'Partially Despatched') AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N' AND o.Created_On>'%s' GROUP BY o.Order_ID ORDER BY o.Created_On ASC LIMIT 0, 1", mysql_real_escape_string($warehouseId), mysql_real_escape_string($order->CreatedOn)));
	if($data->TotalRows > 0) {
		echo sprintf('<input type="button" class="btn" name="back" value="back" onclick="window.self.location.href=\'supplier_order_details.php?orderid=%d\'" /> ', $data->Row['Order_ID']);
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID INNER JOIN warehouse AS w ON ol.Despatch_From_ID=w.Warehouse_ID AND w.Warehouse_ID=%d INNER JOIN postage AS p ON o.Postage_ID=p.Postage_ID WHERE ol.Despatch_ID=0 AND o.Status IN ('Packing', 'Partially Despatched') AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N' AND o.Created_On<'%s' GROUP BY o.Order_ID ORDER BY o.Created_On DESC LIMIT 0, 1",mysql_real_escape_string($warehouseId), mysql_real_escape_string($order->CreatedOn)));
	if($data->TotalRows > 0) {
		echo sprintf('<input type="button" class="btn" name="next" value="next" onclick="window.self.location.href=\'supplier_order_details.php?orderid=%d\'" /> ', $data->Row['Order_ID']);
	}
	$data->Disconnect();
}
?>

</td>
<td></td>
<td align="right" valign="top">

<table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">

	<?php
	if($order->ParentID > 0) {
		$isVisible = false;

		for($i=0; $i<count($parent->Line); $i++) {
			if($parent->Line[$i]->DespatchedFrom->ID == $warehouseId) {
				$isVisible = true;
			}
		}

		if($isVisible) {
			?>

            <tr>
				<th>Orginal Order Ref:</th>
				<td><a href="supplier_order_details.php?orderid=<?php echo $parent->ID; ?>"><?php echo $parent->Prefix . $parent->ID; ?></a></td>
			</tr>

			<?php
		}
	}
	?>

	<tr>
		<th>Order Ref:</th>
		<td><?php echo $order->Prefix . $order->ID; ?></td>
	</tr>

    <?php
	if(($order->Prefix == 'R') || ($order->Prefix == 'B')) {
		$return = new ProductReturn($order->ReturnID);
		$return->Reason->Get();
        ?>

		<tr>
			<th>Return Reason:</th>
			<td><?php echo $return->Reason->Description; ?></td>
		</tr>

    	<?php
	}
	?>

	<tr>
    	<th valign="middle">Customer Ref:</th>
    	<td valign="middle"><?php echo $order->CustomID; ?></td>
    </tr>
	<tr>
		<th>Customer:</th>
		<td><?php echo $order->Customer->Contact->Person->GetFullName(); ?></td>
	</tr>
	<tr>
		<th>Order Status:</th>
		<td><?php echo $order->Status; ?></td>
	</tr>
	<tr>
		<th>Backordered:</th>
                <?php
                if($order->Backordered == 'N') {
                	$boStatus = 'No';

                	for($i=0; $i < count($order->Line); $i++){
                		if(strtolower(trim($order->Line[$i]->Status)) == 'backordered') {
                			$boStatus = 'Yes';
                		}
                	}
                } else {
                	$boStatus = 'Yes';
                }
				?>
		        <td><?php echo $boStatus; ?></td>
			</tr>
              <tr>
                <th>Payment Method: </th>
                <td><?php echo $order->GetPaymentMethod(); ?>
              </tr>
              <tr>
                <th>Card: </th>
                <td>
				<?php
				echo $order->Card->PrivateNumber();
				?>
				&nbsp;
				</td>
              </tr>
              <tr>
                <th>&nbsp;</th>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <th>Order Date:</th>
                <td><?php echo cDatetime($order->OrderedOn, 'shortdate'); ?></td>
              </tr>
		</table>
</td>
</tr>
<tr>
<td colspan="3">
	<br>
	<br>

	<div style="background-color: #eee; padding: 10px 0 10px 0;">
 		<p><span class="pageSubTitle">Order Lines</span><br /><span class="pageDescription">Listing products for despatching.</span></p>

			<table cellspacing="0" class="orderDetails">
				<tr>
					<th>Qty</th>
					<th>Product</th>

					<?php
					echo '<th>Warehouse</th>';
					?>

					<th>Location</th>
					<th>Despatched</th>

					<?php
					echo "<th>Cost</th>";
					echo "<th>SKU</th>";

					if($session->Supplier->ShowProduct == 'Y') {
						echo '<th>Product Number</th>';
					}
		            ?>

		            <th>Quickfind</th>

		            <?php
					echo '<th>Stock Arrival</th>';
					?>

					<th>Backorder</th>
				</tr>

		          <?php
		          $rowCount = 0;

		          $allDespatched = true;

		          for($i=0; $i < count($order->Line); $i++){
		            if((($warehouseId == $order->Line[$i]->DespatchedFrom->ID)) || ((($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N') && ($order->Line[$i]->DespatchedFrom->Contact->DropShipperID == $session->Supplier->ID)))) {
		              	$rowCount++;
		              	$wareHouseId = $order->Line[$i]->DespatchedFrom->ID;

		              	$backgroundColor = 'ffffff';

		              	if(($order->Line[$i]->Status != "Invoiced") && ($order->Line[$i]->Status != "Cancelled") && ($order->Line[$i]->Status != 'Despatched')) {
		              		if($order->IsRestocked == 'Y') {
		              			$backgroundColor = 'ffd399';
		              		} elseif($order->IsWarehouseUndeclined == 'Y') {
		              			$backgroundColor = '99ff99';
		              		}
		              	}

					    if($order->Line[$i]->Product->Stocked == 'Y') {
					      	$branchStock = 0;

					      	$data = new DataQuery(sprintf("SELECT SUM(ws.Quantity_In_Stock) AS Quantity FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
					    	$branchStock += $data->Row['Quantity'];
					      	$data->Disconnect();

					      	if($branchStock == 0) {
					      		$backgroundColor = 'bb99ff';
					      	}
					    }

		              	echo sprintf('<tr style="background-color: #%s;">', $backgroundColor);
						?>

		                <td>
							<?php
								echo $order->Line[$i]->Quantity;
							?>x
						</td>
						<td><?php echo $order->Line[$i]->Product->Name; ?></td>
						<td>
							<?php
							if($order->Line[$i]->DespatchedFrom->Type == 'S') {
								$order->Line[$i]->DespatchedFrom->Contact->Contact->Get();

								echo $order->Line[$i]->DespatchedFrom->Contact->Contact->Parent->Organisation->Name;
							} else {
								$data = new DataQuery(sprintf("SELECT SUM(pl.Quantity_Decremental) AS Quantity_Incoming FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID WHERE p.For_Branch>0 AND pl.Quantity_Decremental>0 AND pl.Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
								echo number_format($data->Row['Quantity_Incoming'], 0, '.', '') . 'x';
								$data->Disconnect();
							}
							?>
						</td>
						<td>
							<?php
							$warehouseLocation = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($order->Line[$i]->DespatchedFrom->ID), mysql_real_escape_string($order->Line[$i]->Product->ID)));
							echo $warehouseLocation->Row['Shelf_Location'];
							$warehouseLocation->Disconnect();
							?>
						</td>
						<td>
							<?php
							if(!empty($order->Line[$i]->DespatchID)) {
								echo '<a href="despatch_view.php?despatchid=' . $order->Line[$i]->DespatchID . '" target="_blank"><img src="./images/icon_tick_2.gif" border="0" /></a>';
							} else {
								$allDespatched = false;
								echo "Not despatched";
							}
							?>
						</td>

						<?php
						if(strtolower($order->Line[$i]->Status) == 'cancelled'){
							?>
							<td colspan="1" align="center">Cancelled</td>
							<?php
						}

						echo sprintf('<td>&pound;%s Per item</td>', ($isWarehouseEditable && (($order->Line[$i]->Status != 'Cancelled') && ($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Despatched'))) ? $form->GetHTML('cost_'.$order->Line[$i]->ID) : $order->Line[$i]->Cost);
						echo sprintf('<td>%s&nbsp;</td>', ($isWarehouseEditable && (($order->Line[$i]->Status != 'Cancelled') && ($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Despatched'))) ? $form->GetHTML('sku_'.$order->Line[$i]->ID) : $supplierProducts[$i]['Supplier_SKU']);

		                if($session->Supplier->ShowProduct == 'Y') {
		                    echo sprintf('<td>%s&nbsp;</td>', ($isWarehouseEditable && (($order->Line[$i]->Status != 'Cancelled') && ($order->Line[$i]->Status != 'Invoiced') && ($order->Line[$i]->Status != 'Despatched'))) ? $form->GetHTML('productnumber_'.$order->Line[$i]->ID) : (($supplierProducts[$i]['Supplier_Product_Number'] > 0) ? $supplierProducts[$i]['Supplier_Product_Number'] : '-'));
						}
						?>

		                <td><a href="../../product.php?pid=<?php echo $order->Line[$i]->Product->ID; ?>"><?php echo $order->Line[$i]->Product->ID; ?></a></td>

		                <?php
		               	echo sprintf('<td nowrap="nowrap">%s days</td>', $form->GetHTML('days_' . $order->Line[$i]->ID));

		                if(!stristr($order->Line[$i]->Status, 'Backordered')){
		                	if(empty($order->Line[$i]->DespatchID)) {
								?>
		                		<td><input name="backorder" type="button" value="backorder" class="btn" onclick="window.location.href='supplier_order_backorder.php?orderid=<?php echo $order->ID; ?>&orderlineid=<?php echo $order->Line[$i]->ID; ?>';" /></td>
		                		<?php
		                	} else {
		                		echo '<td>&nbsp;</td>';
		                	}
		                } elseif(empty($order->Line[$i]->DespatchID)) {
		                	?>
						    <td>Expected:<br /><a href="supplier_order_backorder.php?orderid=<?php echo $order->ID; ?>&orderlineid=<?php echo $order->Line[$i]->ID; ?>&redirect=<?php echo $_SERVER['PHP_SELF']; ?>"><?php print ($order->Line[$i]->BackorderExpectedOn > '0000-00-00 00:00:00') ? cDatetime($order->Line[$i]->BackorderExpectedOn, 'shortdate') : 'Unknown'; ?></a></td>
		                	<?php
		                } else {
		                	echo '<td>&nbsp;</td>';
		                }
		                ?>

		              </tr>

		              <?php
		          }
			  }

		      if($rowCount == 0){
		        echo sprintf('<tr><td colspan="%d" align="center">There are no order lines in this order that are too be shipped from your branch.</td></tr>', 9);
		      }
			  ?>
		    <tr>
				<td colspan="<?php echo ($session->Supplier->ShowProduct == 'Y') ? 11 : 109; ?>" align="left">Cart Weight: ~<?php echo $order->Weight; ?>Kg</td>
			</tr>
		</table>
		<br />

			<?php
            	if($order->IsWarehouseDeclined == 'N') {
            		if(!$allDespatched) {
                        if(Setting::GetValue('sage_pay_active') == 'true') {
                    		if($order->Invoice->Address->Country->ID > 0) {
            					?>

								<input name="despatch" type="button" value="despatch" class="btn" onclick="popUrl('supplier_order_despatch.php?orderid=<?php echo $order->ID; ?>', 800, 600);" />

                				<?php
							}
						}
	                }
				}

                if($isWarehouseEditable) {
                	?>
                	<input name="update" type="submit" value="update" class="btn" />
                	<?php
                }
                ?>

                <input name="print" type="button" value="print" class="btn" onclick="window.self.print();" />


	</div>
	<br />


<?php
$data = new DataQuery(sprintf("SELECT ol2.Product_ID, ol2.Product_Title, r.Note, r.Created_On FROM `return` AS r INNER JOIN order_line AS ol ON ol.Order_Line_ID=r.Order_Line_ID INNER JOIN order_line AS ol2 ON ol2.Product_ID=ol.Product_ID AND ol2.Order_ID=%d AND ol2.Despatch_From_ID=ol.Despatch_From_ID WHERE r.Reason_ID IN (2,3) AND r.Note<>'' ORDER BY r.Return_ID DESC LIMIT 10", mysql_real_escape_string($order->ID)));
if($data->TotalRows > 0) {
	?>

    <div style="background-color: #eee; padding: 10px 0 10px 0;">
 		<p><span class="pageSubTitle">Common Problems</span><br /><span class="pageDescription">Listing common problems with despatched products in this order.</span></p>

		<table cellspacing="0" class="orderDetails">
			<tr>
				<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
				<th nowrap="nowrap" style="padding-right: 5px;">Product</th>
				<th nowrap="nowrap" style="padding-right: 5px;">Problem</th>
				<th nowrap="nowrap" style="padding-right: 5px;">Date</th>
			</tr>

			<?php
			while($data->Row) {
				?>

				<tr>
					<td><a href="../../product.php?pid=<?php echo $data->Row['Product_ID']; ?>"><?php echo $data->Row['Product_ID']; ?></a></td>
					<td><?php echo strip_tags($data->Row['Product_Title']); ?></td>
					<td><?php echo $data->Row['Note']; ?></td>
					<td><?php echo cDatetime($data->Row['Created_On'], 'shortdate'); ?></td>
				</tr>

				<?php
				$data->Next();
			}
			?>

		</table>
		<br />

	</div>
	<br />

	<?php
}
$data->Disconnect();
?>

</td>
</tr>
<tr>
<td align="left" valign="top" width="49.5%">

<table border="0" cellpadding="7" cellspacing="0">
	<tr>
		<td valign="top">

		<table border="0" cellpadding="7" cellspacing="0"
			class="orderTotals">
			<tr>
				<th colspan="2">New Warehouse Notification</th>
			</tr>
			<tr>
				<td><strong><?php echo $form->GetLabel('type'); ?></strong></td>
				<td><?php echo $form->GetHTML('type'); ?></td>
			</tr>
			<tr>
				<td><strong><?php echo $form->GetLabel('note'); ?></strong></td>
				<td><?php echo $form->GetHTML('note'); ?></td>
			</tr>
		</table>
		<br />

		<input name="addnote" type="submit" value="add note" class="btn" /></td>

		<td valign="top">

		<table border="0" cellpadding="7" cellspacing="0"
			class="orderTotals">
			<tr>
				<th>Warehouse Notes</th>
			</tr>

			                  <?php
			                  $data = new DataQuery(sprintf("SELECT Order_Warehouse_Note_ID FROM order_warehouse_note WHERE Order_ID=%d AND Warehouse_ID=%d", $order->ID, $warehouseId));
			                  while($data->Row){
			                  	$note = new OrderWarehouseNote($data->Row['Order_Warehouse_Note_ID']);
			                  	?>

			                  	<tr>
				<td>
				<p><strong>Subject:</strong> <?php echo $note->Type->Name; ?><br />
				<strong>Date:</strong> <?php echo cDatetime($note->CreatedOn); ?><br />
				<strong>Author:</strong> <?php echo ($note->CreatedBy > 0) ? $GLOBALS['COMPANY'] : $session->Warehouse->Name; ?>
										</p>

										<?php echo $note->Note; ?>
									</td>
			</tr>

								<?php
			                  	$data->Next();
			                  }
			                  $data->Disconnect();
							  ?>

			                </table>

		</td>
	</tr>
</table>

</td>
<td width="1%"></td>
<td align="right" valign="top" width="49.5%">

	<?php
	if($order->ParentID > 0) {
		$data = new DataQuery(sprintf("SELECT d.Despatch_ID, d.Created_On FROM order_line AS ol INNER JOIN despatch AS d ON d.Despatch_ID=ol.Despatch_ID WHERE ol.Order_ID=%d AND ol.Despatch_From_ID=%d", mysql_real_escape_string($parent->ID), mysql_real_escape_string($warehouseId)));
		if($data->TotalRows > 0) {
			?>

            <table border="0" cellpadding="7" cellspacing="0" class="orderTotals" width="100%">
				<tr>
					<th colspan="2">Orginal Order Despatches</th>
				</tr>

				<?php
				while($data->Row) {
					?>

                    <tr>
                    	<td><?php echo cDatetime($data->Row['Created_On'], 'shortdatetime'); ?></td>
                    	<td align="right"><a href="despatch_view.php?despatchid=<?php echo $data->Row['Despatch_ID']; ?>" target="_blank"><?php echo $data->Row['Despatch_ID']; ?></a></td>
					</tr>

					<?php
					$data->Next();
				}
				?>

			</table>
			<br />

			<?php
		}
		$data->Disconnect();
	}
	?>

	<table border="0" cellpadding="7" cellspacing="0" class="orderTotals" width="100%">
		<tr>
			<th colspan="2">Tax &amp; Shipping</th>
		</tr>
		<tr>
			<td>Delivery Option:</td>
			<td align="left">
					<?php
						$order->Postage->Get();

						echo $order->Postage->Name;
					?>
                </td>
		</tr>
		<tr>
			<td>Shipping Cost</td>
			<td>
				<?php echo $form->GetHTML('shipping_cost'); ?>
				<input type="submit" name="alteration" value="submit alteration" class="btn" />
			</td>
		</tr>
		<tr>
			<td>Tax Exempt:</td>
			<td align="left"><?php echo (!empty($order->TaxExemptCode)) ? 'Yes' : 'No'; ?></td>
		</tr>
                  <?php
                  if(!empty($order->TaxExemptCode)) {
              		?>
	                <tr>
			<td>Tax Exemption Code:</td>
			<td align="left"><?php echo $order->TaxExemptCode; ?>&nbsp;</td>
		</tr>

                <?php
			  }
			  ?>
            </table>

</td>
</tr>
</table>

<?php
echo $form->Close();
		
$page->Display('footer');
require_once('lib/common/app_footer.php');