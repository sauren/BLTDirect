<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseRequest.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseRequestLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');

if($action == 'report') {
	$session->Secure(2);
	report();
	exit();
} elseif($action == 'purchase') {
	$session->Secure(2);
	purchase();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start(){
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('products', 'Products', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('products', '', '');
	$form->AddOption('products', '250', '250');
	$form->AddOption('products', '500', '500');
	$form->AddOption('products', '750', '750');
	$form->AddOption('products', '1000', '1000');
	$form->AddField('months', 'Months Backtrack', 'select', '1', 'numeric_unsigned', 1, 11);

	for($i=1; $i<=12; $i++) {
		$form->AddOption('months', $i, $i);
	}
	
	$form->AddField('gauge', 'Months Stock Gauge', 'select', '1', 'numeric_unsigned', 1, 11);

	for($i=1; $i<=24; $i++) {
		$form->AddOption('gauge', $i, $i);
	}

	$form->AddField('isstocked', 'Is Stocked', 'select', '', 'alpha', 0, 1, false);
	$form->AddOption('isstocked', '', '');
	$form->AddOption('isstocked', 'Y', 'Yes');
	$form->AddOption('isstocked', 'N', 'No');
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			redirectTo(sprintf('?action=report&products=%s&months=%d&gauge=%d&isstocked=%s', $form->GetValue('products'), $form->GetValue('months'), $form->GetValue('gauge'), $form->GetValue('isstocked')));
		}
	}

	$page = new Page('Top Stock Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow("Report on Top Stock.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Configure your report parameters here.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('products'), $form->GetHTML('products'));
	echo $webForm->AddRow($form->GetLabel('months'), $form->GetHTML('months'));
	echo $webForm->AddRow($form->GetLabel('gauge'), $form->GetHTML('gauge'));
	echo $webForm->AddRow($form->GetLabel('isstocked'), $form->GetHTML('isstocked'));
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('products', 'Products', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('months', 'Months Backtrack', 'hidden', '1', 'numeric_unsigned', 1, 11);
	$form->AddField('gauge', 'Months Stock Gauge', 'hidden', '1', 'numeric_unsigned', 1, 11);
	$form->AddField('isstocked', 'Is Stocked', 'hidden', '', 'alpha', 0, 1, false);

	$products = array();
	
	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.Position_Orders_Recent, COALESCE(o.Orders, 0) AS Orders, COALESCE(o.Quantities, 0) AS Quantities, COALESCE(w.Stock, 0) AS Stock, COALESCE(pu.Stock, 0) AS Incoming, IF(o2.Org_Name IS NULL, CONCAT_WS(' ', p2.Name_First, p2.Name_Last), CONCAT_WS(' ', o2.Org_Name, CONCAT('(', CONCAT_WS(' ', p2.Name_First, p2.Name_Last), ')'))) AS Locked_Supplier FROM product AS p LEFT JOIN supplier AS s ON s.Supplier_ID=p.LockedSupplierID LEFT JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN person AS p2 ON p2.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o2 ON o2.Org_ID=c2.Org_ID LEFT JOIN (SELECT ol.Product_ID, COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantities FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE o.Status LIKE 'Despatched' AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) GROUP BY ol.Product_ID) AS o ON o.Product_ID=p.Product_ID LEFT JOIN (SELECT ws.Product_ID, SUM(ws.Quantity_In_Stock) AS Stock FROM warehouse AS w INNER JOIN warehouse_stock AS ws ON ws.Warehouse_ID=w.Warehouse_ID WHERE w.Type='B' GROUP BY ws.Product_ID) AS w ON w.Product_ID=p.Product_ID LEFT JOIN (SELECT pl.Product_ID, SUM(pl.Quantity_Decremental) AS Stock FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID WHERE p.For_Branch>0 AND pl.Quantity_Decremental>0 GROUP BY pl.Product_ID) AS pu ON pu.Product_ID=p.Product_ID WHERE p.Position_Orders_Recent BETWEEN 1 AND %d AND p.Discontinued='N'%s GROUP BY p.Product_ID ORDER BY p.Position_Orders_Recent ASC", mysql_real_escape_string($form->GetValue('months')), mysql_real_escape_string($form->GetValue('products')), (strlen($form->GetValue('isstocked')) > 0) ? sprintf(' AND p.Is_Stocked=\'%s\'', mysql_real_escape_string($form->GetValue('isstocked'))) : ''));
	while($data->Row) {
		$products[] = $data->Row;
		
		$data->Next();
	}
	$data->Disconnect();
	
	$page = new Page('Top Stock Report', '');
	$page->Display('header');
	?>
	
	<br /><h3>Top <?php echo $form->GetValue('products'); ?> Stock</h3>
	<p>Top stock with stock duration stats.</p>

	<table width="100%" cellspacing="0">
		<tr>
			<th style="border-bottom: 1px solid #999999; padding: 5px; text-align: left;"><strong>Position</strong></th>
			<th style="border-bottom: 1px solid #999999; padding: 5px; text-align: left;"><strong>Product Name</strong></th>
			<th style="border-bottom: 1px solid #999999; padding: 5px; text-align: left;"><strong>Shelf Locations</strong></th>
			<th style="border-bottom: 1px solid #999999; padding: 5px; text-align: right;"><strong>Quickfind</strong></th>
			<th style="border-bottom: 1px solid #999999; padding: 5px; text-align: left;"><strong>Locked Supplier</strong></th>
			<th style="border-bottom: 1px solid #999999; padding: 5px; text-align: right;"><strong>Quantity</strong></th>
			<th style="border-bottom: 1px solid #999999; padding: 5px; text-align: right;"><strong>Orders</strong></th>
			<th style="border-bottom: 1px solid #999999; padding: 5px; text-align: left;"><strong><?php echo $form->GetValue('gauge'); ?> Month Gauge</strong></th>
		</tr>
	  
		<?php
		for($i=0; $i<count($products); $i++) {
			?>
			
			<tr>
				<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px;" align=";eft"><?php echo $products[$i]['Position_Orders_Recent']; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px;"><a href="product_profile.php?pid=<?php echo $products[$i]['Product_ID']; ?>" target="_blank"><?php echo $products[$i]['Product_Title']; ?></a></td>
				<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px;">
					<?php
					$locations = array();

					$data = new DataQuery(sprintf("SELECT DISTINCT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=1 AND Product_ID=%d AND Shelf_Location<>''", $products[$i]['Product_ID']));
					while($data->Row) {
						$locations[] = $data->Row['Shelf_Location'];

						$data->Next();
					}
					$data->Disconnect();

					echo implode(', ', $locations);
					?>
				</td>
				<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px;" align="right"><?php echo $products[$i]['Product_ID']; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px;"><?php echo $products[$i]['Locked_Supplier']; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px;" align="right"><?php echo $products[$i]['Quantities']; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px;" align="right"><?php echo $products[$i]['Orders']; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa; padding: 5px;" width="400">
					<?php
					$supplyStock = 0;
					$supplyIncoming = 0;
					
					if($products[$i]['Quantities'] > 0) {
						$supplyStock = $products[$i]['Stock'] / (($products[$i]['Quantities'] / $form->GetValue('months')));
						$supplyIncoming = $products[$i]['Incoming'] / (($products[$i]['Quantities'] / $form->GetValue('months')));
					}
					
					$percentStock = ($supplyStock / $form->GetValue('gauge')) * 100;
					$percentStock = min($percentStock, 100);
					$percentIncoming = ($supplyIncoming / $form->GetValue('gauge')) * 100;
					$percentIncoming = min($percentIncoming, 100);
					?>

					<div style="float: left; width: <?php echo $percentStock; ?>%; height: 22px; line-height: 22px; background-color: #b80000; font-weight: bold; text-align: right; overflow: hidden;">
						<?php echo ($supplyStock > 0) ? number_format(round($supplyStock, 1), 1, '.', '') : ''; ?>
					</div>
					
					<div style="float: left; width: <?php echo $percentIncoming; ?>%; height: 22px; line-height: 22px; background-color: #00b800; font-weight: bold; text-align: right; overflow: hidden;">
						<?php echo ($supplyIncoming > 0) ? number_format(round($supplyIncoming, 1), 1, '.', '') : ''; ?>
					</div>
				</td>
			</tr>
			
			<?php
		}
		?>
		
	</table>
	<br />

	<?php
	echo sprintf('<input type="button" class="btn" name="purchase" value="purchase" onclick="window.self.location.href = \'?action=purchase&products=%s&months=%d&gauge=%d&isstocked=%s\';" />', $form->GetValue('products'), $form->GetValue('months'), $form->GetValue('gauge'), $form->GetValue('isstocked'));
	
	$page->Display('footer');
}

function purchase() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'purchase', 'alpha', 8, 8);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('products', 'Products', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('months', 'Months Backtrack', 'hidden', '1', 'numeric_unsigned', 1, 11);
	$form->AddField('gauge', 'Months Stock Gauge', 'hidden', '1', 'numeric_unsigned', 1, 11);
	$form->AddField('isstocked', 'Is Stocked', 'hidden', '', 'alpha', 0, 1, false);

	$products = array();
	
	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.SKU, p.Product_Title, p.LockedSupplierID, p.Position_Orders_Recent, p.Position_Quantities_Recent, p.Total_Orders_3_Month, p.Total_Quantities_3_Month, COALESCE(w.Stock, 0)+COALESCE(pu.Stock, 0) AS Stock, COALESCE(o.Quantities, 0) AS Quantities FROM product AS p LEFT JOIN (SELECT ol.Product_ID, COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantities FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE o.Status LIKE 'Despatched' AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) GROUP BY ol.Product_ID) AS o ON o.Product_ID=p.Product_ID LEFT JOIN (SELECT ws.Product_ID, SUM(ws.Quantity_In_Stock) AS Stock FROM warehouse AS w INNER JOIN warehouse_stock AS ws ON ws.Warehouse_ID=w.Warehouse_ID WHERE w.Type='B' GROUP BY ws.Product_ID) AS w ON w.Product_ID=p.Product_ID LEFT JOIN (SELECT pl.Product_ID, SUM(pl.Quantity_Decremental) AS Stock FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID WHERE p.For_Branch>0 AND pl.Quantity_Decremental>0 GROUP BY pl.Product_ID) AS pu ON pu.Product_ID=p.Product_ID WHERE p.Position_Orders_Recent BETWEEN 1 AND %d AND p.Discontinued='N' AND p.Product_Type<>'G'%s GROUP BY p.Product_ID HAVING ((%d/%d)*Quantities*0.875)-Stock>0 ORDER BY p.Product_Title ASC", mysql_real_escape_string($form->GetValue('months')), mysql_real_escape_string($form->GetValue('products')), (strlen($form->GetValue('isstocked')) > 0) ? sprintf(' AND p.Is_Stocked=\'%s\'', $form->GetValue('isstocked')) : '', mysql_real_escape_string($form->GetValue('gauge')), mysql_real_escape_string($form->GetValue('months'))));
	while($data->Row) {
		if(!isset($products[$data->Row['LockedSupplierID']])) {
			$products[$data->Row['LockedSupplierID']] = array();
		}

		$products[$data->Row['LockedSupplierID']][] = $data->Row;
		
		$data->Next();
	}
	$data->Disconnect();

	foreach($products as $supplierId=>$productData) {
		foreach($productData as $product) {
			$quantity = ceil((($form->GetValue('gauge')/$form->GetValue('months'))*$product['Quantities'])-$product['Stock']);

			$form->AddField('quantity_' . $product['Product_ID'], 'Quantity', 'text', $quantity, 'numeric_unsigned', 1, 11, true, 'size="3"');
			$form->AddField('supplier_' . $product['Product_ID'], 'Supplier', 'select', $product['LockedSupplierID'], 'numeric_unsigned', 1, 11);
			$form->AddGroup('supplier_'. $product['Product_ID'], 'S1', 'Suppliers (Costed)');
			$form->AddGroup('supplier_'. $product['Product_ID'], 'S2', 'Suppliers (No Costs)');
			$form->AddOption('supplier_' . $product['Product_ID'], '0', '');
			
			$data = new DataQuery(sprintf("SELECT s.Supplier_ID, IF((LENGTH(TRIM(o.Org_Name)) > 0) AND (LENGTH(TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last))) > 0), CONCAT_WS(' ', TRIM(o.Org_Name), CONCAT('(', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), ')')), IF(LENGTH(TRIM(o.Org_Name)) > 0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS Supplier_Name, sp.Cost FROM supplier AS s INNER JOIN product_supplier_flexible AS psf ON psf.SupplierID=s.Supplier_ID AND psf.ProductID=%d INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN supplier_product AS sp ON sp.Supplier_ID=s.Supplier_ID AND sp.Product_ID=psf.ProductID ORDER BY Supplier_Name ASC", mysql_real_escape_string($product['Product_ID'])));
			if($data->TotalRows > 0) {
				while($data->Row) {
					$form->AddOption('supplier_' . $product['Product_ID'], $data->Row['Supplier_ID'], sprintf('%s [&pound;%s]', $data->Row['Supplier_Name'], number_format($data->Row['Cost'], 2, '.', ',')), 'S' . (($data->Row['Cost'] > 0) ? '1' : '2'));

					$data->Next();
				}
			} else {
				$data2 = new DataQuery(sprintf("SELECT s.Supplier_ID, IF((LENGTH(TRIM(o.Org_Name)) > 0) AND (LENGTH(TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last))) > 0), CONCAT_WS(' ', TRIM(o.Org_Name), CONCAT('(', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), ')')), IF(LENGTH(TRIM(o.Org_Name)) > 0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS Supplier_Name, sp.Cost FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN supplier_product AS sp ON sp.Supplier_ID=s.Supplier_ID AND sp.Product_ID=%d ORDER BY Supplier_Name ASC", mysql_real_escape_string($product['Product_ID'])));
				while($data2->Row) {
					$form->AddOption('supplier_' . $product['Product_ID'], $data2->Row['Supplier_ID'], sprintf('%s [&pound;%s]', $data2->Row['Supplier_Name'], number_format($data2->Row['Cost'], 2, '.', ',')), 'S' . (($data2->Row['Cost'] > 0) ? '1' : '2'));

					$data2->Next();
				}
				$data2->Disconnect();
			}
			$data->Disconnect();
		}
	}

	if(isset($_REQUEST['confirm'])) {
		if(isset($_REQUEST['purchaserequest'])) {
			$newProducts = array();

			foreach($products as $supplierId=>$productData) {
				foreach($productData as $product) {
					if(!isset($newProducts[$form->GetValue('supplier_' . $product['Product_ID'])])) {
						$newProducts[$form->GetValue('supplier_' . $product['Product_ID'])] = array();	
					}

					$newProducts[$form->GetValue('supplier_' . $product['Product_ID'])][] = $product;
				}
			}

			foreach($newProducts as $supplierId=>$productData) {
				if($supplierId > 0) {
					$request = new PurchaseRequest();
					$request->Status = 'Pending';
					$request->Supplier->ID = $supplierId;
					$request->Add();

					foreach($productData as $product) {
						$requestLine = new PurchaseRequestLine();
						$requestLine->PurchaseRequestID = $request->ID;
						$requestLine->Product->ID = $product['Product_ID'];
						$requestLine->Quantity = $form->GetValue('quantity_' . $product['Product_ID']);
						$requestLine->Add();
					}
				}
			}

			redirect(sprintf('Location: ?action=purchase&products=%s&months=%d&gauge=%d&isstocked=%s', $form->GetValue('products'), $form->GetValue('months'), $form->GetValue('gauge'), $form->GetValue('isstocked')));

		} elseif(isset($_REQUEST['purchaseorder'])) {
			$newProducts = array();

			foreach($products as $supplierId=>$productData) {
				foreach($productData as $product) {
					if(!isset($newProducts[$form->GetValue('supplier_' . $product['Product_ID'])])) {
						$newProducts[$form->GetValue('supplier_' . $product['Product_ID'])] = array();	
					}

					$newProducts[$form->GetValue('supplier_' . $product['Product_ID'])][] = $product;
				}
			}

			foreach($newProducts as $supplierId=>$productData) {
				if($supplierId > 0) {
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

					foreach($productData as $product) {
						$purchaseLine = new PurchaseLine();
						$purchaseLine->Purchase = $purchase->ID;
						$purchaseLine->Quantity = $form->GetValue('quantity_' . $product['Product_ID']);
						$purchaseLine->Product->ID = $product['Product_ID'];
						$purchaseLine->SuppliedBy = $supplier->ID;

						$data = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($supplier->ID), mysql_real_escape_string($product['Product_ID'])));
						$purchaseLine->SKU = $data->Row['Supplier_SKU'];
						$data->Disconnect();

						$data = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($warehouse->ID), mysql_real_escape_string($product['Product_ID'])));
						$purchaseLine->Location = $data->Row['Shelf_Location'];
						$data->Disconnect();

						$prices = array();

						$data = new DataQuery(sprintf("SELECT * FROM supplier_product_price WHERE Supplier_ID=%d AND Product_ID=%d AND Quantity<=%d ORDER BY Created_On ASC", mysql_real_escape_string($supplier->ID), mysql_real_escape_string($product['Product_ID']), mysql_real_escape_string($purchaseLine->Quantity)));
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

						$purchaseLine->QuantityDec = $purchaseLine->Quantity;
						$purchaseLine->Add();
					}
				}
			}

			redirect(sprintf('Location: ?action=purchase&products=%s&months=%d&gauge=%d&isstocked=%s', $form->GetValue('products'), $form->GetValue('months'), $form->GetValue('gauge'), $form->GetValue('isstocked')));
		}
	}
	
	$page = new Page('Top Stock Report', '');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('products');
	echo $form->GetHTML('months');
	echo $form->GetHTML('gauge');
	echo $form->GetHTML('isstocked');
	?>
	
	<h3>Purchase Stock</h3>
	<br />

	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
		<thead>
			<tr>
				<th nowrap="nowrap" width="5%">ID</th>
				<th nowrap="nowrap" width="20%">Name</th>
				<th nowrap="nowrap" width="5%">SKU</th>
				<th nowrap="nowrap" width="10%">Position (Orders)</th>
				<th nowrap="nowrap" width="10%">Position (Quantities)</th>
				<th nowrap="nowrap" width="5%">Orders</th>
				<th nowrap="nowrap" width="5%">Quantity Sold</th>
				<th nowrap="nowrap" width="5%">Stocked</th>
				<th nowrap="nowrap" width="5%">Required</th>
				<th nowrap="nowrap" width="35%">Supplier</th>
			</tr>
		</thead>
		<tbody>

			<?php
			$count = 0;

			foreach($products as $supplierId=>$productData) {
				foreach($productData as $product) {
					if($product['LockedSupplierID'] == 0) {
						?>

						<tr>
							<td><?php echo $product['Product_ID']; ?></td>
							<td><a href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><?php echo $product['Product_Title']; ?></a></td>
							<td><?php echo $product['SKU']; ?></td>
							<td><?php echo $product['Position_Orders_Recent']; ?></td>
							<td><?php echo $product['Position_Quantities_Recent']; ?></td>
							<td><?php echo $product['Total_Orders_3_Month']; ?></td>
							<td><?php echo $product['Total_Quantities_3_Month']; ?></td>
							<td><?php echo $product['Stock']; ?></td>
							<td><?php echo $form->GetHTML('quantity_'.$product['Product_ID']); ?></td>
							<td><?php echo $form->GetHTML('supplier_'.$product['Product_ID']); ?></td>
						</tr>

						<?php
						$count++;
					}
				}
			}
			
			if($count == 0) {
				?>

				<tr>
					<td colspan="6">There are no items available for viewing.</th>
				</tr>

				<?php
			}
			?>

		</tbody>
	</table>
	<br />
		
	<?php
	foreach($products as $supplierId=>$productData) {
		if($supplierId > 0) {
			$supplier = new Supplier($supplierId);
			$supplier->Contact->Get();
			?>
			
			<h3><?php echo $supplier->Contact->Person->GetFullName(); ?></h3>
			<br />
			
			<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
				<thead>
					<tr>
						<th nowrap="nowrap" width="5%">ID</th>
						<th nowrap="nowrap" width="20%">Name</th>
						<th nowrap="nowrap" width="5%">SKU</th>
						<th nowrap="nowrap" width="10%">Position (Orders)</th>
						<th nowrap="nowrap" width="10%">Position (Quantities)</th>
						<th nowrap="nowrap" width="5%">Orders</th>
						<th nowrap="nowrap" width="5%">Quantity Sold</th>
						<th nowrap="nowrap" width="5%">Stocked</th>
						<th nowrap="nowrap" width="5%">Required</th>
						<th nowrap="nowrap" width="35%">Supplier</th>
					</tr>
				</thead>
				<tbody>

					<?php
					foreach($productData as $product) {
						if($product['LockedSupplierID'] == $supplierId) {
							?>

							<tr>
								<td><?php echo $product['Product_ID']; ?></td>
								<td><a href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><?php echo $product['Product_Title']; ?></a></td>
								<td><?php echo $product['SKU']; ?></td>
								<td><?php echo $product['Position_Orders_Recent']; ?></td>
								<td><?php echo $product['Position_Quantities_Recent']; ?></td>
								<td><?php echo $product['Total_Orders_3_Month']; ?></td>
								<td><?php echo $product['Total_Quantities_3_Month']; ?></td>
								<td><?php echo $product['Stock']; ?></td>
								<td><?php echo $form->GetHTML('quantity_'.$product['Product_ID']); ?></td>
								<td><?php echo $form->GetHTML('supplier_'.$product['Product_ID']); ?></td>
							</tr>

							<?php
						}
					}
					?>

				</tbody>
			</table>
			<br />

			<?php
		}
	}

	echo '<input class="btn" type="submit" name="purchaserequest" value="purchase request" /> ';
	echo '<input class="btn" type="submit" name="purchaseorder" value="purchase order" /> ';
	echo $form->Close();

	$page->Display('footer');
}