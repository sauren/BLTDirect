<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');

$session->Secure(3);

$priceEnquiry = new PriceEnquiry($_REQUEST['id']);
$priceEnquiry->GetLines();
$priceEnquiry->GetSuppliers();
$priceEnquiry->GetQuantities();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Price Enquiry ID', 'hidden', '', 'numeric_unsigned', 1, 11);

$products = array();

for($j=0; $j<count($priceEnquiry->Supplier); $j++) {
	$priceEnquiry->Supplier[$j]->GetCosts();
	$priceEnquiry->Supplier[$j]->GetLines();
	$priceEnquiry->Supplier[$j]->Supplier->Get();
	$priceEnquiry->Supplier[$j]->Supplier->Contact->Get();
}

for($i=0; $i<count($priceEnquiry->Line); $i++) {
	$cost = 0;
	$supplierId = 0;

	for($j=0; $j<count($priceEnquiry->Supplier); $j++) {
		if($priceEnquiry->Supplier[$j]->Cost[$i]['Cost'] > 0) {
			if(($supplierId == 0) || ($priceEnquiry->Supplier[$j]->Cost[$i]['Cost'] < $cost)) {
				$cost = $priceEnquiry->Supplier[$j]->Cost[$i]['Cost'];
				$supplierId = $priceEnquiry->Supplier[$j]->Supplier->ID;
			}
		}
	}

	if($supplierId > 0) {
		for($j=0; $j<count($priceEnquiry->Supplier); $j++) {
			if($priceEnquiry->Supplier[$j]->Supplier->ID == $supplierId) {
				for($k=0; $k<count($priceEnquiry->Supplier[$j]->Line); $k++) {
					if($priceEnquiry->Supplier[$j]->Line[$k]->PriceEnquiryLineID == $priceEnquiry->Line[$i]->ID) {
						if($priceEnquiry->Supplier[$j]->Line[$k]->IsInStock == 'N') {
							$products[] = array('LineID' => $priceEnquiry->Line[$i]->ID, 'Cost' => $cost, 'StockArrival' => $priceEnquiry->Supplier[$j]->Line[$k]->StockBackorderDays, 'SupplierID' => $supplierId);

							$form->AddField('alternative_'.$priceEnquiry->Line[$i]->ID, 'Alternative Supplier', 'select', '0', 'numeric_unsigned', 1, 11);
							$form->AddOption('alternative_'.$priceEnquiry->Line[$i]->ID, '0', '');

							for($h=0; $h<count($priceEnquiry->Supplier); $h++) {
								if($priceEnquiry->Supplier[$h]->Supplier->ID != $supplierId) {
									if($priceEnquiry->Supplier[$h]->Cost[$i]['Cost'] > 0) {
										$supplierOrganisation = $priceEnquiry->Supplier[$h]->Supplier->Contact->Parent->Organisation->Name;
										$supplierContact = trim(sprintf('%s %s', $priceEnquiry->Supplier[$h]->Supplier->Contact->Person->Name, $priceEnquiry->Supplier[$h]->Supplier->Contact->Person->LastName));
										$supplierName = sprintf('%s%s', $supplierOrganisation, !empty($supplierContact) ? sprintf(' (%s)', $supplierContact) : '');

										$isInStock = 'U';
										$stockBackorderDays = 0;

										for($m=0; $m<count($priceEnquiry->Supplier[$h]->Line); $m++) {
											if($priceEnquiry->Supplier[$h]->Line[$m]->PriceEnquiryLineID == $priceEnquiry->Supplier[$j]->Line[$k]->PriceEnquiryLineID) {
												$isInStock = $priceEnquiry->Supplier[$h]->Line[$m]->IsInStock;
												$stockBackorderDays = $priceEnquiry->Supplier[$h]->Line[$m]->StockBackorderDays;

												break;
											}
										}

										switch(strtoupper($isInStock)) {
											case 'Y':
												$stockStatus = ': In Stock';
												break;
											case 'N':
												$stockStatus = sprintf(': Out Of Stock%s', ($stockBackorderDays > 0) ? sprintf(' (%d Days)', $stockBackorderDays) : '');
												break;
											default:
												$stockStatus = '';
												break;
										}

										$form->AddOption('alternative_'.$priceEnquiry->Line[$i]->ID, $priceEnquiry->Supplier[$h]->Supplier->ID, sprintf('%s - &pound;%s%s', $supplierName, $priceEnquiry->Supplier[$h]->Cost[$i]['Cost'], $stockStatus));
									}
								}
							}
						}

						break;
					}
				}

				break;
			}
		}
	}
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$supplierProducts = array();

		for($j=0; $j<count($priceEnquiry->Supplier); $j++) {
			$priceEnquiry->Supplier[$j]->GetCosts();
		}

		for($i=0; $i<count($priceEnquiry->Line); $i++) {
			$cost = 0;
			$supplierId = 0;

			foreach($products as $product) {
				if($product['LineID'] == $priceEnquiry->Line[$i]->ID) {
					if($form->GetValue('alternative_'.$priceEnquiry->Line[$i]->ID) > 0) {
						$supplierId = $form->GetValue('alternative_'.$priceEnquiry->Line[$i]->ID);

						for($h=0; $h<count($priceEnquiry->Supplier); $h++) {
							if($priceEnquiry->Supplier[$h]->Supplier->ID == $supplierId) {
								$cost = $priceEnquiry->Supplier[$h]->Cost[$i]['Cost'];
								break;
							}
						}
					}
				}
			}

			if($supplierId == 0) {
				for($j=0; $j<count($priceEnquiry->Supplier); $j++) {
					if($priceEnquiry->Supplier[$j]->Cost[$i]['Cost'] > 0) {
						if(($supplierId == 0) || ($priceEnquiry->Supplier[$j]->Cost[$i]['Cost'] < $cost)) {
							$cost = $priceEnquiry->Supplier[$j]->Cost[$i]['Cost'];
							$supplierId = $priceEnquiry->Supplier[$j]->Supplier->ID;
						}
					}
				}
			}

			if($supplierId > 0) {
				if(!isset($supplierProducts[$supplierId])) {
					$supplierProducts[$supplierId] = array();
				}

				$supplierProducts[$supplierId][$priceEnquiry->Line[$i]->Product->ID] = array('Product' => $priceEnquiry->Line[$i]->Product, 'Quantity' => $priceEnquiry->Line[$i]->Quantity, 'Cost' => $cost);
			}
		}

		$warehouseId = 0;
		$branchId = 0;

		$data = new DataQuery(sprintf("SELECT w.Warehouse_ID, u.Branch_ID FROM warehouse AS w INNER JOIN users AS u ON u.Branch_ID=w.Type_Reference_ID WHERE w.Type='B' AND u.User_ID=%d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		if($data->TotalRows > 0) {
			$warehouseId = $data->Row['Warehouse_ID'];
			$branchId = $data->Row['Branch_ID'];
		}
		$data->Disconnect();

		foreach($supplierProducts as $supplierId=>$products) {
			$supplier = new Supplier($supplierId);
			$supplier->Contact->Get();

			$branch = new Branch($branchId);

			$purchase = new Purchase();
			$purchase->PriceEnquiry->ID = $priceEnquiry->ID;
			$purchase->SupplierID = $supplierId;
			$purchase->Status = 'Unfulfilled';
			$purchase->PurchasedOn = date('Y-m-d H:i:s');
			$purchase->Warehouse->ID = $warehouseId;
			$purchase->Branch = $branchId;
			$purchase->Supplier = $supplier->Contact->Person;
			$purchase->SupOrg = ($supplier->Contact->HasParent) ? $supplier->Contact->Parent->Organisation->Name : '';

			$data = new DataQuery(sprintf("SELECT o.Fax FROM person AS p INNER JOIN contact AS c ON c.Person_ID=p.Person_ID INNER JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE p.Person_ID=%d", mysql_real_escape_string($supplier->Contact->Person->ID)));
			$purchase->Supplier->Fax = $data->Row['Fax'];
			$data->Disconnect();

			$purchase->Person->Address = $branch->Address;
			$purchase->Organisation = $branch->Name;
			$purchase->Add();

			foreach($products as $productId=>$productData) {
				$line = new PurchaseLine();
				$line->Purchase = $purchase->ID;
				$line->Quantity = $productData['Quantity'];
				$line->QuantityDec = $productData['Quantity'];
				$line->Product = $productData['Product'];
				$line->Cost = $productData['Cost'];
				$line->SuppliedBy = $supplierId;

				$data = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($supplierId), mysql_real_escape_string($line->Product->ID)));
				if($data->TotalRows > 0) {
					$line->SKU = $data->Row['Supplier_SKU'];
				}
				$data->Disconnect();

				$data = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($purchase->Warehouse->ID), mysql_real_escape_string($line->Product->ID)));
				if($data->TotalRows > 0) {
					$line->Location = $data->Row['Shelf_Location'];
				}
				$data->Disconnect();

				$line->Add();
			}
		}

		redirect(sprintf("Location: purchases_view.php"));
	}
}

$page = new Page(sprintf('<a href="price_enquiry_details.php?id=%d">[#%d] Price Enquiry Details</a> &gt; Purchase Best Buy', $priceEnquiry->ID, $priceEnquiry->ID), 'Some of the best buy products are currently unavailable and may require reassigning.');
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
    <td colspan="2">

		<div style="background-color: #eee; padding: 10px 0 10px 0;">
		 	<p><span class="pageSubTitle">Out Of Stock</span><br /><span class="pageDescription">Listing best buy products out of stock.</span></p>

		 	<table cellspacing="0" class="orderDetails">
				<tr>
					<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
					<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
			        <th nowrap="nowrap" style="padding-right: 5px;">Name</th>
			        <th nowrap="nowrap" style="padding-right: 5px;">Supplier</th>
			        <th nowrap="nowrap" style="padding-right: 5px;">Stock Arrival</th>
			        <th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost</th>
			        <th nowrap="nowrap" style="padding-right: 5px;">Alternative Supplier</th>
				</tr>

				<?php
				foreach($products as $product) {
					for($i=0; $i<count($priceEnquiry->Line); $i++) {
						if($product['LineID'] == $priceEnquiry->Line[$i]->ID) {
							$supplierName = '';

							for($j=0; $j<count($priceEnquiry->Supplier); $j++) {
								if($priceEnquiry->Supplier[$j]->Supplier->ID == $product['SupplierID']) {
									$supplierOrganisation = $priceEnquiry->Supplier[$j]->Supplier->Contact->Parent->Organisation->Name;
									$supplierContact = trim(sprintf('%s %s', $priceEnquiry->Supplier[$j]->Supplier->Contact->Person->Name, $priceEnquiry->Supplier[$j]->Supplier->Contact->Person->LastName));
									$supplierName = sprintf('%s%s', $supplierOrganisation, !empty($supplierContact) ? sprintf(' (%s)', $supplierContact) : '');

									break;
								}
							}
							?>

							<tr>
								<td nowrap="nowrap"><?php echo number_format(round($priceEnquiry->Line[$i]->Quantity, 2), 2, '.', ''); ?></td>
								<td nowrap="nowrap"><?php echo $priceEnquiry->Line[$i]->Product->ID; ?></td>
								<td nowrap="nowrap"><a href="product_profile.php?pid=<?php echo $priceEnquiry->Line[$i]->Product->ID; ?>"><?php echo $priceEnquiry->Line[$i]->Product->Name; ?></a>&nbsp;</td>
								<td nowrap="nowrap"><?php echo $supplierName; ?></td>
								<td nowrap="nowrap"><?php echo ($product['StockArrival'] > 0) ? sprintf('%d Days', $product['StockArrival']) : ''; ?></td>
								<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($product['Cost'], 2), 2, '.', ''); ?></td>
								<td nowrap="nowrap"><?php echo $form->GetHTML('alternative_'.$priceEnquiry->Line[$i]->ID); ?></td>
							</tr>

							<?php
							break;
						}
					}
				}
			  	?>
		    </table>
		    <br />

			<table cellspacing="0" cellpadding="0" border="0" width="100%">
				<tr>
					<td align="left">
						<input type="submit" name="continue" value="continue" class="btn" />
					</td>
					<td align="right"></td>
				</tr>
			</table>

		</div>

    </td>
  </tr>
</table>
<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');