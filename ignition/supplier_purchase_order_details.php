<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseDespatch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseDespatchLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

$session->Secure(3);

$session->Supplier->Contact->Get();

$purchase = new Purchase();

if(!isset($_REQUEST['id']) || !$purchase->Get($_REQUEST['id'])) {
	redirectTo('supplier_purchase_orders_unfulfilled.php');
}

if($purchase->SupplierID != $session->Supplier->ID) {
	redirectTo('supplier_purchase_orders_unfulfilled.php');
}

$purchase->GetLines();
$purchase->GetDespatches();

$isEditable = !(strtolower($purchase->Status) == 'fulfilled');

if($action == 'complete') {
	$purchase->IsSupplierComplete = 'Y';
	$purchase->Update();
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Purchase Order ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('notes', 'Notes', 'textarea', $purchase->SupplierNotes, 'anything', 1, 1024, false, 'rows="3" style="width: 99%; font-family: arial, sans-serif;"');

if($isEditable) {
	for($i=0; $i<count($purchase->Line); $i++) {
		$form->AddField(sprintf('quantity_despatch_%d', $purchase->Line[$i]->ID), sprintf('Despatch Quantity for \'%s\'', $purchase->Line[$i]->Product->Name), 'text', 0, 'numeric_unsigned', 1, 11, true, 'size="5"');
	}
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['adddespatch'])) {
		for($i=0; $i<count($purchase->Line); $i++) {
			$form->Validate(sprintf('quantity_despatch_%d', $purchase->Line[$i]->ID));
		}

		if($form->Valid) {
            for($j=0; $j<count($purchase->Despatch); $j++) {
				$purchase->Despatch[$j]->GetLines();
			}

			for($k=0; $k<count($purchase->Line); $k++) {
                $quantityDespatched = 0;

                for($j=0; $j<count($purchase->Despatch); $j++) {
					for($i=0; $i<count($purchase->Despatch[$j]->Line); $i++) {
						if($purchase->Despatch[$j]->Line[$i]->PurchaseLine->ID == $purchase->Line[$k]->ID) {
							$quantityDespatched += $purchase->Despatch[$j]->Line[$i]->Quantity;
						}
					}
				}

				if(($form->GetValue(sprintf('quantity_despatch_%d', $purchase->Line[$k]->ID)) < 0) || ($form->GetValue(sprintf('quantity_despatch_%d', $purchase->Line[$k]->ID)) > ($purchase->Line[$k]->Quantity - $quantityDespatched))) {
					$form->AddError(sprintf('Despatch Quantity for \'%s\' must be between 0 and %d.', $purchase->Line[$k]->Product->Name, ($purchase->Line[$k]->Quantity - $quantityDespatched)), sprintf('quantity_despatch_%d', $purchase->Line[$k]->ID));
				}
			}
		}

		if($form->Valid) {
			$totalQuantity = 0;

			for($i=0; $i<count($purchase->Line); $i++) {
				$totalQuantity += $form->GetValue(sprintf('quantity_despatch_%d', $purchase->Line[$i]->ID));
			}

			if($totalQuantity > 0) {
				$despatch = new PurchaseDespatch();
				$despatch->Purchase->ID = $purchase->ID;
				$despatch->Add();

				for($i=0; $i<count($purchase->Line); $i++) {
					$quantity = $form->GetValue(sprintf('quantity_despatch_%d', $purchase->Line[$i]->ID));

					if($quantity > 0) {
						$despatchLine = new PurchaseDespatchLine();
						$despatchLine->PurchaseDespatchID = $despatch->ID;
						$despatchLine->PurchaseLine->ID = $purchase->Line[$i]->ID;
						$despatchLine->Quantity = $quantity;
						$despatchLine->Add();
					}
				}

				$purchase->GetDespatches();

				for($j=0; $j<count($purchase->Despatch); $j++) {
					$purchase->Despatch[$j]->GetLines();
				}

				$isComplete = true;

				for($k=0; $k<count($purchase->Line); $k++) {
                	$quantityDespatched = 0;

                    for($j=0; $j<count($purchase->Despatch); $j++) {
						for($i=0; $i<count($purchase->Despatch[$j]->Line); $i++) {
							if($purchase->Despatch[$j]->Line[$i]->PurchaseLine->ID == $purchase->Line[$k]->ID) {
								$quantityDespatched += $purchase->Despatch[$j]->Line[$i]->Quantity;
							}
						}
					}

                    if($quantityDespatched < $purchase->Line[$k]->Quantity) {
						$isComplete = false;
					}
				}

				if($isComplete) {
					$purchase->IsSupplierComplete = 'Y';
					$purchase->Update();
				}
			}
		}
	} else {
		if(isset($_REQUEST['updatenotes'])) {
			$purchase->SupplierNotes = $form->GetValue('notes');
			$purchase->Update();
		}
	}

	if($form->Valid) {
		redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $purchase->ID));
	}
}

for($k=0; $k<count($purchase->Despatch); $k++) {
	$purchase->Despatch[$k]->GetLines();

	for($i=0; $i<count($purchase->Despatch[$k]->Line); $i++) {
		$purchase->Despatch[$k]->Line[$i]->PurchaseLine->Get();
	}
}

$script = sprintf('<script language="javascript" type="text/javascript">
	var toggleDespatch = function(despatchId) {
		var e = null;

		e = document.getElementById(\'despatch_\' + despatchId);
		if(e) {
			if(e.style.display == \'none\') {
				e.style.display = \'\';

				e = document.getElementById(\'despatch_toggle_\' + despatchId);
				if(e) {
					e.src = \'images/aztector_3.gif\';
					e.alt = \'Collapse\';
				}
			} else {
				e.style.display = \'none\';

				e = document.getElementById(\'despatch_toggle_\' + despatchId);
				if(e) {
					e.src = \'images/aztector_4.gif\';
					e.alt = \'Expand\';
				}
			}
		}
	}
	</script>');

$page = new Page(sprintf('[#%d] Purchase Order Details', $purchase->ID), 'Manage this pourchase order here.');
$page->AddToHead($script);
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td align="left" valign="top"></td>
		<td align="right" valign="top">

			<table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
				<tr>
					<th>Purchase Order:</th>
					<td>#<?php echo $purchase->ID; ?></td>
				</tr>
				<tr>
					<th>Status:</th>
					<td><?php echo $purchase->Status; ?></td>
				</tr>
				<tr>
					<th>Supplier:</th>
					<td><?php echo sprintf('%s %s%s', $session->Supplier->Contact->Person->Name, $session->Supplier->Contact->Person->LastName, ($session->Supplier->Contact->Parent->ID > 0) ? sprintf(' (%s)', $session->Supplier->Contact->Parent->Organisation->Name) : ''); ?></td>
				</tr>
				<tr>
					<th>Complete:</th>
					<td><?php echo ($purchase->IsSupplierComplete == 'Y') ? 'Yes' : 'No'; ?></td>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<th>Created On:</th>
					<td><?php echo cDatetime($purchase->CreatedOn, 'shortdate'); ?></td>
				</tr>
			</table>
			<br />

		</td>
	</tr>
	<tr>
		<td align="left">
			<input type="button" name="print" value="print" class="btn" onclick="popUrl('supplier_purchase_order_print.php?id=<?php echo $purchase->ID; ?>', 800, 600);" />

			<?php
			if($purchase->IsSupplierComplete == 'N') {
				echo sprintf('<input type="button" name="complete" value="complete" class="btn" onclick="window.self.location.href = \'?action=complete&id=%d\';" />', $purchase->ID);
			}
			?>

		</td>
		<td align="right"></td>
	</tr>
	<tr>
		<td colspan="2">
			<br />

			<div style="background-color: #eee; padding: 10px 0 10px 0;">
				<p><span class="pageSubTitle">Notes</span><br /><span class="pageDescription">Notes registered against this purchase.</span></p>

				<?php
				echo sprintf('<fieldset style="border: none; padding: 0;">%s</fieldset>', $form->GetHTML('notes'));
				echo '<br />';
				?>

				<table cellspacing="0" cellpadding="0" border="0" width="100%">
					<tr>
						<td align="left">
							<input type="submit" name="updatenotes" value="update" class="btn" />
						</td>
						<td align="right"></td>
					</tr>
				</table>

			</div>
			<br />

			<div style="background-color: #eee; padding: 10px 0 10px 0;">
				<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing stock requested for a purchase order.</span></p>

				<table cellspacing="0" class="orderDetails">
					<tr>
						<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Name</th>
						<th nowrap="nowrap" style="padding-right: 5px;">SKU</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Despatched</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Outstanding</th>

						<?php
						if($isEditable) {
							echo '<th nowrap="nowrap" style="padding-right: 5px;">Despatch Quantity</th>';
						}
						?>

						<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost</th>
						<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Total</th>
					</tr>

					<?php
					if(count($purchase->Line) > 0) {
						$totalCost = 0;

						for($k=0; $k<count($purchase->Line); $k++) {
							$quantityDespatched = 0;

							for($j=0; $j<count($purchase->Despatch); $j++) {
								for($i=0; $i<count($purchase->Despatch[$j]->Line); $i++) {
									if($purchase->Despatch[$j]->Line[$i]->PurchaseLine->ID == $purchase->Line[$k]->ID) {
										$quantityDespatched += $purchase->Despatch[$j]->Line[$i]->Quantity;
									}
								}
							}
							?>

							<tr>
								<td nowrap="nowrap"><?php echo $purchase->Line[$k]->Quantity; ?></td>
								<td nowrap="nowrap"><?php echo $purchase->Line[$k]->Product->ID; ?></td>
								<td nowrap="nowrap"><?php echo $purchase->Line[$k]->Product->Name; ?></td>
								<td nowrap="nowrap"><?php echo $purchase->Line[$k]->SKU; ?></td>
								<td nowrap="nowrap"><?php echo $quantityDespatched; ?></td>
								<td nowrap="nowrap"><?php echo $purchase->Line[$k]->Quantity - $quantityDespatched; ?></td>

								<?php
								if($isEditable) {
									echo sprintf('<td nowrap="nowrap">%s</td>', $form->GetHTML(sprintf('quantity_despatch_%d', $purchase->Line[$k]->ID)));
								}
								?>

								<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($purchase->Line[$k]->Cost, 2), 2, '.', ','); ?></td>
								<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($purchase->Line[$k]->Cost * $purchase->Line[$k]->Quantity, 2), 2, '.', ','); ?></td>
							</tr>

							<?php
							$totalCost += $purchase->Line[$k]->Cost * $purchase->Line[$k]->Quantity;
						}
						?>

						<tr>
							<td nowrap="nowrap" colspan="<?php echo ($isEditable) ? 8 : 7; ?>"></td>
							<td nowrap="nowrap" align="right"><strong>&pound;<?php echo number_format(round($totalCost, 2), 2, '.', ','); ?></strong></td>
						</tr>

						<?php
					} else {
						?>

						<tr>
							<td colspan="<?php echo ($isEditable) ? 9 : 8; ?>" align="center">No products available for viewing.</td>
						</tr>

						<?php
					}
					?>

				</table>
				<br />

				<?php
				if($isEditable) {
					?>

					<table cellspacing="0" cellpadding="0" border="0" width="100%">
						<tr>
							<td align="left"></td>
							<td align="right">
								<input type="submit" name="adddespatch" value="add despatch" class="btn" />
							</td>
						</tr>
					</table>

					<?php
				}
				?>

			</div>
			<br />

			<div style="background-color: #eee; padding: 10px 0 10px 0;">
				<p><span class="pageSubTitle">Despatches</span><br /><span class="pageDescription">Listing despatches made for this purchase order.</span></p>

				<table cellspacing="0" class="orderDetails">
					<tr>
						<th nowrap="nowrap" style="padding-right: 5px;">&nbsp;</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Date</th>
						<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Total</th>
						<th nowrap="nowrap" width="1%">&nbsp;</th>
					</tr>

					<?php
					if(count($purchase->Despatch) > 0) {
						$totalCost = 0;

						for($k=0; $k<count($purchase->Despatch); $k++) {
							$cost = 0;

							for($i=0; $i<count($purchase->Despatch[$k]->Line); $i++) {
								$cost += $purchase->Despatch[$k]->Line[$i]->PurchaseLine->Cost * $purchase->Despatch[$k]->Line[$i]->PurchaseLine->Quantity;
							}
							?>

							<tr>
								<td nowrap="nowrap" width="1%"><a href="javascript:toggleDespatch('<?php echo $purchase->Despatch[$k]->ID; ?>');"><img id="despatch_toggle_<?php echo $purchase->Despatch[$k]->ID; ?>" align="absmiddle" src="images/aztector_4.gif" alt="Expand" border="0" /></a></td>
								<td nowrap="nowrap"><?php echo $purchase->Despatch[$k]->CreatedOn; ?></td>
								<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($cost, 2), 2, '.', ','); ?></td>
								<td nowrap="nowrap" align="center"><a href="javascript:popUrl('supplier_purchase_order_print_despatch.php?id=<?php echo $purchase->Despatch[$k]->ID; ?>', 800, 600);"><img src="images/icon_print_1.gif" alt="Print" /></a></td>
							</tr>
							<tr id="despatch_<?php echo $purchase->Despatch[$k]->ID; ?>" style="display: none; background-color: #fff;">
								<td>&nbsp;</td>
								<td colspan="2">

									<table cellspacing="0" class="orderDetails">
										<tr>
											<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
											<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
											<th nowrap="nowrap" style="padding-right: 5px;">Name</th>
											<th nowrap="nowrap" style="padding-right: 5px;">SKU</th>
											<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost</th>
											<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Total</th>
										</tr>

										<?php
										for($i=0; $i<count($purchase->Despatch[$k]->Line); $i++) {
											?>

											<tr>
												<td nowrap="nowrap"><?php echo $purchase->Despatch[$k]->Line[$i]->Quantity; ?></td>
												<td nowrap="nowrap"><?php echo $purchase->Despatch[$k]->Line[$i]->PurchaseLine->Product->ID; ?></td>
												<td nowrap="nowrap"><?php echo $purchase->Despatch[$k]->Line[$i]->PurchaseLine->Product->Name; ?></td>
												<td nowrap="nowrap"><?php echo $purchase->Despatch[$k]->Line[$i]->PurchaseLine->SKU; ?></td>
												<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($purchase->Despatch[$k]->Line[$i]->PurchaseLine->Cost, 2), 2, '.', ','); ?></td>
												<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($purchase->Despatch[$k]->Line[$i]->PurchaseLine->Cost * $purchase->Despatch[$k]->Line[$i]->Quantity, 2), 2, '.', ','); ?></td>
											</tr>

											<?php
										}
										?>

									</table>
									<br />

								</td>
								<td>&nbsp;</td>
							</tr>

							<?php
							$totalCost += $cost;
						}
						?>

						<tr>
							<td nowrap="nowrap" colspan="2"></td>
							<td nowrap="nowrap" align="right"><strong>&pound;<?php echo number_format(round($totalCost, 2), 2, '.', ','); ?></strong></td>
							<td nowrap="nowrap"></td>
						</tr>

						<?php
					} else {
						?>

						<tr>
							<td colspan="4" align="center">No despatches available for viewing.</td>
						</tr>

						<?php
					}
					?>

				</table>
				<br />

			</div>

		</td>
	</tr>
</table>

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');