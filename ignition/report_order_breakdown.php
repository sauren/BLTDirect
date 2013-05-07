<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'Report End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('range', 'Date range', 'select', 'none', 'alpha_numeric', 0, 32);
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
	$form->AddField('percent', 'Telesales Google PPC Ratio', 'select', '30', 'numeric_unsigned', 1, 11);

	for($i=0; $i<=100; $i=$i+=5) {
		$form->AddOption('percent', $i, sprintf('%d%%', $i));
	}

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

			report($start, $end, $form->GetValue('percent'));
			exit;
		} else {

			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('percent'));
				exit;
			}
		}
	}

	$page = new Page('Order Breakdown Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Order Breakdown.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
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
	echo $window->AddHeader('Select the ratio of telesales orders derived frmo Google PPC.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('percent'), $form->GetHTML('percent'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($start, $end, $percent) {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$page = new Page('Order Breakdown Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');
	?>

	<br />
	<h3>Orders</h3>
	<p>Order statistics on the following order types with a <strong><?php echo $percent; ?>%</strong> Google PPC telesales order ratio.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Type</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Orders</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>% Orders</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Sub Total</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Discounts</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Net</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Shipping</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Tax</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Gross</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Profit</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>% Profit</strong></td>
		</tr>

		<?php
		$connections = getSyncConnections();

		$totalOrders = 0;
		$totalSubTotal = 0;
		$totalDiscount = 0;
		$totalShipping = 0;
		$totalTax = 0;
		$totalGross = 0;
		$totalProfit = 0;
		?>

		<?php
		$orders['NewWebGooglePPC'] = 0;
		$subTotal['NewWebGooglePPC'] = 0;
		$discount['NewWebGooglePPC'] = 0;
		$shipping['NewWebGooglePPC'] = 0;
		$tax['NewWebGooglePPC'] = 0;
		$total['NewWebGooglePPC'] = 0;
		$profit['NewWebGooglePPC'] = 0;

		for($i=0; $i<count($connections); $i++) {
			$ordersArr = array();

			$data = new DataQuery(sprintf("SELECT o.Order_ID, o.SubTotal, o.TotalShipping, o.TotalTax, o.TotalDiscount, o.Total FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID LEFT JOIN orders AS o2 ON o2.Customer_ID=c.Customer_ID AND o2.Order_ID<>o.Order_ID AND o2.Created_On<o.Created_On WHERE o.Order_Prefix IN ('W', 'U', 'L', 'M') AND o.Referrer LIKE '%%Google-PPC%%' AND o2.Order_ID IS NULL AND o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Order_ID", $start, $end), $connections[$i]['Connection']);
			while($data->Row) {
				$subTotal['NewWebGooglePPC'] += $data->Row['SubTotal'];
				$shipping['NewWebGooglePPC'] += $data->Row['TotalShipping'];
				$tax['NewWebGooglePPC'] += $data->Row['TotalTax'];
				$discount['NewWebGooglePPC'] += $data->Row['TotalDiscount'];
				$total['NewWebGooglePPC'] += $data->Row['Total'];
				$orders['NewWebGooglePPC']++;

				$ordersArr[] = $data->Row['Order_ID'];

				$data->Next();
			}
			$data->Disconnect();

			if(count($ordersArr) > 0) {
				$data = new DataQuery(sprintf("SELECT SUM(ol.Line_Total - (ol.Cost * ol.Quantity)) AS Profit FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID LEFT JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE (o.Order_ID=%s) GROUP BY o.Order_ID", implode(' OR o.Order_ID=', $ordersArr)), $connections[$i]['Connection']);
				while($data->Row) {
					$profit['NewWebGooglePPC'] += $data->Row['Profit'];

					$data->Next();
				}
				$data->Disconnect();
			}
		}

		$totalSubTotal += $subTotal['NewWebGooglePPC'];
		$totalDiscount += $discount['NewWebGooglePPC'];
		$totalShipping += $shipping['NewWebGooglePPC'];
		$totalTax += $tax['NewWebGooglePPC'];
		$totalGross += $total['NewWebGooglePPC'];
		$totalProfit += $profit['NewWebGooglePPC'];
		$totalOrders += $orders['NewWebGooglePPC'];
		?>

		<?php
		$orders['NewWebFreeListing'] = 0;
		$subTotal['NewWebFreeListing'] = 0;
		$discount['NewWebFreeListing'] = 0;
		$shipping['NewWebFreeListing'] = 0;
		$tax['NewWebFreeListing'] = 0;
		$total['NewWebFreeListing'] = 0;
		$profit['NewWebFreeListing'] = 0;

		for($i=0; $i<count($connections); $i++) {
			$ordersArr = array();

			$data = new DataQuery(sprintf("SELECT o.Order_ID, o.SubTotal, o.TotalShipping, o.TotalTax, o.TotalDiscount, o.Total FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID LEFT JOIN orders AS o2 ON o2.Customer_ID=c.Customer_ID AND o2.Order_ID<>o.Order_ID AND o2.Created_On<o.Created_On WHERE o.Order_Prefix IN ('W', 'U', 'L', 'M') AND o.Referrer NOT LIKE '%%Google-PPC%%' AND o2.Order_ID IS NULL AND o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Order_ID", $start, $end), $connections[$i]['Connection']);
			while($data->Row) {
				$subTotal['NewWebFreeListing'] += $data->Row['SubTotal'];
				$shipping['NewWebFreeListing'] += $data->Row['TotalShipping'];
				$tax['NewWebFreeListing'] += $data->Row['TotalTax'];
				$discount['NewWebFreeListing'] += $data->Row['TotalDiscount'];
				$total['NewWebFreeListing'] += $data->Row['Total'];
				$orders['NewWebFreeListing']++;

				$ordersArr[] = $data->Row['Order_ID'];

				$data->Next();
			}
			$data->Disconnect();

			if(count($ordersArr) > 0) {
				$data = new DataQuery(sprintf("SELECT SUM(ol.Line_Total - (ol.Cost * ol.Quantity)) AS Profit FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID LEFT JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE (o.Order_ID=%s) GROUP BY o.Order_ID", implode(' OR o.Order_ID=', $ordersArr)), $connections[$i]['Connection']);
				while($data->Row) {
					$profit['NewWebFreeListing'] += $data->Row['Profit'];

					$data->Next();
				}
				$data->Disconnect();
			}
		}

		$totalSubTotal += $subTotal['NewWebFreeListing'];
		$totalDiscount += $discount['NewWebFreeListing'];
		$totalShipping += $shipping['NewWebFreeListing'];
		$totalTax += $tax['NewWebFreeListing'];
		$totalGross += $total['NewWebFreeListing'];
		$totalProfit += $profit['NewWebFreeListing'];
		$totalOrders += $orders['NewWebFreeListing'];
		?>

		<?php
		$orders['NewTelesales'] = 0;
		$subTotal['NewTelesales'] = 0;
		$discount['NewTelesales'] = 0;
		$shipping['NewTelesales'] = 0;
		$tax['NewTelesales'] = 0;
		$total['NewTelesales'] = 0;
		$profit['NewTelesales'] = 0;

		for($i=0; $i<count($connections); $i++) {
			$ordersArr = array();

			$data = new DataQuery(sprintf("SELECT o.Order_ID, o.SubTotal, o.TotalShipping, o.TotalTax, o.TotalDiscount, o.Total FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID LEFT JOIN orders AS o2 ON o2.Customer_ID=c.Customer_ID AND o2.Order_ID<>o.Order_ID AND o2.Created_On<o.Created_On WHERE o.Order_Prefix='T' AND o.Created_On BETWEEN '%s' AND '%s' AND o2.Order_ID IS NULL AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Order_ID", $start, $end), $connections[$i]['Connection']);
			while($data->Row) {
				$subTotal['NewTelesales'] += $data->Row['SubTotal'];
				$shipping['NewTelesales'] += $data->Row['TotalShipping'];
				$tax['NewTelesales'] += $data->Row['TotalTax'];
				$discount['NewTelesales'] += $data->Row['TotalDiscount'];
				$total['NewTelesales'] += $data->Row['Total'];
				$orders['NewTelesales']++;

				$ordersArr[] = $data->Row['Order_ID'];

				$data->Next();
			}
			$data->Disconnect();

			if(count($ordersArr) > 0) {
				$data = new DataQuery(sprintf("SELECT SUM(ol.Line_Total - (ol.Cost * ol.Quantity)) AS Profit FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID LEFT JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE (o.Order_ID=%s) GROUP BY o.Order_ID", implode(' OR o.Order_ID=', $ordersArr)), $connections[$i]['Connection']);
				while($data->Row) {
					$profit['NewTelesales'] += $data->Row['Profit'];

					$data->Next();
				}
				$data->Disconnect();
			}
		}

		$totalSubTotal += $subTotal['NewTelesales'];
		$totalDiscount += $discount['NewTelesales'];
		$totalShipping += $shipping['NewTelesales'];
		$totalTax += $tax['NewTelesales'];
		$totalGross += $total['NewTelesales'];
		$totalProfit += $profit['NewTelesales'];
		$totalOrders += $orders['NewTelesales'];
		?>

		<?php
		$orders['RepeatWebGooglePPC'] = 0;
		$subTotal['RepeatWebGooglePPC'] = 0;
		$discount['RepeatWebGooglePPC'] = 0;
		$shipping['RepeatWebGooglePPC'] = 0;
		$tax['RepeatWebGooglePPC'] = 0;
		$total['RepeatWebGooglePPC'] = 0;
		$profit['RepeatWebGooglePPC'] = 0;

		for($i=0; $i<count($connections); $i++) {
			$ordersArr = array();

			$data = new DataQuery(sprintf("SELECT o.Order_ID, o.SubTotal, o.TotalShipping, o.TotalTax, o.TotalDiscount, o.Total FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN orders AS o2 ON o2.Customer_ID=c.Customer_ID AND o2.Order_ID<>o.Order_ID AND o2.Created_On<o.Created_On WHERE o.Order_Prefix IN ('W', 'U', 'L', 'M') AND o.Referrer LIKE '%%Google-PPC%%' AND o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Order_ID", $start, $end), $connections[$i]['Connection']);
			while($data->Row) {
				$subTotal['RepeatWebGooglePPC'] += $data->Row['SubTotal'];
				$shipping['RepeatWebGooglePPC'] += $data->Row['TotalShipping'];
				$tax['RepeatWebGooglePPC'] += $data->Row['TotalTax'];
				$discount['RepeatWebGooglePPC'] += $data->Row['TotalDiscount'];
				$total['RepeatWebGooglePPC'] += $data->Row['Total'];
				$orders['RepeatWebGooglePPC']++;

				$ordersArr[] = $data->Row['Order_ID'];

				$data->Next();
			}
			$data->Disconnect();

			if(count($ordersArr) > 0) {
				$data = new DataQuery(sprintf("SELECT SUM(ol.Line_Total - (ol.Cost * ol.Quantity)) AS Profit FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID LEFT JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE (o.Order_ID=%s) GROUP BY o.Order_ID", implode(' OR o.Order_ID=', $ordersArr)), $connections[$i]['Connection']);
				while($data->Row) {
					$profit['RepeatWebGooglePPC'] += $data->Row['Profit'];

					$data->Next();
				}
				$data->Disconnect();
			}
		}

		$totalSubTotal += $subTotal['RepeatWebGooglePPC'];
		$totalDiscount += $discount['RepeatWebGooglePPC'];
		$totalShipping += $shipping['RepeatWebGooglePPC'];
		$totalTax += $tax['RepeatWebGooglePPC'];
		$totalGross += $total['RepeatWebGooglePPC'];
		$totalProfit += $profit['RepeatWebGooglePPC'];
		$totalOrders += $orders['RepeatWebGooglePPC'];
		?>

		<?php
		$orders['RepeatWebFreeListing'] = 0;
		$subTotal['RepeatWebFreeListing'] = 0;
		$discount['RepeatWebFreeListing'] = 0;
		$shipping['RepeatWebFreeListing'] = 0;
		$tax['RepeatWebFreeListing'] = 0;
		$total['RepeatWebFreeListing'] = 0;
		$profit['RepeatWebFreeListing'] = 0;

		for($i=0; $i<count($connections); $i++) {
			$ordersArr = array();

			$data = new DataQuery(sprintf("SELECT o.Order_ID, o.SubTotal, o.TotalShipping, o.TotalTax, o.TotalDiscount, o.Total FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID LEFT JOIN order_line AS ol ON o.Order_ID=ol.Order_ID INNER JOIN orders AS o2 ON o2.Customer_ID=c.Customer_ID AND o2.Order_ID<>o.Order_ID AND o2.Created_On<o.Created_On WHERE o.Order_Prefix IN ('W', 'U', 'L', 'M') AND o.Referrer NOT LIKE '%%Google-PPC%%' AND o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Order_ID", $start, $end), $connections[$i]['Connection']);
			while($data->Row) {
				$subTotal['RepeatWebFreeListing'] += $data->Row['SubTotal'];
				$shipping['RepeatWebFreeListing'] += $data->Row['TotalShipping'];
				$tax['RepeatWebFreeListing'] += $data->Row['TotalTax'];
				$discount['RepeatWebFreeListing'] += $data->Row['TotalDiscount'];
				$total['RepeatWebFreeListing'] += $data->Row['Total'];
				$orders['RepeatWebFreeListing']++;

				$ordersArr[] = $data->Row['Order_ID'];

				$data->Next();
			}
			$data->Disconnect();

			if(count($ordersArr) > 0) {
				$data = new DataQuery(sprintf("SELECT SUM(ol.Line_Total - (ol.Cost * ol.Quantity)) AS Profit FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID LEFT JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE (o.Order_ID=%s) GROUP BY o.Order_ID", implode(' OR o.Order_ID=', $ordersArr)), $connections[$i]['Connection']);
				while($data->Row) {
					$profit['RepeatWebFreeListing'] += $data->Row['Profit'];

					$data->Next();
				}
				$data->Disconnect();
			}
		}

		$totalSubTotal += $subTotal['RepeatWebFreeListing'];
		$totalDiscount += $discount['RepeatWebFreeListing'];
		$totalShipping += $shipping['RepeatWebFreeListing'];
		$totalTax += $tax['RepeatWebFreeListing'];
		$totalGross += $total['RepeatWebFreeListing'];
		$totalProfit += $profit['RepeatWebFreeListing'];
		$totalOrders += $orders['RepeatWebFreeListing'];
		?>

		<?php
		$orders['RepeatTelesales'] = 0;
		$subTotal['RepeatTelesales'] = 0;
		$discount['RepeatTelesales'] = 0;
		$shipping['RepeatTelesales'] = 0;
		$tax['RepeatTelesales'] = 0;
		$total['RepeatTelesales'] = 0;
		$profit['RepeatTelesales'] = 0;

		for($i=0; $i<count($connections); $i++) {
			$ordersArr = array();

			$data = new DataQuery(sprintf("SELECT o.Order_ID, o.SubTotal, o.TotalShipping, o.TotalTax, o.TotalDiscount, o.Total FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN orders AS o2 ON o2.Customer_ID=c.Customer_ID AND o2.Order_ID<>o.Order_ID AND o2.Created_On<o.Created_On WHERE o.Order_Prefix='T' AND o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Order_ID", $start, $end), $connections[$i]['Connection']);
			while($data->Row) {
				$subTotal['RepeatTelesales'] += $data->Row['SubTotal'];
				$shipping['RepeatTelesales'] += $data->Row['TotalShipping'];
				$tax['RepeatTelesales'] += $data->Row['TotalTax'];
				$discount['RepeatTelesales'] += $data->Row['TotalDiscount'];
				$total['RepeatTelesales'] += $data->Row['Total'];
				$orders['RepeatTelesales']++;

				$ordersArr[] = $data->Row['Order_ID'];

				$data->Next();
			}
			$data->Disconnect();

			if(count($ordersArr) > 0) {
				$data = new DataQuery(sprintf("SELECT SUM(ol.Line_Total - (ol.Cost * ol.Quantity)) AS Profit FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID LEFT JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE (o.Order_ID=%s) GROUP BY o.Order_ID", implode(' OR o.Order_ID=', $ordersArr)), $connections[$i]['Connection']);
				while($data->Row) {
					$profit['RepeatTelesales'] += $data->Row['Profit'];

					$data->Next();
				}
				$data->Disconnect();
			}
		}

		$totalSubTotal += $subTotal['RepeatTelesales'];
		$totalDiscount += $discount['RepeatTelesales'];
		$totalShipping += $shipping['RepeatTelesales'];
		$totalTax += $tax['RepeatTelesales'];
		$totalGross += $total['RepeatTelesales'];
		$totalProfit += $profit['RepeatTelesales'];
		$totalOrders += $orders['RepeatTelesales'];
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>New Web Google PPC</td>
			<td align="right"><?php echo $orders['NewWebGooglePPC']; ?></td>
			<td align="right"><?php echo round(($orders['NewWebGooglePPC'] / $totalOrders)*100); ?>%</td>
			<td align="right">&pound;<?php echo number_format($subTotal['NewWebGooglePPC'], 2, '.', ','); ?></td>
			<td align="right">-&pound;<?php echo number_format($discount['NewWebGooglePPC'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(($subTotal['NewWebGooglePPC'] - $discount['NewWebGooglePPC']), 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($shipping['NewWebGooglePPC'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($tax['NewWebGooglePPC'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($total['NewWebGooglePPC'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($profit['NewWebGooglePPC'], 2, '.', ','); ?></td>
			<td align="right"><?php echo round(($profit['NewWebGooglePPC'] / $subTotal['NewWebGooglePPC'])*100); ?>%</td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>New Web Free Listing</td>
			<td align="right"><?php echo $orders['NewWebFreeListing']; ?></td>
			<td align="right"><?php echo round(($orders['NewWebFreeListing'] / $totalOrders)*100); ?>%</td>
			<td align="right">&pound;<?php echo number_format($subTotal['NewWebFreeListing'], 2, '.', ','); ?></td>
			<td align="right">-&pound;<?php echo number_format($discount['NewWebFreeListing'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(($subTotal['NewWebFreeListing'] - $discount['NewWebFreeListing']), 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($shipping['NewWebFreeListing'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($tax['NewWebFreeListing'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($total['NewWebFreeListing'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($profit['NewWebFreeListing'], 2, '.', ','); ?></td>
			<td align="right"><?php echo round(($profit['NewWebFreeListing'] / $subTotal['NewWebFreeListing'])*100); ?>%</td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>New Telesales Google PPC</td>
			<td align="right"><?php echo ($percent / 100) * $orders['NewTelesales']; ?></td>
			<td align="right"><?php echo round(((($percent / 100) * $orders['NewTelesales']) / $totalOrders)*100); ?>%</td>
			<td align="right">&pound;<?php echo number_format(($percent / 100) * $subTotal['NewTelesales'], 2, '.', ','); ?></td>
			<td align="right">-&pound;<?php echo number_format(($percent / 100) * $discount['NewTelesales'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(($percent / 100) * ($subTotal['NewTelesales'] - $discount['NewTelesales']), 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(($percent / 100) * $shipping['NewTelesales'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(($percent / 100) * $tax['NewTelesales'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(($percent / 100) * $total['NewTelesales'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(($percent / 100) * $profit['NewTelesales'], 2, '.', ','); ?></td>
			<td align="right"><?php echo round(((($percent / 100) * $profit['NewTelesales']) / $subTotal['NewTelesales'])*100); ?>%</td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>New Telesales Free Listing</td>
			<td align="right"><?php echo ((100 - $percent) / 100) * $orders['NewTelesales']; ?></td>
			<td align="right"><?php echo round(((((100 - $percent) / 100) * $orders['NewTelesales']) / $totalOrders)*100); ?>%</td>
			<td align="right">&pound;<?php echo number_format(((100 - $percent) / 100) * $subTotal['NewTelesales'], 2, '.', ','); ?></td>
			<td align="right">-&pound;<?php echo number_format(((100 - $percent) / 100) * $discount['NewTelesales'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(((100 - $percent) / 100) * ($subTotal['NewTelesales'] - $discount['NewTelesales']), 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(((100 - $percent) / 100) * $shipping['NewTelesales'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(((100 - $percent) / 100) * $tax['NewTelesales'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(((100 - $percent) / 100) * $total['NewTelesales'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(((100 - $percent) / 100) * $profit['NewTelesales'], 2, '.', ','); ?></td>
			<td align="right"><?php echo round(((((100 - $percent) / 100) * $profit['NewTelesales']) / $subTotal['NewTelesales'])*100); ?>%</td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>Repeat Web Google PPC</td>
			<td align="right"><?php echo $orders['RepeatWebGooglePPC']; ?></td>
			<td align="right"><?php echo round(($orders['RepeatWebGooglePPC'] / $totalOrders)*100); ?>%</td>
			<td align="right">&pound;<?php echo number_format($subTotal['RepeatWebGooglePPC'], 2, '.', ','); ?></td>
			<td align="right">-&pound;<?php echo number_format($discount['RepeatWebGooglePPC'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(($subTotal['RepeatWebGooglePPC'] - $discount['RepeatWebGooglePPC']), 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($shipping['RepeatWebGooglePPC'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($tax['RepeatWebGooglePPC'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($total['RepeatWebGooglePPC'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($profit['RepeatWebGooglePPC'], 2, '.', ','); ?></td>
			<td align="right"><?php echo round(($profit['RepeatWebGooglePPC'] / $subTotal['RepeatWebGooglePPC'])*100); ?>%</td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>Repeat Web Free Listing</td>
			<td align="right"><?php echo $orders['RepeatWebFreeListing']; ?></td>
			<td align="right"><?php echo round(($orders['RepeatWebFreeListing'] / $totalOrders)*100); ?>%</td>
			<td align="right">&pound;<?php echo number_format($subTotal['RepeatWebFreeListing'], 2, '.', ','); ?></td>
			<td align="right">-&pound;<?php echo number_format($discount['RepeatWebFreeListing'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(($subTotal['RepeatWebFreeListing'] - $discount['RepeatWebFreeListing']), 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($shipping['RepeatWebFreeListing'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($tax['RepeatWebFreeListing'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($total['RepeatWebFreeListing'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($profit['RepeatWebFreeListing'], 2, '.', ','); ?></td>
			<td align="right"><?php echo round(($profit['RepeatWebFreeListing'] / $subTotal['RepeatWebFreeListing'])*100); ?>%</td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>Repeat Telesales</td>
			<td align="right"><?php echo $orders['RepeatTelesales']; ?></td>
			<td align="right"><?php echo round(($orders['RepeatTelesales'] / $totalOrders)*100); ?>%</td>
			<td align="right">&pound;<?php echo number_format($subTotal['RepeatTelesales'], 2, '.', ','); ?></td>
			<td align="right">-&pound;<?php echo number_format($discount['RepeatTelesales'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format(($subTotal['RepeatTelesales'] - $discount['RepeatTelesales']), 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($shipping['RepeatTelesales'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($tax['RepeatTelesales'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($total['RepeatTelesales'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($profit['RepeatTelesales'], 2, '.', ','); ?></td>
			<td align="right"><?php echo round(($profit['RepeatTelesales'] / $subTotal['RepeatTelesales'])*100); ?>%</td>
		</tr>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><strong>Totals</td>
			<td align="right"><strong><?php echo $totalOrders; ?></strong></td>
			<td align="right"><strong>100%</strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalSubTotal, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>-&pound;<?php echo number_format($totalDiscount, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal-$totalDiscount), 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalShipping, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalTax, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalGross, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalProfit, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&nbsp;</strong></td>
		</tr>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>