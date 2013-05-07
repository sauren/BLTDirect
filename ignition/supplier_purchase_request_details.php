<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseRequest.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseRequestLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProductPrice.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProductPriceCollection.php');

$session->Secure(3);

$purchaseRequest = new PurchaseRequest();

if(!isset($_REQUEST['id']) || !$purchaseRequest->Get($_REQUEST['id'])) {
	redirectTo('supplier_purchase_requests_pending.php');
}

if($purchaseRequest->Supplier->ID != $session->Supplier->ID) {
	redirectTo('supplier_purchase_requests_pending.php');
}

$purchaseRequest->Supplier->Get();
$purchaseRequest->Supplier->Contact->Get();
$purchaseRequest->GetLines();

$isEditable = (strtolower($purchaseRequest->Status) == 'pending');

if($action == "confirm") {
	$purchaseRequest->Status = 'Confirmed';
	$purchaseRequest->Update();

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $purchaseRequest->ID));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Purchase Request ID', 'hidden', '', 'numeric_unsigned', 1, 11);

$prices = new SupplierProductPriceCollection();
$pricesStore = array();

for($k=0; $k<count($purchaseRequest->Line); $k++) {
	$purchaseRequest->Line[$k]->Product->Get();

	$prices->Reset();
	$prices->GetPrices($purchaseRequest->Line[$k]->Product->ID, $purchaseRequest->Supplier->ID);

	$pricesStore[$k] = $prices->GetPrice($purchaseRequest->Line[$k]->Quantity);

	if($isEditable) {
		$form->AddField(sprintf('cost_%d', $purchaseRequest->Line[$k]->ID), sprintf('Cost for \'%s\'', $purchaseRequest->Line[$k]->Product->Name), 'text', $pricesStore[$k], 'float', 1, 11, true, 'size="5"');
		$form->AddField(sprintf('stocked_%d', $purchaseRequest->Line[$k]->ID), sprintf('Is Stocked for \'%s\'', $purchaseRequest->Line[$k]->Product->Name), 'checkbox', $purchaseRequest->Line[$k]->IsStocked, 'boolean', 1, 1, false, sprintf('onclick="toggleFields(this, %d);"', $purchaseRequest->Line[$k]->ID));
		$form->AddField(sprintf('arrival_%d', $purchaseRequest->Line[$k]->ID), sprintf('Stock Arrival (Days) for \'%s\'', $purchaseRequest->Line[$k]->Product->Name), 'text', $purchaseRequest->Line[$k]->StockArrivalDays, 'float', 1, 11, true, 'size="5"' . (($purchaseRequest->Line[$k]->IsStocked == 'Y') ? ' disabled="disabled"': ''));
		$form->AddField(sprintf('available_%d', $purchaseRequest->Line[$k]->ID), sprintf('Stock Available for \'%s\'', $purchaseRequest->Line[$k]->Product->Name), 'text', $purchaseRequest->Line[$k]->StockAvailable, 'float', 1, 11, true, 'size="5"' . (($purchaseRequest->Line[$k]->IsStocked == 'Y') ? ' disabled="disabled"': ''));
	}
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['updateproducts'])) {
		if($form->Validate()) {
			for($k=0; $k<count($purchaseRequest->Line); $k++) {
				$purchaseRequest->Line[$k]->IsStocked = $form->GetValue(sprintf('stocked_%d', $purchaseRequest->Line[$k]->ID));
				$purchaseRequest->Line[$k]->StockArrivalDays = ($purchaseRequest->Line[$k]->IsStocked == 'N') ? $form->GetValue(sprintf('arrival_%d', $purchaseRequest->Line[$k]->ID)) : 0;
				$purchaseRequest->Line[$k]->StockAvailable = ($purchaseRequest->Line[$k]->IsStocked == 'N') ? $form->GetValue(sprintf('available_%d', $purchaseRequest->Line[$k]->ID)) : 0;
				$purchaseRequest->Line[$k]->Update();

				if($pricesStore[$k] != $form->GetValue(sprintf('cost_%d', $purchaseRequest->Line[$k]->ID))) {
                    $price = new SupplierProductPrice();
					$price->Product->ID = $purchaseRequest->Line[$k]->Product->ID;
					$price->Supplier->ID = $purchaseRequest->Supplier->ID;
					$price->Quantity = $purchaseRequest->Line[$k]->Quantity;
					$price->Cost = $form->GetValue(sprintf('cost_%d', $purchaseRequest->Line[$k]->ID));
					$price->Add();
				}
			}

			redirect(sprintf("Location: ?id=%d", $purchaseRequest->ID));
		}
	}
}

$script = sprintf('<script language="javascript" type="text/javascript">
    var toggleFields = function(obj, id) {
    	var arrival = document.getElementById(\'arrival_\'+id);
    	var available = document.getElementById(\'available_\'+id);

    	if(arrival && available) {
			if(obj.checked) {
				arrival.setAttribute(\'disabled\', \'disabled\');
				available.setAttribute(\'disabled\', \'disabled\');
			} else {
				arrival.removeAttribute(\'disabled\');
				available.removeAttribute(\'disabled\');
			}
		}
    }
    </script>');

$page = new Page(sprintf('[#%d] Purchase Request Details', $purchaseRequest->ID), 'Manage this purchase request here.');
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
					<th>Purchase Request:</th>
					<td>#<?php echo $purchaseRequest->ID; ?></td>
				</tr>
				<tr>
					<th>Status:</th>
					<td><?php echo $purchaseRequest->Status; ?></td>
				</tr>
				<tr>
					<th>Supplier:</th>
					<td><?php echo sprintf('%s %s%s', $purchaseRequest->Supplier->Contact->Person->Name, $purchaseRequest->Supplier->Contact->Person->LastName, ($purchaseRequest->Supplier->Contact->Parent->ID > 0) ? sprintf(' (%s)', $purchaseRequest->Supplier->Contact->Parent->Organisation->Name) : ''); ?></td>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<th>Created On:</th>
					<td><?php echo cDatetime($purchaseRequest->CreatedOn, 'shortdate'); ?></td>
				</tr>
			</table><br />

		</td>
	</tr>
	<tr>
		<td colspan="2">
			<br />

			<?php
			if($isEditable) {
				?>

				<div style="background-color: #eee; padding: 10px 0 10px 0;">
					<p><span class="pageSubTitle">Finished?</span><br /><span class="pageDescription">Confirm these stock settings by clicking the button below.</span></p>

					<input name="confirm" type="button" value="confirm" class="btn" onclick="confirmRequest('?action=confirm&id=<?php echo $purchaseRequest->ID; ?>', 'Are you sure you wish to confirm these stock settings?');" />
				</div>
				<br />

				<?php
			}
			?>

			<div style="background-color: #eee; padding: 10px 0 10px 0;">
				<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing stock requested for a purchase order.</span></p>

				<table cellspacing="0" class="orderDetails">
					<tr>
						<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Name</th>
						<th nowrap="nowrap" style="padding-right: 5px;">SKU</th>
						<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost</th>
						<th nowrap="nowrap" style="padding-right: 5px; text-align: center;">Is Stocked</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Stock Arrival (Days)</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Stock Available</th>
					</tr>

					<?php
					if(count($purchaseRequest->Line) > 0) {
						for($k=0; $k<count($purchaseRequest->Line); $k++) {
							$data = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", $purchaseRequest->Supplier->ID, $purchaseRequest->Line[$k]->Product->ID));
							$sku = ($data->TotalRows > 0) ? $data->Row['Supplier_SKU'] : '';
							$data->Disconnect();
							?>

							<tr>
								<td nowrap="nowrap"><?php echo $purchaseRequest->Line[$k]->Quantity; ?></td>
								<td nowrap="nowrap"><?php echo $purchaseRequest->Line[$k]->Product->ID; ?></td>
								<td nowrap="nowrap"><?php echo $purchaseRequest->Line[$k]->Product->Name; ?></td>
								<td nowrap="nowrap"><?php echo $sku; ?></td>
								<td nowrap="nowrap" align="right">&pound;<?php echo ($isEditable) ? $form->GetHTML(sprintf('cost_%d', $purchaseRequest->Line[$k]->ID)) : number_format(round($pricesStore[$k], 2), 2, '.', ','); ?></td>
								<td nowrap="nowrap" align="center"><?php echo ($isEditable) ? $form->GetHTML(sprintf('stocked_%d', $purchaseRequest->Line[$k]->ID)) : $purchaseRequest->Line[$k]->IsStocked; ?></td>
								<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('arrival_%d', $purchaseRequest->Line[$k]->ID)) : $purchaseRequest->Line[$k]->StockArrivalDays; ?></td>
								<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('available_%d', $purchaseRequest->Line[$k]->ID)) : $purchaseRequest->Line[$k]->StockAvailable; ?></td>
							</tr>

							<?php
						}
					} else {
						?>

						<tr>
							<td colspan="8" align="center">No products available for viewing.</td>
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