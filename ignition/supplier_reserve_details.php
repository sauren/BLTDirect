<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Reserve.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReserveItem.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseReserve.php');

$session->Secure(3);

$reserve = new Reserve();

if(!isset($_REQUEST['id']) || !$reserve->Get($_REQUEST['id'])) {
	redirectTo('supplier_reserves_pending.php');
}

if($reserve->supplier->ID != $session->Supplier->ID) {
	redirectTo('supplier_reserves_pending.php');
}

$reserve->supplier->Get();
$reserve->supplier->Contact->Get();
$reserve->getLines();

$isEditable = (strtolower($reserve->status) != 'completed');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Reserve ID', 'hidden', $reserve->id, 'numeric_unsigned', 1, 11);

if($isEditable) {
	for($i=0; $i<count($reserve->line); $i++) {
		$data = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", $reserve->supplier->ID, $reserve->line[$i]->product->ID));
		$supplierSku = $data->Row['Supplier_SKU'];
		$data->Disconnect();

		$form->AddField('product_quantity_'.$reserve->line[$i]->id, sprintf('Quantity for \'%s\'', $reserve->line[$i]->product->Name), 'text', 0, 'numeric_unsigned', 1, 11, false, 'size="5"');
		$form->AddField('product_sku_'.$reserve->line[$i]->id, sprintf('SKU for \'%s\'', $reserve->line[$i]->product->Name), 'text', $supplierSku, 'paragraph', 1, 30, false);
	}
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		for($i=0; $i<count($reserve->line); $i++) {
			if($form->GetValue('product_quantity_'.$reserve->line[$i]->id) > $reserve->line[$i]->quantityRemaining) {
				$form->AddError(sprintf('Quantity Reserving for \'%s\' cannot exceed the quantity remaining.', $reserve->line[$i]->product->Name), 'product_quantity_'.$reserve->line[$i]->id);
			}
		}

		if($form->Valid) {
			for($i=0; $i<count($reserve->line); $i++) {
				$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", $reserve->supplier->ID, $reserve->line[$i]->product->ID));
				if($data->TotalRows > 0) {
					$supplierProduct = new SupplierProduct($data->Row['Supplier_Product_ID']);
					$supplierProduct->SKU = $form->GetValue('product_sku_'.$reserve->line[$i]->id);
					$supplierProduct->Update();
				} else {
					$supplierProduct = new SupplierProduct();
					$supplierProduct->Supplier->ID = $reserve->supplier->ID;
					$supplierProduct->Product->ID = $reserve->line[$i]->product->ID;
					$supplierProduct->SKU = $form->GetValue('product_sku_'.$reserve->line[$i]->id);
					$supplierProduct->Add();
				}
				$data->Disconnect();
			}

			$warehouse = new Warehouse();

			if($warehouse->GetByType($reserve->supplier->ID, 'S')) {
				for($i=0; $i<count($reserve->line); $i++) {
					if($form->GetValue('product_quantity_'.$reserve->line[$i]->id) > 0) {
						$warehouseReserve = new WarehouseReserve();
						$warehouseReserve->warehouse->ID = $warehouse->ID;
						$warehouseReserve->product->ID = $reserve->line[$i]->product->ID;
						$warehouseReserve->quantity = $form->GetValue('product_quantity_'.$reserve->line[$i]->id);
						$warehouseReserve->add();

						$reserve->line[$i]->quantityRemaining -= $form->GetValue('product_quantity_'.$reserve->line[$i]->id);
						$reserve->line[$i]->update();
					}
				}
			}

			$complete = true;

			for($i=0; $i<count($reserve->line); $i++) {
				if($reserve->line[$i]->quantityRemaining > 0) {
					$complete = false;
				}
			}

			if($complete) {
				$reserve->complete();
			}

			redirect(sprintf('Location: ?id=%d', $reserve->id));
		}
	}
}

$page = new Page(sprintf('[#%d] Reserve Details', $debit->ID), 'Manage this reserve here.');
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
					<th>Reserve:</th>
					<td>#<?php echo $reserve->id; ?></td>
				</tr>
				<tr>
					<th>Status:</th>
					<td><?php echo $reserve->status; ?></td>
				</tr>
				<tr>
					<th>Supplier:</th>
					<td><?php echo sprintf('%s %s%s', $reserve->supplier->Contact->Person->Name, $reserve->supplier->Contact->Person->LastName, ($reserve->supplier->Contact->Parent->ID > 0) ? sprintf(' (%s)', $reserve->supplier->Contact->Parent->Organisation->Name) : ''); ?></td>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<th>Created On:</th>
					<td><?php echo cDatetime($reserve->createdOn, 'shortdate'); ?></td>
				</tr>
			</table>
			<br />

		</td>
	</tr>
	<tr>
		<td valign="top">

			<?php
			if($isEditable) {
				if($reserve->status != 'Completed') {
					echo sprintf('<input name="complete" type="submit" value="Complete" class="submit" /> ', $reserve->id);
				}
			}
			?>

			<input name="print" type="button" value="print" class="btn" onclick="popUrl('supplier_reserve_print.php?id=<?php echo $reserve->id; ?>', 800, 600);" />
			<br />

		</td>
		<td align="right" valign="top"></td>
	</tr>
	<tr>
		<td colspan="2">
			<br />

			<div style="background-color: #eee; padding: 10px 0 10px 0;">
				<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing products requesting reserving.</span></p>

				<table cellspacing="0" class="orderDetails">
					<tr>
						<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Name</th>
						<th nowrap="nowrap" style="padding-right: 5px;">SKU</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Expires</th>
						<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost</th>

						<?php
						if($isEditable) {
							echo '<th nowrap="nowrap" style="padding-right: 5px;">Quantity Remaining</th>';
							echo '<th nowrap="nowrap" style="padding-right: 5px;">Quantity Reserving</th>';
						}
						?>

					</tr>

					<?php
					if(count($reserve->line) > 0) {
						for($i=0; $i<count($reserve->line); $i++) {
							?>

							<tr>
								<td nowrap="nowrap"><?php echo number_format(round($reserve->line[$i]->quantity, 2), 2, '.', ''); ?></td>
								<td nowrap="nowrap"><?php echo $reserve->line[$i]->product->ID; ?></td>
								<td nowrap="nowrap"><?php echo $reserve->line[$i]->product->Name; ?></td>
								<td nowrap="nowrap">

									<?php
									if($isEditable) {
										echo $form->GetHTML('product_sku_'.$reserve->line[$i]->id);
									} else {
										$data = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", $reserve->supplier->ID, $reserve->line[$i]->product->ID));
										echo $data->Row['Supplier_SKU'];
										$data->Disconnect();
									}
									?>

								</td>
								<td nowrap="nowrap">

									<?php
									$data = new DataQuery(sprintf("SELECT DropSupplierExpiresOn FROM product WHERE Product_ID=%d AND DropSupplierID=%d", $reserve->line[$i]->product->ID, $reserve->supplier->ID));
									echo ($data->TotalRows > 0) ? cDatetime($data->Row['DropSupplierExpiresOn'], 'shortdate') : '';
									$data->Disconnect();
									?>

								</td>
								<td nowrap="nowrap" align="right">

									<?php
									$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Supplier_ID=%d", $reserve->line[$i]->product->ID, $reserve->supplier->ID));
									echo ($data->TotalRows > 0) ? sprintf('&pound;%s', number_format(round($data->Row['Cost'], 2), 2, '.', ',')) : '';
									$data->Disconnect();
									?>

								</td>

								<?php
								if($isEditable) {
									echo sprintf('<td nowrap="nowrap">%s</td>', number_format(round($reserve->line[$i]->quantityRemaining, 2), 2, '.', ''));
									echo sprintf('<td nowrap="nowrap">%s</td>', $form->GetHTML('product_quantity_'.$reserve->line[$i]->id));
								}
								?>

							</tr>

							<?php
						}
					} else {
						?>

						<tr>
							<td colspan="<?php echo ($isEditable) ? 8 : 6; ?>" align="center">No products available for viewing.</td>
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