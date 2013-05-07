<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Page.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseBatch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseBatchLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierReturnRequest.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierReturnRequestLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseReserve.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');

$session->Secure(3);

$purchase = new Purchase($_REQUEST['pid']);
$purchase->GetLines();

$isEditable = ((strtolower($purchase->Status) == 'unfulfilled') || (strtolower($purchase->Status) == 'partially fulfilled'));

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('pid','','hidden',$_REQUEST['pid'],'numeric_unsigned',1,11);
$form->AddField('status','Status','hidden',((isset($_REQUEST['status']) && (strlen($_REQUEST['status']) > 0)) ? $_REQUEST['status'] : 'U'),'alpha',1,1);
$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
$form->AddField('warehouse','Warehouse','select', $purchase->Warehouse->ID,'numeric_unsigned',1, 11);
$form->AddOption('warehouse', '', '');

$data = new DataQuery(sprintf("SELECT Warehouse_ID, Warehouse_Name FROM warehouse WHERE Type='B' ORDER BY Warehouse_Name ASC"));
while($data->Row) {
	$form->AddOption('warehouse', $data->Row['Warehouse_ID'], $data->Row['Warehouse_Name']);

	$data->Next();
}
$data->Disconnect();

$notes = new Form($_SERVER['PHP_SELF']);
$notes->AddField('date', 'Purchase Date', 'text', sprintf('%s/%s/%s', substr($purchase->PurchasedOn, 8, 2), substr($purchase->PurchasedOn, 5, 2), substr($purchase->PurchasedOn, 0, 4)), 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$notes->AddField('notes','Notes','textarea',$purchase->OrderNote,'anything',0,2000, false, 'style="width: 100%;" rows="5"');
$notes->AddField('customreference','Customer Reference Number','text',$purchase->CustomReferenceNumber, 'anything', 0, 30, false);
$notes->AddField('confirm','Confirm','hidden','true','alpha',4,4);
$notes->AddField('action','Action','hidden','notes','alpha',4,5);
$notes->AddField('pid','','hidden',$_REQUEST['pid'],'numeric_unsigned',1,11);

$manufacturers = array();

$data = new DataQuery(sprintf("SELECT Manufacturer_ID, Manufacturer_Name FROM manufacturer WHERE IsDataProjector='N' ORDER BY Manufacturer_Name ASC"));
while($data->Row) {
	$manufacturers[] = $data->Row;

	$data->Next();	
}
$data->Disconnect();

for($i=0; $i<count($purchase->Line); $i++) {
	if($purchase->Line[$i]->Product->ID == 0) {
		$form->AddField('name_' . $purchase->Line[$i]->ID, 'Name for ' . $purchase->Line[$i]->Product->Name, 'textarea', $purchase->Line[$i]->Product->Name, 'paragraph', 1, 100, true, 'style="font-family: arial, sans-serif;"');
		$form->AddField('cost_' . $purchase->Line[$i]->ID, 'Price for ' . $purchase->Line[$i]->Product->Name, 'text', $purchase->Line[$i]->Cost, 'float', 1, 11, true, 'size="5"');
	}

	$form->AddField('qty_'.$i, 'Quantity Arrived', 'text', '0', 'numeric_unsigned', 1, 9, true, 'size="5"');
	$form->AddField('qty_free_'.$i, 'Quantity Free', 'text', '0', 'numeric_unsigned', 1, 9, true, 'size="5"');
	$form->AddField('advice_'.$i, 'Advice Note', 'text', $purchase->Line[$i]->AdviceNote, 'anything', 1, 120, false);

	if($purchase->Line[$i]->Product->ID > 0) {
		$form->AddField('manufacturer_'.$i, 'Manufacturer', 'select', $purchase->Line[$i]->Manufacturer->ID, 'numeric_unsigned', 1, 11);
		$form->AddOption('manufacturer_'.$i, '0', '');

		foreach($manufacturers as $manufacturer) {
			$form->AddOption('manufacturer_'.$i, $manufacturer['Manufacturer_ID'], $manufacturer['Manufacturer_Name']);
		}

		if($purchase->SupplierID > 0) {
			$form->AddField('qty_damaged_'.$i, 'Quantity Damaged', 'text', '0', 'numeric_unsigned', 1, 9, true, 'size="5"');
		}
	}
}

if($action == 'addcustom') {
	if($isEditable) {
		$line = new PurchaseLine();
		$line->Quantity = 1;
		$line->Purchase = $purchase->ID;
		$line->Add();
	}	

	redirect(sprintf("Location: ?pid=%d", $purchase->ID));
}

if(isset($_REQUEST['confirm'])) {
	if($action == 'notes') {
		if($notes->Valid){
			$purchase->PurchasedOn = sprintf('%s-%s-%s 00:00:00', substr($notes->GetValue('date'), 6, 4), substr($notes->GetValue('date'), 3, 2), substr($notes->GetValue('date'), 0, 2));
			$purchase->OrderNote = $notes->GetValue('notes');
			$purchase->CustomReferenceNumber = $notes->GetValue('customreference');
			$purchase->Update();

			redirect("Location: purchases_view.php?status=".$form->GetValue('status'));
		}
	} elseif($action == 'apply free') {
		for($i=0; $i<count($purchase->Line); $i++) {
			$form->Validate('qty_free_'.$i);
		}
		
		if($form->Valid) {
			for($i=0; $i<count($purchase->Line); $i++) {
				if($form->GetValue('qty_free_'.$i) > 0) {
					$total = $purchase->Line[$i]->Cost * $purchase->Line[$i]->Quantity;
					
					$purchase->Line[$i]->QuantityDec += $form->GetValue('qty_free_'.$i);
					$purchase->Line[$i]->Quantity += $form->GetValue('qty_free_'.$i);
					$purchase->Line[$i]->Cost = $total / $purchase->Line[$i]->Quantity;
					$purchase->Line[$i]->Update();		
				}
			}
			
			redirect(sprintf("Location: ?pid=%d&status=%s%s", $_REQUEST['pid'], $form->GetValue('status'), $direct));
		}
	} else {
		if($form->Validate()) {
			$addBatch = false;
			$direct = '';

			for($i=0; $i<count($purchase->Line); $i++) {
				$quantity = $form->GetValue('qty_'.$i);

				if($purchase->Line[$i]->Product->ID > 0) {
					if($purchase->SupplierID > 0) {
						$quantity += $form->GetValue('qty_damaged_'.$i);
					}
				}

				if($quantity > 0) {
					$addBatch = true;
				}
			}

			if($purchase->SupplierID > 0) {
				for($i=0; $i<count($purchase->Line); $i++) {
					if($purchase->Line[$i]->Product->ID > 0) {
						$quantity = $form->GetValue('qty_damaged_'.$i);

						if($quantity > 0) {
							$returnRequest = new SupplierReturnRequest();
							$returnRequest->Purchase->ID = $purchase->ID;
							$returnRequest->Supplier->ID = $purchase->SupplierID;
							$returnRequest->Status = 'Pending';
							$returnRequest->Add();

							$returnRequestLine = new SupplierReturnRequestLine();
							$returnRequestLine->SupplierReturnRequestID = $returnRequest->ID;
							$returnRequestLine->Type->GetByName('Damaged');
							$returnRequestLine->PurchaseLine->ID = $purchase->Line[$i]->ID;
							$returnRequestLine->Product->ID = $purchase->Line[$i]->Product->ID;
							$returnRequestLine->Quantity = $quantity;
							$returnRequestLine->Cost = $purchase->Line[$i]->Cost;
							$returnRequestLine->Add();

							$returnRequest->Recalculate();
						}
					} else {
						$purchase->Line[$i]->Cost = $form->GetValue('cost_'.$purchase->Line[$i]->ID);
						$purchase->Line[$i]->Product->Name = $form->GetValue('name_'.$purchase->Line[$i]->ID);
					}

					$purchase->Line[$i]->AdviceNote = $form->GetValue('advice_'.$i);

					if($purchase->Line[$i]->Product->ID > 0) {
						$purchase->Line[$i]->Manufacturer->ID = $form->GetValue('manufacturer_'.$i);
					}

					$purchase->Line[$i]->Update();
				}
			}

			if($addBatch) {
				$fulfilled = true;
				$partial = false;

				$batch = new PurchaseBatch();
				$batch->Purchase->ID = $purchase->ID;
				$batch->Status = 'Unchecked';
				$batch->Add();

				for($i=0; $i<count($purchase->Line); $i++) {
					$quantity = $form->GetValue('qty_'.$i);
					$quantityArrived = $form->GetValue('qty_'.$i);

					if($purchase->Line[$i]->Product->ID > 0) {
						if($purchase->SupplierID > 0) {
							$quantity += $form->GetValue('qty_damaged_'.$i);
						}
					}

					$purchase->Line[$i]->QuantityDec = (($purchase->Line[$i]->QuantityDec - $quantity) < 0) ? 0 : $purchase->Line[$i]->QuantityDec - $quantity;

					if($purchase->Line[$i]->Product->ID > 0) {
						if($quantityArrived > 0) {
							$warehouseStock = new WarehouseStock();
							$warehouseStock->Product->ID = $purchase->Line[$i]->Product->ID;
							$warehouseStock->Manufacturer->ID = $purchase->Line[$i]->Manufacturer->ID;
							$warehouseStock->Warehouse->ID = $form->GetValue('warehouse');
							$warehouseStock->QuantityInStock = $quantityArrived;
							$warehouseStock->Cost = $purchase->Line[$i]->Cost;
							
							$data = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>''", mysql_real_escape_string($warehouseStock->Warehouse->ID), mysql_real_escape_string($warehouseStock->Product->ID)));
							if($data->TotalRows) {
								$warehouseStock->Location = $data->Row['Shelf_Location'];
							}
							$data->Disconnect();

							$warehouseStock->Add();

							if($purchase->Type == 'Stock') {
								$data = new DataQuery(sprintf('SELECT Warehouse_ID FROM warehouse WHERE Type_Reference_ID=%d AND Type=\'S\'', mysql_real_escape_string($purchase->SupplierID)));
								if($data->TotalRows > 0) {
									WarehouseReserve::deductReserves($data->Row['Warehouse_ID'], $purchase->Line[$i]->Product->ID, $quantityArrived);
								}
								$data->Disconnect();
							}
						}
					}

					if($purchase->Line[$i]->QuantityDec > 0) {
						$fulfilled = false;
					}

					$purchase->Line[$i]->Update();

					if($quantity > 0) {
						$batchLine = new PurchaseBatchLine();
						$batchLine->PurchaseBatchID = $batch->ID;
						$batchLine->PurchaseLine->ID = $purchase->Line[$i]->ID;
						$batchLine->Quantity = $quantity;
						$batchLine->Status = 'Unchecked';
						$batchLine->Add();
					}
				}

				if($fulfilled){
					$purchase->Status = "Fulfilled";
					$purchase->Update();
				} else {
					for($i=0; $i < count($purchase->Line); $i++){
		            	if($purchase->Line[$i]->QuantityDec != $purchase->Line[$i]->Quantity) {
							$partial = true;
						}
					}

					if($partial) {
						$purchase->Status = "Partially Fulfilled";
						$purchase->Update();
					}
				}

				$direct .= sprintf('&batchid=%d', $batch->ID);
			} else {
				$fulfill = true;

				for($i=0; $i < count($purchase->Line); $i++){
					if($purchase->Line[$i]->QuantityDec > 0) {
						$fulfill = false;
						break;
					}
				}

				if($fulfill) {
					$purchase->Status = "Fulfilled";
					$purchase->Update();
				}
			}

			redirect(sprintf("Location: %s?pid=%d&status=%s%s", $_SERVER['PHP_SELF'],$_REQUEST['pid'], $form->GetValue('status'), $direct));
		}
	}
}

$script = '';

if(isset($_REQUEST['batchid'])) {
	$script .= sprintf('<script language="javascript" type="text/javascript">
		window.onload = function() {
			popUrl(\'purchase_print_batch.php?batchid=%d\', 800, 600);
		}
		</script>', $_REQUEST['batchid']);
}

$page = new Page("Edit Purchase Order [#".$_REQUEST['pid']."]",'Here you can fulfill a purchase order');
$page->AddToHead($script);
$page->Display('header');
?>

			<table width="100%"  border="0" cellspacing="0" cellpadding="0">
              <tr>
			    <td>
                  <table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
                  <tr>
                    <td valign="top" class="billing"><p><strong>Billing Address:</strong><br />
                    <?php echo $purchase->GetSupplierAddress(); ?>
                    <td valign="top" class="shipping"><p><strong>Shipping Address:</strong><br />
                    <?php echo $purchase->GetBranchShip(); ?>
                  </tr>
                </table>
              </tr>
              <tr>
                <td colspan="2"><br>  <br />
                <?php
                if(!$form->Valid) {
						echo $form->GetError();
						echo "<br>";
					}

                	echo $notes->Open();
					echo $notes->GetHTML('confirm');
					echo $notes->GetHTML('action');
					echo $notes->GetHTML('pid');
					?>
                <table cellspacing="0" class="orderDetails">
					  <tr>
					    <th>Item</th>
					    <th>Value</th>
					  </tr>
					  <tr>
					  	<td><?php echo $notes->GetLabel('date'); ?></td>
					 	<td><?php echo $notes->GetHTML('date'); ?></td>
					  </tr>
					  <tr>
					  	<td><?php echo $notes->GetLabel('customreference'); ?></td>
					 	<td><?php echo $notes->GetHTML('customreference'); ?></td>
					  </tr>
					  <tr>
					  	<td><?php echo $notes->GetLabel('notes'); ?></td>
					 	<td><?php echo $notes->GetHTML('notes'); ?></td>
					  </tr>
					 </table>
					<br />

					<input type="button" class="btn" value="back" onclick="window.self.location.href='purchases_view.php?status=<?php print $_REQUEST['status']; ?>'" />&nbsp;
 					<input type="submit" class="btn" value="update" name="updatenotes" />
<?php
echo $notes->Close();
?>
<br /> <br />            <br>
                  <table cellspacing="0" class="orderDetails">
                  <tr>
				  	<th></th>
                    <th>Qty Incoming</th>
                    <th>Product</th>
                    <th>Quickfind</th>
                    <th>Cost</th>
                    <th>Initial Quantity</th>
                    <th>Qty Arrived</th>

                    <?php
                    if($purchase->SupplierID > 0) {
                    	echo '<th>Qty Damaged</th>';
                    }
                    ?>

                    <th>Advice Note</th>
                    <th>Manufacturer</th>
                    <th>Shelf Location</th>
                    <th>Qty Free</th>
                    <th>Reserves</th>
                  </tr>

                 <?php
                 	echo $form->Open();
					echo $form->GetHTML('confirm');
					echo $form->GetHTML('status');
					echo $form->GetHTML('pid');

                  for($i=0; $i < count($purchase->Line); $i++){
                  	if($purchase->Line[$i]->Product->ID > 0) {
                  		$purchase->Line[$i]->Product->Get();
                  	}
			?>
                  <tr <?php echo (isset($_REQUEST['line']) && ($_REQUEST['line'] == $purchase->Line[$i]->ID)) ? 'style="background-color: #9c9;"' : ''; ?>>
                  	<td><?php
                  	if($purchase->Line[$i]->Product->ID > 0) {
                  		echo (!empty($purchase->Line[$i]->Product->DefaultImage->Thumb->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'] . $purchase->Line[$i]->Product->DefaultImage->Thumb->FileName)) ? sprintf('<img src="%s%s" />', $GLOBALS['PRODUCT_IMAGES_DIR_WS'], $purchase->Line[$i]->Product->DefaultImage->Thumb->FileName) : '';
                  	}
                  	?></td>
                    <td><?php echo $purchase->Line[$i]->QuantityDec; ?></td>
                    <td>
                    	<?php
                    	if($purchase->Line[$i]->Product->ID > 0) {
                    		echo $purchase->Line[$i]->Product->Name;
                    		?><br /><small>Part Number: <?php echo $purchase->Line[$i]->Product->SKU;?></small>
                    		<?php
                    	} else {
							echo ($isEditable) ? $form->GetHTML('name_' . $purchase->Line[$i]->ID) : $purchase->Line[$i]->Product->Name;
						}
						?>
                    </td>
					<td>
						<?php
						if($purchase->Line[$i]->Product->ID > 0) {
							?>
							<a href="product_profile.php?pid=<?php echo $purchase->Line[$i]->Product->ID; ?>"><?php echo $purchase->Line[$i]->Product->ID;?></a>
							<?php
						}
						?>
					</td>
					<td>
						<?php
						if($purchase->Line[$i]->Product->ID > 0) {
							?>
							&pound;<?php echo number_format($purchase->Line[$i]->Cost, 2, '.', ','); 
						} else {
							echo ($isEditable) ? $form->GetHTML('cost_' . $purchase->Line[$i]->ID) : $purchase->Line[$i]->Product->Name;
						}
						?>
					</td>
					<td><?php echo $purchase->Line[$i]->Quantity; ?></td>
					<td><?php echo ($purchase->Line[$i]->QuantityDec > 0) ? $form->GetHTML('qty_'.$i) : 'Fulfilled'; ?></td>

					<?php
					if($purchase->SupplierID > 0) {
						if($purchase->Line[$i]->Product->ID > 0) {
							echo sprintf('<td>%s</td>', ($purchase->Line[$i]->QuantityDec > 0) ? $form->GetHTML('qty_damaged_'.$i) : '-');
						} else {
							echo '<td></td>';
						}
					}
					?>

					<td><?php echo $form->GetHTML('advice_'.$i); ?></td>
					<td><?php
					if($purchase->Line[$i]->Product->ID > 0) {
						echo $form->GetHTML('manufacturer_'.$i);
					}
					?></td>
					<td>
						<?php
						if($purchase->Line[$i]->Product->ID > 0) {
							$data = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>''", mysql_real_escape_string($purchase->Warehouse->ID), mysql_real_escape_string($purchase->Line[$i]->Product->ID)));
							if($data->TotalRows) {
								echo $data->Row['Shelf_Location'];
							}
							$data->Disconnect();
						}
						?>
					</td>
					<td><?php echo $form->GetHTML('qty_free_'.$i); ?></td>
					<td>
						<?php
						if($purchase->Line[$i]->Product->ID > 0) {
							$data = new DataQuery(sprintf("SELECT SUM(quantity) AS quantity FROM warehouse_reserve WHERE productId=%d", mysql_real_escape_string($purchase->Line[$i]->Product->ID)));
							echo $data->Row['quantity'];
							$data->Disconnect();
						}
						?>
					</td>
                  </tr>

                  <?php
                  }
			?>
                  </table>
                  <br><br>

                  <input type="button" class="btn" value="back" onclick="window.self.location.href='purchases_view.php?status=<?php print $_REQUEST['status']; ?>'" />&nbsp;
                  <?php if(strtolower($purchase->Status) != 'fulfilled'){ ?>

                  <?php echo $form->GetHTML('warehouse'); ?>
				<input type="submit" name="action" value="update" class="btn" />
				<input type="submit" name="action" value="apply free" class="btn" />

				<?php
				if($isEditable) {
					?>
					<input type="button" name="addcustom" value="add custom" class="btn" onclick="window.location.href='?pid=<?php echo $purchase->ID; ?>&action=addcustom';" />
				<?php } ?>
			<?php } ?>

            </table>
			<?php
			echo $form->Close();
			$page->Display('footer');
			require_once('lib/common/app_footer.php');