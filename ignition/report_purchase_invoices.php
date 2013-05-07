<?php
ini_set('max_execution_time', '900');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DebitStore.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierInvoiceQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierShippingCalculator.php');
	
if($action == 'report') {
	$session->Secure(3);
	report();
	exit();
} elseif($action == 'invoice') {
	$session->Secure(3);
	invoice();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('date', 'Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('supplier', 'Supplier', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddGroup('supplier', 'Y', 'Favourites');
	$form->AddGroup('supplier', 'N', 'Non-Favourites');
	$form->AddOption('supplier', '', '');

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, s.Is_Favourite, CONCAT_WS(' ', o.Org_Name, CONCAT('(', CONCAT_WS(' ', p.Name_First, p.Name_Last), ')')) AS Supplier_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID INNER JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID ORDER BY Supplier_Name ASC"));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier_Name'], $data->Row['Is_Favourite']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			redirect(sprintf("Location: %s?action=report&supplier=%d&date=%s", $_SERVER['PHP_SELF'], $form->GetValue('supplier'), sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('date'), 6, 4), substr($form->GetValue('date'), 3, 2), substr($form->GetValue('date'), 0, 2))));
		}
	}

	$page = new Page('Purchase Invoices Report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on purchase invoices.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select the supplier and an invoice date to proceed.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHtml('supplier') . $form->GetIcon('supplier'));
	echo $webForm->AddRow($form->GetLabel('date'), $form->GetHtml('date') . $form->GetIcon('date'));
	echo $webForm->AddRow('', '<input class="btn" type="submit" name="submit" value="submit" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'check', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('date', 'Date', 'hidden', '', 'anything', 1, 19);
	$form->AddField('dateoffset', 'Date Offset', 'hidden', '0', 'numeric_signed', 1, 11);
	$form->AddField('supplier', 'Supplier', 'hidden', '', 'numeric_unsigned', 1, 11);

	$orderCount = 5;

	for($i=1; $i<=$orderCount; $i++) {
		$form->AddField('order_'.$i, 'Order ID (#'.$i.')', 'text', '', 'numeric_unsigned', 1, 11, false);
	}

	$date = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('date'))), date('d', strtotime($form->GetValue('date'))) + $form->GetValue('dateoffset'), date('Y', strtotime($form->GetValue('date')))));

	$supplier = new Supplier($form->GetValue('supplier'));
	$supplier->Contact->Get();

	$shippingCosts = array();

	$data = new DataQuery(sprintf("SELECT * FROM supplier_shipping WHERE Supplier_ID=%d", mysql_real_escape_string($supplier->ID)));
	while($data->Row) {
		$shippingCosts[] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

    $orders = array();

	if(isset($_REQUEST['orders'])) {
		foreach($_REQUEST['orders'] as $orderId) {
			$orders[$orderId] = $orderId;
		}
	}

	$charges = array();

	$data = new DataQuery(sprintf("SELECT CategoryID, Charge FROM supplier_category_charge WHERE SupplierID=%s", mysql_real_escape_string($form->GetValue('supplier'))));
	while($data->Row) {
		$charges[] = array('Charge' => $data->Row['Charge'], 'Categories' => getCategories($data->Row['CategoryID']));

		$data->Next();
	}
	$data->Disconnect();

	$additionalOrders = array();

	for($i=1; $i<=$orderCount; $i++) {
		if(is_numeric($form->GetValue('order_'.$i))) {
			$additionalOrders[] = $form->GetValue('order_'.$i);
			$orders[$form->GetValue('order_'.$i)] = $form->GetValue('order_'.$i);
		}
	}

    $sqlWhere = '';

	if(count($orders) > 0) {
		$sqlWhere .= sprintf(' AND (p.Order_ID=%s)', implode(' OR p.Order_ID=', $orders));
	}

	$data = new DataQuery(sprintf("SELECT p.Purchase_ID, p.Purchased_On, p.Order_ID, o.Shipping_Country_ID, o.Shipping_Region_ID, o.Postage_ID, pg.Postage_Title, c.Country AS Shipping_Country FROM purchase AS p INNER JOIN orders AS o ON o.Order_ID=p.Order_ID LEFT JOIN countries AS c ON c.Country_ID=o.Shipping_Country_ID LEFT JOIN postage AS pg ON pg.Postage_ID=o.Postage_ID WHERE ((p.Purchased_On>='%s' AND p.Purchased_On<ADDDATE('%s', INTERVAL 1 DAY))%s) AND p.Supplier_ID=%d%s ORDER BY p.Order_ID ASC", mysql_real_escape_string($date), mysql_real_escape_string($date), (count($additionalOrders) > 0) ? sprintf(' OR (p.Order_ID=%s)', implode(' OR p.Order_ID=',$additionalOrders)) : '', $form->GetValue('supplier'), $sqlWhere));
	while($data->Row) {
		$weeeQty = 0;

        $data2 = new DataQuery(sprintf("SELECT pl.*, p.Product_Title, p.Weight, p.Shipping_Class_ID, sc.Shipping_Class_Title FROM purchase_line AS pl LEFT JOIN product AS p ON p.Product_ID=pl.Product_ID LEFT JOIN shipping_class AS sc ON sc.Shipping_Class_ID=p.Shipping_Class_ID WHERE pl.Purchase_ID=%d", $data->Row['Purchase_ID']));
		while($data2->Row) {
			$form->AddField(sprintf('debit_%d_%d', $data->Row['Purchase_ID'], $data2->Row['Product_ID']), sprintf('Debit %s for Purchase %d', strip_tags($data2->Row['Product_Title']), $data->Row['Purchase_ID']), 'text', '0', 'float', 1, 11, true, 'size="5"');
			$form->AddField(sprintf('invoice_%d_%d', $data->Row['Purchase_ID'], $data2->Row['Product_ID']), sprintf('Invoice of %s for Purchase %d', strip_tags($data2->Row['Product_Title']), $data->Row['Purchase_ID']), 'text', '', 'anything', 1, 30, false, 'size="5"');
			$form->AddField(sprintf('date_%d_%d', $data->Row['Purchase_ID'], $data2->Row['Product_ID']), sprintf('Date of %s for Purchase %d', strip_tags($data2->Row['Product_Title']), $data->Row['Purchase_ID']), 'text', '', 'date_ddmmyyy', 1, 10, false, 'size="10" onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
            $form->AddField(sprintf('qty_%d_%d', $data->Row['Purchase_ID'], $data2->Row['Product_ID']), sprintf('Quantity of %s for Purchase %d', strip_tags($data2->Row['Product_Title']), $data->Row['Purchase_ID']), 'text', $data2->Row['Quantity'], 'numeric_unsigned', 1, 11, false, 'size="3"');

            $data3 = new DataQuery(sprintf("SELECT Category_ID FROM product_in_categories WHERE Product_ID=%d", $data2->Row['Product_ID']));
			while($data3->Row) {
				foreach($charges as $charge) {
					if($charge['Charge'] > 0) {
						foreach($charge['Categories'] as $category) {
							if($category == $data3->Row['Category_ID']) {
								$weeeQty += $data2->Row['Quantity'];

								break(3);
							}
						}
					}
				}

				$data3->Next();
			}
			$data3->Disconnect();

			$data2->Next();
		}
		$data2->Disconnect();

        $form->AddField(sprintf('debit_%d_weee', $data->Row['Purchase_ID']), sprintf('Actual Cost WEEE for Purchase %d', $data->Row['Purchase_ID']), 'text', '0', 'float', 1, 11, true, 'size="5"');
		$form->AddField(sprintf('debit_%d_postage', $data->Row['Purchase_ID']), sprintf('Actual Cost Postage for Purchase %d', $data->Row['Purchase_ID']), 'text', '0', 'float', 1, 11, true, 'size="5"');
		$form->AddField(sprintf('debit_%d_shipping', $data->Row['Purchase_ID']), sprintf('Actual Cost Shipping for Purchase %d', $data->Row['Purchase_ID']), 'text', '0', 'float', 1, 11, true, 'size="5"');
        $form->AddField(sprintf('invoice_%d_weee', $data->Row['Purchase_ID']), sprintf('Invoice of WEEE for Purchase %d', $data->Row['Purchase_ID']), 'text', '', 'anything', 1, 30, false, 'size="5"');
		$form->AddField(sprintf('invoice_%d_postage', $data->Row['Purchase_ID']), sprintf('Invoice of Postage for Purchase %d', $data->Row['Purchase_ID']), 'text', '', 'anything', 1, 30, false, 'size="5"');
		$form->AddField(sprintf('invoice_%d_shipping', $data->Row['Purchase_ID']), sprintf('Invoice of Shipping for Purchase %d', $data->Row['Purchase_ID']), 'text', '', 'anything', 1, 30, false, 'size="5"');
		$form->AddField(sprintf('date_%d_weee', $data->Row['Purchase_ID']), sprintf('Date of WEEE for Purchase %d', $data->Row['Purchase_ID']), 'text', '', 'date_ddmmyyy', 1, 10, false, 'size="10" onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
        $form->AddField(sprintf('date_%d_postage', $data->Row['Purchase_ID']), sprintf('Date of Postage for Purchase %d', $data->Row['Purchase_ID']), 'text', '', 'date_ddmmyyy', 1, 10, false, 'size="10" onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
        $form->AddField(sprintf('date_%d_shipping', $data->Row['Purchase_ID']), sprintf('Date of Shipping for Purchase %d', $data->Row['Purchase_ID']), 'text', '', 'date_ddmmyyy', 1, 10, false, 'size="10" onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
		$form->AddField(sprintf('qty_%d_weee', $data->Row['Purchase_ID']), sprintf('Quantity WEEE for Purchase %d', $data->Row['Purchase_ID']), 'text', $weeeQty, 'numeric_unsigned', 1, 11, false, 'size="3"');
		$form->AddField(sprintf('qty_%d_postage', $data->Row['Purchase_ID']), sprintf('Quantity of Postage for Purchase %d', $data->Row['Purchase_ID']), 'text', 1, 'numeric_unsigned', 1, 11, false, 'size="3"');
		$form->AddField(sprintf('qty_%d_shipping', $data->Row['Purchase_ID']), sprintf('Quantity of Shipping for Purchase %d', $data->Row['Purchase_ID']), 'text', 1, 'numeric_unsigned', 1, 11, false, 'size="3"');

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if(isset($_REQUEST['debit'])) {
			if($form->Validate()) {
				$store = new DebitStore();
				$store->Supplier->ID = $supplier->ID;

	            $data = new DataQuery(sprintf("SELECT p.Purchase_ID, p.Purchased_On, o.Order_ID, o.Order_Prefix, o.Shipping_Country_ID, o.Shipping_Region_ID, o.Postage_ID, pg.Postage_Title, c.Country AS Shipping_Country FROM purchase AS p INNER JOIN orders AS o ON o.Order_ID=p.Order_ID LEFT JOIN countries AS c ON c.Country_ID=o.Shipping_Country_ID LEFT JOIN postage AS pg ON pg.Postage_ID=o.Postage_ID WHERE ((p.Purchased_On>='%s' AND p.Purchased_On<ADDDATE('%s', INTERVAL 1 DAY))%s) AND p.Supplier_ID=%d%s ORDER BY p.Order_ID ASC", mysql_real_escape_string($date), mysql_real_escape_string($date), (count($additionalOrders) > 0) ? sprintf(' OR (p.Order_ID=%s)', implode(' OR p.Order_ID=', mysql_real_escape_string($additionalOrders))) : '', mysql_real_escape_string($form->GetValue('supplier')), $sqlWhere));
				while($data->Row) {
					$store->DebitedOn = $data->Row['Created_On'];

	                $productCost = 0;
					$productWeight = 0;
					$weeeQty = 0;
					$weeeCost = 0;

					$products = array();

					$data2 = new DataQuery(sprintf("SELECT pl.*, p.Product_Title, p.Weight, p.Shipping_Class_ID, sc.Shipping_Class_Title FROM purchase_line AS pl LEFT JOIN product AS p ON p.Product_ID=pl.Product_ID LEFT JOIN shipping_class AS sc ON sc.Shipping_Class_ID=p.Shipping_Class_ID WHERE pl.Purchase_ID=%d", $data->Row['Purchase_ID']));
					while($data2->Row) {
						$productCost += $data2->Row['Cost'] * $data2->Row['Quantity'];
						$productWeight += $data2->Row['Weight'] * $data2->Row['Quantity'];

						$products[] = $data2->Row;

						$data2->Next();
					}
					$data2->Disconnect();

	                $shippingCalculator = new SupplierShippingCalculator($data->Row['Shipping_Country_ID'], $data->Row['Shipping_Region_ID'], $productCost, $productWeight, $data->Row['Postage_ID'], $supplier->ID);

	                foreach($products as $product) {
						$shippingCalculator->Add($product['Quantity'], $product['Shipping_Class_ID']);

                        $formDate = $form->GetValue(sprintf('date_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID']));

						$invoiceDate = $data->Row['Created_On'];
						$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

                        $value = $form->GetValue(sprintf('debit_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID']));

						if($value > 0) {
		                    $store->Product->ID = $product['Product_ID'];
		                    $store->Description = sprintf('Cost discrepancy for \'%s\' from Invoice \'%s\' on %s. (Original Order: %s%d)', $product['Product_Title'], $form->GetValue(sprintf('invoice_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID'])), cDatetime($invoiceDate, 'shortdate'), $data->Row['Order_Prefix'], $data->Row['Order_ID']);
							$store->Quantity = $product['Quantity'];
							$store->Cost = number_format(round($value - $product['Cost'], 2), 2, '.', '');
							$store->Add();
						}

                        if($form->GetValue(sprintf('qty_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID'])) != $product['Quantity']) {
                            $store->Product->ID = $product['Product_ID'];
			                $store->Description = sprintf('Quantity discrepancy for \'%s\' from Invoice \'%s\' on %s. (Original Order: %s%d)', $product['Product_Title'], $form->GetValue(sprintf('invoice_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID'])), cDatetime($invoiceDate, 'shortdate'), $data->Row['Order_Prefix'], $data->Row['Order_ID']);
							$store->Quantity = $form->GetValue(sprintf('qty_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID'])) - $product['Quantity'];
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

                    $formDate = $form->GetValue(sprintf('date_%d_weee', $data->Row['Purchase_ID']));

					$invoiceDate = $data->Row['Created_On'];
					$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

					$value = $form->GetValue(sprintf('debit_%d_weee', $data->Row['Purchase_ID']));

	                if($value > 0) {
						$store->Product->ID = 0;
						$store->Description = sprintf('WEEE charge cost discrepancy from Invoice \'%s\' on %s. (Original Order: %s%d)', $form->GetValue(sprintf('invoice_%d_weee', $data->Row['Purchase_ID'])), cDatetime($invoiceDate, 'shortdate'), $data->Row['Order_Prefix'], $data->Row['Order_ID']);
						$store->Quantity = $weeeQty;
						$store->Cost = number_format(round($value - ($weeeCost / $weeeQty), 2), 2, '.', '');
						$store->Add();
					}

                    if($form->GetValue(sprintf('qty_%d_weee', $data->Row['Purchase_ID'])) != $weeeQty) {
                        $store->Product->ID = 0;
			            $store->Description = sprintf('WEEE charge quantity discrepancy from Invoice \'%s\' on %s. (Original Order: %s%d)', $form->GetValue(sprintf('invoice_%d_weee', $data->Row['Purchase_ID'])), cDatetime($invoiceDate, 'shortdate'), $data->Row['Order_Prefix'], $data->Row['Order_ID']);
						$store->Quantity = $form->GetValue(sprintf('qty_%d_weee', $data->Row['Purchase_ID'])) - $weeeQty;
						$store->Cost = number_format(round((($value > 0) ? $value : $weeeCost / $weeeQty), 2), 2, '.', '');
						$store->Add();
					}

                    $formDate = $form->GetValue(sprintf('date_%d_postage', $data->Row['Purchase_ID']));

					$invoiceDate = $data->Row['Created_On'];
					$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

	                $value = $form->GetValue(sprintf('debit_%d_postage', $data->Row['Purchase_ID']));

	                if($value > 0) {
						$store->Product->ID = 0;
						$store->Description = sprintf('Postage charge cost discrepancy from Invoice \'%s\' on %s. (Original Order: %s%d)', $form->GetValue(sprintf('invoice_%d_postage', $data->Row['Purchase_ID'])), cDatetime($invoiceDate, 'shortdate'), $data->Row['Order_Prefix'], $data->Row['Order_ID']);
						$store->Quantity = 1;
						$store->Cost = number_format(round($value - $shippingCalculator->GetTotal(), 2), 2, '.', '');
						$store->Add();
					}

                    if($form->GetValue(sprintf('qty_%d_postage', $data->Row['Purchase_ID'])) != 1) {
						$store->Product->ID = 0;
			            $store->Description = sprintf('Postage charge quantity discrepancy from Invoice \'%s\' on %s. (Original Order: %s%d)', $form->GetValue(sprintf('invoice_%d_postage', $data->Row['Purchase_ID'])), cDatetime($invoiceDate, 'shortdate'), $data->Row['Order_Prefix'], $data->Row['Order_ID']);
						$store->Quantity = $form->GetValue(sprintf('qty_%d_postage', $data->Row['Purchase_ID'])) - 1;
						$store->Cost = number_format(round((($value > 0) ? $value : $shippingCalculator->GetTotal()), 2), 2, '.', '');
						$store->Add();
					}

                    $formDate = $form->GetValue(sprintf('date_%d_shipping', $data->Row['Purchase_ID']));

					$invoiceDate = $data->Row['Created_On'];
					$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

                    $value = $form->GetValue(sprintf('debit_%d_shipping', $data->Row['Purchase_ID']));

	                if($value > 0) {
                        $store->Product->ID = 0;
						$store->Description = sprintf('Postage charge not applicable from Invoice \'%s\' on %s. (Original Order: %s%d)', $form->GetValue(sprintf('invoice_%d_shipping', $data->Row['Purchase_ID'])), cDatetime($invoiceDate, 'shortdate'), $data->Row['Order_Prefix'], $data->Row['Order_ID']);
						$store->Quantity = $form->GetValue(sprintf('qty_%d_shipping', $data->Row['Purchase_ID']));
						$store->Cost = number_format(round($value, 2), 2, '.', '');
						$store->Add();
					}

					$data->Next();
				}
				$data->Disconnect();

				redirect('Location: debit_store.php');
			}
		} elseif(isset($_REQUEST['query'])) {
            if($form->Validate()) {
	            $data = new DataQuery(sprintf("SELECT p.Purchase_ID, p.Purchased_On, o.Order_ID, o.Order_Prefix, o.Shipping_Country_ID, o.Shipping_Region_ID, o.Postage_ID, pg.Postage_Title, c.Country AS Shipping_Country FROM purchase AS p INNER JOIN orders AS o ON o.Order_ID=p.Order_ID LEFT JOIN countries AS c ON c.Country_ID=o.Shipping_Country_ID LEFT JOIN postage AS pg ON pg.Postage_ID=o.Postage_ID WHERE ((p.Purchased_On>='%s' AND p.Purchased_On<ADDDATE('%s', INTERVAL 1 DAY))%s) AND p.Supplier_ID=%d%s ORDER BY p.Order_ID ASC", mysql_real_escape_string($date), mysql_real_escape_string($date), (count($additionalOrders) > 0) ? sprintf(' OR (p.Order_ID=%s)', implode(' OR p.Order_ID=', $additionalOrders)) : '', $form->GetValue('supplier'), $sqlWhere));
				while($data->Row) {
	                $productCost = 0;
					$productWeight = 0;
					$weeeQty = 0;
					$weeeCost = 0;
					
					$queries = array();
					$products = array();

					$data2 = new DataQuery(sprintf("SELECT pl.*, p.Product_Title, p.Weight, p.Shipping_Class_ID, sc.Shipping_Class_Title FROM purchase_line AS pl LEFT JOIN product AS p ON p.Product_ID=pl.Product_ID LEFT JOIN shipping_class AS sc ON sc.Shipping_Class_ID=p.Shipping_Class_ID WHERE pl.Purchase_ID=%d", $data->Row['Purchase_ID']));
					while($data2->Row) {
						$productCost += $data2->Row['Cost'] * $data2->Row['Quantity'];
						$productWeight += $data2->Row['Weight'] * $data2->Row['Quantity'];

						$products[] = $data2->Row;

						$data2->Next();
					}
					$data2->Disconnect();

	                $shippingCalculator = new SupplierShippingCalculator($data->Row['Shipping_Country_ID'], $data->Row['Shipping_Region_ID'], $productCost, $productWeight, $data->Row['Postage_ID'], $supplier->ID);

	                foreach($products as $product) {
						$shippingCalculator->Add($product['Quantity'], $product['Shipping_Class_ID']);

                        $formDate = $form->GetValue(sprintf('date_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID']));

						$invoiceDate = $data->Row['Created_On'];
						$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

						$value = $form->GetValue(sprintf('debit_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID']));

                        if($value > 0) {
		                    $query = new SupplierInvoiceQuery();
							$query->Supplier->ID = $supplier->ID;
							$query->InvoiceReference = $form->GetValue(sprintf('invoice_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID']));
							$query->InvoiceDate = $invoiceDate;
							$query->Status = 'Pending';
				            $query->Product->ID = $product['Product_ID'];
							$query->Description = sprintf('Cost discrepancy for \'%s\'. (Original Order: %s%d)', $product['Product_Title'], $data->Row['Order_Prefix'], $data->Row['Order_ID']);
							$query->Quantity = $product['Quantity'];
							$query->Cost = number_format(round($value - $product['Cost'], 2), 2, '.', '');
							$query->Total = $query->Cost * $query->Quantity;
                            $query->ChargeStandard = number_format(round($product['Cost'], 2), 2, '.', '');
							$query->ChargeReceived = number_format(round($value, 2), 2, '.', '');
							$query->Add();
							
							$queries[] = $query->ID;
						}

		                if($form->GetValue(sprintf('qty_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID'])) != $product['Quantity']) {
		                    $query = new SupplierInvoiceQuery();
							$query->Supplier->ID = $supplier->ID;
							$query->InvoiceReference = $form->GetValue(sprintf('invoice_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID']));
							$query->InvoiceDate = $invoiceDate;
							$query->Status = 'Pending';
				            $query->Product->ID = $product['Product_ID'];
							$query->Description = sprintf('Quantity discrepancy for \'%s\'. (Original Order: %s%d)', $product['Product_Title'], $data->Row['Order_Prefix'], $data->Row['Order_ID']);
		                    $query->Quantity = $form->GetValue(sprintf('qty_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID'])) - $product['Quantity'];
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

                    $formDate = $form->GetValue(sprintf('date_%d_weee', $data->Row['Purchase_ID']));

					$invoiceDate = $data->Row['Created_On'];
					$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

					$value = $form->GetValue(sprintf('debit_%d_weee', $data->Row['Purchase_ID']));

                    if($value > 0) {
						$query = new SupplierInvoiceQuery();
						$query->Supplier->ID = $supplier->ID;
						$query->InvoiceReference = $form->GetValue(sprintf('invoice_%d_weee', $data->Row['Purchase_ID']));
						$query->InvoiceDate = $invoiceDate;
						$query->Status = 'Pending';
						$query->Product->ID = 0;
						$query->Description = sprintf('WEEE charge cost discrepancy. (Original Order: %s%d)', $data->Row['Order_Prefix'], $data->Row['Order_ID']);
						$query->Quantity = $weeeQty;
						$query->Cost = number_format(round($value - ($weeeCost / $weeeQty), 2), 2, '.', '');
						$query->Total = $query->Cost * $query->Quantity;
                        $query->ChargeStandard = number_format(round(($weeeCost / $weeeQty), 2), 2, '.', '');
						$query->ChargeReceived = number_format(round($value, 2), 2, '.', '');
						$query->Add();
						
						$queries[] = $query->ID;
					}

		            if($form->GetValue(sprintf('qty_%d_weee', $data->Row['Purchase_ID'])) != $weeeQty) {
		                $query = new SupplierInvoiceQuery();
						$query->Supplier->ID = $supplier->ID;
						$query->InvoiceReference = $form->GetValue(sprintf('invoice_%d_weee', $data->Row['Purchase_ID']));
						$query->InvoiceDate = $invoiceDate;
						$query->Status = 'Pending';
				        $query->Product->ID = 0;
						$query->Description = sprintf('WEEE charge quantity discrepancy. (Original Order: %s%d)', $data->Row['Order_Prefix'], $data->Row['Order_ID']);
		                $query->Quantity = $form->GetValue(sprintf('qty_%d_weee', $data->Row['Purchase_ID'])) - $weeeQty;
						$query->Cost = number_format(round((($value > 0) ? $value : ($weeeCost / $weeeQty)), 2), 2, '.', '');
						$query->Total = $query->Cost * $query->Quantity;
                        $query->ChargeStandard = number_format(round(($weeeCost / $weeeQty), 2), 2, '.', '');
						$query->ChargeReceived = number_format(round($value, 2), 2, '.', '');
						$query->Add();
						
						$queries[] = $query->ID;
					}

					$formDate = $form->GetValue(sprintf('date_%d_postage', $data->Row['Purchase_ID']));

					$invoiceDate = $data->Row['Created_On'];
					$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

	                $value = $form->GetValue(sprintf('debit_%d_postage', $data->Row['Purchase_ID']));

                    if($value > 0) {
						$query = new SupplierInvoiceQuery();
						$query->Supplier->ID = $supplier->ID;
						$query->InvoiceReference = $form->GetValue(sprintf('invoice_%d_postage', $data->Row['Purchase_ID']));
						$query->InvoiceDate = $invoiceDate;
						$query->Status = 'Pending';
						$query->Product->ID = 0;
						$query->Description = sprintf('Postage charge cost discrepancy. (Original Order: %s%d)', $data->Row['Order_Prefix'], $data->Row['Order_ID']);
						$query->Quantity = 1;
						$query->Cost = number_format(round($value - $shippingCalculator->GetTotal(), 2), 2, '.', '');
						$query->Total = $query->Cost * $query->Quantity;
                        $query->ChargeStandard = number_format(round($shippingCalculator->GetTotal(), 2), 2, '.', '');
						$query->ChargeReceived = number_format(round($value, 2), 2, '.', '');
						$query->Add();
						
						$queries[] = $query->ID;
					}

		            if($form->GetValue(sprintf('qty_%d_postage', $data->Row['Purchase_ID'])) != 1) {
		                $query = new SupplierInvoiceQuery();
						$query->Supplier->ID = $supplier->ID;
						$query->InvoiceReference = $form->GetValue(sprintf('invoice_%d_postage', $data->Row['Purchase_ID']));
						$query->InvoiceDate = $invoiceDate;
						$query->Status = 'Pending';
				        $query->Product->ID = 0;
						$query->Description = sprintf('Postage charge quantity discrepancy. (Original Order: %s%d)', $data->Row['Order_Prefix'], $data->Row['Order_ID']);
		                $query->Quantity = $form->GetValue(sprintf('qty_%d_postage', $data->Row['Purchase_ID'])) - 1;
						$query->Cost = number_format(round((($value > 0) ? $value : $shippingCalculator->GetTotal()), 2), 2, '.', '');
						$query->Total = $query->Cost * $query->Quantity;
                        $query->ChargeStandard = number_format(round($shippingCalculator->GetTotal(), 2), 2, '.', '');
						$query->ChargeReceived = number_format(round($value, 2), 2, '.', '');
						$query->Add();
						
						$queries[] = $query->ID;
					}

                    $formDate = $form->GetValue(sprintf('date_%d_shipping', $data->Row['Purchase_ID']));

					$invoiceDate = $data->Row['Created_On'];
					$invoiceDate = !empty($formDate) ? sprintf('%s-%s-%s 00:00:00', substr($formDate, 6, 4), substr($formDate, 3, 2), substr($formDate, 0, 2)) : $invoiceDate;

                    $value = $form->GetValue(sprintf('debit_%d_shipping', $data->Row['Purchase_ID']));

	                if($value > 0) {
                        $query = new SupplierInvoiceQuery();
						$query->Supplier->ID = $supplier->ID;
						$query->InvoiceReference = $form->GetValue(sprintf('invoice_%d_shipping', $data->Row['Purchase_ID']));
						$query->InvoiceDate = $invoiceDate;
						$query->Status = 'Pending';
						$query->Product->ID = 0;
						$query->Description = sprintf('Postage charge not applicable. (Original Order: %s%d)', $data->Row['Order_Prefix'], $data->Row['Order_ID']);
						$query->Quantity = $form->GetValue(sprintf('qty_%d_shipping', $data->Row['Purchase_ID']));
						$query->Cost = number_format(round($value, 2), 2, '.', '');
						$query->Total = $query->Cost * $query->Quantity;
                        $query->ChargeStandard = number_format(round(0, 2), 2, '.', '');
						$query->ChargeReceived = number_format(round($value, 2), 2, '.', '');
						$query->Add();
						
						$queries[] = $query->ID;
					}

					$data->Next();
				}
				$data->Disconnect();

				if(count($queries) > 0) {
					redirect(sprintf('Location: ?action=invoice&queryids=%s', implode(',', $queries)));
				}
			}
		}
	}

	$page = new Page('Purchase Invoices Report: ' . cDatetime($date, 'longdate'));
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('date');
	echo $form->GetHTML('dateoffset');
	echo $form->GetHTML('supplier');

    $window = new StandardWindow("Insert orders.");
	$webForm = new StandardForm();

	echo $window->Open();
	echo $window->AddHeader('Enter additional orders to this report.');
	echo $window->OpenContent();
	echo $webForm->Open();

    for($i=1; $i<=$orderCount; $i++) {
		echo $webForm->AddRow($form->GetLabel('order_'.$i), $form->GetHTML('order_'.$i) . $form->GetIcon('order_'.$i));
	}

	echo $webForm->AddRow('', '<input class="btn" type="submit" name="insert" value="insert" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	?>

	<br />

	<table width="100%">
		<tr>
			<td align="left"><a href="<?php echo sprintf('%s?action=report&supplier=%d&date=%s&dateoffset=%d', $_SERVER['PHP_SELF'], $form->GetValue('supplier'), $form->GetValue('date'), $form->GetValue('dateoffset') - 1); ?>"><img src="images/aztector_1.gif" alt="Previous Day" align="absmiddle" /> Previous Day</a></td>
			<td align="right"><a href="<?php echo sprintf('%s?action=report&supplier=%d&date=%s&dateoffset=%d', $_SERVER['PHP_SELF'], $form->GetValue('supplier'), $form->GetValue('date'), $form->GetValue('dateoffset') + 1); ?>">Next Day <img src="images/aztector_2.gif" alt="Next Day" align="absmiddle" /></a></td>
		</tr>
	</table>
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
		$totalCost = 0;

		$data = new DataQuery(sprintf("SELECT p.Purchase_ID, p.Order_ID, o.Shipping_Country_ID, o.Shipping_Region_ID, o.Postage_ID, pg.Postage_Title, c.Country AS Shipping_Country FROM purchase AS p INNER JOIN orders AS o ON o.Order_ID=p.Order_ID LEFT JOIN countries AS c ON c.Country_ID=o.Shipping_Country_ID LEFT JOIN postage AS pg ON pg.Postage_ID=o.Postage_ID WHERE ((p.Purchased_On>='%s' AND p.Purchased_On<ADDDATE('%s', INTERVAL 1 DAY))%s) AND p.Supplier_ID=%d%s ORDER BY p.Order_ID ASC", mysql_real_escape_string($date), mysql_real_escape_string($date), (count($additionalOrders) > 0) ? sprintf(' OR (p.Order_ID=%s)', implode(' OR p.Order_ID=', $additionalOrders)) : '', $form->GetValue('supplier'), $sqlWhere));
		while($data->Row) {
			?>

			<tr>
				<td></td>
				<td></td>
				<td><input type="checkbox" name="orders[]" value="<?php echo $data->Row['Order_ID']; ?>" <?php echo isset($orders[$data->Row['Order_ID']]) ? 'checked="checked"' : '';?> /> <strong>Order: <a href="order_details.php?orderid=<?php echo $data->Row['Order_ID']; ?>" target="_blank"><?php echo $data->Row['Order_ID']; ?></a></strong> - <?php echo $data->Row['Shipping_Country']; ?></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>

			<?php
			$productCost = 0;
			$productWeight = 0;
			$weeeQty = 0;
			$weeeCost = 0;

			$products = array();

			$data2 = new DataQuery(sprintf("SELECT pl.*, p.Product_Title, p.Weight, p.Shipping_Class_ID, sc.Shipping_Class_Title FROM purchase_line AS pl LEFT JOIN product AS p ON p.Product_ID=pl.Product_ID LEFT JOIN shipping_class AS sc ON sc.Shipping_Class_ID=p.Shipping_Class_ID WHERE pl.Purchase_ID=%d", $data->Row['Purchase_ID']));
			while($data2->Row) {
				$productCost += $data2->Row['Cost'] * $data2->Row['Quantity'];
				$productWeight += $data2->Row['Weight'] * $data2->Row['Quantity'];

				$products[] = $data2->Row;

				$data2->Next();
			}
			$data2->Disconnect();

			$shippingCalculator = new SupplierShippingCalculator($data->Row['Shipping_Country_ID'], $data->Row['Shipping_Region_ID'], $productCost, $productWeight, $data->Row['Postage_ID'], $supplier->ID);

			foreach($products as $product) {
				$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product_in_categories AS pic INNER JOIN product_categories AS pc ON pc.Category_ID=pic.Category_ID WHERE pic.Product_ID=%d", mysql_real_escape_string($product['Product_ID'])));
				$hasCategory = ($data2->Row['Count'] > 0) ? true : false;
				$data2->Disconnect();

				$shippingCalculator->Add($product['Quantity'], $product['Shipping_Class_ID']);
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $product['Quantity']; ?></td>
					<td><?php echo $form->GetHTML(sprintf('qty_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID'])); ?></td>
					<td><?php echo $product['Product_Title']; ?></td>
					<td><a href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>" target="_blank"><?php echo $product['Product_ID']; ?></a></td>
					<td><?php echo $product['Shipping_Class_Title']; ?></td>
					<td align="center"><?php echo $hasCategory ? '' : 'No'; ?></td>
					<td align="right">&pound;<?php echo number_format($product['Cost'], 2, '.', ','); ?></td>
					<td align="right"><?php echo $form->GetHTML(sprintf('debit_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID'])); ?></td>
					<td align="right"><?php echo $form->GetHTML(sprintf('invoice_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID'])); ?></td>
					<td align="right"><?php echo $form->GetHTML(sprintf('date_%d_%d', $data->Row['Purchase_ID'], $product['Product_ID'])); ?></td>
					<td align="right">&pound;<?php echo number_format($product['Cost'] * $product['Quantity'], 2, '.', ','); ?></td>
				</tr>

				<?php
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

			if($weeeCost > 0) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $weeeQty; ?></td>
					<td><?php echo $form->GetHTML(sprintf('qty_%d_weee', $data->Row['Purchase_ID'])); ?></td>
					<td>WEEE Charge</td>
					<td></td>
					<td></td>
					<td></td>
					<td align="right">&pound;<?php echo number_format($weeeCost / $weeeQty, 2, '.', ','); ?></td>
					<td align="right"><?php echo $form->GetHTML(sprintf('debit_%d_weee', $data->Row['Purchase_ID'])); ?></td>
					<td align="right"><?php echo $form->GetHTML(sprintf('invoice_%d_weee', $data->Row['Purchase_ID'])); ?></td>
					<td align="right"><?php echo $form->GetHTML(sprintf('date_%d_weee', $data->Row['Purchase_ID'])); ?></td>
					<td align="right">&pound;<?php echo number_format($weeeCost, 2, '.', ','); ?></td>
				</tr>

				<?php
			}

			$shippingCost = $shippingCalculator->GetTotal();

			if($shippingCost > 0) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td>1</td>
					<td><?php echo $form->GetHTML(sprintf('qty_%d_postage', $data->Row['Purchase_ID'])); ?></td>
					<td>Postage Charge (<?php echo $data->Row['Postage_Title']; ?>)</td>
					<td></td>
					<td></td>
					<td></td>
					<td align="right">&pound;<?php echo number_format($shippingCost, 2, '.', ','); ?></td>
					<td align="right"><?php echo $form->GetHTML(sprintf('debit_%d_postage', $data->Row['Purchase_ID'])); ?></td>
					<td align="right"><?php echo $form->GetHTML(sprintf('invoice_%d_postage', $data->Row['Purchase_ID'])); ?></td>
					<td align="right"><?php echo $form->GetHTML(sprintf('date_%d_postage', $data->Row['Purchase_ID'])); ?></td>
					<td align="right">&pound;<?php echo number_format($shippingCost, 2, '.', ','); ?></td>
				</tr>

				<?php
			} else {
                ?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td>0</td>
					<td><?php echo $form->GetHTML(sprintf('qty_%d_shipping', $data->Row['Purchase_ID'])); ?></td>
					<td>Postage Charge</td>
					<td></td>
					<td></td>
					<td></td>
					<td align="right">&pound;<?php echo number_format(0, 2, '.', ','); ?></td>
					<td align="right"><?php echo $form->GetHTML(sprintf('debit_%d_shipping', $data->Row['Purchase_ID'])); ?></td>
					<td align="right"><?php echo $form->GetHTML(sprintf('invoice_%d_shipping', $data->Row['Purchase_ID'])); ?></td>
					<td align="right"><?php echo $form->GetHTML(sprintf('date_%d_shipping', $data->Row['Purchase_ID'])); ?></td>
					<td align="right">&pound;<?php echo number_format(0, 2, '.', ','); ?></td>
				</tr>

				<?php
			}

			$totalCost += $weeeCost;
			$totalCost += $productCost;
			$totalCost += $shippingCost;

			$data->Next();
		}
		$data->Disconnect();
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

	<?php
	if(count($orders) > 0) {
		$hiddenOrders = array();

		$sqlWhere = sprintf(' AND (p.Order_ID<>%s)', implode(' AND p.Order_ID<>', $orders));

		$data = new DataQuery(sprintf("SELECT p.Order_ID FROM purchase AS p INNER JOIN orders AS o ON o.Order_ID=p.Order_ID WHERE p.Purchased_On>='%s' AND p.Purchased_On<ADDDATE('%s', INTERVAL 1 DAY) AND p.Supplier_ID=%d%s ORDER BY p.Order_ID ASC", mysql_real_escape_string($date), mysql_real_escape_string($date), mysql_real_escape_string($form->GetValue('supplier')), $sqlWhere));
		while($data->Row) {
			$hiddenOrders[] = $data->Row;

			$data->Next();
		}
		$data->Disconnect();
		?>

		<table width="100%" border="0">
			<tr>
				<td style="border-bottom:1px solid #aaaaaa;" width="1%">&nbsp;</td>
				<td style="border-bottom:1px solid #aaaaaa;" width="49%"><strong>Order</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" width="1%">&nbsp;</td>
				<td style="border-bottom:1px solid #aaaaaa;" width="49%"><strong>Order</strong></td>
			</tr>

			<?php
			foreach($hiddenOrders as $order) {
				?>

				<tr>
					<td><input type="checkbox" name="orders[]" value="<?php echo $order['Order_ID']; ?>" /></td>
					<td><?php echo $order['Order_ID']; ?></td>
				</tr>

				<?php
			}
			?>

		</table>
		<br />

		<?php
	}

	echo '<input type="submit" name="filter" value="filter" class="btn" /> ';
	echo '<input type="submit" name="debit" value="debit" class="btn" /> ';
	echo '<input type="submit" name="query" value="query" class="btn" /> ';

	if(count($orders) > 0) {
		echo sprintf('<input type="button" name="removefilter" value="remove filter" class="btn" onclick="window.self.location.href = \'%s?action=report&supplier=%d&date=%s&dateoffset=%d\';" />', mysql_real_escape_string($_SERVER['PHP_SELF']), mysql_real_escape_string($form->GetValue('supplier')), mysql_real_escape_string($form->GetValue('date')), mysql_real_escape_string($form->GetValue('dateoffset')));
	}

	echo $form->Close();
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
				new DataQuery(sprintf("UPDATE supplier_invoice_query SET InvoiceAmount=%f WHERE InvoiceReference LIKE '%s'", $form->GetValue(sprintf('amount_%s', mysql_real_escape_string($reference))), mysql_real_escape_string($reference)));
			}
			
			redirect('Location: ?action=start');
		}	
	}
	
	$page = new Page('Purchase Invoices Report');
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