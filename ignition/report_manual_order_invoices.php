<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DebitStore.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Postage.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierInvoiceQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierShippingCalculator.php');

if($action == 'invoice') {
	$session->Secure(3);
	invoice();
	exit();
} else {
	$session->Secure(3);
	report();
	exit();
}

function getCategories($id) {
	$categories = array($id);

	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($id)));
	while($data->Row) {
		$categories = array_merge($categories, getCategories($data->Row['Category_ID']));

		$data->Next();
	}
	$data->Disconnect();

	return $categories;
}

function report() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('order', 'Order ID', 'text', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$order = new Order($form->GetValue('order'));

			$data = new DataQuery(sprintf("SELECT ol.*, p.Product_Title, p.Weight, p.Shipping_Class_ID, sc.Shipping_Class_Title FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' LEFT JOIN product AS p ON p.Product_ID=ol.Product_ID LEFT JOIN shipping_class AS sc ON sc.Shipping_Class_ID=p.Shipping_Class_ID WHERE ol.Order_ID=%d", mysql_real_escape_string($order->ID)));
			while($data->Row) {
				$form->AddField(sprintf('debit_%d', $data->Row['Product_ID']), sprintf('Debit %s', strip_tags($data->Row['Product_Title'])), 'text', '0', 'float', 1, 11, true, 'size="5"');
				$form->AddField(sprintf('invoice_%d', $data->Row['Product_ID']), sprintf('Invoice of %s', strip_tags($data->Row['Product_Title'])), 'text', '', 'anything', 1, 30, false, 'size="5"');
				$form->AddField(sprintf('date_%d', $data->Row['Product_ID']), sprintf('Date of %s', strip_tags($data->Row['Product_Title'])), 'text', '', 'date_ddmmyyy', 1, 10, false, 'size="10" onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
			    $form->AddField(sprintf('qty_%d', $data->Row['Product_ID']), sprintf('Quantity of %s', strip_tags($data->Row['Product_Title'])), 'text', $data->Row['Quantity'], 'numeric_unsigned', 1, 11, false, 'size="3"');

				$data->Next();
			}
			$data->Disconnect();
		}
	}
	
	if(isset($_REQUEST['confirm'])) {
		if(isset($_REQUEST['debit'])) {
			if($form->Validate()) {
				$store = new DebitStore();
				$store->DebitedOn = $order->CreatedOn;

				$products = array();

		       	$data2 = new DataQuery(sprintf("SELECT ol.*, w.Type_Reference_ID, p.Product_Title, p.Weight, p.Shipping_Class_ID, sc.Shipping_Class_Title FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' LEFT JOIN product AS p ON p.Product_ID=ol.Product_ID LEFT JOIN shipping_class AS sc ON sc.Shipping_Class_ID=p.Shipping_Class_ID WHERE ol.Order_ID=%d", mysql_real_escape_string($order->ID)));
				while($data2->Row) {
					$products[] = $data2->Row;

					$data2->Next();
				}
				$data2->Disconnect();
				
				foreach($products as $product) {
					$store->Supplier->ID = $product['Type_Reference_ID'];
					
	                $formDate = $form->GetValue(sprintf('date_%d', $product['Product_ID']));

					$invoiceDate = $order->CreatedOn;
					$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

					$value = $form->GetValue(sprintf('debit_%d', $product['Product_ID']));

					if($value > 0) {
						$store->Product->ID = $product['Product_ID'];
						$store->Description = sprintf('Cost discrepancy for \'%s\' from Invoice \'%s\' on %s.', $product['Product_Title'], $form->GetValue(sprintf('invoice_%d', $product['Product_ID'])), cDatetime($invoiceDate, 'shortdate'));
						$store->Quantity = $product['Quantity'];
						$store->Cost = number_format(round($value - $product['Cost'], 2), 2, '.', '');
						$store->Add();
					}

	                if($form->GetValue(sprintf('qty_%d', $product['Product_ID'])) != $product['Quantity']) {
            			$store->Product->ID = $product['Product_ID'];
				        $store->Description = sprintf('Quantity discrepancy for \'%s\' from Invoice \'%s\' on %s.', $product['Product_Title'], $form->GetValue(sprintf('invoice_%d', $product['Product_ID'])), cDatetime($invoiceDate, 'shortdate'));
						$store->Quantity = $form->GetValue(sprintf('qty_%d', $product['Product_ID'])) - $product['Quantity'];
						$store->Cost = number_format(round((($value > 0) ? $value : $product['Cost']), 2), 2, '.', '');
						$store->Add();
					}
				}

				redirect('Location: debit_store.php');
			}
		} elseif(isset($_REQUEST['query'])) {
	        if($form->Validate()) {
				$queries = array();

				$products = array();

		       	$data2 = new DataQuery(sprintf("SELECT ol.*, w.Type_Reference_ID, p.Product_Title, p.Weight, p.Shipping_Class_ID, sc.Shipping_Class_Title FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' LEFT JOIN product AS p ON p.Product_ID=ol.Product_ID LEFT JOIN shipping_class AS sc ON sc.Shipping_Class_ID=p.Shipping_Class_ID WHERE ol.Order_ID=%d", $order->ID));
				while($data2->Row) {
					$products[] = $data2->Row;

					$data2->Next();
				}
				$data2->Disconnect();
				
				foreach($products as $product) {
	                $formDate = $form->GetValue(sprintf('date_%d', $product['Product_ID']));

					$invoiceDate = $order->CreatedOn;
					$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

					$value = $form->GetValue(sprintf('debit_%d', $product['Product_ID']));

					if($value > 0) {
	                    $query = new SupplierInvoiceQuery();
						$query->Supplier->ID = $product['Type_Reference_ID'];
						$query->InvoiceReference = $form->GetValue(sprintf('invoice_%d', $product['Product_ID']));
						$query->InvoiceDate = $invoiceDate;
						$query->Status = 'Pending';
			            $query->Product->ID = $product['Product_ID'];
						$query->Description = sprintf('Cost discrepancy for \'%s\'.', $product['Product_Title']);
						$query->Quantity = $product['Quantity'];
						$query->Cost = number_format(round($value - $product['Cost'], 2), 2, '.', '');
						$query->Total = $query->Cost * $query->Quantity;
						$query->ChargeStandard = number_format(round($product['Cost'], 2), 2, '.', '');
						$query->ChargeReceived = number_format(round($value, 2), 2, '.', '');
						$query->Add();
						
						$queries[] = $query->ID;
					}

	                if($form->GetValue(sprintf('qty_%d', $product['Product_ID'])) != $product['Quantity']) {
	                    $query = new SupplierInvoiceQuery();
						$query->Supplier->ID = $product['Type_Reference_ID'];
						$query->InvoiceReference = $form->GetValue(sprintf('invoice_%d', $product['Product_ID']));
						$query->InvoiceDate = $invoiceDate;
						$query->Status = 'Pending';
			            $query->Product->ID = $product['Product_ID'];
						$query->Description = sprintf('Quantity discrepancy for \'%s\'.', $product['Product_Title']);
	                    $query->Quantity = $form->GetValue(sprintf('qty_%d', $product['Product_ID'])) - $product['Quantity'];
						$query->Cost = number_format(round((($value > 0) ? $value : $product['Cost']), 2), 2, '.', '');
						$query->Total = $query->Cost * $query->Quantity;
						$query->ChargeStandard = number_format(round($product['Cost'], 2), 2, '.', '');
						$query->ChargeReceived = number_format(round($value, 2), 2, '.', '');
						$query->Add();
						
						$queries[] = $query->ID;
					}
				}

				if(count($queries) > 0) {
					redirect(sprintf('Location: ?action=invoice&queryids=%s', implode(',', $queries)));
				}
			}
		}
	}
	
	$page = new Page('Manual Order Invoices Report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	$window = new StandardWindow("Select order.");
	$webForm = new StandardForm();

	echo $window->Open();
	echo $window->AddHeader('Enter order for this report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('order'), $form->GetHTML('order') . $form->GetIcon('order'));
	echo $webForm->AddRow('', '<input class="btn" type="submit" name="submit" value="submit" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	if(isset($_REQUEST['confirm'])) {
		if($form->Valid) {
			echo '<br />';

	        $data = new DataQuery(sprintf("SELECT w.Warehouse_ID, w.Warehouse_Name FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' WHERE ol.Order_ID=%d GROUP BY w.Warehouse_ID ORDER BY w.Warehouse_Name", $order->ID));
	        while($data->Row) {
				?>

				<h3><?php echo $data->Row['Warehouse_Name']; ?></h3>
				<br />

				<table width="100%" border="0">
					<tr>
						<td style="border-bottom:1px solid #aaaaaa;" width="12%"><strong>Qty</strong></td>
						<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
						<td style="border-bottom:1px solid #aaaaaa;" width="8%"><strong>Quickfind</strong></td>
						<td style="border-bottom:1px solid #aaaaaa;" width="16%"><strong>Shipping Class</strong></td>
						<td style="border-bottom:1px solid #aaaaaa; text-align: center;" width="8%"><strong>Has Category</strong></td>
						<td style="border-bottom:1px solid #aaaaaa; text-align: right;" width="8%"><strong>Cost</strong></td>
						<td style="border-bottom:1px solid #aaaaaa;"><strong>Actual Cost</strong></td>
						<td style="border-bottom:1px solid #aaaaaa;"><strong>Invoice</strong></td>
						<td style="border-bottom:1px solid #aaaaaa;"><strong>Date</strong></td>
						<td style="border-bottom:1px solid #aaaaaa; text-align: right;" width="12%"><strong>Total Cost</strong></td>
					</tr>

					<?php
					$productCost = 0;
					$productWeight = 0;

					$products = array();

					$data2 = new DataQuery(sprintf("SELECT ol.*, p.Product_Title, p.Weight, p.Shipping_Class_ID, sc.Shipping_Class_Title FROM order_line AS ol LEFT JOIN product AS p ON p.Product_ID=ol.Product_ID LEFT JOIN shipping_class AS sc ON sc.Shipping_Class_ID=p.Shipping_Class_ID WHERE ol.Order_ID=%d AND ol.Despatch_From_ID=%d", $order->ID, $data->Row['Warehouse_ID']));
					while($data2->Row) {
						$productCost += $data2->Row['Cost'] * $data2->Row['Quantity'];
						$productWeight += $data2->Row['Weight'] * $data2->Row['Quantity'];

						$products[] = $data2->Row;

						$data2->Next();
					}
					$data2->Disconnect();
					
					foreach($products as $product) {
		                $data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product_in_categories AS pic INNER JOIN product_categories AS pc ON pc.Category_ID=pic.Category_ID WHERE pic.Product_ID=%d", $product['Product_ID']));
						$hasCategory = ($data2->Row['Count'] > 0) ? true : false;
						$data2->Disconnect();
						?>

						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td><?php echo $product['Quantity']; ?></td>
							<td><?php echo $product['Product_Title']; ?></td>
							<td><a href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>" target="_blank"><?php echo $product['Product_ID']; ?></a></td>
							<td><?php echo $product['Shipping_Class_Title']; ?></td>
							<td align="center"><?php echo $hasCategory ? '' : 'No'; ?></td>
							<td align="right">&pound;<?php echo number_format($product['Cost'], 2, '.', ','); ?></td>
							<td align="right"><?php echo $form->GetHTML(sprintf('debit_%d', $product['Product_ID'])); ?></td>
							<td align="right"><?php echo $form->GetHTML(sprintf('invoice_%d', $product['Product_ID'])); ?></td>
							<td align="right"><?php echo $form->GetHTML(sprintf('date_%d', $product['Product_ID'])); ?></td>
							<td align="right">&pound;<?php echo number_format($product['Cost'] * $product['Quantity'], 2, '.', ','); ?></td>
						</tr>

						<?php
					}

					$totalCost = 0;
					$totalCost += $productCost;
					?>

		            <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>						
						<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
					</tr>
				</table>
				<br />

				<?php
				$data->Next();
			}
			$data->Disconnect();
			?>
			
			<input type="submit" name="debit" value="debit" class="btn" />
			<input type="submit" name="query" value="query" class="btn" />
			
			<?php
		}
	}

	echo $form->Close();

	echo $page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function invoice() {
	if(!isset($_REQUEST['queryids'])) {
		redirect('Location: ?action=report');
	}
	
	$queries = explode(',', $_REQUEST['queryids']);
	$referencedQueries = array();
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'invoice', 'alpha', 7, 7);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('queryids', 'Query IDs', 'hidden', '', 'anything', 1, 2048);
	
	foreach($queries as $queryId) {
		$query = new SupplierInvoiceQuery($queryId);
		
		if(!empty($query->InvoiceReference)) {
			$reference = trim($query->InvoiceReference);
			
			if(!isset($referencedQueries[$reference])) {
				$referencedQueries[$reference] = $reference;
			
				$form->AddField(sprintf('amount_%s', $reference), sprintf('Invoice Amount for Query Refence \'%s\'', $reference), 'text', '', 'float', 1, 11);	
			}
		}
	}
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			foreach($referencedQueries as $reference) {
				new DataQuery(sprintf("UPDATE supplier_invoice_query SET InvoiceAmount=%f WHERE InvoiceReference LIKE '%s'", $form->GetValue(sprintf('amount_%s', $reference)), $reference));
			}
			
			redirect('Location: ?action=report');
		}	
	}
	
	$page = new Page('Manual Order Invoices Report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('queryids');

	$window = new StandardWindow("Add additional information.");
	$webForm = new StandardForm();

	echo $window->Open();
	echo $window->AddHeader('Enter invoice amounts for these invoice queries.');
	echo $window->OpenContent();
	echo $webForm->Open();

	foreach($referencedQueries as $reference) {
		echo $webForm->AddRow(sprintf('%s', $reference), $form->GetHTML(sprintf('amount_%s', $reference)) . $form->GetIcon(sprintf('amount_%s', $reference)));
	}
	
	echo $webForm->AddRow('', '<input class="btn" type="submit" name="submit" value="submit" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
}