<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderWarehouseNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Package.php');

$session->Secure(3);

$order = new Order($_REQUEST['orderid']);
$order->GetLines();
$order->Customer->Get();
$order->Customer->Contact->Get();
$order->GetTransactions();
$order->PaymentMethod->Get();

if($order->IsDeclined == 'Y') {
	redirectTo('orders_packing.php');	
}

if($order->IsWarehouseDeclined == 'Y') {
	redirectTo('orders_packing.php');	
}

if($action == 'unpack') {
	$order->Status = 'Pending';
	$order->Update();

	redirect(sprintf("Location: orders_packing.php"));
}

if($order->ReceivedOn == '0000-00-00 00:00:00'){
	$order->Received();
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'hidden', $order->ID, 'numeric_unsigned', 1, 11);
$form->AddField('type', 'Type', 'select', '0', 'numeric_unsigned', 1, 11, false);
$form->AddOption('type', '0', '');

$data = new DataQuery(sprintf("SELECT * FROM order_warehouse_note_type ORDER BY Name ASC"));
while($data->Row) {
	$form->AddOption('type', $data->Row['Order_Warehouse_Note_Type_ID'], $data->Row['Name']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('note', 'Note', 'textarea', '', 'anything', 1, 2048, false, 'style="width: 100%;" rows="5"');

for($i=0; $i < count($order->Line); $i++){
	$form->AddField('qty_' . $order->Line[$i]->ID, 'Quantity of ' . $order->Line[$i]->Product->Name, 'text',  $order->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="3"');
}

$form->AddField('warehouses', 'Warehouses', 'select', '0', 'numeric_unsigned', 0, 11, false, 'onchange="despatch(this);"');
$form->AddOption('warehouses', '0', '-- Despatch From --');

$warehouses = array();

for($i=0; $i<count($order->Line); $i++) {
	$order->Line[$i]->DespatchedFrom->Contact->Get();
                  	
	if(empty($order->Line[$i]->DespatchID) && (($order->Line[$i]->DespatchedFrom->Type == 'B') || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N')))) {
		if($order->Line[$i]->DespatchedFrom->Type == 'B') {
			$warehouses[$order->Line[$i]->DespatchedFrom->ID] = $order->Line[$i]->DespatchedFrom->Name;
		} else {
			if($order->Line[$i]->DespatchedFrom->Contact->DropShipperID > 0) {
				$warehouse = new Warehouse();
				
				if($warehouse->Get($order->Line[$i]->DespatchedFrom->Contact->DropShipperID)) {
					$warehouses[$order->Line[$i]->DespatchedFrom->Contact->DropShipperID] = $warehouse->Name;
				}
			}
		}
	}
}

foreach($warehouses as $warehouseId=>$warehouseName) {
	$form->AddOption('warehouses', $warehouseId, $warehouseName);
}

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
			$warehouseNote->Warehouse->ID = $session->Warehouse->ID;
			$warehouseNote->Add();

			redirect(sprintf("Location: %s?orderid=%d", $_SERVER['PHP_SELF'], $order->ID));
		}
	}
}

$script = sprintf('<script language="javascript" type="text/javascript">
	function despatch(obj) {
		if(obj.value > 0) {
			popUrl(\'order_despatch.php?orderid=%d&warehouseid=\' + obj.value, 650, 450);
		}
	}
	</script>', $order->ID);

$script .= sprintf('<script language="javascript" type="text/javascript">
	var toggleCommonProducts = function() {
		var block = document.getElementById(\'common-products-block\');
		var image = document.getElementById(\'common-products-image\');

		if(block && image) {
			block.style.display = (block.style.display == \'none\') ? \'block\' : \'none\';
			image.src = (block.style.display == \'none\') ? \'images/aztector_4.gif\' : \'images/aztector_3.gif\';
		}		
	}
	</script>');

$page = new Page(sprintf('%s%s Order Details for %s', $order->Prefix, $order->ID, $order->Customer->Contact->Person->GetFullName()), '');
$page->AddToHead($script);
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('orderid');

$orderNoteAlert = ($order->HasAlerts()) ? 'yes' : 'no';
?>
<script language="javascript" type="text/javascript">
var isPrompt  = '<?php echo $orderNoteAlert; ?>';
var refreshCart;

if(isPrompt == 'yes'){
	popUrl('order_alerts.php?oid=<?php echo $order->ID; ?>', 500, 400);
}
</script>

<?php 
	// show an alert if the payment was taken by PDQ and the invoice and shipping address are the same
	if($order->PaymentMethod->Reference == 'pdq' && md5(serialize($order->Invoice->Address)) == md5(serialize($order->Shipping->Address))){ 
?>
<table class="error" cellspacing="0">
	<tr>
		<td valign="top"><img src="images/icon_alert_2.gif" width="16" height="16" align="absmiddle">	
		<strong>PDQ Payment:</strong><br>
		Payment taken manually by PDQ machine.<br />
		Please obtain and include the customer's card receipt attached to a printed invoice with this shipment.
		</td>
	</tr>
</table>
<?php } ?>
<br />
<?php
if(isset($_REQUEST['postage']) && $_REQUEST['postage'] == 'error'){
	$order->CalculateShipping();

	if($order->Error){
?>
<table class="error" cellspacing="0">
  <tr>
    <td valign="top"><img src="images/icon_alert_2.gif" width="16" height="16" align="absmiddle">	<strong>Shipping Information Not Found:</strong><br>
	Sorry could not find any shipping settings for this location. Please change shipping location. <a href="order_changeAddress.php?orderid=<?php echo $order->ID; ?>&type=shipping">Click Here</a>
    </td>
  </tr>
</table>
<br />
<?php
	} else {
?>
<table class="error" cellspacing="0">
  <tr>
    <td valign="top"><img src="images/icon_alert_2.gif" width="16" height="16" align="absmiddle">	<strong>Shipping Information Needed:</strong><br>
	Please select an Appropriate Shipping Option: <?php echo $order->PostageOptions; ?>
    </td>
  </tr>
</table>
<br />
<?php
	}
}

if($order->Invoice->Address->Country->ID == 0) {
	$bubble = new Bubble('Missing Invoice Details', 'This order cannot be invoiced and despatched until at least an invoice country value is provided.');

	echo $bubble->GetHTML();
	echo '<br />';
}
?>
			<table width="100%"  border="0" cellspacing="0" cellpadding="0">
              <tr>
			    <td>
                  <table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
                  <tr>
                    <td valign="top" class="shipping"><p><strong>Shipping Address:</strong><br />
                            <?php echo $order->GetShippingAddress(); ?><br /><br /><?php echo $order->Customer->Contact->Person->GetPhone('<br />');  ?></p>
					</td>
                  </tr>
                </table>
                <br />

                  <?php
                  if(strtolower($order->Status) == 'packing') {
					?>
					<input name="action" type="submit" value="unpack" class="btn" />
					<?php
                  }
                  
					$data = new DataQuery(sprintf("SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID INNER JOIN warehouse AS w ON ol.Despatch_From_ID=w.Warehouse_ID LEFT JOIN supplier AS s ON s.Supplier_ID=w.Type_Reference_ID AND w.Type='S' WHERE (w.Type='B' OR (w.Type='S' AND s.Is_Drop_Shipper='N')) AND ol.Despatch_ID=0 AND o.Status IN ('Partially Despatched', 'Packing') AND o.Is_Declined='N' AND o.Is_Failed='N' AND o.Is_Warehouse_Declined='N' AND o.Is_Awaiting_Customer='N' AND o.Order_ID>%d GROUP BY o.Order_ID ORDER BY o.Order_ID ASC LIMIT 0, 1", mysql_real_escape_string($order->ID)));
					if($data->TotalRows > 0) {
						echo sprintf('<input type="button" class="btn" name="back" value="back" onclick="window.self.location.href=\'?orderid=%d\'" /> ', $data->Row['Order_ID']);
					}
					$data->Disconnect();

					$data = new DataQuery(sprintf("SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID INNER JOIN warehouse AS w ON ol.Despatch_From_ID=w.Warehouse_ID LEFT JOIN supplier AS s ON s.Supplier_ID=w.Type_Reference_ID AND w.Type='S' WHERE (w.Type='B' OR (w.Type='S' AND s.Is_Drop_Shipper='N')) AND ol.Despatch_ID=0 AND o.Status IN ('Partially Despatched', 'Packing') AND o.Is_Declined='N' AND o.Is_Failed='N' AND o.Is_Warehouse_Declined='N' AND o.Is_Awaiting_Customer='N' AND o.Order_ID<%d GROUP BY o.Order_ID ORDER BY o.Order_ID DESC LIMIT 0, 1", mysql_real_escape_string($order->ID)));
					if($data->TotalRows > 0) {
						echo sprintf('<input type="button" class="btn" name="next" value="next" onclick="window.self.location.href=\'?orderid=%d\'" /> ', $data->Row['Order_ID']);
					}
					$data->Disconnect();
					?>

			    </td>
			    <td></td>
			    <td align="right" valign="middle"><table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
                  <tr>
                    <th>Order Ref: </th>
                    <td><?php echo $order->Prefix . $order->ID; ?></td>
                  </tr>
				  <tr>
                    <th>Customer Ref: </th>
                    <td><?php echo $order->CustomID; ?> &nbsp;</td>
                  </tr>
                  <tr>
                    <th>Customer: </th>
                    <td><?php echo $order->Customer->Contact->Person->GetFullName(); ?></td>
                  </tr>
                  <tr>
                    <th>Order Status:</th>
                    <td><?php echo $order->Status; ?></td>
                  </tr>
                   <tr>
			        <th>Sample: </th>
			        <td><?php echo ($order->Sample == 'Y') ? 'Yes' : 'No'; ?></td>
			      </tr>
                  <?php if($order->Sample == 'N') { ?>
                  <tr>
                    <th>Payment Method: </th>
                    <td><?php echo $order->GetPaymentMethod(); ?></td>
                  </tr>
                  <?php } ?>
                  <tr>
                    <th>&nbsp;</th>
                    <td>&nbsp;</td>
                  </tr>
                  <tr>
                    <th>Order Date: </th>
                    <td><?php echo cDatetime($order->OrderedOn, 'shortdate'); ?></td>
                  </tr>

                </table>                </td>
              </tr>
              <tr>
                <td colspan="3">
                	<br />
                 <?php
				if($order->Invoice->Address->Country->ID > 0) {
                	echo $form->GetHTML('warehouses');
				}
				?>
				
				<br />
				<br />
				
			<div style="background-color: #eee; padding: 10px 0 10px 0;">
				<p><span class="pageSubTitle">Order Lines</span><br /><span class="pageDescription">Listing products for despatching.</span></p>

                  <table cellspacing="0" class="orderDetails">
                  <tr>
                    <th>Qty</th>
                    <th>Product</th>
                    <th>Warehouse</th>
                    <th>Location</th>
                    <th>Stocked</th>
                    <th>Incoming</th>
                    <th>Despatched</th>
                    <th>Quickfind</th>
                    <th>Backorder</th>
                  </tr>

                  <?php
                  $rowCount = 0;

                  for($i=0; $i<count($order->Line); $i++){
                  	//$order->Line[$i]->DespatchedFrom->Contact->Get();
                  	
                  	if(($order->Line[$i]->DespatchedFrom->Type == 'B') || (($order->Line[$i]->DespatchedFrom->Type == 'S') && ($order->Line[$i]->DespatchedFrom->Contact->IsDropShipper == 'N'))) {
                  		$rowCount++;

                  		$dataComponents = new DataQuery(sprintf("SELECT * FROM product_components WHERE Component_Of_Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
                  		if($dataComponents->TotalRows > 0) {
                  			$hasComponents = true;
                  		} else {
                  			$hasComponents = false;
                  		}

                  		if(!$hasComponents) {
                  			$stocked = false;
                  			$qtyStocked = 0;
                  			$warehouseId = $order->Line[$i]->DespatchedFrom->ID;

                  			while($warehouseId > 0) {
                  				$data = new DataQuery(sprintf("SELECT SUM(Quantity_In_Stock) AS Quantity FROM warehouse_stock WHERE Product_ID=%d AND Warehouse_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID), mysql_real_escape_string($warehouseId)));
	                  			if($data->TotalRows > 0) {
	                  				$qtyStocked += $data->Row['Quantity'];

	                  				$stocked = true;
	                  			}
	                  			$data->Disconnect();

                  				$data = new DataQuery(sprintf("SELECT Parent_Warehouse_ID FROM warehouse WHERE Warehouse_ID=%d", mysql_real_escape_string($warehouseId)));
                  				if($data->TotalRows > 0) {
                  					$warehouseId = $data->Row['Parent_Warehouse_ID'];
                  				} else {
                  					$warehouseId = 0;
                  				}
                  				$data->Disconnect();
                  			}
                  		}
						?>
				<tr <?php print ((!$hasComponents) && ($stocked) && ($qtyStocked <= 0)) ? 'style="background-color: #fcc;"' : ((!$hasComponents) && ($stocked) && ($order->Line[$i]->Quantity > $qtyStocked) ? 'style="background-color: #fdc;"' : ''); ?>>
                    <td>
					<?php echo $order->Line[$i]->Quantity; ?>x</td>
                    <td>
                    	<a href="product_profile.php?pid=<?php print $order->Line[$i]->Product->ID; ?>"><?php echo $order->Line[$i]->Product->Name; ?></a>
                    	<br />Part Number: <?php print $order->Line[$i]->Product->SKU; ?>
					</td>
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
						$warehouseLocation = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1",
																mysql_real_escape_string($order->Line[$i]->DespatchedFrom->ID),
																mysql_real_escape_string($order->Line[$i]->Product->ID)));

						echo $warehouseLocation->Row['Shelf_Location'];
						$warehouseLocation->Disconnect();
						?>&nbsp;
					</td>
					<td>
					<?php
					if((!$hasComponents) && ($stocked)) {
						echo sprintf('%sx', $qtyStocked);
					} else {
						echo '&nbsp;';
					}
					?>
					</td>
					<td>
						<?php
						if((!$hasComponents) && ($stocked)) {
							$data = new DataQuery(sprintf("SELECT SUM(pl.Quantity_Decremental) AS Quantity_Incoming FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID WHERE p.For_Branch>0 AND pl.Quantity_Decremental>0 AND pl.Product_ID=%d", mysql_real_escape_string($order->Line[$i]->Product->ID)));
							echo $data->Row['Quantity_Incoming'].'x';
							$data->Disconnect();
						} else {
							echo '&nbsp;';
						}
						?>
					</td>
					<td>
						<?php if(!empty($order->Line[$i]->DespatchID))
						echo '<a href="despatch_view.php?despatchid=' . $order->Line[$i]->DespatchID . '" target="_blank"><img src="./images/icon_tick_2.gif" border="0" /></a>';
						else{
							echo '';
						}
						?>
					</td>
					<?php
					if(strtolower($order->Line[$i]->Status) == 'cancelled'){
					?>
					<td colspan="2" align="center">Cancelled</td>
					<?php
						} ?>
				<td><a href="product_profile.php?pid=<?php print $order->Line[$i]->Product->ID; ?>"><?php echo $order->Line[$i]->Product->ID; ?></a></td>
                  <?php
                  if(strtolower($order->Line[$i]->Status)!='backordered' && empty($order->Line[$i]->DespatchID)){
                  	?>
                    <td><input name="Backorder" type="button" id="Backorder"  value="backorder" class="btn" onclick="window.self.location.href='order_backorder.php?orderid=<?php echo $order->ID; ?>&orderlineid=<?php echo $order->Line[$i]->ID; ?>&redirect=<?php echo $_SERVER['PHP_SELF']; ?>';" /></td>
                    <?php
                  } elseif(empty($order->Line[$i]->DespatchID)) {
                    ?>
					<td>Expected:<br /><?php print ($order->Line[$i]->BackorderExpectedOn > '0000-00-00 00:00:00') ? cDatetime($order->Line[$i]->BackorderExpectedOn, 'shortdate') : 'Unknown'; ?></td>
					<?php
                  } else {
                    ?>
                   	<td>&nbsp;</td>
                    <?php
                  }
                  ?>
                  </tr>
                  <?php
                  while($dataComponents->Row) {
                  	$component = new Product($dataComponents->Row['Product_ID']);

                  	$warehouseFindMain = new DataQuery(sprintf("SELECT SUM(Quantity_In_Stock) AS Quantity FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d", $order->Line[$i]->DespatchedFrom, mysql_real_escape_string($component->ID)));
                  	$qtyStocked = $warehouseFindMain->Row['Quantity'];
						?>

						<tr <?php print ($qtyStocked <= 0) ? 'style="background-color: #fcc;"' : (($order->Line[$i]->Quantity > $qtyStocked) ? 'style="background-color: #fdc;"' : 'style="background-color: #eee;"'); ?>>
							<td>Component: <?php print ($dataComponents->Row['Component_Quantity']*$order->Line[$i]->Quantity); ?>x</td>
							<td>
								<a href="product_profile.php?pid=<?php print $component->ID; ?>"><?php echo $component->Name; ?></a><br />
								Part Number: <?php print $component->SKU; ?>
							</td>
							<td></td>
							<td>
								<?php
								$warehouseFindMain = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1",$order->Line[$i]->DespatchedFrom, mysql_real_escape_string($component->ID)));
								echo $warehouseFindMain->Row['Shelf_Location'];
								$warehouseFindMain->Disconnect();
								?>
							</td>
							<td>
								<?php
								echo $qtyStocked.'x';
								?>
							</td>
							<td>
								<?php
								$data = new DataQuery(sprintf("SELECT SUM(pl.Quantity_Decremental) AS Quantity_Incoming FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID WHERE p.For_Branch>0 AND pl.Quantity_Decremental>0 AND pl.Product_ID=%d", mysql_real_escape_string($component->ID)));
								echo $data->Row['Quantity_Incoming'].'x';
								$data->Disconnect();
								?>
							</td>
							<td>&nbsp;</td>
							<td><a href="product_profile.php?pid=<?php print $component->ID; ?>"><?php print $component->ID; ?></a></td>
							<td colspan="4">&nbsp;</td>
						</tr>

						<?php
						$warehouseFindMain->Disconnect();

						$dataComponents->Next();
                  }
                  $dataComponents->Disconnect();
                  	}
                  }
                  if($rowCount == 0){
                  	echo "<tr><td colspan='9' align='center'>There are no items available for viewing.</td></tr>";
                  }
			?>
                  <tr>
                    <td colspan="9" align="left">Cart Weight: ~<?php echo $order->Weight; ?>Kg</td>
                  </tr>
                </table>
            </div>
			<br />

		    	<?php
		    	$data = new DataQuery(sprintf("SELECT ol2.Product_ID, ol2.Product_Title, r.Note, r.Created_On FROM `return` AS r INNER JOIN order_line AS ol ON ol.Order_Line_ID=r.Order_Line_ID INNER JOIN order_line AS ol2 ON ol2.Product_ID=ol.Product_ID AND ol2.Order_ID=%d INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type LIKE 'B' INNER JOIN warehouse AS w2 ON w2.Warehouse_ID=ol2.Despatch_From_ID AND w2.Type LIKE 'B' WHERE r.Reason_ID IN (2,3) AND r.Note<>'' ORDER BY r.Return_ID DESC", mysql_real_escape_string($order->ID)));
				if($data->TotalRows > 0) {
					?>

					<div style="background-color: #eee; padding: 10px 0 10px 0;">
				 		<p><span class="pageSubTitle">Common Problems</span><br /><span class="pageDescription">Listing common problems with despatched products in this order.</span></p>

						<div style="background-color: #ddd; padding: 10px; margin-bottom: 10px;">
				 			<a href="javascript:toggleCommonProducts();"><img src="images/aztector_4.gif" id="common-products-image" /></a> <strong style="font-size: 14px;"><?php echo $data->TotalRows; ?> Common Problems</strong>
				 		</div>
				 		<div id="common-products-block" style="display: none;">
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
										<td><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>"><?php echo $data->Row['Product_ID']; ?></a></td>
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

                	<?php
                	$sizes = $order->CalculatePackages();
                	?>

					<table border="0" cellpadding="6" cellspacing="0" class="orderTotals" width="100%">
						<tr>
							<th colspan="2">Packing Requirements</th>
						</tr>

						<?php
						foreach($sizes as $packageId=>$qty) {
							$package = new Package($packageId);
							?>

							<tr>
								<td><?php echo $qty; ?>x</td>
								<td><?php echo $package->Name; ?></td>
							</tr>

							<?php
						}
						?>

					</table>
					<br />
					
					<table border="0" cellpadding="6" cellspacing="0" class="orderTotals" width="100%">
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

                </td>
                <td width="1%"></td>
                <td align="right" valign="top" width="49.5%">

                <table border="0" cellpadding="6" cellspacing="0" class="orderTotals" width="100%">
                  <tr>
                    <th colspan="2">Shipping Information</th>
                  </tr>
                  <tr>
                    <td>Delivery Option:</td>
                    <td align="right">
                      <?php
                      	echo $order->PostageOptions;
						?>
                    </td>
                  </tr>
                </table>

                </td>
              </tr>
            </table>
			<?php
			echo $form->Close();
			$page->Display('footer');
			?>