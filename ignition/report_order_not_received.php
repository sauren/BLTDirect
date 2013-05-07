<?php
require_once('lib/common/app_header.php');

if($action == 'report') {
	$session->Secure(3);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
    $form->AddField('start', 'Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('range', 'Date Range', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('range', 'none', '-- None --');
	$form->AddOption('range', 'all', '-- All --');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'thisminute', 'This Minute');
	$form->AddOption('range', 'thishour', 'This Hour');
	$form->AddOption('range', 'thisday', 'This Day');
	$form->AddOption('range', 'thismonth', 'This Month');
	$form->AddOption('range', 'thisyear', 'This Year');
	$form->AddOption('range', 'thisfinancialyear', 'This Financial Year');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lasthour', 'Last Hour');
	$form->AddOption('range', 'last3hours', 'Last 3 Hours');
	$form->AddOption('range', 'last6hours', 'Last 6 Hours');
	$form->AddOption('range', 'last12hours', 'Last 12 Hours');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastday', 'Last Day');
	$form->AddOption('range', 'last2days', 'Last 2 Days');
	$form->AddOption('range', 'last3days', 'Last 3 Days');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastweek', 'Last Week (Last 7 Days)');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastmonth', 'Last Month');
	$form->AddOption('range', 'last3months', 'Last 3 Months');
	$form->AddOption('range', 'last6months', 'Last 6 Months');
	$form->AddOption('range', 'last12months', 'Last 12 Months');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastyear', 'Last Year');
	$form->AddOption('range', 'last2years', 'Last 2 Years');
	$form->AddOption('range', 'last3years', 'Last 3 Years');
	$form->AddField('supplier', 'Supplier', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddGroup('supplier', 'Y', 'Favourites');
	$form->AddGroup('supplier', 'N', 'Non-Favourites');
	$form->AddOption('supplier', '', '');

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, s.Is_Favourite, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID ORDER BY Supplier_Name ASC"));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier_Name'], $data->Row['Is_Favourite']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 1)) {
			switch($form->GetValue('range')) {
				case 'all': 		$start = date('Y-m-d H:i:s', 0);
				$end = date('Y-m-d H:i:s');
				break;

				case 'thisminute': 	$start = date('Y-m-d H:i:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thishour': 	$start = date('Y-m-d H:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thisday': 	$start = date('Y-m-d 00:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thismonth': 	$start = date('Y-m-01 00:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thisyear': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")));
				$end = date('Y-m-d H:i:s');
				break;
				case 'thisfinancialyear':
					$boundary = date('Y-m-d 00:00:00', mktime(0, 0, 0, 5, 1, date("Y")));

					if(time() < strtotime($boundary)) {
						$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 5, 1, date("Y")-1));
						$end = $boundary;
					} else {
						$start = $boundary;
						$end = date('Y-m-d 00:00:00', mktime(0, 0, 0, 5, 1, date("Y")+1));
					}

					break;

				case 'lasthour': 	$start = date('Y-m-d H:00:00', mktime(date("H")-1, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last3hours': 	$start = date('Y-m-d H:00:00', mktime(date("H")-3, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last6hours': 	$start = date('Y-m-d H:00:00', mktime(date("H")-6, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last12hours': $start = date('Y-m-d H:00:00', mktime(date("H")-12, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;

				case 'lastday': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last2days': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-2, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last3days': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;

				case 'lastweek': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;

				case 'lastmonth': 	$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-1, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;
				case 'last3months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-3, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;
				case 'last6months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-6, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;
				case 'last12months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-12, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;

				case 'lastyear': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-1));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
				case 'last2years': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-2));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
				case 'last3years': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-3));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
			}

            redirect(sprintf("Location: %s?action=report&supplier=%d&start=%s&end=%s", $_SERVER['PHP_SELF'], $form->GetValue('supplier'), $start, $end));
		} else {
			if($form->Validate()) {
				redirect(sprintf("Location: %s?action=report&supplier=%d&start=%s&end=%s", $_SERVER['PHP_SELF'], $form->GetValue('supplier'), sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2))))))));
			}
		}
	}

	$page = new Page('Orders Not Received Report');
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
    echo $window->AddHeader('Select the supplier for this report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHtml('supplier') . $form->GetIcon('supplier'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Select one of the predefined date ranges for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('range'), $form->GetHTML('range'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Or select the date range from below for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start'));
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DebitLine.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Postage.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierShippingCalculator.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start Date', 'hidden', '', 'anything', 1, 19);
	$form->AddField('end', 'End Date', 'hidden', '', 'anything', 1, 19);
	$form->AddField('supplier', 'Supplier', 'hidden', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
            $data = new DataQuery(sprintf("SELECT o.Order_Prefix, o.Order_ID, o2.Shipping_Country_ID, o2.Shipping_Region_ID, o2.Postage_ID, o.Created_On, o2.Order_Prefix AS Parent_Order_Prefix, o2.Order_ID AS Parent_Order_ID, SUM(ol2.Cost * ol.Quantity) AS Total_Cost FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Despatch_ID>0 INNER JOIN orders AS o2 ON o2.Order_ID=o.Parent_ID INNER JOIN order_line AS ol2 ON ol2.Order_ID=o2.Order_ID AND ol2.Despatch_ID>0 AND ol.Product_ID=ol2.Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol2.Despatch_From_ID AND w.Type='S' AND w.Type_Reference_ID=%d WHERE o.Order_Prefix='N' AND o.Created_On>='%s' AND o.Created_On<'%s' GROUP BY o.Order_ID ORDER BY o.Order_ID", mysql_real_escape_string($form->GetValue('supplier')), mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
			if($data->TotalRows > 0) {
	            $supplier = new Supplier($form->GetValue('supplier'));
				$supplier->Contact->Get();

				$debit = new Debit();
				$debit->Prefix = 'N';
				$debit->Supplier->ID = $supplier->ID;
	            $debit->IsPaid = 'N';
				$debit->Status = 'Active';
				$debit->Person = $supplier->Contact->Person;
				$debit->Organisation = $supplier->Contact->Parent->Organisation->Name;
				$debit->Add();

				$shipping = array();

                while($data->Row) {
                    $productCost = 0;
					$productWeight = 0;

					$products = array();

					$data2 = new DataQuery(sprintf("SELECT ol.Product_ID, ol.Product_Title, p.Weight, p.Shipping_Class_ID, ol.Quantity, ol2.Cost FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Despatch_ID>0 LEFT JOIN product AS p ON p.Product_ID=ol.Product_ID INNER JOIN orders AS o2 ON o2.Order_ID=o.Parent_ID INNER JOIN order_line AS ol2 ON ol2.Order_ID=o2.Order_ID AND ol2.Despatch_ID>0 AND ol.Product_ID=ol2.Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol2.Despatch_From_ID AND w.Type='S' AND w.Type_Reference_ID=%d WHERE o.Order_ID=%d ORDER BY ol.Product_ID", mysql_real_escape_string($form->GetValue('supplier')), mysql_real_escape_string($data->Row['Order_ID'])));
					while($data2->Row) {
						$productCost += $data2->Row['Cost'] * $data2->Row['Quantity'];
						$productWeight += $data2->Row['Weight'] * $data2->Row['Quantity'];

						$products[] = $data2->Row;

						$data2->Next();
					}
					$data2->Disconnect();

                    $shippingCalculator = new SupplierShippingCalculator($data->Row['Shipping_Country_ID'], $data->Row['Shipping_Region_ID'], $productCost, $productWeight, $data->Row['Postage_ID'], $form->GetValue('supplier'));

                    foreach($products as $product) {
                    	$shippingCalculator->Add($product['Quantity'], $product['Shipping_Class_ID']);

                        $line = new DebitLine();
		                $line->DebitID = $debit->ID;
						$line->Description = sprintf('%s from %s%d on %s. (Original Order: %s%d)', $product['Product_Title'], $data->Row['Order_Prefix'], $data->Row['Order_ID'], cDatetime($data->Row['Created_On'], 'shortdate'), $data->Row['Parent_Order_Prefix'], $data->Row['Parent_Order_ID']);
						$line->Reason = 'Product not received.';
						$line->Quantity = $product['Quantity'];
						$line->Product->ID = $product['Product_ID'];
						$line->SuppliedBy = $form->GetValue('supplier');
						$line->Cost = $product['Cost'];
						$line->Total = $line->Cost * $line->Quantity;
						$line->Add();

						$debit->Total += $line->Total;
					}

					$shippingCost = (string) $shippingCalculator->GetTotal();

					if(!isset($shipping[$data->Row['Postage_ID']])) {
						$shipping[$data->Row['Postage_ID']] = array();
					}

                    if(!isset($shipping[$data->Row['Postage_ID']][$shippingCost])) {
						$shipping[$data->Row['Postage_ID']][$shippingCost] = 0;
					}

					$shipping[$data->Row['Postage_ID']][$shippingCost]++;

					$data->Next();
				}
				$data->Disconnect();

				foreach($shipping as $postageId=>$shippingCosts) {
					$postage = new Postage($postageId);

					foreach($shippingCosts as $cost=>$quantity) {
						if($cost > 0) {
							$line = new DebitLine();
			                $line->DebitID = $debit->ID;
							$line->Description = sprintf('Shipping charge from original orders on %s postage option.', $postage->Name);
							$line->Quantity = $quantity;
							$line->SuppliedBy = $form->GetValue('supplier');
							$line->Cost = $cost;
							$line->Total = $line->Cost * $line->Quantity;
							$line->Add();

							$debit->Total += $line->Total;
						}
					}
				}

				$debit->Update();

				new DataQuery(sprintf("UPDATE debit SET Created_On='%s' WHERE Debit_ID=%d", date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('end'))), date('d', strtotime($form->GetValue('end'))) - 1, date('Y', strtotime($form->GetValue('end'))))), $debit->ID));

				redirect(sprintf('Location: debit_awaiting_payment.php?action=open&id=%d', $debit->ID));
			}
			$data->Disconnect();

			redirect(sprintf("Location: %s?action=report&supplier=%d&start=%s&end=%s", $_SERVER['PHP_SELF'], $form->GetValue('supplier'), $form->GetValue('start'), $form->GetValue('end')));
		}
	}

	$page = new Page('Orders Not Received Report: ' . cDatetime($date, 'longdate'));
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('start');
	echo $form->GetHTML('end');
	echo $form->GetHTML('supplier');
	?>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Order</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Parent Order</strong></td>
			<td style="border-bottom:1px solid #aaaaaa; text-align: right;"><strong>Total Cost</strong></td>
		</tr>

		<?php
		$total = 0;

		$data = new DataQuery(sprintf("SELECT o.Order_Prefix, o.Order_ID, o.Created_On, o2.Order_Prefix AS Parent_Order_Prefix, o2.Order_ID AS Parent_Order_ID, SUM(ol2.Cost * ol.Quantity) AS Total_Cost FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Despatch_ID>0 INNER JOIN orders AS o2 ON o2.Order_ID=o.Parent_ID INNER JOIN order_line AS ol2 ON ol2.Order_ID=o2.Order_ID AND ol2.Despatch_ID>0 AND ol.Product_ID=ol2.Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol2.Despatch_From_ID AND w.Type='S' AND w.Type_Reference_ID=%d WHERE o.Order_Prefix='N' AND o.Created_On>='%s' AND o.Created_On<'%s' GROUP BY o.Order_ID ORDER BY o.Order_ID", mysql_real_escape_string($form->GetValue('supplier')), mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
		while($data->Row) {
			$total += $data->Row['Total_Cost'];
			?>

            <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><a href="order_details.php?orderid=<?php echo $data->Row['Order_ID']; ?>" target="_blank"><?php echo $data->Row['Order_Prefix']; ?><?php echo $data->Row['Order_ID']; ?></a></td>
				<td><a href="order_details.php?orderid=<?php echo $data->Row['Parent_Order_ID']; ?>" target="_blank"><?php echo $data->Row['Parent_Order_Prefix']; ?><?php echo $data->Row['Parent_Order_ID']; ?></a></td>
				<td align="right">&pound;<?php echo number_format(round($data->Row['Total_Cost'], 2), 2, '.', ','); ?></td>
			</tr>

			<?php
			$data->Next();
		}
		$data->Disconnect();
		?>

        <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td></td>
			<td></td>
			<td align="right"><strong>&pound;<?php echo number_format(round($total, 2), 2, '.', ','); ?></strong></td>
		</tr>
	</table>
	<br />

	<input type="submit" name="debit" value="debit" class="btn" />

	<?php
	echo $form->Close();
}