<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProductPrice.php');

$session->Secure(3);

$priceEnquiry = new PriceEnquiry($_REQUEST['id']);
$priceEnquiry->GetLines();

$matrix = array();
$suppliers = array();
$fixed = array(1, 10, 50, 100);
$favourites = array();

$data = new DataQuery(sprintf("SELECT Supplier_ID FROM supplier WHERE Is_Favourite='Y'"));
while($data->Row) {
	$favourites[] = $data->Row['Supplier_ID'];
	
	$data->Next();	
}
$data->Disconnect();
	
foreach($priceEnquiry->Line as $line) {
	$priceData = array();
	$supplierData = array();
	
	$data = new DataQuery(sprintf("SELECT Supplier_ID, Quantity, Cost FROM supplier_product_price WHERE Product_ID=%d ORDER BY Quantity ASC, Supplier_Product_Price_ID ASC", mysql_real_escape_string($line->Product->ID)));
	while($data->Row) {
		if(!isset($priceData[$data->Row['Quantity']])) {
			$priceData[$data->Row['Quantity']] = array();
		}

		$priceData[$data->Row['Quantity']][$data->Row['Supplier_ID']] = $data->Row['Cost'];
		
		$data->Next();
	}
	$data->Disconnect();
	
	foreach($priceData as $quantity=>$item) {
		foreach($item as $supplierId=>$cost) {
			if($cost == 0) {
				unset($priceData[$quantity][$supplierId]);
			} else {
				if(!isset($suppliers[$supplierId])) {
					$suppliers[$supplierId] = new Supplier($supplierId);
					$suppliers[$supplierId]->Contact->Get();

					if($suppliers[$supplierId]->Contact->Parent->ID > 0) {
						$suppliers[$supplierId]->Contact->Parent->Get();
					}
				}
				
				$supplierData[$supplierId] = $suppliers[$supplierId];
			}
		}
	}
	
	foreach($favourites as $supplierId) {
		if(!isset($supplierData[$supplierId])) {
			if(!isset($suppliers[$supplierId])) {
				$suppliers[$supplierId] = new Supplier($supplierId);
				$suppliers[$supplierId]->Contact->Get();

				if($suppliers[$supplierId]->Contact->Parent->ID > 0) {
					$suppliers[$supplierId]->Contact->Parent->Get();
				}
			}
			
			$supplierData[$supplierId] = $suppliers[$supplierId];
		}
	}
	
	foreach($fixed as $quantity) {
		if(!isset($priceData[$quantity])) {
			$priceData[$quantity] = array();
		}
		
		foreach($supplierData as $supplierId=>$supplier) {
			if(!isset($priceData[$quantity][$supplierId])) {
				$priceData[$quantity][$supplierId] = 0;
			}
		}
	}
	
	ksort($priceData);
	
	$matrix[$line->Product->ID] = array('prices' => $priceData, 'suppliers' => $supplierData);
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Price Enquiry ID', 'hidden', '', 'numeric_unsigned', 1, 11);

foreach($priceEnquiry->Line as $line) {
	foreach($matrix[$line->Product->ID]['suppliers'] as $supplierId=>$supplier) {
		foreach($matrix[$line->Product->ID]['prices'] as $quantity=>$item) {
			if(isset($item[$supplierId])) {
				$form->AddField(sprintf('cost_%d_%d_%d', $line->Product->ID, $supplierId, $quantity), 'Cost', 'text', ($item[$supplierId] > 0) ? $item[$supplierId] : '', 'float', 1, 11, false, 'size="1"');
			}
		}
	}
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		foreach($priceEnquiry->Line as $line) {
			foreach($matrix[$line->Product->ID]['suppliers'] as $supplierId=>$supplier) {
				foreach($matrix[$line->Product->ID]['prices'] as $quantity=>$item) {
					if(isset($item[$supplierId])) {
						$value = $form->GetValue(sprintf('cost_%d_%d_%d', $line->Product->ID, $supplierId, $quantity));
						
						if((($item[$supplierId] > 0) && (bccomp($value, $item[$supplierId], 2) != 0)) || (($item[$supplierId] == 0) && !empty($value))) {
							if($quantity > 1) {
								$price = new SupplierProductPrice();
								$price->Product->ID = $line->Product->ID;
								$price->Supplier->ID = $supplierId;
								$price->Quantity = $quantity;
								$price->Cost = $value;
								$price->Add();
							} else {
								$product = new SupplierProduct();
								$product->Product->ID = $line->Product->ID;
								$product->Supplier->ID = $supplierId;
								$product->Cost = $value;
								$product->Add();
							}
						}
					}
				}
			}
		}

		redirect(sprintf("Location: ?id=%d", $form->GetValue('id')));
	}
}

$page = new Page(sprintf('[#%d] Price Enquiry Matrix', $priceEnquiry->ID), 'Matrix of supplier quantity cost prices.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');

$columns = 2;
$index = 0;
?>

<table width="100%">
	<tr>
	
		<?php
		foreach($priceEnquiry->Line as $line) {
			if($index == 0) {
				echo sprintf('<td valign="top" width="%s%%" style="padding-right: 10px;">', round(100 / $columns));
			}
			?>

			<div style="background-color: #eee; padding: 10px 0 10px 0;">
				<p><span class="pageSubTitle"><?php echo $line->Product->Name; ?> (#<a href="product_profile.php?pid=<?php echo $line->Product->ID; ?>"><?php echo $line->Product->ID; ?></a>)</span><br /><span class="pageDescription">Listing quantity prices for suppliers.</span></p>

				<table width="100%" border="0" cellspacing="0" cellpadding="0" class="orderdetails">
					<thead>
						<tr>
							<th>Current Prices</th>

							<?php
							foreach($matrix[$line->Product->ID]['prices'] as $quantity=>$item) {
								echo sprintf('<th style="text-align: right;" width="100">%sx</th>', $quantity);
							}
							?>
						</tr>
					</thead>
					<tbody>

						<?php
						if(!empty($matrix[$line->Product->ID]['suppliers'])) {
							foreach($matrix[$line->Product->ID]['suppliers'] as $supplierId=>$supplier) {
								$supplierName = trim(sprintf('%s &lt;%s&gt;', $supplier->Contact->Parent->Organisation->Name, trim(sprintf('%s %s', $supplier->Contact->Person->Name, $supplier->Contact->Person->LastName))));
								?>

								<tr>
									<td><?php echo $supplierName; ?></td>

									<?php
									foreach($matrix[$line->Product->ID]['prices'] as $quantity=>$item) {
										echo sprintf('<td align="right">%s</td>', isset($item[$supplierId]) ? $form->GetHTML(sprintf('cost_%d_%d_%d', $line->Product->ID, $supplierId, $quantity)) : '-');
									}
									?>

								</tr>

								<?php
							}
						} else {
							?>
							
							<tr>
								<td align="center" colspan="<?php echo 1 + count($matrix[$line->Product->ID]['prices']); ?>">There are no items available for viewing.</td>
							</tr>
									
							<?php
						}
						?>

					</tbody>
				</table>

			</div>
			<br />

			<?php
			$index++;
			
			if($index >= ceil(count($priceEnquiry->Line) / $columns)) {
				$index = 0;
				
				echo '</td>';
			}
		}
		?>
		
	</tr>
</table>

<input type="submit" class="btn" name="update" value="update" />

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');