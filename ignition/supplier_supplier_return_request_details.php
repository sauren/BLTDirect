<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierReturnRequest.php');

$session->Secure(3);

$returnRequest = new SupplierReturnRequest();

if(!isset($_REQUEST['id']) || !$returnRequest->Get($_REQUEST['id'])) {
	redirectTo('supplier_supplier_return_requests_pending.php');
}

if($returnRequest->Supplier->ID != $session->Supplier->ID) {
	redirectTo('supplier_supplier_return_requests_pending.php');
}

$returnRequest->Supplier->Get();
$returnRequest->Supplier->Contact->Get();
$returnRequest->GetLines();
$returnRequest->Courier->Get();

if($returnRequest->Order->ID > 0) {
	$returnRequest->Order->GetLines();

} elseif($returnRequest->Purchase->ID > 0) {
	for($k=0; $k<count($returnRequest->Line); $k++) {
		$returnRequest->Line[$k]->PurchaseLine->Get();
	}
}

$isEditable = (strtolower($returnRequest->Status) == 'pending') ? true : false;

if($action == 'confirm') {
	if(!empty($returnRequest->AuthorisationNumber)) {
		$returnRequest->Status = 'Confirmed';
		$returnRequest->Update();
	}

	redirect(sprintf("Location: ?id=%d&complete=%s", $returnRequest->ID, !empty($returnRequest->AuthorisationNumber) ? 'true' : 'false'));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Supplier Return Request ID', 'hidden', $returnRequest->ID, 'numeric_unsigned', 1, 11);
$form->AddField('authorisation', 'Authorisation Number', 'text', $returnRequest->AuthorisationNumber, 'anything', 0, 60, false);

for($k=0; $k<count($returnRequest->Line); $k++) {
	$returnRequest->Line[$k]->Type->Get();
	$returnRequest->Line[$k]->Product->Get();

	if($returnRequest->Line[$k]->RelatedProduct->ID > 0) {
		$returnRequest->Line[$k]->RelatedProduct->Get();
	}

	if($isEditable) {
		$form->AddField(sprintf('handling_method_%d', $returnRequest->Line[$k]->ID), sprintf('Handling Method for \'%s\'', $returnRequest->Line[$k]->Product->Name), 'select', $returnRequest->Line[$k]->HandlingMethod, 'alpha', 1, 1, true);
		$form->AddOption(sprintf('handling_method_%d', $returnRequest->Line[$k]->ID), 'R', 'Percentage');
		$form->AddOption(sprintf('handling_method_%d', $returnRequest->Line[$k]->ID), 'F', 'Fixed');
		$form->AddField(sprintf('handling_charge_%d', $returnRequest->Line[$k]->ID), sprintf('Handling Charge for \'%s\'', $returnRequest->Line[$k]->Product->Name), 'text', $returnRequest->Line[$k]->HandlingCharge, 'float', 1, 11, true, 'size="5"');
		$form->AddField(sprintf('rejected_%d', $returnRequest->Line[$k]->ID), sprintf('Is Rejected for \'%s\'', $returnRequest->Line[$k]->Product->Name), 'checkbox', $returnRequest->Line[$k]->IsRejected, 'boolean', 1, 1, false);
		$form->AddField(sprintf('reason_%d', $returnRequest->Line[$k]->ID), sprintf('Rejected Reason for \'%s\'', $returnRequest->Line[$k]->Product->Name), 'textarea', $returnRequest->Line[$k]->RejectedReason, 'anything', 0, 240, false, 'rows="2" style="font-family: arial, sans-serif; width: 100%;"');
	}
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['update']) || isset($_REQUEST['updateproducts'])) {
		for($k=0; $k<count($returnRequest->Line); $k++) {
			$form->Validate(sprintf('handling_method_%d', $returnRequest->Line[$k]->ID));
			$form->Validate(sprintf('handling_charge_%d', $returnRequest->Line[$k]->ID));
			$form->Validate(sprintf('rejected_%d', $returnRequest->Line[$k]->ID));
			$form->Validate(sprintf('reason_%d', $returnRequest->Line[$k]->ID));
		}

		if($form->Valid) {
			for($k=0; $k<count($returnRequest->Line); $k++) {
				$returnRequest->Line[$k]->HandlingMethod = $form->GetValue(sprintf('handling_method_%d', $returnRequest->Line[$k]->ID));
				$returnRequest->Line[$k]->HandlingCharge = $form->GetValue(sprintf('handling_charge_%d', $returnRequest->Line[$k]->ID));
				$returnRequest->Line[$k]->IsRejected = $form->GetValue(sprintf('rejected_%d', $returnRequest->Line[$k]->ID));
				$returnRequest->Line[$k]->RejectedReason = $form->GetValue(sprintf('reason_%d', $returnRequest->Line[$k]->ID));
				$returnRequest->Line[$k]->Update();
			}

			$returnRequest->LinesFetched = false;
		}
	}

	if(isset($_REQUEST['update'])) {
		$form->Validate('authorisation');

		if($form->Valid) {
			$returnRequest->AuthorisationNumber = $form->GetValue('authorisation');
		}
	}

	if($form->Valid) {
		$returnRequest->Recalculate();

		redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $returnRequest->ID));
	}
}

$page = new Page(sprintf('[#%d] Supplier Return Request Details', $returnRequest->ID), 'Manage this supplier return request here.');
$page->Display('header');

if(isset($_REQUEST['complete']) && ($_REQUEST['complete'] == 'false')) {
	$bubble = new Bubble('Could Not Complete', 'You must specify an authorisation number before confirming this return request.');

	echo $bubble->GetHTML();
	echo '<br />';
}

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
					<th>Return Request:</th>
					<td>#<?php echo $returnRequest->ID; ?></td>
				</tr>
				<tr>
					<th>Status:</th>
					<td><?php echo $returnRequest->Status; ?></td>
				</tr>
				<tr>
					<th>Supplier:</th>
					<td><?php echo sprintf('%s %s%s', $returnRequest->Supplier->Contact->Person->Name, $returnRequest->Supplier->Contact->Person->LastName, ($returnRequest->Supplier->Contact->Parent->ID > 0) ? sprintf(' (%s)', $returnRequest->Supplier->Contact->Parent->Organisation->Name) : ''); ?></td>
				</tr>

				<?php
				if($returnRequest->Order->ID > 0) {
					?>

					<tr>
						<th>Order:</th>
						<td><?php echo $returnRequest->Order->ID; ?></td>
					</tr>

					<?php
				}

				if($returnRequest->Purchase->ID > 0) {
					?>

					<tr>
						<th>Purchase:</th>
						<td><?php echo $returnRequest->Purchase->ID; ?></td>
					</tr>

					<?php
				}
				?>

				<tr>
					<th>Courier:</th>
					<td><?php echo $returnRequest->Courier->Name; ?></td>
				</tr>
				<tr>
					<th>Authorisation Number:</th>
					<td><?php echo ($isEditable) ? $form->GetHTML('authorisation') : $returnRequest->AuthorisationNumber; ?></td>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<th>Created On:</th>
					<td><?php echo cDatetime($returnRequest->CreatedOn, 'shortdate'); ?></td>
				</tr>
			</table>
			<br />

		</td>
	</tr>
	<tr>
		<td valign="top"></td>
		<td align="right" valign="top">

			<?php
			if($isEditable) {
				?>

				<input name="update" type="submit" value="update" class="btn" />

				<?php
			}
			?>

		</td>
	</tr>
	<tr>
    <td colspan="2">
		<br />

		<?php
		if($isEditable) {
			?>

			<div style="background-color: #eee; padding: 10px 0 10px 0;">
				<p><span class="pageSubTitle">Finished?</span><br /><span class="pageDescription">Confirm this product return request. You must provide an authorsation number before finishing.</span></p>

				<input name="confirm" type="button" value="confirm" class="btn" onclick="confirmRequest('?action=confirm&id=<?php echo $returnRequest->ID; ?>', 'Are you sure you wish to confirm this return request?');" />
			</div>
			<br />

			<?php
		}
		?>

		<div style="background-color: #eee; padding: 10px 0 10px 0;">
			<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing products requesting for return.</span></p>

			<?php
			$columns = 11;
			?>

			<table cellspacing="0" class="orderDetails">
				<tr>
					<th nowrap="nowrap" style="padding-right: 5px;">Quantity<br />&nbsp;</th>
					<th nowrap="nowrap" style="padding-right: 5px;">Quickfind<br />&nbsp;</th>
					<th nowrap="nowrap" style="padding-right: 5px;">Name<br />&nbsp;</th>
					<th nowrap="nowrap" style="padding-right: 5px;">Type<br />&nbsp;</th>
					<th nowrap="nowrap" style="padding-right: 5px;">Related<br />Product</th>
					<th nowrap="nowrap" style="padding-right: 5px;">Reason<br />&nbsp;</th>

					<?php
					if($returnRequest->Purchase->ID > 0) {
						echo '<th nowrap="nowrap" style="padding-right: 5px;">Advice<br />Note</th>';
						$columns++;
					}
					?>

					<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost<br />&nbsp;</th>

					<?php
					if($isEditable) {
						echo '<th nowrap="nowrap" style="padding-right: 5px;">Handling<br />Method</th>';
						$columns++;
					}
					?>

					<th nowrap="nowrap" style="padding-right: 5px;">Handling<br />Charge</th>
					<th nowrap="nowrap" style="padding-right: 5px; text-align: center;">Is Rejected<br />&nbsp;</th>
					<th nowrap="nowrap" style="padding-right: 5px;">Rejected<br />Reason</th>
					<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Total<br />&nbsp;</th>
				</tr>

				<?php
				if(count($returnRequest->Line) > 0) {
					for($k=0; $k<count($returnRequest->Line); $k++) {
						$cost = 0;

						if($returnRequest->Line[$k]->IsRejected == 'N') {
							$cost += $returnRequest->Line[$k]->Cost * $returnRequest->Line[$k]->Quantity;

							switch($returnRequest->Line[$k]->HandlingMethod) {
								case 'R':
									$cost -= ($cost / 100) * $returnRequest->Line[$k]->HandlingCharge;
									break;

								case 'F':
									$cost -= $returnRequest->Line[$k]->HandlingCharge;
									break;
							}
						}

						$handlingCharge = ($isEditable) ? $form->GetHTML(sprintf('handling_charge_%d', $returnRequest->Line[$k]->ID)) : number_format($returnRequest->Line[$k]->HandlingCharge, 2, '.', '');

						switch($returnRequest->Line[$k]->HandlingMethod) {
							case 'R':
								$handlingText = sprintf('%s%%', $handlingCharge);
								break;

							case 'F':
								$handlingText = sprintf('&pound;%s', $handlingCharge);
								break;

							default:
								$handlingText = '';
								break;
						}
						?>

						<tr>
							<td nowrap="nowrap"><?php echo number_format($returnRequest->Line[$k]->Quantity, 2, '.', ''); ?></td>
							<td nowrap="nowrap"><?php echo $returnRequest->Line[$k]->Product->ID; ?></td>
							<td><?php echo $returnRequest->Line[$k]->Product->Name; ?></td>
							<td nowrap="nowrap"><?php echo $returnRequest->Line[$k]->Type->Name; ?></td>
							<td><?php echo ($returnRequest->Line[$k]->RelatedProduct->ID > 0) ? sprintf('%d: %s', $returnRequest->Line[$k]->RelatedProduct->ID, $returnRequest->Line[$k]->RelatedProduct->Name) : 'None'; ?></td>
							<td nowrap="nowrap"><?php echo $returnRequest->Line[$k]->Reason; ?></td>

							<?php
							if($returnRequest->Purchase->ID > 0) {
								echo sprintf('<td nowrap="nowrap">%s</td>', $returnRequest->Line[$k]->PurchaseLine->AdviceNote);
							}
							?>

							<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($returnRequest->Line[$k]->Cost, 2), 2, '.', ','); ?></td>

							<?php
							if($isEditable) {
								echo sprintf('<td nowrap="nowrap">%s</td>', $form->GetHTML(sprintf('handling_method_%d', $returnRequest->Line[$k]->ID)));
							}
							?>

							<td nowrap="nowrap"><?php echo $handlingText; ?></td>
							<td nowrap="nowrap" align="center"><?php echo ($isEditable) ? $form->GetHTML(sprintf('rejected_%d', $returnRequest->Line[$k]->ID)) : $returnRequest->Line[$k]->IsRejected; ?></td>
							<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('reason_%d', $returnRequest->Line[$k]->ID)) : $returnRequest->Line[$k]->RejectedReason; ?></td>
							<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($cost, 2), 2, '.', ','); ?></td>
						</tr>

						<?php
					}
					?>

					<tr>
						<td colspan="<?php echo $columns - 1; ?>">&nbsp;</td>
						<td nowrap="nowrap" align="right"><strong>&pound;<?php echo number_format(round($returnRequest->Total, 2), 2, '.', ','); ?></strong></td>
					</tr>

					<?php
				} else {
					?>

					<tr>
						<td colspan="<?php echo $columns; ?>" align="center">No products available for viewing.</td>
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
							<td align="left">
								<input type="submit" name="updateproducts" value="update" class="btn" />
							</td>
							<td align="right"></td>
						</tr>
					</table>

				<?php
			}
			?>

		</div>

		</td>
	</tr>
</table>

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');