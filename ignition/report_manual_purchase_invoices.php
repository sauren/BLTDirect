<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DebitStore.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Postage.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
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
	$form->AddField('purchase', 'Purchase ID', 'text', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$purchase = new Purchase();

			if($purchase->Get($form->GetValue('purchase'))) {
	            $purchase->Supplier->Address->Region->GetIDFromString($purchase->Supplier->Address->Region->Name);
				$purchase->Supplier->Address->Country->ID = $purchase->Supplier->Address->Country->GetIDFromString($purchase->Supplier->Address->Country->Name);

				$supplier = new Supplier($purchase->SupplierID);
				$supplier->Contact->Get();

				$charges = array();

				$data = new DataQuery(sprintf("SELECT CategoryID, Charge FROM supplier_category_charge WHERE SupplierID=%s", $supplier->ID));
				while($data->Row) {
					$charges[] = array('Charge' => $data->Row['Charge'], 'Categories' => getCategories($data->Row['CategoryID']));

					$data->Next();
				}
				$data->Disconnect();
			}

			$postage = new Postage(1);

			$weeeQty = 0;

			$data = new DataQuery(sprintf("SELECT pl.*, p.Product_Title, p.Weight, p.Shipping_Class_ID, sc.Shipping_Class_Title FROM purchase_line AS pl LEFT JOIN product AS p ON p.Product_ID=pl.Product_ID LEFT JOIN shipping_class AS sc ON sc.Shipping_Class_ID=p.Shipping_Class_ID WHERE pl.Purchase_ID=%d", $purchase->ID));
			while($data->Row) {
				$form->AddField(sprintf('debit_%d', $data->Row['Product_ID']), sprintf('Debit %s', strip_tags($data->Row['Product_Title'])), 'text', '0', 'float', 1, 11, true, 'size="5"');
				$form->AddField(sprintf('invoice_%d', $data->Row['Product_ID']), sprintf('Invoice of %s', strip_tags($data->Row['Product_Title'])), 'text', '', 'anything', 1, 30, false, 'size="5"');
				$form->AddField(sprintf('date_%d', $data->Row['Product_ID']), sprintf('Date of %s', strip_tags($data->Row['Product_Title'])), 'text', '', 'date_ddmmyyy', 1, 10, false, 'size="10" onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
			    $form->AddField(sprintf('qty_%d', $data->Row['Product_ID']), sprintf('Quantity of %s', strip_tags($data->Row['Product_Title'])), 'text', $data->Row['Quantity'], 'numeric_unsigned', 1, 11, false, 'size="3"');

			    $data2 = new DataQuery(sprintf("SELECT Category_ID FROM product_in_categories WHERE Product_ID=%d", $data->Row['Product_ID']));
				while($data2->Row) {
					foreach($charges as $charge) {
						if($charge['Charge'] > 0) {
							foreach($charge['Categories'] as $category) {
								if($category == $data2->Row['Category_ID']) {
									$weeeQty += $data->Row['Quantity'];

									break(3);
								}
							}
						}
					}

					$data2->Next();
				}
				$data2->Disconnect();

				$data->Next();
			}
			$data->Disconnect();

			$form->AddField('debit_weee', 'Debit WEEE', 'text', '0', 'float', 1, 11, true, 'size="5"');
			$form->AddField('debit_postage', 'Debit Postage', 'text', '0', 'float', 1, 11, true, 'size="5"');
	        $form->AddField('debit_shipping', 'Actual Cost Shipping', 'text', '0', 'float', 1, 11, true, 'size="5"');
			$form->AddField('invoice_weee', 'Invoice of WEEE', 'text', '', 'anything', 1, 30, false, 'size="5"');
			$form->AddField('invoice_postage', 'Invoice of Postage', 'text', '', 'anything', 1, 30, false, 'size="5"');
	        $form->AddField('invoice_shipping', 'Invoice of Shipping', 'text', '', 'anything', 1, 30, false, 'size="5"');
			$form->AddField('date_weee', 'Date of WEEE', 'text', '', 'date_ddmmyyy', 1, 10, false, 'size="10" onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
			$form->AddField('date_postage', 'Date of Postage', 'text', '', 'date_ddmmyyy', 1, 10, false, 'size="10" onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	        $form->AddField('date_shipping', 'Date of Shipping', 'text', '', 'date_ddmmyyy', 1, 10, false, 'size="10" onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
			$form->AddField('qty_weee', 'Quantity WEEE', 'text', $weeeQty, 'numeric_unsigned', 1, 11, false, 'size="3"');
			$form->AddField('qty_postage', 'Quantity of Postage', 'text', 1, 'numeric_unsigned', 1, 11, false, 'size="3"');
	        $form->AddField('qty_shipping', 'Quantity of Shipping', 'text', 1, 'numeric_unsigned', 1, 11, false, 'size="3"');
		}
	}

	if(isset($_REQUEST['confirm'])) {
		if(isset($_REQUEST['debit'])) {
			if($form->Validate()) {
				$store = new DebitStore();
				$store->Supplier->ID = $supplier->ID;
				$store->DebitedOn = $purchase->CreatedOn;

		        $productCost = 0;
				$productWeight = 0;
				$weeeQty = 0;
				$weeeCost = 0;

				$products = array();

		        $data = new DataQuery(sprintf("SELECT pl.*, p.Product_Title, p.Weight, p.Shipping_Class_ID, sc.Shipping_Class_Title FROM purchase_line AS pl LEFT JOIN product AS p ON p.Product_ID=pl.Product_ID LEFT JOIN shipping_class AS sc ON sc.Shipping_Class_ID=p.Shipping_Class_ID WHERE pl.Purchase_ID=%d", mysql_real_escape_string($purchase->ID)));
				while($data->Row) {
		            $productCost += $data->Row['Cost'] * $data->Row['Quantity'];
					$productWeight += $data->Row['Weight'] * $data->Row['Quantity'];

					$products[] = $data->Row;

					$data->Next();
				}
				$data->Disconnect();

	            $shippingCalculator = new SupplierShippingCalculator($purchase->Supplier->Address->Country->ID, $purchase->Supplier->Address->Region->ID, $productCost, $productWeight, $postage->ID, $supplier->ID);

				foreach($products as $product) {
					$shippingCalculator->Add($product['Quantity'], $product['Shipping_Class_ID']);

	                $formDate = $form->GetValue(sprintf('date_%d', $product['Product_ID']));

					$invoiceDate = $purchase->CreatedOn;
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

		            $data3 = new DataQuery(sprintf("SELECT Category_ID FROM product_in_categories WHERE Product_ID=%d", mysql_real_escape_string($product['Product_ID'])));
					while($data3->Row) {
						foreach($charges as $charge) {
							if($charge['Charge'] > 0) {
								foreach($charge['Categories'] as $category) {
									if($category == $data3->Row['Category_ID']) {
										$weeeCost += $charge['Charge'] * $product['Quantity'];
										$weeeQty += $product['Quantity'];

										break(3);
									}
								}
							}
						}

						$data3->Next();
					}
					$data3->Disconnect();
				}

	            $formDate = $form->GetValue('date_weee');

				$invoiceDate = $purchase->CreatedOn;
				$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

				$value = $form->GetValue('debit_weee');

		        if($value > 0) {
					$store->Product->ID = 0;
					$store->Description = sprintf('WEEE charge cost discrepancy from Invoice \'%s\' on %s.', $form->GetValue('invoice_weee'), cDatetime($invoiceDate, 'shortdate'));
					$store->Quantity = $weeeQty;
					$store->Cost = number_format(round($value - ($weeeCost / $weeeQty), 2), 2, '.', '');
					$store->Add();
				}

	            if($form->GetValue('qty_weee') != $weeeQty) {
	                $store->Product->ID = 0;
				    $store->Description = sprintf('WEEE charge quantity discrepancy from Invoice \'%s\' on %s.', $form->GetValue('invoice_weee'), cDatetime($invoiceDate, 'shortdate'));
					$store->Quantity = $form->GetValue('qty_weee') - $weeeQty;
					$store->Cost = number_format(round((($value > 0) ? $value : $weeeCost / $weeeQty), 2), 2, '.', '');
					$store->Add();
				}

	            $formDate = $form->GetValue('date_postage');

				$invoiceDate = $purchase->CreatedOn;
				$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

		        $value = $form->GetValue('debit_postage');

		        if($value > 0) {
					$store->Product->ID = 0;
					$store->Description = sprintf('Postage charge cost discrepancy from Invoice \'%s\' on %s.', $form->GetValue('invoice_postage'), cDatetime($invoiceDate, 'shortdate'));
					$store->Quantity = 1;
					$store->Cost = number_format(round($value - $shippingCalculator->GetTotal(), 2), 2, '.', '');
					$store->Add();
				}

	            if($form->GetValue('qty_postage') != 1) {
					$store->Product->ID = 0;
				    $store->Description = sprintf('Postage charge quantity discrepancy from Invoice \'%s\' on %s.', $form->GetValue('invoice_postage'), cDatetime($invoiceDate, 'shortdate'));
					$store->Quantity = $form->GetValue('qty_postage') - 1;
					$store->Cost = number_format(round((($value > 0) ? $value : $shippingCalculator->GetTotal()), 2), 2, '.', '');
					$store->Add();
				}

	            $formDate = $form->GetValue('date_shipping');

				$invoiceDate = $data->Row['Created_On'];
				$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

	            $value = $form->GetValue('debit_shipping');

		        if($value > 0) {
	                $store->Product->ID = 0;
					$store->Description = sprintf('Postage charge not applicable from Invoice \'%s\' on %s.', $form->GetValue('invoice_shipping'), cDatetime($invoiceDate, 'shortdate'));
					$store->Quantity = $form->GetValue('qty_shipping');
					$store->Cost = number_format(round($value, 2), 2, '.', '');
					$store->Add();
				}

				redirect('Location: debit_store.php');
			}
		} elseif(isset($_REQUEST['query'])) {
	        if($form->Validate()) {
		        $productCost = 0;
				$productWeight = 0;
				$weeeQty = 0;
				$weeeCost = 0;
				
				$queries = array();
				$products = array();

		        $data = new DataQuery(sprintf("SELECT pl.*, p.Product_Title, p.Weight, p.Shipping_Class_ID, sc.Shipping_Class_Title FROM purchase_line AS pl LEFT JOIN product AS p ON p.Product_ID=pl.Product_ID LEFT JOIN shipping_class AS sc ON sc.Shipping_Class_ID=p.Shipping_Class_ID WHERE pl.Purchase_ID=%d", mysql_real_escape_string($purchase->ID)));
				while($data->Row) {
		            $productCost += $data->Row['Cost'] * $data->Row['Quantity'];
					$productWeight += $data->Row['Weight'] * $data->Row['Quantity'];

					$products[] = $data->Row;

					$data->Next();
				}
				$data->Disconnect();

	            $shippingCalculator = new SupplierShippingCalculator($purchase->Supplier->Address->Country->ID, $purchase->Supplier->Address->Region->ID, $productCost, $productWeight, $postage->ID, $supplier->ID);

				foreach($products as $product) {
					$shippingCalculator->Add($product['Quantity'], $product['Shipping_Class_ID']);

	                $formDate = $form->GetValue(sprintf('date_%d', $product['Product_ID']));

					$invoiceDate = $purchase->CreatedOn;
					$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

					$value = $form->GetValue(sprintf('debit_%d', $product['Product_ID']));

					if($value > 0) {
	                    $query = new SupplierInvoiceQuery();
						$query->Supplier->ID = $supplier->ID;
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
						$query->Supplier->ID = $supplier->ID;
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

		            $data3 = new DataQuery(sprintf("SELECT Category_ID FROM product_in_categories WHERE Product_ID=%d", mysql_real_escape_string($product['Product_ID'])));
					while($data3->Row) {
						foreach($charges as $charge) {
							if($charge['Charge'] > 0) {
								foreach($charge['Categories'] as $category) {
									if($category == $data3->Row['Category_ID']) {
										$weeeCost += $charge['Charge'] * $product['Quantity'];
										$weeeQty += $product['Quantity'];

										break(3);
									}
								}
							}
						}

						$data3->Next();
					}
					$data3->Disconnect();
				}

	            $formDate = $form->GetValue('date_weee');

				$invoiceDate = $purchase->CreatedOn;
				$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

				$value = $form->GetValue('debit_weee');

		        if($value > 0) {
					$query = new SupplierInvoiceQuery();
					$query->Supplier->ID = $supplier->ID;
					$query->InvoiceReference = $form->GetValue('invoice_weee');
					$query->InvoiceDate = $invoiceDate;
					$query->Status = 'Pending';
					$query->Product->ID = 0;
					$query->Description = 'WEEE charge cost discrepancy.';
					$query->Quantity = $weeeQty;
					$query->Cost = number_format(round($value - ($weeeCost / $weeeQty), 2), 2, '.', '');
					$query->Total = $query->Cost * $query->Quantity;
	                $query->ChargeStandard = number_format(round(($weeeCost / $weeeQty), 2), 2, '.', '');
					$query->ChargeReceived = number_format(round($value, 2), 2, '.', '');
					$query->Add();
					
					$queries[] = $query->ID;
				}

	            if($form->GetValue('qty_weee') != $weeeQty) {
	                $query = new SupplierInvoiceQuery();
					$query->Supplier->ID = $supplier->ID;
					$query->InvoiceReference = $form->GetValue('invoice_weee');
					$query->InvoiceDate = $invoiceDate;
					$query->Status = 'Pending';
			        $query->Product->ID = 0;
					$query->Description = 'WEEE charge quantity discrepancy.';
	                $query->Quantity = $form->GetValue('qty_weee') - $weeeQty;
					$query->Cost = number_format(round((($value > 0) ? $value : ($weeeCost / $weeeQty)), 2), 2, '.', '');
					$query->Total = $query->Cost * $query->Quantity;
	                $query->ChargeStandard = number_format(round(($weeeCost / $weeeQty), 2), 2, '.', '');
					$query->ChargeReceived = number_format(round($value, 2), 2, '.', '');
					$query->Add();
					
					$queries[] = $query->ID;
				}

	            $formDate = $form->GetValue('date_postage');

				$invoiceDate = $purchase->CreatedOn;
				$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

		        $value = $form->GetValue('debit_postage');

	            if($value > 0) {
					$query = new SupplierInvoiceQuery();
					$query->Supplier->ID = $supplier->ID;
					$query->InvoiceReference = $form->GetValue('invoice_postage');
					$query->InvoiceDate = $invoiceDate;
					$query->Status = 'Pending';
					$query->Product->ID = 0;
					$query->Description = 'Postage charge cost discrepancy.';
					$query->Quantity = 1;
					$query->Cost = number_format(round($value - $shippingCalculator->GetTotal(), 2), 2, '.', '');
					$query->Total = $query->Cost * $query->Quantity;
	                $query->ChargeStandard = number_format(round(($weeeCost / $weeeQty), 2), 2, '.', '');
					$query->ChargeReceived = number_format(round($value, 2), 2, '.', '');
					$query->Add();
					
					$queries[] = $query->ID;
				}

	            if($form->GetValue('qty_postage') != 1) {
	                $query = new SupplierInvoiceQuery();
					$query->Supplier->ID = $supplier->ID;
					$query->InvoiceReference = $form->GetValue('invoice_postage');
					$query->InvoiceDate = $invoiceDate;
					$query->Status = 'Pending';
			        $query->Product->ID = 0;
					$query->Description = 'Postage charge quantity discrepancy.';
	                $query->Quantity = $form->GetValue('qty_postage') - 1;
					$query->Cost = number_format(round((($value > 0) ? $value : $shippingCalculator->GetTotal()), 2), 2, '.', '');
					$query->Total = $query->Cost * $query->Quantity;
	                $query->ChargeStandard = number_format(round($shippingCalculator->GetTotal(), 2), 2, '.', '');
					$query->ChargeReceived = number_format(round($value, 2), 2, '.', '');
					$query->Add();
					
					$queries[] = $query->ID;
				}

	            $formDate = $form->GetValue('date_shipping');

				$invoiceDate = $data->Row['Created_On'];
				$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

	            $value = $form->GetValue('debit_shipping');

		        if($value > 0) {
	                $query = new SupplierInvoiceQuery();
					$query->Supplier->ID = $supplier->ID;
					$query->InvoiceReference = $form->GetValue('invoice_shipping');
					$query->InvoiceDate = $invoiceDate;
					$query->Status = 'Pending';
					$query->Product->ID = 0;
					$query->Description = 'Postage charge not applicable.';
					$query->Quantity = $form->GetValue('qty_shipping');
					$query->Cost = number_format(round($value, 2), 2, '.', '');
					$query->Total = $query->Cost * $query->Quantity;
	                $query->ChargeStandard = number_format(round(0, 2), 2, '.', '');
					$query->ChargeReceived = number_format(round($value, 2), 2, '.', '');
					$query->Add();
					
					$queries[] = $query->ID;
				}

				if(count($queries) > 0) {
					redirect(sprintf('Location: ?action=invoice&queryids=%s', implode(',', $queries)));
				}
			}
		}
	}

	$page = new Page('Manual Purchase Invoices Report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	$window = new StandardWindow("Select purchase order.");
	$webForm = new StandardForm();

	echo $window->Open();
	echo $window->AddHeader('Enter purchase order for this report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('purchase'), $form->GetHTML('purchase') . $form->GetIcon('purchase'));
	echo $webForm->AddRow('', '<input class="btn" type="submit" name="submit" value="submit" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	if(isset($_REQUEST['confirm'])) {
		if($form->Valid) {
			?>

			<br />

			<table width="100%" border="0">
				<tr>
					<td style="border-bottom:1px solid #aaaaaa;" width="6%"><strong>Qty</strong></td>
					<td style="border-bottom:1px solid #aaaaaa;" width="6%"><strong>Actual Qty</strong></td>
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
				$weeeQty = 0;
				$weeeCost = 0;

				$products = array();

				$data = new DataQuery(sprintf("SELECT pl.*, p.Product_Title, p.Weight, p.Shipping_Class_ID, sc.Shipping_Class_Title FROM purchase_line AS pl LEFT JOIN product AS p ON p.Product_ID=pl.Product_ID LEFT JOIN shipping_class AS sc ON sc.Shipping_Class_ID=p.Shipping_Class_ID WHERE pl.Purchase_ID=%d", mysql_real_escape_string($purchase->ID)));
				while($data->Row) {
					$productCost += $data->Row['Cost'] * $data->Row['Quantity'];
					$productWeight += $data->Row['Weight'] * $data->Row['Quantity'];

					$products[] = $data->Row;

					$data->Next();
				}
				$data->Disconnect();

	            $shippingCalculator = new SupplierShippingCalculator($purchase->Supplier->Address->Country->ID, $purchase->Supplier->Address->Region->ID, $productCost, $productWeight, $postage->ID, $supplier->ID);

				foreach($products as $product) {
	                $data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product_in_categories AS pic INNER JOIN product_categories AS pc ON pc.Category_ID=pic.Category_ID WHERE pic.Product_ID=%d", mysql_real_escape_string($product['Product_ID'])));
					$hasCategory = ($data2->Row['Count'] > 0) ? true : false;
					$data2->Disconnect();

					$shippingCalculator->Add($product['Quantity'], $product['Shipping_Class_ID']);
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo $product['Quantity']; ?></td>
						<td><?php echo $form->GetHTML(sprintf('qty_%d', $product['Product_ID'])); ?></td>
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
					$data = new DataQuery(sprintf("SELECT Category_ID FROM product_in_categories WHERE Product_ID=%d", mysql_real_escape_string($product['Product_ID'])));
					while($data->Row) {
						foreach($charges as $charge) {
							if($charge['Charge'] > 0) {
								foreach($charge['Categories'] as $category) {
									if($category == $data->Row['Category_ID']) {
										$weeeCost += $charge['Charge'] * $product['Quantity'];
										$weeeQty += $product['Quantity'];

										break(3);
									}
								}
							}
						}

						$data->Next();
					}
					$data->Disconnect();
				}

				if($weeeCost > 0) {
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo $weeeQty; ?></td>
						<td><?php echo $form->GetHTML('qty_weee'); ?></td>
						<td>WEEE Charge</td>
						<td></td>
						<td></td>
						<td></td>
						<td align="right">&pound;<?php echo number_format($weeeCost / $weeeQty, 2, '.', ','); ?></td>
						<td align="right"><?php echo $form->GetHTML('debit_weee'); ?></td>
						<td align="right"><?php echo $form->GetHTML('invoice_weee'); ?></td>
						<td align="right"><?php echo $form->GetHTML('date_weee'); ?></td>
						<td align="right">&pound;<?php echo number_format($weeeCost, 2, '.', ','); ?></td>
					</tr>

					<?php
				}

	            $shippingCost = $shippingCalculator->GetTotal();

				if($shippingCost > 0) {
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td>1</td>
						<td><?php echo $form->GetHTML('qty_postage'); ?></td>
						<td>Postage Charge (<?php echo $postage->Name; ?>)</td>
						<td></td>
						<td></td>
						<td></td>
						<td align="right">&pound;<?php echo number_format($shippingCost, 2, '.', ','); ?></td>
						<td align="right"><?php echo $form->GetHTML('debit_postage'); ?></td>
						<td align="right"><?php echo $form->GetHTML('invoice_postage'); ?></td>
						<td align="right"><?php echo $form->GetHTML('date_postage'); ?></td>
						<td align="right">&pound;<?php echo number_format($shippingCost, 2, '.', ','); ?></td>
					</tr>

					<?php
				} else {
	                ?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td>0</td>
						<td><?php echo $form->GetHTML('qty_shipping'); ?></td>
						<td>Postage Charge</td>
						<td></td>
						<td></td>
						<td></td>
						<td align="right">&pound;<?php echo number_format(0, 2, '.', ','); ?></td>
						<td align="right"><?php echo $form->GetHTML('debit_shipping'); ?></td>
						<td align="right"><?php echo $form->GetHTML('invoice_shipping'); ?></td>
						<td align="right"><?php echo $form->GetHTML('date_shipping'); ?></td>
						<td align="right">&pound;<?php echo number_format(0, 2, '.', ','); ?></td>
					</tr>

					<?php
				}

				$totalCost = 0;
	            $totalCost += $weeeCost;
				$totalCost += $productCost;
				$totalCost += $shippingCost;
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
					<td></td>
					<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
				</tr>
			</table>
			<br />

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
	
	$page = new Page('Manual Purchase Invoices Report');
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