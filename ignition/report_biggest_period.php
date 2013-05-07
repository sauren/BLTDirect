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
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
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
	$form->AddField('period', 'Report Period', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('period', '', '-- Select Period --');
	$form->AddOption('period', 'day', 'Biggest Day');
	$form->AddOption('period', 'month', 'Biggest Month');
	$form->AddOption('period', 'year', 'Biggest Year');
	$form->AddField('list', 'List by', 'select', '', 'alpha_numeric', 0, 32);
	$form->AddOption('list', '', '-- Select Listing --');
	$form->AddOption('list', 'T', 'Turnover');
	$form->AddOption('list', 'O', 'Orders');
	$form->AddField('inclusion', 'Inclusion filter', 'select', '', 'alpha_numeric', 0, 32);
	$form->AddOption('inclusion', '', '-- Select Filter --');
	$form->AddOption('inclusion', 'A', 'All Orders');
	$form->AddOption('inclusion', 'T', 'Non Web Orders (Combined)');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 1)) {
			if(strlen($form->GetValue('period')) == 0){
				$form->AddError('Please select a period to report on.', 'period');
			}
			if(strlen($form->GetValue('inclusion')) == 0){
				$form->AddError('Please select inclusion filter to report on.', 'period');
			}

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
			if($form->Validate()){
				report($start, $end, $form->GetValue('period'), $form->GetValue('list'), $form->GetValue('inclusion'));
				exit;
			}
		} else {
			if(strlen($form->GetValue('period')) == 0){
				$form->AddError('Please select a period to report on.', 'period');
			}
			if(strlen($form->GetValue('inclusion')) == 0){
				$form->AddError('Please select inclusion filter to report on.', 'period');
			}


			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('period'), $form->GetValue('list'), $form->GetValue('inclusion'));
				exit;
			}
		}
	}

	$page = new Page('Biggest Period Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Biggest Period.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select the report criteria.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('period'), $form->GetHTML('period'));
	echo $webForm->AddRow($form->GetLabel('list'), $form->GetHTML('list'));
	echo $webForm->AddRow($form->GetLabel('inclusion'), $form->GetHTML('inclusion'));
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

function report($start, $end, $period, $list, $inclusion){
	$orderTypes = array();
	$orderTypes['W'] = "Website (bltdirect.com)";
	$orderTypes['U'] = "Website (bltdirect.co.uk)";
	$orderTypes['L'] = "Website (lightbulbsuk.co.uk)";
	$orderTypes['M'] = "Mobile";
	$orderTypes['T'] = "Telesales";
	$orderTypes['F'] = "Fax";
	$orderTypes['E'] = "Email";

	$connections = getSyncConnections();

	$page = new Page('Biggest Period Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');
	?>

	<br />
	<h3>Biggest <?php print ($period == 'day') ? 'Day' : (($period == 'month') ? 'Month' : (($period == 'year') ? 'Year' : '')); ?></h3>
	<p>Biggest period statistics on orders between specified period.</p>

	<?php
	$dateFormat = '';

	if($period == 'day') {
		$dateFormat = '%Y-%m-%d';
	} elseif($period == 'month') {
		$dateFormat = '%Y-%m';
	} elseif($period == 'year') {
		$dateFormat = '%Y';
	}

	if(strlen($dateFormat) > 0) {
		if($inclusion == 'A') {
			if($list == 'T') {
				$data = new DataQuery(sprintf("SELECT DATE_FORMAT(o.Ordered_On, '%s') AS Date, COUNT(*) AS Orders, SUM(o.Total) AS Total, SUM(o.SubTotal) AS SubTotal FROM orders AS o WHERE o.Status NOT LIKE 'Cancelled' AND o.Status NOT LIKE 'Unauthorised' AND o.Ordered_On BETWEEN '%s' AND '%s' AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' GROUP BY DATE_FORMAT(o.Ordered_On, '%s') ORDER BY Total DESC LIMIT 0, 20", $dateFormat, $start, $end, $dateFormat));
			} elseif($list == 'O') {
				$data = new DataQuery(sprintf("SELECT DATE_FORMAT(o.Ordered_On, '%s') AS Date, COUNT(*) AS Orders, SUM(o.Total) AS Total, SUM(o.SubTotal) AS SubTotal FROM orders AS o WHERE o.Status NOT LIKE 'Cancelled' AND o.Status NOT LIKE 'Unauthorised' AND o.Ordered_On BETWEEN '%s' AND '%s' AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' GROUP BY DATE_FORMAT(o.Ordered_On, '%s') ORDER BY Orders DESC LIMIT 0, 20", $dateFormat, $start, $end, $dateFormat));
			}
		} elseif($inclusion == 'T') {
			if($list == 'T') {
				$data = new DataQuery(sprintf("SELECT DATE_FORMAT(o.Ordered_On, '%s') AS Date, COUNT(*) AS Orders, SUM(o.Total) AS Total, SUM(o.SubTotal) AS SubTotal FROM orders AS o WHERE o.Order_Prefix<>'W' AND o.Status NOT LIKE 'Cancelled' AND o.Status NOT LIKE 'Unauthorised' AND o.Ordered_On BETWEEN '%s' AND '%s' AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' GROUP BY DATE_FORMAT(o.Ordered_On, '%s') ORDER BY Total DESC LIMIT 0, 20", $dateFormat, $start, $end, $dateFormat));
			} elseif($list == 'O') {
				$data = new DataQuery(sprintf("SELECT DATE_FORMAT(o.Ordered_On, '%s') AS Date, COUNT(*) AS Orders, SUM(o.Total) AS Total, SUM(o.SubTotal) AS SubTotal FROM orders AS o WHERE o.Order_Prefix<>'W' AND o.Status NOT LIKE 'Cancelled' AND o.Status NOT LIKE 'Unauthorised' AND o.Ordered_On BETWEEN '%s' AND '%s' AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' GROUP BY DATE_FORMAT(o.Ordered_On, '%s') ORDER BY Orders DESC LIMIT 0, 20", $dateFormat, $start, $end, $dateFormat));
			}
		}

		if($data->TotalRows > 0) {
			?>

			<table width="100%" border="0">
			  <tr>
			  	<td style="border-bottom:1px solid #aaaaaa"><strong>Rank #</strong></td>
				<td style="border-bottom:1px solid #aaaaaa"><strong>Date</strong></td>
				<td style="border-bottom:1px solid #aaaaaa"><strong>Orders</strong></td>
				<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Sub Total</strong></td>
				<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Total</strong></td>
			  </tr>

			 <?php
			 $rank = 1;

			 while($data->Row) {
			 	$oldDate = explode('-', $data->Row['Date']);
			 	$newDate = array();
			 	$newDate[] = $oldDate[0];

			 	if(!isset($oldDate[1])) {
			 		$newDate[] = 1;
			 		$newDate[] = 1;
			 	} else {
					$newDate[] = $oldDate[1];

					if(!isset($oldDate[2])) {
						$newDate[] = 1;
					} else {
						$newDate[] = $oldDate[2];
					}
			 	}

			 	$startDate = date('Y-m-d 00:00:00', mktime(0, 0, 0, date($newDate[1]), date($newDate[2]), date($newDate[0])));

			 	if($period == 'day') {
			 		$newDate[2]++;
			 	} elseif($period == 'month') {
			 		$newDate[1]++;
			 	} elseif($period == 'year') {
			 		$newDate[0]++;
			 	}

			 	$endDate = date('Y-m-d 00:00:00', mktime(0, 0, 0, date($newDate[1]), date($newDate[2]), date($newDate[0])));

				if($inclusion == 'A') {
					$accumulation = array();

					$totalOrders = 0;
					$totalSub = 0;
					$totalGross = 0;

					for($k=0; $k<count($connections); $k++) {
						$data2 = new DataQuery(sprintf("SELECT o.Order_Prefix, COUNT(*) AS Orders, SUM(o.Total) AS Total, SUM(o.SubTotal) AS SubTotal FROM orders AS o WHERE o.Status NOT LIKE 'Cancelled' AND o.Status NOT LIKE 'Unauthorised' AND o.Ordered_On BETWEEN '%s' AND '%s' AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' GROUP BY Order_Prefix", $startDate, $endDate), $connections[$k]['Connection']);
						while($data2->Row) {
							if(!isset($accumulation[$data2->Row['Order_Prefix']])) {
								$accumulation[$data2->Row['Order_Prefix']] = array('Orders' => 0, 'SubTotal' => 0, 'Total' => 0);
							}

							$accumulation[$data2->Row['Order_Prefix']]['Orders'] += $data2->Row['Orders'];
							$accumulation[$data2->Row['Order_Prefix']]['SubTotal'] += $data2->Row['SubTotal'];
							$accumulation[$data2->Row['Order_Prefix']]['Total'] += $data2->Row['Total'];

							$totalOrders += $data2->Row['Orders'];
							$totalSub += $data2->Row['SubTotal'];
							$totalGross += $data2->Row['Total'];

							$data2->Next();
						}
						$data2->Disconnect();
					}
					?>

					<tr class="dataRowOver">
						<td><?php echo $rank; ?></td>
						<td><?php echo $data->Row['Date']; ?></td>
						<td><?php echo $totalOrders; ?></td>
						<td align="right">&pound;<?php echo number_format($totalSub, 2, '.', ','); ?></td>
						<td align="right"><strong>&pound;<?php echo number_format($totalGross, 2, '.', ','); ?></strong></td>
					  </tr>

					<?php


					foreach($accumulation as $prefix=>$dataArray) {
						?>

						  <tr class="dataRow">
							<td style="color: <?php echo ($prefix == 'L') ? '#933' : '#339'; ?>;">&nbsp;<?php print $orderTypes[$prefix]; ?></td>
							<td>&nbsp;</td>
							<td><?php echo $dataArray['Orders']; ?></td>
							<td align="right">&pound;<?php echo number_format($dataArray['SubTotal'], 2, '.', ','); ?></td>
							<td align="right">&pound;<?php echo number_format($dataArray['Total'], 2, '.', ','); ?></td>
						  </tr>

						<?php
					}
				} elseif($inclusion == 'T') {
					?>

					<tr class="dataRowOver">
						<td><?php echo $rank; ?></td>
						<td><?php echo $data->Row['Date']; ?></td>
						<td><?php echo $data->Row['Orders']; ?></td>
						<td align="right">&pound;<?php echo number_format($data->Row['SubTotal'], 2, '.', ','); ?></td>
						<td align="right"><strong>&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></strong></td>
					  </tr>

					    <?php
				}

				$rank++;
				$data->Next();
			 }
		 	?>

			</table>

		  <?php
		}
		$data->Disconnect();
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>