<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductReorder.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseRequest.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseRequestLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add') {
	$session->Secure(3);
	add();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function add() {
	if(!isset($_REQUEST['id'])) {
		redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
	}

	$product = new Product();

	if($product->Get($_REQUEST['id'])) {
		$reorder = new ProductReorder();
		$reorder->Product->ID = $product->ID;
		$reorder->ReorderQuantity = $product->StockReorderQuantity;
		$reorder->Add();
	}

	redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
}
function remove() {
	if(isset($_REQUEST['id'])) {
		$reorder = new ProductReorder();
		$reorder->Delete($_REQUEST['id']);
	}

	redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
}

function view() {
	$products = array();
	$suppliers = array();
	$potential = array();

	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.LockedSupplierID, p.Product_Title, p.SKU, p.Stock_Level_Alert, p.Position_Orders_Recent, p.Position_Quantities_Recent, p.Total_Orders_3_Month, p.Total_Quantities_3_Month, pr.ProductReorderID, pr.ReorderQuantity FROM product_reorder AS pr INNER JOIN product AS p ON p.Product_ID=pr.ProductID WHERE pr.IsHidden='N'"));
	while($data->Row) {
		$products[] = $data->Row;
		
		if($data->Row['LockedSupplierID'] > 0) {
			$suppliers[$data->Row['LockedSupplierID']] = $data->Row['LockedSupplierID'];
		}

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.LockedSupplierID, p.Product_Title, p.SKU, p.Stock_Level_Alert, p.Stock_Reorder_Quantity, p.Position_Orders_Recent, p.Position_Quantities_Recent, p.Total_Orders_3_Month, p.Total_Quantities_3_Month, w.Quantity_Stocked, pu.Quantity_Incoming FROM product AS p INNER JOIN (SELECT ws.Product_ID, SUM(ws.Quantity_In_Stock) AS Quantity_Stocked FROM warehouse AS w INNER JOIN warehouse_stock AS ws ON ws.Warehouse_ID=w.Warehouse_ID WHERE w.Type='B' GROUP BY ws.Product_ID) AS w ON w.Product_ID=p.product_ID INNER JOIN (SELECT pl.Product_ID, SUM(pl.Quantity_Decremental) AS Quantity_Incoming FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID WHERE p.For_Branch>0 AND pl.Quantity_Decremental>0 GROUP BY pl.Product_ID) AS pu ON pu.Product_ID=p.product_ID LEFT JOIN product_reorder AS pr ON pr.ProductID=p.Product_ID WHERE p.Product_Type='S' AND p.Is_Stocked='Y' AND p.LockedSupplierID>0 AND p.Monitor_Stock='Y' AND ISNULL(pr.ProductID) GROUP BY p.Product_ID HAVING Quantity_Stocked+Quantity_Incoming<(Stock_Level_Alert/100)*150 ORDER BY p.Product_Title ASC"));
	while($data->Row) {
		if(!isset($potential[$data->Row['LockedSupplierID']])) {
			$potential[$data->Row['LockedSupplierID']] = array();
		}

		$potential[$data->Row['LockedSupplierID']][] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	foreach($products as $product) {
		$form->AddField('quantity_' . $product['ProductReorderID'], 'Quantity', 'text', $product['ReorderQuantity'], 'numeric_unsignedd', 1, 11, true, 'style="width: 100%;"');
		
		if($product['LockedSupplierID'] == 0) {
			$form->AddField('select_' . $product['ProductReorderID'], 'Select', 'checkbox', 'N', 'boolean', 1, 1, false);
			$form->AddField('supplier_' . $product['ProductReorderID'], 'Supplier', 'select', $product['LockedSupplierID'], 'numeric_unsigned', 1, 11);
			$form->AddGroup('supplier_'. $product['ProductReorderID'], 'S1', 'Suppliers (Costed)');
			$form->AddGroup('supplier_'. $product['ProductReorderID'], 'S2', 'Suppliers (No Costs)');
			$form->AddOption('supplier_' . $product['ProductReorderID'], '0', '');
			
			$data = new DataQuery(sprintf("SELECT s.Supplier_ID, IF((LENGTH(TRIM(o.Org_Name)) > 0) AND (LENGTH(TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last))) > 0), CONCAT_WS(' ', TRIM(o.Org_Name), CONCAT('(', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), ')')), IF(LENGTH(TRIM(o.Org_Name)) > 0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS Supplier_Name, sp.Cost FROM supplier AS s INNER JOIN product_supplier_flexible AS psf ON psf.SupplierID=s.Supplier_ID AND psf.ProductID=%d INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN supplier_product AS sp ON sp.Supplier_ID=s.Supplier_ID AND sp.Product_ID=psf.ProductID ORDER BY Supplier_Name ASC", mysql_real_escape_string($product['Product_ID'])));
			if($data->TotalRows > 0) {
				while($data->Row) {
					$form->AddOption('supplier_' . $product['ProductReorderID'], $data->Row['Supplier_ID'], sprintf('%s [&pound;%s]', $data->Row['Supplier_Name'], number_format($data->Row['Cost'], 2, '.', ',')), 'S' . (($data->Row['Cost'] > 0) ? '1' : '2'));

					$data->Next();
				}
			} else {
				$data2 = new DataQuery(sprintf("SELECT s.Supplier_ID, IF((LENGTH(TRIM(o.Org_Name)) > 0) AND (LENGTH(TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last))) > 0), CONCAT_WS(' ', TRIM(o.Org_Name), CONCAT('(', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), ')')), IF(LENGTH(TRIM(o.Org_Name)) > 0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS Supplier_Name, sp.Cost FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN supplier_product AS sp ON sp.Supplier_ID=s.Supplier_ID AND sp.Product_ID=%d ORDER BY Supplier_Name ASC", mysql_real_escape_string($product['Product_ID'])));
				while($data2->Row) {
					$form->AddOption('supplier_' . $product['ProductReorderID'], $data2->Row['Supplier_ID'], sprintf('%s [&pound;%s]', $data2->Row['Supplier_Name'], number_format($data2->Row['Cost'], 2, '.', ',')), 'S' . (($data2->Row['Cost'] > 0) ? '1' : '2'));

					$data2->Next();
				}
				$data2->Disconnect();
			}
			$data->Disconnect();
		}
	}
	
	foreach($suppliers as $supplierId) {
		$form->AddField('date_' . $supplierId, 'Date', 'text', date('d/m/Y'), 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	}

	if(isset($_REQUEST['confirm'])) {
		if(isset($_REQUEST['priceenquiry'])) {
			$hasProducts = false;
			
			foreach($_REQUEST as $key=>$value) {
				if(preg_match('/select_([\d]*)/', $key, $matches)) {
					if($value == 'Y') {
						foreach($products as $product) {
							if($product['ProductReorderID'] == $matches[1]) {
								$hasProducts = true;
								break(2);
							}
						}
					}
				}
			}
			
			if($hasProducts) {
				$priceEnquiry = new PriceEnquiry();
				$priceEnquiry->Status = 'Pending';
				$priceEnquiry->Add();
				
				foreach($_REQUEST as $key=>$value) {
					if(preg_match('/select_([\d]*)/', $key, $matches)) {
						if($value == 'Y') {
							foreach($products as $product) {
								if($product['ProductReorderID'] == $matches[1]) {
									$priceEnquiry->AddLine($product['Product_ID'], $form->GetValue('quantity_'.$product['ProductReorderID']));
									break;
								}
							}
						}
					}
				}
			
				$data = new DataQuery(sprintf("SELECT Supplier_ID FROM supplier WHERE Is_Favourite='Y'"));
				while($data->Row) {
					$priceEnquiry->AddSupplier($data->Row['Supplier_ID']);

					$data->Next();
				}
				$data->Disconnect();

				$priceEnquiry->Recalculate();
			}

			redirect('Location: ?action=view');

		} elseif(isset($_REQUEST['purchaserequest'])) {
			$supplierData = array();

			foreach($_REQUEST as $key=>$value) {
				if(preg_match('/supplier_([\d]*)/', $key, $matches)) {
					if($value > 0) {
						if(!isset($supplierData[$value])) {
							$supplierData[$value] = array();
						}

						$supplierData[$value][] = $matches[1];
					}
				}
			}

			foreach($supplierData as $supplierId=>$productReorders) {
				$request = new PurchaseRequest();
				$request->Status = 'Pending';
				$request->Supplier->ID = $supplierId;
				$request->Add();

				foreach($productReorders as $productReorderId) {
					$reorder = new ProductReorder($productReorderId);
					$reorder->IsHidden = 'Y';
					$reorder->Update();

					$requestLine = new PurchaseRequestLine();
					$requestLine->PurchaseRequestID = $request->ID;
					$requestLine->Product->ID = $reorder->Product->ID;
					$requestLine->Quantity = $form->GetValue('quantity_'.$productReorderId);
					$requestLine->Add();
				}
			}

			redirect('Location: ?action=view');

		} elseif(isset($_REQUEST['purchaseorder'])) {
			$supplierData = array();

			foreach($_REQUEST as $key=>$value) {
				if(preg_match('/supplier_([\d]*)/', $key, $matches)) {
					if($value > 0) {
						if(!isset($supplierData[$value])) {
							$supplierData[$value] = array();
						}

						$supplierData[$value][] = $matches[1];
					}
				}
			}

			foreach($supplierData as $supplierId=>$productReorders) {
				$supplier = new Supplier($supplierId);
				$supplier->Contact->Get();

				$user = new User();
				$user->ID = $GLOBALS['SESSION_USER_ID'];
				$user->Get();

				$data = new DataQuery(sprintf("SELECT w.Warehouse_ID FROM users AS u INNER JOIN warehouse AS w ON w.Type_Reference_ID=u.Branch_ID AND w.Type='B' WHERE u.User_ID=%d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

				$warehouse = new Warehouse();
				$warehouse->ID = $data->Row['Warehouse_ID'];
				$warehouse->Get();
				$warehouse->Contact->Get();

				$data->Disconnect();

				$purchase = new Purchase();
				$purchase->SupplierID = $supplierId;
				$purchase->PurchasedOn = date('Y-m-d H:i:s');
				$purchase->Person = $user->Person;
				$purchase->Person->Address = $warehouse->Contact->Address;
				$purchase->Organisation = $warehouse->Name;
				$purchase->Warehouse->ID = $warehouse->ID;
				$purchase->Status = 'Unfulfilled';
				$purchase->Branch = $warehouse->Contact->ID;
				$purchase->PSID = 0;
				$purchase->Supplier = $supplier->Contact->Person;
				$purchase->SupOrg = ($supplier->Contact->HasParent) ? $supplier->Contact->Parent->Organisation->Name : '';

				$data = new DataQuery(sprintf("SELECT o.Fax FROM person AS p INNER JOIN contact AS c ON c.Person_ID=p.Person_ID INNER JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE p.Person_ID=%d", mysql_real_escape_string($supplier->Contact->Person->ID)));
				$purchase->Supplier->Fax = $data->Row['Fax'];
				$data->Disconnect();

				$purchase->Add();

				foreach($productReorders as $productReorderId) {
					$reorder = new ProductReorder();

					if($reorder->Get($productReorderId)) {
						$purchaseLine = new PurchaseLine();
						$purchaseLine->Purchase = $purchase->ID;
						$purchaseLine->Quantity = $form->GetValue('quantity_'.$productReorderId);
						$purchaseLine->Product = $reorder->Product;
						$purchaseLine->SuppliedBy = $supplier->ID;

						$data = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($supplier->ID), mysql_real_escape_string($reorder->Product->ID)));
						$purchaseLine->SKU = $data->Row['Supplier_SKU'];
						$data->Disconnect();

						$data = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($warehouse->ID), mysql_real_escape_string($reorder->Product->ID)));
						$purchaseLine->Location = $data->Row['Shelf_Location'];
						$data->Disconnect();

						$prices = array();

						$data = new DataQuery(sprintf("SELECT * FROM supplier_product_price WHERE Supplier_ID=%d AND Product_ID=%d AND Quantity<=%d ORDER BY Created_On ASC", mysql_real_escape_string($supplier->ID), mysql_real_escape_string($reorder->Product->ID), $form->GetValue('quantity_'.$productReorderId)));
						while($data->Row) {
							if($data->Row['Cost'] > 0) {
								$prices[$data->Row['Quantity']] = $data->Row;
							} else {
								unset($prices[$data->Row['Quantity']]);
							}

							$data->Next();
						}
						$data->Disconnect();

						krsort($prices);

						if(count($prices) > 0) {
							foreach($prices as $price) {
								$purchaseLine->Cost = $price['Cost'];
								break;
							}
						}

						$purchaseLine->QuantityDec = $form->GetValue('quantity_'.$productReorderId);
						$purchaseLine->Add();

						$reorder->Delete();
					}
				}
			}

			redirect('Location: ?action=view');
		} else {
			foreach($suppliers as $supplierId) {
				if(isset($_REQUEST['purchaserequest-' . $supplierId])) {
					$request = new PurchaseRequest();
					$request->Status = 'Pending';
					$request->Supplier->ID = $supplierId;
					$request->Add();

					foreach($products as $product) {
						if($product['LockedSupplierID'] == $supplierId) {
							$reorder = new ProductReorder($product['ProductReorderID']);
							$reorder->IsHidden = 'Y';
							$reorder->Update();

							$requestLine = new PurchaseRequestLine();
							$requestLine->PurchaseRequestID = $request->ID;
							$requestLine->Product->ID = $reorder->Product->ID;
							$requestLine->Quantity = $form->GetValue('quantity_'.$product['ProductReorderID']);
							$requestLine->Add();
						}
					}

					redirect('Location: ?action=view');
					
				} elseif(isset($_REQUEST['purchaseorder-' . $supplierId])) {
					$supplier = new Supplier($supplierId);
					$supplier->Contact->Get();

					$user = new User();
					$user->ID = $GLOBALS['SESSION_USER_ID'];
					$user->Get();

					$data = new DataQuery(sprintf("SELECT w.Warehouse_ID FROM users AS u INNER JOIN warehouse AS w ON w.Type_Reference_ID=u.Branch_ID AND w.Type='B' WHERE u.User_ID=%d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

					$warehouse = new Warehouse();
					$warehouse->ID = $data->Row['Warehouse_ID'];
					$warehouse->Get();
					$warehouse->Contact->Get();

					$data->Disconnect();

					$purchase = new Purchase();
					$purchase->SupplierID = $supplierId;
					$purchase->PurchasedOn = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('date_' . $supplierId), 6, 4), substr($form->GetValue('date_' . $supplierId), 3, 2), substr($form->GetValue('date_' . $supplierId), 0, 2));
					$purchase->Person = $user->Person;
					$purchase->Person->Address = $warehouse->Contact->Address;
					$purchase->Organisation = $warehouse->Name;
					$purchase->Warehouse->ID = $warehouse->ID;
					$purchase->Status = 'Unfulfilled';
					$purchase->Branch = $warehouse->Contact->ID;
					$purchase->PSID = 0;
					$purchase->Supplier = $supplier->Contact->Person;
					$purchase->SupOrg = ($supplier->Contact->HasParent) ? $supplier->Contact->Parent->Organisation->Name : '';

					$data = new DataQuery(sprintf("SELECT o.Fax FROM person AS p INNER JOIN contact AS c ON c.Person_ID=p.Person_ID INNER JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE p.Person_ID=%d", mysql_real_escape_string($supplier->Contact->Person->ID)));
					$purchase->Supplier->Fax = $data->Row['Fax'];
					$data->Disconnect();

					$purchase->Add();

					foreach($products as $product) {
						if($product['LockedSupplierID'] == $supplierId) {
							$reorder = new ProductReorder();

							if($reorder->Get($product['ProductReorderID'])) {
								$purchaseLine = new PurchaseLine();
								$purchaseLine->Purchase = $purchase->ID;
								$purchaseLine->Quantity = $form->GetValue('quantity_'.$product['ProductReorderID']);
								$purchaseLine->Product = $reorder->Product;
								$purchaseLine->SuppliedBy = $supplier->ID;

								$data = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($supplier->ID), mysql_real_escape_string($reorder->Product->ID)));
								$purchaseLine->SKU = $data->Row['Supplier_SKU'];
								$data->Disconnect();

								$data = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($warehouse->ID), mysql_real_escape_string($reorder->Product->ID)));
								$purchaseLine->Location = $data->Row['Shelf_Location'];
								$data->Disconnect();

								$prices = array();

								$data = new DataQuery(sprintf("SELECT * FROM supplier_product_price WHERE Supplier_ID=%d AND Product_ID=%d AND Quantity<=%d ORDER BY Created_On ASC", mysql_real_escape_string($supplier->ID), mysql_real_escape_string($reorder->Product->ID), $form->GetValue('quantity_'.$product['ProductReorderID'])));
								while($data->Row) {
									if($data->Row['Cost'] > 0) {
										$prices[$data->Row['Quantity']] = $data->Row;
									} else {
										unset($prices[$data->Row['Quantity']]);
									}

									$data->Next();
								}
								$data->Disconnect();

								krsort($prices);

								if(count($prices) > 0) {
									foreach($prices as $price) {
										$purchaseLine->Cost = $price['Cost'];
										break;
									}
								}

								$purchaseLine->QuantityDec = $form->GetValue('quantity_'.$product['ProductReorderID']);
								$purchaseLine->Add();

								$reorder->Delete();
							}
						}
					}

					redirect('Location: ?action=view');
				}
			}
		}
	}

	$page = new Page('Product Reorders', 'Listing all products in the reordering list with sales statistics for the last 3 months.');
	$page->LinkScript('js/scw.js');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('confirm');
	?>

	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
		<thead>
			<tr>
				<th nowrap="nowrap" width="1%"></th>
				<th nowrap="nowrap">ID</th>
				<th nowrap="nowrap">Name</th>
				<th nowrap="nowrap">SKU</th>
				<th nowrap="nowrap">Reorder</th>
				<th nowrap="nowrap">Alert Level</th>
				<th nowrap="nowrap">Position (Orders)</th>
				<th nowrap="nowrap">Position (Quantities)</th>
				<th nowrap="nowrap">Orders</th>
				<th nowrap="nowrap">Quantity Sold</th>
				<th nowrap="nowrap">Supplier</th>
				<th nowrap="nowrap" width="1%">&nbsp;</th>
			</tr>
		</thead>
		<tbody>

			<?php
			if(count($products) > 0) {
				foreach($products as $product) {
					if($product['LockedSupplierID'] == 0) {
						?>

						<tr>
							<td><?php echo $form->GetHTML('select_'.$product['ProductReorderID']); ?></td>
							<td><?php echo $product['Product_ID']; ?></td>
							<td><?php echo $product['Product_Title']; ?></td>
							<td><?php echo $product['SKU']; ?></td>
							<td><?php echo $form->GetHTML('quantity_'.$product['ProductReorderID']); ?></td>
							<td><?php echo $product['Stock_Level_Alert']; ?></td>
							<td><?php echo $product['Position_Orders_Recent']; ?></td>
							<td><?php echo $product['Position_Quantities_Recent']; ?></td>
							<td><?php echo $product['Total_Orders_3_Month']; ?></td>
							<td><?php echo $product['Total_Quantities_3_Month']; ?></td>
							<td><?php echo $form->GetHTML('supplier_'.$product['ProductReorderID']); ?></td>
							<td nowrap="nowrap">
								<a href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><img src="images/folderopen.gif" alt="View" border="0" /></a>
								<a href="javascript:confirmRequest('<?php echo $_SERVER['PHP_SELF']; ?>?action=remove&id=<?php echo $product['ProductReorderID']; ?>', 'Are you sure you want to remove this item?');"><img src="images/aztector_6.gif" alt="Remove" border="0" /></a>
							</td>
						</tr>

						<?php
					}
				}
			} else {
				?>

				<tr>
					<td colspan="12">There are no items available for viewing.</th>
				</tr>

				<?php
			}
			?>

		</tbody>
	</table>
	<br />
	
	<table width="100%">
		<tr>
			<td align="left">
				<input type="submit" name="priceenquiry" value="price enquiry" class="btn" />
			</td>
			<td align="right">
				<input type="submit" name="purchaserequest" value="purchase request" class="btn" />
				<input type="submit" name="purchaseorder" value="purchase order" class="btn" />
			</td>
		</tr>
	</table>
	<br />
		
	<?php
	foreach($suppliers as $supplierId) {
		$supplier = new Supplier($supplierId);
		$supplier->Contact->Get();

		$total = 0;
		?>
		
		<h3><?php echo $supplier->Contact->Person->GetFullName(); ?></h3>
		<p>Purchase order date: <?php echo $form->GetHTML('date_'.$supplierId); ?></p>
		
		<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
			<thead>
				<tr>
					<th nowrap="nowrap" width="5%">ID</th>
					<th nowrap="nowrap" width="30%">Name</th>
					<th nowrap="nowrap" width="8%">SKU</th>
					<th nowrap="nowrap" width="5%">Reorder</th>
					<th nowrap="nowrap" width="5%">Alert Level</th>
					<th nowrap="nowrap" width="9%">Position (Orders)</th>
					<th nowrap="nowrap" width="9%">Position (Quantities)</th>
					<th nowrap="nowrap" width="9%">Orders</th>
					<th nowrap="nowrap" width="9%">Quantity Sold</th>
					<th nowrap="nowrap" width="5%">Cost</th>
					<th nowrap="nowrap" width="5%">Total</th>
					<th nowrap="nowrap" width="1%">&nbsp;</th>
				</tr>
			</thead>
			<tbody>

				<?php
				if(count($products) > 0) {
					foreach($products as $product) {
						if($product['LockedSupplierID'] == $supplierId) {
							$data = new DataQuery(sprintf("SELECT * FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($supplierId), mysql_real_escape_string($product['Product_ID'])));
							$cost = ($data->TotalRows > 0) ? $data->Row['Cost'] : 0;
							$data->Disconnect();
							?>

							<tr>
								<td><?php echo $product['Product_ID']; ?></td>
								<td><?php echo $product['Product_Title']; ?></td>
								<td><?php echo $product['SKU']; ?></td>
								<td><?php echo $form->GetHTML('quantity_'.$product['ProductReorderID']); ?></td>
								<td><?php echo $product['Stock_Level_Alert']; ?></td>
								<td><?php echo $product['Position_Orders_Recent']; ?></td>
								<td><?php echo $product['Position_Quantities_Recent']; ?></td>
								<td><?php echo $product['Total_Orders_3_Month']; ?></td>
								<td><?php echo $product['Total_Quantities_3_Month']; ?></td>
								<td align="right">&pound;<?php echo number_format($cost, 2, '.', ','); ?></td>
								<td align="right">&pound;<?php echo number_format($cost * $product['ReorderQuantity'], 2, '.', ','); ?></td>
								<td nowrap="nowrap">
									<a href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><img src="images/folderopen.gif" alt="View" border="0" /></a>
									<a href="javascript:confirmRequest('?action=remove&id=<?php echo $product['ProductReorderID']; ?>', 'Are you sure you want to remove this item?');"><img src="images/aztector_6.gif" alt="Remove" border="0" /></a>
								</td>
							</tr>

							<?php
							$total += $cost * $product['ReorderQuantity'];
						}
					}
					?>

					<tr>
						<td>&nbsp;</td>
						<td colspan="9">Free Shipping Minimum Purchase Amount</td>
						<td align="right">&pound;<?php echo number_format($supplier->FreeShippingMinimumPurchase, 2, '.', ','); ?></td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td colspan="9"><strong>Total</strong></td>
						<td align="right"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
						<td>&nbsp;</td>
					</tr>

					<?php
				} else {
					?>

					<tr>
						<td colspan="12">There are no items available for viewing.</th>
					</tr>

					<?php
				}
				?>

			</tbody>
		</table>
		<br />

		<table width="100%">
			<tr>
				<td align="left"></td>
				<td align="right">
					<input type="submit" name="purchaserequest-<?php echo $supplierId; ?>" value="purchase request" class="btn" />
					<input type="submit" name="purchaseorder-<?php echo $supplierId; ?>" value="purchase order" class="btn" />
				</td>
			</tr>
		</table>
		<br />

		<?php
		if(isset($potential[$supplierId])) {
			?>

			<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="5%"><strong>ID</strong></td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="24%"><strong>Name</strong></td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="10%"><strong>SKU</strong></td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="5%" align="right"><strong>Reorder</strong></td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="5%" align="right"><strong>Alert Level</strong></td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="5%" align="right"><strong>Stock</strong><br />Quantity</td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="5%" align="right"><strong>Stock</strong><br />Incoming</td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="5%" align="right"><strong>Stock</strong><br />Level</td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="5%" align="right"><strong>Stock</strong><br />Overstocked</td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="5%" align="right"><strong>Position</strong><br />Orders</td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="5%" align="right"><strong>Position</strong><br />Quantities</td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="5%" align="right"><strong>Orders</strong></td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="5%" align="right"><strong>Quantity Sold</strong></td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="5%" align="right"><strong>Cost</strong></td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="5%" align="right"><strong>Total</strong></td>
						<td style="border-bottom: 1px solid #000; padding: 5px;" width="1%">&nbsp;</td>
					</tr>
				</thead>
				<tbody>

					<?php
					foreach($potential[$supplierId] as $product) {
						$data = new DataQuery(sprintf("SELECT * FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($supplierId), mysql_real_escape_string($product['Product_ID'])));
						$cost = ($data->TotalRows > 0) ? $data->Row['Cost'] : 0;
						$data->Disconnect();
						?>

						<tr>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;"><?php echo $product['Product_ID']; ?></td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;"><?php echo $product['Product_Title']; ?></td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;"><?php echo $product['SKU']; ?></td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;" align="right"><?php echo $product['Stock_Reorder_Quantity']; ?></td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;" align="right"><?php echo $product['Stock_Level_Alert']; ?></td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;" align="right"><?php echo $product['Quantity_Stocked']; ?></td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;" align="right"><?php echo $product['Quantity_Incoming']; ?></td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;" align="right"><?php echo $product['Quantity_Stocked']+$product['Quantity_Incoming']; ?></td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;" align="right"><?php echo ((($product['Quantity_Stocked']+$product['Quantity_Incoming'])/$product['Stock_Level_Alert'])*100)-100; ?>%</td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;" align="right"><?php echo $product['Position_Orders_Recent']; ?></td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;" align="right"><?php echo $product['Position_Quantities_Recent']; ?></td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;" align="right"><?php echo $product['Total_Orders_3_Month']; ?></td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;" align="right"><?php echo $product['Total_Quantities_3_Month']; ?></td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;" align="right">&pound;<?php echo number_format($cost, 2, '.', ','); ?></td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;" align="right">&pound;<?php echo number_format($cost * $product['Stock_Reorder_Quantity'], 2, '.', ','); ?></td>
							<td style="border-bottom: 1px dotted #ccc; padding: 5px;" nowrap="nowrap">
								<a href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><img src="images/folderopen.gif" alt="View" border="0" /></a>
								<a href="?action=add&id=<?php echo $product['Product_ID']; ?>"><img src="images/button-plus.gif" alt="Add" border="0" /></a>
							</td>
						</tr>

						<?php
						$total += $cost * $product['Stock_Reorder_Quantity'];
					}
					?>

					<tr>
						<td style="border-bottom: 1px dotted #ccc; padding: 5px;">&nbsp;</td>
						<td style="border-bottom: 1px dotted #ccc; padding: 5px;" colspan="13"><strong>Total</strong></td>
						<td style="border-bottom: 1px dotted #ccc; padding: 5px;" align="right"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
						<td style="border-bottom: 1px dotted #ccc; padding: 5px;">&nbsp;</td>
					</tr>
				</tbody>
			</table>
			<br />

			<?php
		}
	}

	echo $form->Close();

	$page->Display('footer');
}