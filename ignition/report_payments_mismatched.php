<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Payment Mismatch Report', 'Please choose a start and end date for your report');
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
	$form->AddOption('range', 'lastmonth', 'Last Month');
	$form->AddOption('range', 'last3months', 'Last 3 Months');
	$form->AddOption('range', 'last6months', 'Last 6 Months');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastyear', 'Last Year');
	$form->AddOption('range', 'last2years', 'Last 2 Years');
	$form->AddOption('range', 'last3years', 'Last 3 Years');

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

			report($start, $end, $form->GetValue('parent'));
			exit;
		} else {
			
			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))));
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

	$window = new StandardWindow("Report on Payments Mismatched.");
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

function report($start, $end){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$page = new Page('Payment Mismatch Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');


	$tolerance = 0.05;

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_payment SELECT p.Order_ID, SUM(p.Amount) AS Amount FROM payment AS p INNER JOIN orders AS o ON o.Order_ID=p.Order_ID WHERE ((p.Transaction_Type='PAYMENT' OR p.Transaction_Type='AUTHORISE') AND p.Status LIKE 'OK') AND (o.Created_On BETWEEN '%s' AND '%s') AND o.Status LIKE 'Despatched' GROUP BY p.Order_ID", mysql_real_escape_string($start), mysql_real_escape_string($end)));

	  $data = new DataQuery(sprintf("SELECT o.Order_ID, o.Total, o.Created_On, p.Amount FROM temp_payment AS p INNER JOIN orders AS o ON o.Order_ID=p.Order_ID WHERE p.Amount NOT BETWEEN (o.Total-%f) AND (o.Total+%f) ORDER BY o.Order_ID DESC", mysql_real_escape_string($tolerance), mysql_real_escape_string($tolerance)));

	  $mismatches = array();
	  $totalUnder = 0;
	  $totalOver = 0;
	  $totalDiff = 0;

	  while($data->Row) {
	  	if($data->Row['Total'] != $data->Row['Amount']) {
			$diff = number_format((($data->Row['Total'] - $data->Row['Amount'])*-1), 2, '.', ',');
  			$mismatches[] = array	(
										'id' => $data->Row['Order_ID'],
										'date' => $data->Row['Created_On'],
										'total' => $data->Row['Total'],
										'payment' => $data->Row['Amount'],
										'diff' => $diff,
									);

			if($diff > 0) {
				$totalOver += $diff;
			} else {
				$totalUnder +=  $diff;
			}

			$totalDiff += $diff;
	  	}

	  	$data->Next();
	  }

	  $data->Disconnect();

	  new DataQuery("DROP TABLE temp_payment");
	  ?>
	<h3>Overall Mismatched Credit Card Payments</h3>
	<p>Overall mismatch details for this period. Calculated with a &pound;<?php print $tolerance; ?> tolerance.</p>
	<table width="100%" border="0" >
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>Total Mismatches:</td>
			<td align="right"><?php echo count($mismatches); ?></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>Total Undercharges:</td>
			<td align="right">&pound;<?php echo number_format($totalUnder, 2, '.', ','); ?></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>Total Overcharges:</td>
			<td align="right">&pound;<?php echo number_format($totalOver, 2, '.', ','); ?></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><strong>Total Difference:</strong></td>
			<td align="right"><strong>&pound;<?php echo (($totalDiff > 0) ? '+'.number_format($totalDiff, 2, '.', ',') : number_format($totalDiff, 2, '.', ',')); ?></strong></td>
		</tr>
	</table><br />
	<h3>Overcharged Credit Card Payments</h3>
	<p>Orders where the order total and credit card payment amounts do not match and have been overcharged.  Calculated with a &pound;<?php print $tolerance; ?> tolerance.</p>
	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>No</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Order Date</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Order No</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Order Total</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Payment Amount</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Difference</strong></td>
	  </tr>
	  <?php
	  $counter = 1;
	  for($i = 0; $i < count($mismatches); $i++) {
		if($mismatches[$i]['diff'] > 0) {
			?>
			  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>#<?php echo $counter; ?></td>
				<td><?php echo cDatetime($mismatches[$i]['date'], 'shortdate'); ?></td>
				<td align="right"><a href="order_details.php?orderid=<?php echo $mismatches[$i]['id']; ?>"><?php echo $mismatches[$i]['id']; ?></a></td>
				<td align="right">&pound;<?php echo $mismatches[$i]['total']; ?></td>
				<td align="right">&pound;<?php echo $mismatches[$i]['payment'] ?></td>
				<td align="right">&pound;<?php echo (($mismatches[$i]['diff'] > 0) ? '+'.$mismatches[$i]['diff'] : $mismatches[$i]['diff']); ?></td>
			  </tr>
			<?php
			$counter++;
		}
	  }
	  ?>
	  </table><br />

	  <h3>Undercharged Credit Card Payments</h3>
	<p>Orders where the order total and credit card payment amounts do not match and have been undercharged.  Calculated with a &pound;<?php print $tolerance; ?> tolerance.</p>
	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>No</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Order Date</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Order No</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Order Total</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Payment Amount</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Difference</strong></td>
	  </tr>
	  <?php
	   $counter = 1;
	  for($i = 0; $i < count($mismatches); $i++) {
		if($mismatches[$i]['diff'] < 0) {
			?>
			  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>#<?php echo $counter; ?></td>
				<td><?php echo cDatetime($mismatches[$i]['date'], 'shortdate'); ?></td>
				<td align="right"><a href="order_details.php?orderid=<?php echo $mismatches[$i]['id']; ?>"><?php echo $mismatches[$i]['id']; ?></a></td>
				<td align="right">&pound;<?php echo $mismatches[$i]['total']; ?></td>
				<td align="right">&pound;<?php echo $mismatches[$i]['payment'] ?></td>
				<td align="right">&pound;<?php echo (($mismatches[$i]['diff'] > 0) ? '+'.$mismatches[$i]['diff'] : $mismatches[$i]['diff']); ?></td>
			  </tr>
			<?php
	 	 $counter++;
		}
	  }
		?>
	  </table><br />
	  <?php

	  $page->Display('footer');
}?>