<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function timeToString($time) {
	if($time < 60) {
		$time = sprintf('%s seconds', number_format($time, 1, '.', ','));

	} elseif(($time/60) < 60) {
		$time = sprintf('%s minutes', number_format(($time/60), 1, '.', ','));

	} elseif(($time/60/60) < 24) {
		$time = sprintf('%s hours', number_format(($time/60/60), 1, '.', ','));

	} elseif(($time/60/60/24) < 365.25) {
		$time = sprintf('%s days', number_format(($time/60/60/24), 1, '.', ','));

	} elseif(($time/60/60/24/365.25) < 365.25) {
		$time = sprintf('%s years', number_format(($time/60/60/24/365.25), 1, '.', ','));
	}

	return $time;
}

function start() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Sales Rep Report', 'Please choose a start and end date for your report');
	$year = cDatetime(getDatetime(), 'y');
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
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastyear', 'Last Year');
	$form->AddOption('range', 'last2years', 'Last 2 Years');
	$form->AddOption('range', 'last3years', 'Last 3 Years');
	$form->AddField('branch', 'Branch', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('branch', '0', '-- All --');

	$data = new DataQuery(sprintf("SELECT b.Branch_ID, b.Branch_Name FROM users AS u INNER JOIN branch AS b ON u.Branch_ID=b.Branch_ID GROUP BY Branch_ID ORDER BY b.Branch_Name ASC"));
	while($data->Row) {
		$form->AddOption('branch', $data->Row['Branch_ID'], $data->Row['Branch_Name']);

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

			report($start, $end, $form->GetValue('branch'));
			exit;
		} else {

			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('branch'));
				exit;
			}
		}
	}

	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Report on Sales Reps.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();

	echo $window->AddHeader('Select a branch to report users from.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('branch'), $form->GetHTML('branch'));
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
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($start, $end, $branch){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$orderTypes = array();
	$orderTypes['W'] = "Website (bltdirect.com)";
	$orderTypes['U'] = "Website (bltdirect.co.uk)";
	$orderTypes['L'] = "Website (lightbulbsuk.co.uk)";
	$orderTypes['M'] = "Mobile";
	$orderTypes['T'] = "Telesales";
	$orderTypes['F'] = "Fax";
	$orderTypes['E'] = "Email";

	$script = sprintf('<script language="javascript" type="text/javascript">
		var toggleInfo = function(obj) {
			var e = document.getElementById(\'Info-\' + obj.value);
			if(e) {
				if(obj.checked) {
					e.style.display = \'block\';
				} else {
					e.style.display = \'none\';
				}
			}
		}
		</script>');

	$page = new Page('Sales Rep Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead($script);
	$page->Display('header');

	$user = new User();
	$enquiry = new Enquiry();

	$cutOffStart = 8.5;
	$cutOffEnd = 17.0;
	$cutOffStartDate = '08:30:00';
	$cutOffEndDate = '17:00:00';

	$firstOrders = array();

	$data = new DataQuery(sprintf("SELECT Customer_ID, MIN(Order_ID) AS First_Order_ID FROM orders WHERE Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N' GROUP BY Customer_ID"));
	while($data->Row) {
		$firstOrders[$data->Row['Customer_ID']] = $data->Row['First_Order_ID'];

		$data->Next();
	}
	$data->Disconnect();

	$lastContacted = array();

	$data = new DataQuery(sprintf("SELECT Contact_ID, MAX(Completed_On) AS Last_Contacted_On FROM contact_schedule WHERE Is_Complete='Y' AND Contact_Schedule_Type_ID<>2 GROUP BY Contact_ID"));
	while($data->Row) {
		$data2 = new DataQuery(sprintf("SELECT Contact_Schedule_Type_ID FROM contact_schedule WHERE Is_Complete='Y' AND Contact_ID=%d AND Completed_On='%s' LIMIT 0, 1", $data->Row['Contact_ID'], $data->Row['Last_Contacted_On']));
		if($data2->TotalRows > 0) {
			$lastContacted[$data->Row['Contact_ID']] = array('Contacted' => $data->Row['Last_Contacted_On'], 'Type' => $data2->Row['Contact_Schedule_Type_ID']);
		}
		$data2->Disconnect();

		$data->Next();
	}
	$data->Disconnect();

	$scheduleTypes = array();

	$data = new DataQuery(sprintf("SELECT Contact_Schedule_Type_ID, Name, Colour FROM contact_schedule_type"));
	while($data->Row) {
		$scheduleTypes[$data->Row['Contact_Schedule_Type_ID']] = array('Name' => $data->Row['Name'], 'Colour' => $data->Row['Colour']);

		$data->Next();
	}
	$data->Disconnect();
	?>

	<div style="background-color: #f6f6f6; padding: 10px;">
		<p><span class="pageSubTitle">Select Sales Reps</span><br /><span class="pageDescription">Check the boxes of the sales reps you wish to review.</span></p>

		<?php
		$data = new DataQuery(sprintf("SELECT * FROM users AS u INNER JOIN person AS p ON u.Person_ID=p.Person_ID %s ORDER BY p.Name_First ASC, p.Name_Last ASC", ($branch > 0) ? sprintf('WHERE u.Branch_ID=%d', mysql_real_escape_string($branch)) : ''));
		while($data->Row) {
			$user->ID = $data->Row['User_ID'];
			$user->Get();

			echo sprintf('<input type="checkbox" value="%s" onclick="toggleInfo(this);" /> %s<br />', $user->ID, trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)));

			$data->Next();
		}
		$data->Disconnect();
		?>

	</div><br />

	<?php
	$data = new DataQuery(sprintf("SELECT * FROM users AS u INNER JOIN person AS p ON u.Person_ID=p.Person_ID %s ORDER BY p.Name_First ASC, p.Name_Last ASC", ($branch > 0) ? sprintf('WHERE u.Branch_ID=%d', mysql_real_escape_string($branch)) : ''));
	while($data->Row) {
		$user->ID = $data->Row['User_ID'];
		$user->Get();
		?>

		<div style="display: none;" id="Info-<?php echo $user->ID; ?>">
			<div style="background-color: #f6f6f6; padding: 10px;">
				<p><span class="pageSubTitle"><?php echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)); ?></span><br /><span class="pageDescription">Sales rep details between the given period.</span></p>

				<br />
				<h3>Sales Summary</h3>
				<p>Summary of sales statistics owned by this sales rep.</p>

				<table width="95%" border="0">
					<tr>
						<td style="border-bottom:1px solid #aaaaaa"><strong>Order Type</strong></td>
						<td style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>
						<td style="border-bottom:1px solid #aaaaaa"><strong>Sub Total</strong></td>
						<td style="border-bottom:1px solid #aaaaaa"><strong>Shipping</strong></td>
						<td style="border-bottom:1px solid #aaaaaa"><strong>Tax</strong></td>
						<td style="border-bottom:1px solid #aaaaaa"><strong>Gross</strong></td>
					</tr>

					<?php
					$totalOrders = 0;
					$totalSubTotal = 0;
					$totalShipping = 0;
					$totalTax = 0;
					$total = 0;

					$data2 = new DataQuery(sprintf("select count(Order_ID) as OrderCount, Order_Prefix, sum(SubTotal) as SubTotal, sum(TotalShipping) as TotalShipping, sum(TotalTax) as TotalTax, sum(Total) as Total from orders where Created_On between '%s' and '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N' AND Owned_By=%d group by Order_Prefix", $start, $end, $user->ID));
					while($data2->Row){
						$totalOrders += $data2->Row['OrderCount'];
						$totalSubTotal += $data2->Row['SubTotal'];
						$totalShipping += $data2->Row['TotalShipping'];
						$totalTax += $data2->Row['TotalTax'];
						$total += $data2->Row['Total'];

						if($data2->Row['OrderCount'] > 0) {
							?>

							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td><?php echo $orderTypes[$data2->Row['Order_Prefix']]; ?></td>
								<td><?php echo $data2->Row['OrderCount']; ?></td>
								<td>&pound;<?php echo number_format($data2->Row['SubTotal'], 2, '.', ','); ?></td>
								<td>&pound;<?php echo number_format($data2->Row['TotalShipping'], 2, '.', ','); ?></td>
								<td>&pound;<?php echo number_format($data2->Row['TotalTax'], 2, '.', ','); ?></td>
								<td>&pound;<?php echo number_format($data2->Row['Total'], 2, '.', ','); ?></td>
							</tr>

							<?php
						}
						$data2->Next();
					}
					$data2->Disconnect();
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><strong>Totals</strong></td>
						<td><strong><?php echo $totalOrders; ?></strong></td>
						<td><strong>&pound;<?php echo number_format($totalSubTotal, 2, '.', ','); ?></strong></td>
						<td><strong>&pound;<?php echo number_format($totalShipping, 2, '.', ','); ?></strong></td>
						<td><strong>&pound;<?php echo number_format($totalTax, 2, '.', ','); ?></strong></td>
						<td><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
					</tr>
				</table><br />

				<br />
				<h3>Sales Schedule Legend</h3>
				<p>Use this legend for the below last contacted column in the breakdown table.</p>

				<table width="95%" border="0">
					<tr>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Schedule Type</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Colour</strong></td>
					</tr>

					<?php
					foreach($scheduleTypes as $scheduleType) {
						?>

						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td align="left"><?php echo $scheduleType['Name']; ?></td>
							<td align="left" style="background-color: #<?php echo $scheduleType['Colour']; ?>;">&nbsp;</td>
						</tr>

						<?php
					}
					?>

				</table><br />

				<br />
				<h3>Sales Breakdown</h3>
				<p>Breakdown of sales owned by this sales rep.</p>

				<table width="95%" border="0">
					<tr>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Order Date</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Order Ref.</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Sub Total</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Shipping</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Tax</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Gross</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Cost</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Discount</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Discount %</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Profit</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Profit %</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Last Contacted</strong><br />Excluding Recent Enquiry</td>
					</tr>

					<?php
					$totalSubTotal = 0;
					$totalShipping = 0;
					$totalTax = 0;
					$totalGross = 0;
					$totalCost = 0;
					$totalProfit = 0;
					$totalDiscount = 0;

					$data2 = new DataQuery(sprintf("SELECT MIN(ol.Cost) AS Lowest_Cost, o.*, n.Contact_ID, p.Name_First, p.Name_Last, SUM(ol.Cost * ol.Quantity) AS Cost, SUM(ol.Line_Discount) AS Discount, SUM(((ol.Price - ol.Cost) * ol.Quantity) - ol.Line_Discount) AS Profit FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON c.Contact_ID=n.Contact_ID INNER JOIN person AS p ON p.Person_ID=n.Person_ID LEFT JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Created_On BETWEEN '%s' AND '%s' AND ol.Product_ID>0 AND o.Owned_By=%d AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N' GROUP BY o.Order_ID ORDER BY Discount DESC", mysql_real_escape_string($start), mysql_real_escape_string($end), mysql_real_escape_string($user->ID)));
					while($data2->Row){
						$totalSubTotal += $data2->Row['SubTotal'];
						$totalShipping += $data2->Row['TotalShipping'];
						$totalTax += $data2->Row['TotalTax'];
						$totalGross += $data2->Row['Total'];
						$totalCost += $data2->Row['Cost'];
						$totalProfit += $data2->Row['Profit'];
						$totalDiscount += $data2->Row['Discount'];

						$style = '';

						if($data2->Row['Lowest_Cost'] == 0) {
							$style = 'background-color: #fff;';
						}

						if(isset($firstOrders[$data2->Row['Customer_ID']]) && ($firstOrders[$data2->Row['Customer_ID']] == $data2->Row['Order_ID'])) {
							$style = 'background-color: #ff9;';
						}
						?>

						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');" style="<?php echo $style; ?>">
							<td align="left"><?php print cDatetime($data2->Row['Created_On'], 'shortdatetime'); ?></td>
							<td align="left"><a href="order_details.php?orderid=<?php echo $data2->Row['Order_ID']; ?>" target="_blank"><?php echo $data2->Row['Order_Prefix'].$data2->Row['Order_ID']; ?></a></td>
							<td align="left"><a href="contact_profile.php?cid=<?php echo $data2->Row['Contact_ID']; ?>" target="_blank"><?php echo trim(sprintf("%s %s", $data2->Row['Name_First'], $data2->Row['Name_Last'])); ?></a></td>
							<td align="left">&pound;<?php echo number_format($data2->Row['SubTotal'], 2, '.', ','); ?></td>
							<td align="left">&pound;<?php echo number_format($data2->Row['TotalShipping'], 2, '.', ','); ?></td>
							<td align="left">&pound;<?php echo number_format($data2->Row['TotalTax'], 2, '.', ','); ?></td>
							<td align="left">&pound;<?php echo number_format($data2->Row['Total'], 2, '.', ','); ?></td>
							<td align="left">&pound;<?php echo number_format($data2->Row['Cost'], 2, '.', ','); ?></td>
							<td align="left">&pound;<?php echo number_format($data2->Row['Discount'], 2, '.', ','); ?></td>
							<td align="left"><?php echo number_format(($data2->Row['Discount']/$data2->Row['SubTotal'])*100, 2, '.', ','); ?>%</td>
							<td align="left">&pound;<?php echo number_format($data2->Row['Profit'], 2, '.', ','); ?></td>
							<td align="left"><?php echo number_format(($data2->Row['Profit']/$data2->Row['SubTotal'])*100, 2, '.', ','); ?>%</td>
							<td align="left" style="<?php echo isset($lastContacted[$data2->Row['Contact_ID']]) ? sprintf('background-color: #%s;', $scheduleTypes[$lastContacted[$data2->Row['Contact_ID']]['Type']]['Colour']) : ''; ?>"><?php echo isset($lastContacted[$data2->Row['Contact_ID']]) ? $lastContacted[$data2->Row['Contact_ID']]['Contacted'] : ''; ?></td>
						</tr>

						<?php
						$data2->Next();
					}
					$data2->Disconnect();
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td align="left"><strong>&pound;<?php echo number_format($totalSubTotal, 2, '.', ','); ?></strong></td>
						<td align="left"><strong>&pound;<?php echo number_format($totalShipping, 2, '.', ','); ?></strong></td>
						<td align="left"><strong>&pound;<?php echo number_format($totalTax, 2, '.', ','); ?></strong></td>
						<td align="left"><strong>&pound;<?php echo number_format($totalGross, 2, '.', ','); ?></strong></td>
						<td align="left"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
						<td align="left"><strong>&pound;<?php echo number_format($totalDiscount, 2, '.', ','); ?></strong></td>
						<td align="left"><strong>
							<?php 
							if($totalSubTotal > 0){
							echo number_format(($totalDiscount/$totalSubTotal)*100, 2, '.', ','); }?>%</strong></td>
						<td align="left"><strong>&pound;<?php echo number_format($totalProfit, 2, '.', ','); ?></strong></td>
						<td align="left"><strong><?php 
							if($totalSubTotal > 0){
								echo number_format(($totalProfit/$totalSubTotal)*100, 2, '.', ','); }?>%</strong></td>
					</tr>
				</table><br />

				<br />
				<h3>Enquiry Summary</h3>
				<p>Summary of enquiry statistics made by this sales rep.</p>

				<table width="95%" border="0">
					<tr>
						<td style="border-bottom:1px solid #aaaaaa"><strong>Enquiry Type</strong></td>
						<td style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>
						<td style="border-bottom:1px solid #aaaaaa"><strong>Response Time</strong></td>
						<td style="border-bottom:1px solid #aaaaaa"><strong>Average Response Time</strong></td>
						<td style="border-bottom:1px solid #aaaaaa"><strong>Time Open</strong></td>
						<td style="border-bottom:1px solid #aaaaaa"><strong>Average Time Open</strong></td>
					</tr>

					<?php
					$totalOrders = 0;
					$totalQuotes = 0;
					$totalOpenTime = 0;
					$totalResponseTime = 0;
					$totalEnquiries = 0;
					$totalAverageOpenTime = 0;
					$totalAverageResponseTime = 0;

					$responses = array();
					$results = array();
					$enquiries = array();
					$enquirySummary = array();

					$data2 = new DataQuery(sprintf("SELECT e.Owned_By, e.Enquiry_ID, el.Created_On, el.Is_Customer_Message FROM enquiry AS e INNER JOIN enquiry_line AS el ON el.Enquiry_ID=e.Enquiry_ID WHERE e.Created_On BETWEEN '%s' AND '%s' AND e.Owned_By=%d AND el.Is_Draft='N' AND el.Is_Public='Y' ORDER BY el.Created_On ASC", mysql_real_escape_string($start), mysql_real_escape_string($end), mysql_real_escape_string($user->ID)));
					while($data2->Row) {
						if(!isset($responses[$data2->Row['Enquiry_ID']])) {
							$responses[$data2->Row['Enquiry_ID']] = array();
						}

						$line = array();
						$line['Created_On'] = $data2->Row['Created_On'];
						$line['Is_Customer_Message'] = $data2->Row['Is_Customer_Message'];

						$responses[$data2->Row['Enquiry_ID']][] = $line;

						$data2->Next();
					}
					$data2->Disconnect();

					foreach($responses as $key=>$lines) {
						if(count($lines) >= 2) {
							$index = 0;
							$startInd = -1;
							$endInd = -1;

							for($i=0; $i<count($lines); $i++) {
								$index++;

								$startInd = $i;
								break;
							}

							if(($startInd >= 0) && ($index < count($lines))) {
								for($i=$index; $i<count($lines); $i++) {

									if($lines[$i]['Is_Customer_Message'] == 'N') {
										$endInd = $i;
										break;
									}
								}

								if($endInd >= 0) {
									$startTime = substr($lines[$startInd]['Created_On'], 11, 2) + (substr($lines[$startInd]['Created_On'], 14, 2) / 60);
									$endTime = substr($lines[$endInd]['Created_On'], 11, 2) + (substr($lines[$endInd]['Created_On'], 14, 2) / 60);

									if($startTime < $cutOffStart) {
										$lines[$startInd]['Created_On'] = date(sprintf('Y-m-d %s', $cutOffStartDate), strtotime($lines[$startInd]['Created_On']));
									} elseif($startTime > $cutOffEnd) {
										$lines[$startInd]['Created_On'] = date(sprintf('Y-m-d %s', $cutOffEndDate), strtotime($lines[$startInd]['Created_On']));
									}

									if($endTime > $cutOffEnd) {
										$lines[$endInd]['Created_On'] = date(sprintf('Y-m-d %s', $cutOffEndDate), strtotime($lines[$endInd]['Created_On']));
									} elseif($endTime < $cutOffStart) {
										$lines[$endInd]['Created_On'] = date(sprintf('Y-m-d %s', $cutOffStartDate), strtotime($lines[$endInd]['Created_On']));
									}

									$startTime = substr($lines[$startInd]['Created_On'], 11, 2) + (substr($lines[$startInd]['Created_On'], 14, 2) / 60);
									$endTime = substr($lines[$endInd]['Created_On'], 11, 2) + (substr($lines[$endInd]['Created_On'], 14, 2) / 60);

									if(trim(substr($lines[$startInd]['Created_On'], 0, 10)) == trim(substr($lines[$endInd]['Created_On'], 0, 10))) {
										$results[$key] = strtotime($lines[$endInd]['Created_On']) - strtotime($lines[$startInd]['Created_On']);
									} else {
										$accumulated = ($cutOffEnd - $startTime) * 3600;
										$accumulated += ($endTime - $cutOffStart) * 3600;

										$currentDate = trim(substr($lines[$startInd]['Created_On'], 0, 10));
										$days = -1;

										while($currentDate != trim(substr($lines[$endInd]['Created_On'], 0, 10))) {
											$currentDate = date('Y-m-d', strtotime(sprintf('%s 00:00:00', $currentDate)) + 86400);

											if((strtolower(date('D', strtotime($currentDate))) != 'sat') && (strtolower(date('D', strtotime($currentDate))) != 'sun')) {
												$days++;
											}
										}

										if($days > 0) {
											for($i=0; $i<$days; $i++) {
												$accumulated += ($cutOffEnd - $cutOffStart) * 3600;;
											}
										}

										$results[$key] = $accumulated;
									}
								}
							}
						}
					}

					$data2 = new DataQuery(sprintf("SELECT e.*, et.Name, p.Name_First, p.Name_Last, COUNT(DISTINCT o.Order_ID) AS Orders_Processed, COUNT(DISTINCT q.Quote_ID) AS Quotes_Processed, n.Contact_ID FROM enquiry AS e LEFT JOIN enquiry_type AS et ON et.Enquiry_Type_ID=e.Enquiry_Type_ID INNER JOIN customer AS c ON c.Customer_ID=e.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p ON p.Person_ID=n.Person_ID LEFT JOIN orders AS o ON o.Customer_ID=e.Customer_ID AND ((e.Status NOT LIKE 'Closed' AND o.Created_On BETWEEN e.Created_On AND NOW()) OR (e.Status LIKE 'Closed' AND o.Created_On BETWEEN e.Created_On AND e.Closed_On)) LEFT JOIN quote AS q ON q.Customer_ID=e.Customer_ID AND ((e.Status NOT LIKE 'Closed' AND q.Created_On BETWEEN e.Created_On AND NOW()) OR (e.Status LIKE 'Closed' AND q.Created_On BETWEEN e.Created_On AND e.Closed_On)) WHERE e.Created_On BETWEEN '%s' AND '%s' AND e.Owned_By=%d GROUP BY e.Enquiry_ID", mysql_real_escape_string($start), mysql_real_escape_string($end), mysql_real_escape_string($user->ID)));
					while($data2->Row){
						if($data2->Row['Status'] == 'Closed') {
							$openTime = strtotime($data2->Row['Closed_On']) - strtotime($data2->Row['Created_On']);
						} else {
							$openTime = time() - strtotime($data2->Row['Created_On']);
						}

						$responseTime = isset($results[$data2->Row['Enquiry_ID']]) ? $results[$data2->Row['Enquiry_ID']] : 0;

						$totalOrders += $data2->Row['Orders_Processed'];
						$totalQuotes += $data2->Row['Quotes_Processed'];
						$totalOpenTime += $openTime;
						$totalResponseTime += $responseTime;

						$item = $data2->Row;
						$item['Open_Time'] = $openTime;
						$item['Response_Time'] = $responseTime;

						$enquiries[] = $item;

						$data2->Next();
					}
					$data2->Disconnect();

					$totalOpenTime = timeToString($totalOpenTime);
					$totalResponseTime = timeToString($totalResponseTime);

					foreach($enquiries as $enquiryItem) {
						if(!isset($enquirySummary[$enquiryItem['Prefix']])) {
							$enquirySummary[$enquiryItem['Prefix']] = array();
							$enquirySummary[$enquiryItem['Prefix']]['Number'] = 0;
							$enquirySummary[$enquiryItem['Prefix']]['Average_Response'] = array();
						}

						$enquirySummary[$enquiryItem['Prefix']]['Number']++;
						$enquirySummary[$enquiryItem['Prefix']]['Average_Response'][] = $enquiryItem['Response_Time'];
						$enquirySummary[$enquiryItem['Prefix']]['Average_Open'][] = $enquiryItem['Open_Time'];

						$totalEnquiries++;
					}

					foreach($enquirySummary as $key=>$enquiryType) {
						$averageResponse = 0;
						$averageOpen = 0;

						foreach($enquiryType['Average_Response'] as $responseTime) {
							$averageResponse += $responseTime;
						}

						foreach($enquiryType['Average_Open'] as $openTime) {
							$averageOpen += $openTime;
						}

						$totalAverageResponseTime += $averageResponse;
						$totalAverageOpenTime += $averageOpen;

						$enquirySummary[$key]['Response_Time'] = timeToString($averageResponse);
						$enquirySummary[$key]['Open_Time'] = timeToString($averageOpen);
						$enquirySummary[$key]['Average_Response'] = timeToString($averageResponse / $enquiryType['Number']);
						$enquirySummary[$key]['Average_Open'] = timeToString($averageOpen / $enquiryType['Number']);
					}

					foreach($enquirySummary as $prefix=>$enquiryItem) {
						?>

						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td><?php echo $orderTypes[$prefix]; ?></td>
							<td><?php echo $enquiryItem['Number']; ?></td>
							<td><?php echo $enquiryItem['Response_Time']; ?></td>
							<td><?php echo $enquiryItem['Average_Response']; ?></td>
							<td><?php echo $enquiryItem['Open_Time']; ?></td>
							<td><?php echo $enquiryItem['Average_Open']; ?></td>
						</tr>

						<?php
					}
					if($totalEnquiries > 0){
					$totalAverageResponseTime = timeToString($totalAverageResponseTime / $totalEnquiries);
					$totalAverageOpenTime = timeToString($totalAverageOpenTime / $totalEnquiries);
					}else{
						$totalAverageResponseTime = 0;
						$totalAverageOpenTime = 0;
					}
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><strong>Totals</strong></td>
						<td><strong><?php echo $totalEnquiries; ?></strong></td>
						<td><strong><?php echo $totalResponseTime; ?></strong></td>
						<td><strong><?php echo $totalAverageResponseTime; ?></strong></td>
						<td><strong><?php echo $totalOpenTime; ?></strong></td>
						<td><strong><?php echo $totalAverageOpenTime; ?></strong></td>
					</tr>
				</table><br />

				<br />
				<h3>Enquiry Breakdown</h3>
				<p>Breakdown of enquiries owned by this sales rep.</p>

				<table width="95%" border="0">
					<tr>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Enquiry Date</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Enquiry Ref.</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Type</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Response Time</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Time Open</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Quotes Processed</strong></td>
						<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Orders Processed</strong></td>
					</tr>

					<?php
					for($i=0; $i<count($enquiries); $i++) {
						$enquiry->ID = $enquiries[$i]['Enquiry_ID'];
						$enquiry->Prefix = $enquiries[$i]['Prefix'];

						$enquiries[$i]['Open_Time'] = timeToString($enquiries[$i]['Open_Time']);
						$enquiries[$i]['Response_Time'] = timeToString($enquiries[$i]['Response_Time']);
						?>

						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td align="left"><?php print cDatetime($enquiries[$i]['Created_On'], 'shortdatetime'); ?></td>
							<td align="left"><a href="enquiry_details.php?enquiryid=<?php print $enquiries[$i]['Enquiry_ID']; ?>" target="_blank"><?php print $enquiry->GetReference(); ?></a></td>
							<td align="left"><a href="contact_profile.php?cid=<?php print $enquiries[$i]['Contact_ID']; ?>" target="_blank"><?php print trim(sprintf("%s %s", $enquiries[$i]['Name_First'], $enquiries[$i]['Name_Last'])); ?></a></td>
							<td align="left"><?php print $enquiries[$i]['Name']; ?></td>
							<td align="left"><?php print $enquiries[$i]['Response_Time']; ?></td>
							<td align="left"><?php print $enquiries[$i]['Open_Time']; ?></td>
							<td align="right"><?php print $enquiries[$i]['Orders_Processed']; ?></td>
							<td align="right"><?php print $enquiries[$i]['Quotes_Processed']; ?></td>
						</tr>

						<?php
					}
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td align="left"><strong><?php echo $totalResponseTime; ?></strong></td>
						<td align="left"><strong><?php echo $totalOpenTime; ?></strong></td>
						<td align="right"><strong><?php echo $totalOrders; ?></strong></td>
						<td align="right"><strong><?php echo $totalQuotes; ?></strong></td>
					</tr>
				</table><br />

			</div>
			<br />
		</div>

		<?php
		$data->Next();
	}
	$data->Disconnect();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}