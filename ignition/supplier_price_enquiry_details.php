<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquirySupplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquirySupplierLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProductPrice.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');

$session->Secure(3);

$supplierId = $_REQUEST['supplier'];

$priceEnquiry = new PriceEnquiry($_REQUEST['id']);
$priceEnquiry->GetLines();
$priceEnquiry->GetSuppliers();
$priceEnquiry->GetQuantities();

$isEditable = (strtolower($priceEnquiry->Status) != 'complete') ? true : false;

$supplierPriceEnquiry = new PriceEnquirySupplier();

if(!$supplierPriceEnquiry->GetByEnquiryAndSupplierID($priceEnquiry->ID, $supplierId)) {
	redirect(sprintf("Location: price_enquiries_pending.php"));
}

$supplierPriceEnquiry->Supplier->Get();
$supplierPriceEnquiry->Supplier->Contact->Get();
$supplierPriceEnquiry->GetLines();
$supplierPriceEnquiry->GetCosts();

if($action == "complete") {
	$supplierPriceEnquiry->IsComplete = 'Y';
	$supplierPriceEnquiry->Update();

	redirect(sprintf("Location: %s?id=%d&supplier=%d", $_SERVER['PHP_SELF'], $priceEnquiry->ID, $supplierPriceEnquiry->Supplier->ID));
}

$quantities = array(1);

for($i=0; $i<count($priceEnquiry->Quantity); $i++) {
	$quantities[] = $priceEnquiry->Quantity[$i]->Quantity;
}

$lineCache = array();

for($i=0; $i<count($supplierPriceEnquiry->Line); $i++) {
	$lineData = array();
	$lineData['IsInStock'] = $supplierPriceEnquiry->Line[$i]->IsInStock;
	$lineData['StockBackorderDays'] = $supplierPriceEnquiry->Line[$i]->StockBackorderDays;

	$lineCache[$supplierPriceEnquiry->Line[$i]->PriceEnquiryLineID] = $lineData;
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Price Enquiry ID', 'hidden', $priceEnquiry->ID, 'numeric_unsigned', 1, 11);
$form->AddField('supplier', 'Supplier ID', 'hidden', '0', 'numeric_unsigned', 1, 11);

$supplierProducts = array();

if($isEditable) {
	for($k=0; $k<count($priceEnquiry->Line); $k++) {
		foreach($quantities as $quantity) {
			$cost = '';

			for($i=0; $i<count($supplierPriceEnquiry->Cost[$k]['Items']); $i++) {
				$item = $supplierPriceEnquiry->Cost[$k]['Items'][$i];

				if($item['Quantity'] == $quantity) {
					if($item['Cost'] > 0) {
						$cost = $item['Cost'];
					}

					break;
				}
			}

			$form->AddField(sprintf('cost_%d_%d', $priceEnquiry->Line[$k]->ID, $quantity), sprintf('Cost for \'%dx\' of \'%s\'', $quantity, $priceEnquiry->Line[$k]->Product->Name), 'text', $cost, 'float', 1, 11, false, 'size="5"');
		}

		$supplierProducts[$priceEnquiry->Line[$k]->ID] = array('SupplierProductID' => 0, 'SupplierProductNumber' => 0, 'SupplierSKU' => '');

	    $data = new DataQuery(sprintf("SELECT Supplier_Product_ID, Supplier_Product_Number, Supplier_SKU FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($supplierPriceEnquiry->Supplier->ID), mysql_real_escape_string($priceEnquiry->Line[$k]->Product->ID)));
		if($data->TotalRows > 0) {
			$supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierProductID'] = $data->Row['Supplier_Product_ID'];
			$supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierProductNumber'] = $data->Row['Supplier_Product_Number'];
			$supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierSKU'] = $data->Row['Supplier_SKU'];
		}
		$data->Disconnect();

		$form->AddField(sprintf('isstocked_%d', $priceEnquiry->Line[$k]->ID), sprintf('Is In Stock for \'%s\'', $priceEnquiry->Line[$k]->Product->Name), 'radio', isset($lineCache[$priceEnquiry->Line[$k]->ID]['IsInStock']) ? $lineCache[$priceEnquiry->Line[$k]->ID]['IsInStock'] : 'U', 'alpha', 1, 1);
		$form->AddOption(sprintf('isstocked_%d', $priceEnquiry->Line[$k]->ID), 'N', 'N');
		$form->AddOption(sprintf('isstocked_%d', $priceEnquiry->Line[$k]->ID), 'Y', 'Y');
		$form->AddField(sprintf('stockbackorder_%d', $priceEnquiry->Line[$k]->ID), sprintf('Backorder Stock (Days) for \'%s\'', $priceEnquiry->Line[$k]->Product->Name), 'text', isset($lineCache[$priceEnquiry->Line[$k]->ID]['StockBackorderDays']) ? $lineCache[$priceEnquiry->Line[$k]->ID]['StockBackorderDays'] : 0, 'numeric_unsigned', 1, 11, true, 'size="3"');
		$form->AddField(sprintf('productnumber_%d', $priceEnquiry->Line[$k]->ID), sprintf('Supplier Product Number for \'%s\'', $priceEnquiry->Line[$k]->Product->Name), ($supplierPriceEnquiry->Supplier->ShowProduct == 'N') ? 'hidden' : 'text', $supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierProductNumber'], 'numeric_unsigned', 1, 11, true, 'size="3"');
		$form->AddField(sprintf('sku_%d', $priceEnquiry->Line[$k]->ID), sprintf('Supplier SKU for \'%s\'', $priceEnquiry->Line[$k]->Product->Name), 'text', $supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierSKU'], 'anything', 1, 30, false, 'size="10"');
	}
}

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
	if(isset($_REQUEST['updatecosts'])) {
		if($form->Validate()) {
			for($k=0; $k<count($priceEnquiry->Line); $k++) {
				foreach($quantities as $quantity) {
					$cost = '';
					$quantityId = 0;

					for($i=0; $i<count($supplierPriceEnquiry->Cost[$k]['Items']); $i++) {
						$item = $supplierPriceEnquiry->Cost[$k]['Items'][$i];

						if($item['Quantity'] == $quantity) {
							if($item['Cost'] > 0) {
								$cost = $item['Cost'];
							}

							$quantityId = $item['Supplier_Product_Price_ID'];

							break;
						}
					}

					$newCost = $form->GetValue(sprintf('cost_%d_%d', $priceEnquiry->Line[$k]->ID, $quantity));

					if($cost != $newCost) {
						if($quantity > 1) {
							if($quantityId > 0) {
								$price = new SupplierProductPrice($quantityId);

								if($newCost <> $price->Cost) {
									$price->Cost = $newCost;
									$price->Add();
								}
							} else {
								$price = new SupplierProductPrice();
								$price->Supplier->ID = $supplierPriceEnquiry->Supplier->ID;
								$price->Product->ID = $priceEnquiry->Line[$k]->Product->ID;
								$price->Quantity = $quantity;
								$price->Cost = $newCost;
								$price->Add();
							}
						} else {
							$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($supplierPriceEnquiry->Supplier->ID), mysql_real_escape_string($priceEnquiry->Line[$k]->Product->ID)));
							if($data->TotalRows > 0) {
								$product = new SupplierProduct($data->Row['Supplier_Product_ID']);
								$product->Cost = $newCost;
								$product->Update();
							} else {
								if($newCost > 0) {
									$product = new SupplierProduct();
									$product->Supplier->ID = $supplierPriceEnquiry->Supplier->ID;
									$product->Product->ID = $priceEnquiry->Line[$k]->Product->ID;
									$product->Cost = $newCost;
									$product->Add();
								}
							}
							$data->Disconnect();
						}
					}
				}

				$found = false;

                $data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($supplierPriceEnquiry->Supplier->ID), mysql_real_escape_string($priceEnquiry->Line[$k]->Product->ID)));
				if($data->TotalRows > 0) {
					$product = new SupplierProduct($data->Row['Supplier_Product_ID']);
					$product->SupplierProductNumber = $form->GetValue(sprintf('productnumber_%d', $priceEnquiry->Line[$k]->ID));
					$product->SKU = $form->GetValue(sprintf('sku_%d', $priceEnquiry->Line[$k]->ID));
					$product->Update();
				} else {
					$product = new SupplierProduct();
					$product->Supplier->ID = $supplierPriceEnquiry->Supplier->ID;
					$product->Product->ID = $priceEnquiry->Line[$k]->Product->ID;
                    $product->SupplierProductNumber = $form->GetValue(sprintf('productnumber_%d', $priceEnquiry->Line[$k]->ID));
					$product->SKU = $form->GetValue(sprintf('sku_%d', $priceEnquiry->Line[$k]->ID));
					$product->Add();
				}
				$data->Disconnect();

				for($i=0; $i<count($supplierPriceEnquiry->Line); $i++) {
					if($priceEnquiry->Line[$k]->ID == $supplierPriceEnquiry->Line[$i]->PriceEnquiryLineID) {
						$line = new PriceEnquirySupplierLine($supplierPriceEnquiry->Line[$i]->ID);
						$line->IsInStock = $form->GetValue(sprintf('isstocked_%d', $priceEnquiry->Line[$k]->ID));
						$line->StockBackorderDays = $form->GetValue(sprintf('stockbackorder_%d', $priceEnquiry->Line[$k]->ID));
						$line->Update();

						$found = true;
						break;
					}
				}

				if(!$found) {
					$line = new PriceEnquirySupplierLine();
					$line->PriceEnquirySupplierID = $supplierPriceEnquiry->ID;
					$line->PriceEnquiryLineID = $priceEnquiry->Line[$k]->ID;
					$line->IsInStock = $form->GetValue(sprintf('isstocked_%d', $priceEnquiry->Line[$k]->ID));
					$line->StockBackorderDays = $form->GetValue(sprintf('stockbackorder_%d', $priceEnquiry->Line[$k]->ID));
					$line->Add();
				}
			}

			redirect(sprintf("Location: %s?id=%d&supplier=%d", $_SERVER['PHP_SELF'], $priceEnquiry->ID, $supplierPriceEnquiry->Supplier->ID));
		}
	}
}

$page = new Page(sprintf('<a href="price_enquiry_details.php?id=%d">[#%d] Price Enquiry Details</a> &gt; Supplier Price Enquiry Details', $priceEnquiry->ID, $priceEnquiry->ID), 'Manage the suppliers price enquiry details here.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');
echo $form->GetHTML('supplier');

for($k=0; $k<count($priceEnquiry->Line); $k++) {
	if($supplierPriceEnquiry->Supplier->ShowProduct == 'N') {
		echo $form->GetHTML(sprintf('productnumber_%d', $priceEnquiry->Line[$k]->ID));
	}
}
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td align="left" valign="top"></td>
		<td align="right" valign="top">

			<table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
				<tr>
					<th>Price Enquiry:</th>
					<td>#<?php echo $priceEnquiry->ID; ?></td>
				</tr>
				<tr>
					<th>Status:</th>
					<td><?php echo $priceEnquiry->Status; ?></td>
				</tr>
				<tr>
					<th>Supplier:</th>
					<td><?php echo sprintf('%s %s%s', $supplierPriceEnquiry->Supplier->Contact->Person->Name, $supplierPriceEnquiry->Supplier->Contact->Person->LastName, ($supplierPriceEnquiry->Supplier->Contact->Parent->ID > 0) ? sprintf(' (%s)', $supplierPriceEnquiry->Supplier->Contact->Parent->Organisation->Name) : ''); ?></td>
				</tr>
				<tr>
					<th>Position:</th>
					<td><?php echo ($supplierPriceEnquiry->Position > 0) ? sprintf('#%d', $supplierPriceEnquiry->Position) : '<em>Undisclosed</em>'; ?></td>
				</tr>
				<tr>
					<th>Prices Complete:</th>
					<td><?php echo ($supplierPriceEnquiry->IsComplete == 'N') ? 'No' : 'Yes'; ?></td>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<th>Created On:</th>
					<td><?php echo cDatetime($priceEnquiry->CreatedOn, 'shortdate'); ?></td>
				</tr>
			</table>
			<br />

		</td>
	</tr>
	<tr>
		<td colspan="2">
			<br />

			<?php
			if($isEditable) {
				if($supplierPriceEnquiry->IsComplete == 'N') {
					?>

					<div style="background-color: #eee; padding: 10px 0 10px 0;">
						<p><span class="pageSubTitle">Finished?</span><br /><span class="pageDescription">Mark pricing for your price enquiry complete by clicking the below button.</span></p>

						<input name="complete" type="button" value="complete" class="btn" onclick="confirmRequest('supplier_price_enquiry_details.php?id=<?php echo $priceEnquiry->ID; ?>&supplier=<?php echo $supplierPriceEnquiry->Supplier->ID; ?>&action=complete', 'Please confirm you wish to mark these prices as complete?');" />
					</div>
					<br />

					<?php
				}
			}
			?>

			<div style="background-color: #eee; padding: 10px 0 10px 0;">
				<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing product discounts and special nets for the quantities requested.</span></p>

				<?php
				$columns = 6;
				?>

				<table cellspacing="0" class="orderDetails">
					<tr>
						<th nowrap="nowrap" style="padding-right: 5px;">Quantity<br />&nbsp;</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Quickfind<br />&nbsp;</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Name<br />&nbsp;</th>

						<?php
						foreach($quantities as $quantity) {
							$columns++;

							echo sprintf('<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">%dx<br />&nbsp;</th>', $quantity);
						}
						?>

						<th nowrap="nowrap" style="padding-right: 5px; padding-left: 5px; text-align: center;">Is In Stock<br />Y / N</th>
						<th nowrap="nowrap" style="padding-right:5px;">Backorder Stock<br />(Days)</th>

						<?php
						if($supplierPriceEnquiry->Supplier->ShowProduct == 'Y') {
							$columns++;

							echo '<th nowrap="nowrap" style="padding-right:5px;">Supplier<br />Product Number</th>';
						}
						?>

						<th nowrap="nowrap" style="padding-right:5px;">Supplier<br />SKU</th>
					</tr>

					<?php
					if(count($priceEnquiry->Line) > 0) {
						for($k=0; $k<count($priceEnquiry->Line); $k++) {
							switch(strtoupper(isset($lineCache[$priceEnquiry->Line[$k]->ID]['IsInStock']) ? $lineCache[$priceEnquiry->Line[$k]->ID]['IsInStock'] : 'U')) {
								case 'Y':
									$isInStock = 'Yes';
									break;
								case 'N':
									$isInStock = 'No';
									break;
								default:
									$isInStock = 'Unknown';
									break;
							}
							?>

							<tr>
								<td nowrap="nowrap"><?php echo $priceEnquiry->Line[$k]->Quantity; ?></td>
								<td nowrap="nowrap"><?php echo $priceEnquiry->Line[$k]->Product->ID; ?></td>
								<td nowrap="nowrap"><?php echo $priceEnquiry->Line[$k]->Product->Name; ?></td>

								<?php
								foreach($quantities as $quantity) {
									$cost = '';

									for($i=0; $i<count($supplierPriceEnquiry->Cost[$k]['Items']); $i++) {
										$item = $supplierPriceEnquiry->Cost[$k]['Items'][$i];

										if($item['Quantity'] == $quantity) {
											if($item['Cost'] > 0) {
												$cost = $item['Cost'];
											}

											break;
										}
									}

									echo sprintf('<td nowrap="nowrap" align="right">%s</td>', ($isEditable) ? sprintf('&pound;%s', $form->GetHTML(sprintf('cost_%d_%d', $priceEnquiry->Line[$k]->ID, $quantity))) : (!empty($cost) ? sprintf('&pound;%s', $cost) : '-'));
								}
								?>

								<td nowrap="nowrap" align="center"><?php echo ($isEditable) ? sprintf('%s %s', $form->GetHTML(sprintf('isstocked_%d', $priceEnquiry->Line[$k]->ID), 2), $form->GetHTML(sprintf('isstocked_%d', $priceEnquiry->Line[$k]->ID), 1)) : $isInStock; ?></td>
								<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('stockbackorder_%d', $priceEnquiry->Line[$k]->ID)) : (isset($lineCache[$priceEnquiry->Line[$k]->ID]['StockBackorderDays']) ? $lineCache[$priceEnquiry->Line[$k]->ID]['StockBackorderDays'] : 0); ?></td>

								<?php
								if($supplierPriceEnquiry->Supplier->ShowProduct == 'Y') {
									?>

									<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('productnumber_%d', $priceEnquiry->Line[$k]->ID)) : (($supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierProductNumber'] > 0) ? $supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierProductNumber'] : '-'); ?></td>

									<?php
								}
								?>

								<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('sku_%d', $priceEnquiry->Line[$k]->ID)) : $supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierSKU']; ?>&nbsp;</td>
							</tr>

							<?php
						}
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
								<input type="submit" name="updatecosts" value="update" class="btn" />
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