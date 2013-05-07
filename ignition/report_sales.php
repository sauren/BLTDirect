<?php
require_once('lib/common/app_header.php');

if($action == 'export') {
	$session->Secure(2);
	export();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function export() {
	if(isset($_REQUEST['file'])) {
		redirect(sprintf("Location: %s%s", $GLOBALS['TEMP_REPORT_DIR_WS'], $_REQUEST['file']));
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function breakRow($row) {
	$str = '';

	foreach($row as $cell) {
		$str .= $cell;
	}

	return sprintf("%s\n", substr($str, 0, -1));
}

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

			report($start, $end, $form->GetValue('range'));
			exit;
		} else {

			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('range'));
				exit;
			}
		}
	}

	$page = new Page('Sales Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Sales.");
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

function report($start, $end, $range) {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Region.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$orderTypes = array();
	$orderTypes['W'] = "Website (bltdirect.com)";
	$orderTypes['U'] = "Website (bltdirect.co.uk)";
	$orderTypes['L'] = "Website (lightbulbsuk.co.uk)";
	$orderTypes['M'] = "Mobile";
	$orderTypes['T'] = "Telesales";
	$orderTypes['F'] = "Fax";
	$orderTypes['E'] = "Email";
	$orderTypes['N'] = "Not Received";
	$orderTypes['R'] = "Return";
	$orderTypes['B'] = "Broken";

	$referrersArray = array();

	$page = new Page('Sales Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	$contents = '';

	$line = array();
	$line[] = sprintf('"Order Type",');
	$line[] = sprintf('"Number",');
	$line[] = sprintf('"Sub Total",');
	$line[] = sprintf('"Discounts",');
	$line[] = sprintf('"Net",');
	$line[] = sprintf('"Shipping",');
	$line[] = sprintf('"Tax",');
	$line[] = sprintf('"Gross",');
	$line[] = sprintf('"%%",');

	$contents .= breakRow($line);
	?>

	<br />
	<h3>Orders</h3>
	<p>Order statistics on all orders. These are figures on orders taken and are not reflective of actual monies received.</p>

	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Order Type</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Sub Total</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Discounts</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Net</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Shipping</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Tax</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Gross</strong></td>
		<td style="border-bottom:1px solid #aaaaaa">&nbsp;</td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>%</strong></td>
	  </tr>

	 <?php
	 $connections = getSyncConnections();

	 $totalOrders = 0;
	 $totalSubTotal = 0;
	 $totalDiscount = 0;
	 $totalNet = 0;
	 $totalShipping = 0;
	 $totalTax = 0;
	 $total = 0;

	 for($i=0;$i<count($connections);$i++) {
	 	$getTotalOrders = new DataQuery(sprintf("select count(Order_ID) as OrderCount from orders where Created_On between '%s' and '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N'", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$i]['Connection']);
	 	$totalOrders += $getTotalOrders->Row['OrderCount'];
	 	$getTotalOrders->Disconnect();
	 }

	 for($i=0;$i<count($connections);$i++) {
	 	$orders = new DataQuery(sprintf("select count(Order_ID) as OrderCount, Order_Prefix, sum(SubTotal) as SubTotal, sum(TotalDiscount) as TotalDiscount, sum(TotalShipping) as TotalShipping, sum(TotalTax) as TotalTax, sum(Total) as Total from orders where Created_On between '%s' and '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N' group by Order_Prefix", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$i]['Connection']);
	 	while($orders->Row){
	 		$totalSubTotal += $orders->Row['SubTotal'];
	 		$totalShipping += $orders->Row['TotalShipping'];
	 		$totalTax += $orders->Row['TotalTax'];
			$totalDiscount += $orders->Row['TotalDiscount'];
	 		$total += $orders->Row['Total'];

			$line = array();
			$line[] = sprintf('"%s",', $orderTypes[$orders->Row['Order_Prefix']]);
			$line[] = sprintf('"%s",', $orders->Row['OrderCount']);
			$line[] = sprintf('"%s",', number_format($orders->Row['SubTotal'], 2, '.', ','));
			$line[] = sprintf('"%s",', number_format($orders->Row['TotalDiscount'], 2, '.', ','));
			$line[] = sprintf('"%s",', number_format($orders->Row['SubTotal']-$orders->Row['TotalDiscount'], 2, '.', ','));
			$line[] = sprintf('"%s",', number_format($orders->Row['TotalShipping'], 2, '.', ','));
			$line[] = sprintf('"%s",', number_format($orders->Row['TotalTax'], 2, '.', ','));
			$line[] = sprintf('"%s",', number_format($orders->Row['Total'], 2, '.', ','));
			$line[] = sprintf('"%s",', round(($orders->Row['OrderCount']/$totalOrders)*100));

			$contents .= breakRow($line);

	 		if($orders->Row['OrderCount'] > 0) {
				?>

			  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo $orderTypes[$orders->Row['Order_Prefix']]; ?></td>
				<td align="right"><?php echo $orders->Row['OrderCount']; ?></td>
				<td align="right">&pound;<?php echo number_format($orders->Row['SubTotal'], 2, '.', ','); ?></td>
				<td align="right">-&pound;<?php echo number_format($orders->Row['TotalDiscount'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format(($orders->Row['SubTotal']-$orders->Row['TotalDiscount']), 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($orders->Row['TotalShipping'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($orders->Row['TotalTax'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($orders->Row['Total'], 2, '.', ','); ?></td>
				<td><img src="images/bar_green_1.gif" border="0" height="16" width="<?php echo round(($orders->Row['OrderCount']/$totalOrders)*100); ?>" /></td>
				<td align="right"><?php echo round(($orders->Row['OrderCount']/$totalOrders)*100); ?>%</td>
			  </tr>

				<?php
	 		}
	 		$orders->Next();
	 	}
	 	$orders->Disconnect();
	 }
	?>

	 <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
	 	<td><strong>Totals</td>
		<td align="right"><strong><?php echo $totalOrders; ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalSubTotal, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>-&pound;<?php echo number_format($totalDiscount, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal-$totalDiscount), 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalShipping, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalTax, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
		<td>&nbsp;</td>
		<td align="right"><strong>100%</strong></td>
	  </tr>

	  <?php
	  if($range == 'thismonth') {
	  	$days = date('t');
	  	$past = substr($end, 8, 2);
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Monthly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalOrders / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

	  	<?php
	  } elseif($range == 'thisyear') {
	  	$days = 365 + date('L');
	  	$past = date('z') + 1;
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Yearly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalOrders / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

  		<?php
	} elseif($range == 'thisfinancialyear') {
	  	$days = 365.25;
	  	$boundary = 120 + date('L');
	  	$index = date('z') + 1;

	  	if($index < $boundary) {
	  		$past = ($days - $boundary) + $index;
	  	} else {
	  		$past = $index - $boundary;
	  	}
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Yearly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalOrders / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

	  	<?php
	  }
	  ?>
	</table>
	<br />

	<?php
	$fileName = sprintf('sales_%s.csv', date('ymdHis'));

	$fh = fopen($GLOBALS['TEMP_REPORT_DIR_FS'].$fileName, 'w') or die("Can't open file");
	fwrite($fh, $contents);
	fclose($fh);
	?>

	<input type="button" class="btn" value="export to csv" onclick="window.self.location.href='<?php print $_SERVER['PHP_SELF']; ?>?action=export&file=<?php echo $fileName; ?>'" />
	<br /><br />

	<h3>Watch Lists</h3>
	<p>Orders for watched products.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom: 1px solid #aaaaaa;" width="50%"><strong>Name</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;" width="25%" align="right"><strong>Number</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;" width="25%" align="right"><strong>Net</strong></td>
		</tr>

		<?php
		$data = new DataQuery(sprintf("SELECT pw.Name, SUM(ol.Line_Total-ol.Line_Discount) AS Total, SUM(ol.Quantity) AS Quantity FROM product_watch AS pw INNER JOIN product_watch_item AS pwi ON pwi.ProductWatchID=pw.ProductWatchID INNER JOIN order_line AS ol ON ol.Product_ID=pwi.ProductID INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID WHERE o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' GROUP BY pw.ProductWatchID ORDER BY pw.Name ASC", mysql_real_escape_string($start), mysql_real_escape_string($end)));
		if($data->TotalRows > 0) {
			while ($data->Row) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $data->Row['Name']; ?></td>
					<td align="right"><?php echo $data->Row['Quantity']; ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
				</tr>

				<?php
				$data->Next();
			}
		} else {
			?>

			<tr>
				<td align="center" colspan="3">No Statistics Available</td>
			</tr>

			<?php
		}
		$data->Disconnect();
		?>

	</table>
	<br />

	<h3>Invoiced</h3>
	<p>Invoice statistics on all invoices. These are figures on invoices to customers and should be reflective of actual monies received.</p>
	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Invoice Type</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Sub Total</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Discounts</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Net</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Shipping</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Tax</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Gross</strong></td>
		<td style="border-bottom:1px solid #aaaaaa">&nbsp;</td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>%</strong></td>
	  </tr>
	 <?php
	 $connections = getSyncConnections();
	 $totalInvoices = 0;
	 $totalSubTotal = 0;
	 $totalDiscount = 0;
	 $totalNet = 0;
	 $totalShipping = 0;
	 $totalTax = 0;
	 $total = 0;

	 for($i=0;$i<count($connections);$i++) {
	 	$getTotalInvoices = new DataQuery(sprintf("select count(Invoice_ID) as InvoiceCount from invoice where Created_On between '%s' and '%s'", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$i]['Connection']);
	 	$totalInvoices += $getTotalInvoices->Row['InvoiceCount'];
	 	$getTotalInvoices->Disconnect();
	 }


	 for($i=0;$i<count($connections);$i++) {
	 	$invoices = new DataQuery(sprintf("select count(i.Invoice_ID) as InvoiceCount, o.Order_Prefix, sum(i.Invoice_Net) as SubTotal, sum(i.Invoice_Discount) as TotalDiscount, sum(i.Invoice_Shipping) as TotalShipping, sum(i.Invoice_Tax) as TotalTax, sum(i.Invoice_Total) as Total from invoice as i inner join orders as o on i.Order_ID=o.Order_ID where i.Created_On between '%s' and '%s' AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' group by o.Order_Prefix", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$i]['Connection']);
	 	while($invoices->Row){
	 		$totalSubTotal += $invoices->Row['SubTotal'];
	 		$totalShipping += $invoices->Row['TotalShipping'];
	 		$totalTax += $invoices->Row['TotalTax'];
			$totalDiscount += $invoices->Row['TotalDiscount'];
	 		$total += $invoices->Row['Total'];

	 		if($invoices->Row['InvoiceCount'] > 0) {
				?>

			  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo $orderTypes[$invoices->Row['Order_Prefix']]; ?></td>
				<td align="right"><?php echo $invoices->Row['InvoiceCount']; ?></td>
				<td align="right">&pound;<?php echo number_format($invoices->Row['SubTotal'], 2, '.', ','); ?></td>
				<td align="right">-&pound;<?php echo number_format($invoices->Row['TotalDiscount'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format(($invoices->Row['SubTotal']-$invoices->Row['TotalDiscount']), 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($invoices->Row['TotalShipping'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($invoices->Row['TotalTax'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($invoices->Row['Total'], 2, '.', ','); ?></td>
				<td><img src="images/bar_green_1.gif" border="0" height="16" width="<?php echo round(($invoices->Row['InvoiceCount']/$totalInvoices)*100); ?>" /></td>
				<td align="right"><?php echo round(($invoices->Row['InvoiceCount']/$totalInvoices)*100); ?>%</td>
			  </tr>
				<?php
	 		}
	 		$invoices->Next();
	 	}
	 	$invoices->Disconnect();
	 }
	?>

	 <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
	 	<td><strong>Totals</td>
		<td align="right"><strong><?php echo $totalInvoices; ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalSubTotal, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>-&pound;<?php echo number_format($totalDiscount, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal-$totalDiscount), 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalShipping, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalTax, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
		<td>&nbsp;</td>
		<td align="right"><strong>100%</strong></td>
	  </tr>

	  <?php
	  if($range == 'thismonth') {
	  	$days = date('t');
	  	$past = substr($end, 8, 2);
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Monthly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalInvoices / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

	  	<?php
	  } elseif($range == 'thisyear') {
	  	$days = 365 + date('L');
	  	$past = date('z') + 1;
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Yearly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalInvoices / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

  		<?php
	} elseif($range == 'thisfinancialyear') {
	  	$days = 365.25;
	  	$boundary = 120 + date('L');
	  	$index = date('z') + 1;

	  	if($index < $boundary) {
	  		$past = ($days - $boundary) + $index;
	  	} else {
	  		$past = $index - $boundary;
	  	}
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Yearly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalInvoices / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

	  	<?php
	  }
	  ?>
	</table><br />


<!-- Invoices to Credit Account Customers -->
	<h3>Invoices to Credit Account Customers</h3>
	<p>These are invoices on orders with payment method of credit account.</p>
	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Payment Type</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Sub Total</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Discounts</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Net</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Shipping</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Tax</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Gross</strong></td>
		<td style="border-bottom:1px solid #aaaaaa">&nbsp;</td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>%</strong></td>
	  </tr>
	 <?php
	 $connections = getSyncConnections();
	 $totalInvoices = 0;
	 $totalSubTotal = 0;
	 $totalDiscount = 0;
	 $totalNet = 0;
	 $totalShipping = 0;
	 $totalTax = 0;
	 $total = 0;

	 for($i=0;$i<count($connections);$i++) {
	 	$getTotalInvoices = new DataQuery(sprintf("select count(Invoice_ID) as InvoiceCount from invoice where Created_On between '%s' and '%s'", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$i]['Connection']);
	 	$totalInvoices += $getTotalInvoices->Row['InvoiceCount'];
	 	$getTotalInvoices->Disconnect();
	 }


	 for($i=0;$i<count($connections);$i++) {
	 	$invoices = new DataQuery(sprintf("select count(i.Invoice_ID) as InvoiceCount, o.Order_Prefix, sum(i.Invoice_Net) as SubTotal, sum(i.Invoice_Discount) as TotalDiscount, sum(i.Invoice_Shipping) as TotalShipping, sum(i.Invoice_Tax) as TotalTax, sum(i.Invoice_Total) as Total from invoice as i
												inner join orders as o on i.Order_ID=o.Order_ID
												where i.Created_On between '%s' and '%s' AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' and i.Payment_ID=0 group by o.Order_Prefix", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$i]['Connection']);
	 	while($invoices->Row){
	 		$totalSubTotal += $invoices->Row['SubTotal'];
	 		$totalShipping += $invoices->Row['TotalShipping'];
	 		$totalTax += $invoices->Row['TotalTax'];
			$totalDiscount += $invoices->Row['TotalDiscount'];
	 		$total += $invoices->Row['Total'];

	 		if($invoices->Row['InvoiceCount'] > 0) {
				?>

			  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo $orderTypes[$invoices->Row['Order_Prefix']]; ?></td>
				<td align="right"><?php echo $invoices->Row['InvoiceCount']; ?></td>
				<td align="right">&pound;<?php echo number_format($invoices->Row['SubTotal'], 2, '.', ','); ?></td>
				<td align="right">-&pound;<?php echo number_format($invoices->Row['TotalDiscount'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format(($invoices->Row['SubTotal']-$invoices->Row['TotalDiscount']), 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($invoices->Row['TotalShipping'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($invoices->Row['TotalTax'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($invoices->Row['Total'], 2, '.', ','); ?></td>
				<td><img src="images/bar_green_1.gif" border="0" height="16" width="<?php echo round(($invoices->Row['InvoiceCount']/$totalInvoices)*100); ?>" /></td>
				<td align="right"><?php echo round(($invoices->Row['InvoiceCount']/$totalInvoices)*100); ?>%</td>
			  </tr>
				<?php
	 		}
	 		$invoices->Next();
	 	}
	 	$invoices->Disconnect();
	 }
	?>

	 <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
	 	<td><strong>Totals</td>
		<td align="right"><strong><?php echo $totalInvoices; ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalSubTotal, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>-&pound;<?php echo number_format($totalDiscount, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal-$totalDiscount), 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalShipping, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalTax, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
		<td>&nbsp;</td>
		<td align="right"><strong>100%</strong></td>
	  </tr>

	  <?php
	  if($range == 'thismonth') {
	  	$days = date('t');
	  	$past = substr($end, 8, 2);
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Monthly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalInvoices / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

	  	<?php
	  } elseif($range == 'thisyear') {
	  	$days = 365 + date('L');
	  	$past = date('z') + 1;
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Yearly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalInvoices / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

  		<?php
	} elseif($range == 'thisfinancialyear') {
	  	$days = 365.25;
	  	$boundary = 120 + date('L');
	  	$index = date('z') + 1;

	  	if($index < $boundary) {
	  		$past = ($days - $boundary) + $index;
	  	} else {
	  		$past = $index - $boundary;
	  	}
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Yearly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalInvoices / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

	  	<?php
	  }
	  ?>
	</table><br />


<!-- Payments -->
	<h3>Credit Card Payments</h3>
	<p>Payment statistics on all invoices. These are payments on invoices by credit cards from customers and should be reflective of actual monies received.</p>
	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Payment Type</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Sub Total</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Discounts</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Net</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Shipping</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Tax</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Gross</strong></td>
		<td style="border-bottom:1px solid #aaaaaa">&nbsp;</td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>%</strong></td>
	  </tr>
	 <?php
	 $connections = getSyncConnections();
	 $totalInvoices = 0;
	 $totalSubTotal = 0;
	 $totalDiscount = 0;
	 $totalNet = 0;
	 $totalShipping = 0;
	 $totalTax = 0;
	 $total = 0;

	 for($i=0;$i<count($connections);$i++) {
	 	$getTotalInvoices = new DataQuery(sprintf("select count(Invoice_ID) as InvoiceCount from invoice where Created_On between '%s' and '%s'", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$i]['Connection']);
	 	$totalInvoices += $getTotalInvoices->Row['InvoiceCount'];
	 	$getTotalInvoices->Disconnect();
	 }


	 for($i=0;$i<count($connections);$i++) {
	 	$invoices = new DataQuery(sprintf("select count(i.Invoice_ID) as InvoiceCount, o.Order_Prefix, sum(i.Invoice_Net) as SubTotal, sum(i.Invoice_Discount) as TotalDiscount, sum(i.Invoice_Shipping) as TotalShipping, sum(i.Invoice_Tax) as TotalTax, sum(i.Invoice_Total) as Total from invoice as i
												inner join orders as o on i.Order_ID=o.Order_ID
												inner join payment as p on i.Payment_ID=p.Payment_ID
												where i.Created_On between '%s' and '%s' AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' and i.Payment_ID>0 and p.Status='OK' group by o.Order_Prefix", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$i]['Connection']);
	 	while($invoices->Row){
	 		$totalSubTotal += $invoices->Row['SubTotal'];
	 		$totalShipping += $invoices->Row['TotalShipping'];
	 		$totalTax += $invoices->Row['TotalTax'];
			$totalDiscount += $invoices->Row['TotalDiscount'];
	 		$total += $invoices->Row['Total'];

	 		if($invoices->Row['InvoiceCount'] > 0) {
				?>

			  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo $orderTypes[$invoices->Row['Order_Prefix']]; ?></td>
				<td align="right"><?php echo $invoices->Row['InvoiceCount']; ?></td>
				<td align="right">&pound;<?php echo number_format($invoices->Row['SubTotal'], 2, '.', ','); ?></td>
				<td align="right">-&pound;<?php echo number_format($invoices->Row['TotalDiscount'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format(($invoices->Row['SubTotal']-$invoices->Row['TotalDiscount']), 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($invoices->Row['TotalShipping'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($invoices->Row['TotalTax'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($invoices->Row['Total'], 2, '.', ','); ?></td>
				<td><img src="images/bar_green_1.gif" border="0" height="16" width="<?php echo round(($invoices->Row['InvoiceCount']/$totalInvoices)*100); ?>" /></td>
				<td align="right"><?php echo round(($invoices->Row['InvoiceCount']/$totalInvoices)*100); ?>%</td>
			  </tr>
				<?php
	 		}
	 		$invoices->Next();
	 	}
	 	$invoices->Disconnect();
	 }
	?>

	 <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
	 	<td><strong>Totals</td>
		<td align="right"><strong><?php echo $totalInvoices; ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalSubTotal, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>-&pound;<?php echo number_format($totalDiscount, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal-$totalDiscount), 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalShipping, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalTax, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
		<td>&nbsp;</td>
		<td align="right"><strong>100%</strong></td>
	  </tr>

	  <?php
	  if($range == 'thismonth') {
	  	$days = date('t');
	  	$past = substr($end, 8, 2);
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Monthly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalInvoices / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

	  	<?php
	  } elseif($range == 'thisyear') {
	  	$days = 365 + date('L');
	  	$past = date('z') + 1;
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Yearly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalInvoices / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

  		<?php
	} elseif($range == 'thisfinancialyear') {
	  	$days = 365.25;
	  	$boundary = 120 + date('L');
	  	$index = date('z') + 1;

	  	if($index < $boundary) {
	  		$past = ($days - $boundary) + $index;
	  	} else {
	  		$past = $index - $boundary;
	  	}
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Yearly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalInvoices / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

	  	<?php
	  }
	  ?>
	</table><br />

<!-- Refunds -->
	<h3>Refunds</h3>
	<p>These are refunds onto credit cards and should be reflective of actual monies outgoing.</p>
	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Refund Type</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Sub Total</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Shipping</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Tax</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Gross</strong></td>
		<td style="border-bottom:1px solid #aaaaaa">&nbsp;</td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>%</strong></td>
	  </tr>
	 <?php
	 $connections = getSyncConnections();
	 $totalRefunds = 0;
	 $totalSubTotal = 0;
	 $totalNet = 0;
	 $totalShipping = 0;
	 $totalTax = 0;
	 $total = 0;

	 for($i=0;$i<count($connections);$i++) {
	 	$getTotalRefunds = new DataQuery(sprintf("select count(Credit_Note_ID) as InvoiceCount from credit_note where Credited_On between '%s' and '%s'", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$i]['Connection']);
	 	$totalRefunds += $getTotalRefunds->Row['InvoiceCount'];
	 	$getTotalRefunds->Disconnect();
	 }


	 for($i=0;$i<count($connections);$i++) {
	 	$refunds = new DataQuery(sprintf("select count(i.Credit_Note_ID) as InvoiceCount, o.Order_Prefix, sum(i.TotalNet) as SubTotal, sum(i.TotalShipping) as TotalShipping, sum(i.TotalTax) as TotalTax, sum(i.Total) as Total from credit_note as i
												inner join orders as o on i.Order_ID=o.Order_ID
												where i.Credited_On between '%s' and '%s' AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' group by o.Order_Prefix", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$i]['Connection']);
	 	while($refunds->Row){
	 		$totalSubTotal += ($refunds->Row['SubTotal']-$refunds->Row['TotalShipping']);
	 		$totalShipping += $refunds->Row['TotalShipping'];
	 		$totalTax += $refunds->Row['TotalTax'];
	 		$total += $refunds->Row['Total'];

	 		if($refunds->Row['InvoiceCount'] > 0) {
				?>

			  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo $orderTypes[$refunds->Row['Order_Prefix']]; ?></td>
				<td align="right"><?php echo $refunds->Row['InvoiceCount']; ?></td>
				<td align="right">-&pound;<?php echo number_format(($refunds->Row['SubTotal']-$refunds->Row['TotalShipping']), 2, '.', ','); ?></td>
				<td align="right">-&pound;<?php echo number_format($refunds->Row['TotalShipping'], 2, '.', ','); ?></td>
				<td align="right">-&pound;<?php echo number_format($refunds->Row['TotalTax'], 2, '.', ','); ?></td>
				<td align="right">-&pound;<?php echo number_format($refunds->Row['Total'], 2, '.', ','); ?></td>
				<td><img src="images/bar_green_1.gif" border="0" height="16" width="<?php echo round(($refunds->Row['InvoiceCount']/$totalRefunds)*100); ?>" /></td>
				<td align="right"><?php echo round(($refunds->Row['InvoiceCount']/$totalRefunds)*100); ?>%</td>
			  </tr>
				<?php
	 		}
	 		$refunds->Next();
	 	}
	 	$refunds->Disconnect();
	 }
	?>

	 <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
	 	<td><strong>Totals</td>
		<td align="right"><strong><?php echo $totalRefunds; ?></strong></td>
		<td align="right"><strong>-&pound;<?php echo number_format($totalSubTotal, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>-&pound;<?php echo number_format($totalShipping, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>-&pound;<?php echo number_format($totalTax, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>-&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
		<td>&nbsp;</td>
		<td align="right"><strong>100%</strong></td>
	  </tr>

	  <?php
	  if($range == 'thismonth') {
	  	$days = date('t');
	  	$past = substr($end, 8, 2);
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Monthly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalRefunds / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>-&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>-&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>-&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>-&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

	  	<?php
	  } elseif($range == 'thisyear') {
	  	$days = 365 + date('L');
	  	$past = date('z') + 1;
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Yearly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalRefunds / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>-&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>-&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>-&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>-&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

  		<?php
	} elseif($range == 'thisfinancialyear') {
	  	$days = 365.25;
	  	$boundary = 120 + date('L');
	  	$index = date('z') + 1;

	  	if($index < $boundary) {
	  		$past = ($days - $boundary) + $index;
	  	} else {
	  		$past = $index - $boundary;
	  	}
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Estimated Yearly Totals</td>
			<td align="right"><strong><?php echo number_format(($totalRefunds / $past) * $days, 0, '.', ''); ?></strong></td>
			<td align="right"><strong>-&pound;<?php echo number_format(($totalSubTotal / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>-&pound;<?php echo number_format(($totalShipping / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>-&pound;<?php echo number_format(($totalTax / $past) * $days, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>-&pound;<?php echo number_format(($total / $past) * $days, 2, '.', ','); ?></strong></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>

	  	<?php
	  }
	  ?>
	</table><br />

<!-- Quotations -->
	<h3>Quotations</h3>
	<p>Quotations statistics for sales representatives.</p>
	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Name</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Sub Total </strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Shipping</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Tax</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Gross</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Average Gross</strong></td>
	  </tr>
	  <?php
	  $totalQuotes = 0;
	  $totalSubTotal = 0;
	  $totalShipping = 0;
	  $totalTax = 0;
	  $total = 0;
	  $totalAverage = 0;

	  $rep = new DataQuery(sprintf("select count(Quote_ID) as Count, Created_By, sum(Total) as Total, sum(Sub_Total) as SubTotal, sum(Total_Shipping) as TotalShipping, sum(Total_Tax) as TotalTax from quote where Created_By > 0 and Created_On between '%s' and '%s' AND Status<>'Cancelled' group by Created_By", mysql_real_escape_string($start), mysql_real_escape_string($end)));
	  if($rep->TotalRows > 0) {
	  	while($rep->Row){
	  		$user = new User($rep->Row['Created_By']);

			$totalQuotes += $rep->Row['Count'];
			$totalSubTotal += $rep->Row['SubTotal'];
			$totalShipping += $rep->Row['TotalShipping'];
			$totalTax += $rep->Row['TotalTax'];
			$total += $rep->Row['Total'];
		  ?>
		  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><?php echo $user->Person->Name . ' ' . $user->Person->LastName; ?></td>
			<td align="right"><?php echo $rep->Row['Count']; ?></td>
			<td align="right">&pound;<?php echo number_format($rep->Row['SubTotal'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($rep->Row['TotalShipping'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($rep->Row['TotalTax'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($rep->Row['Total'], 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($rep->Row['Total']/$rep->Row['Count'], 2, '.', ','); ?></td>

		  </tr>
		  <?php
		  $rep->Next();
	  	}

	  	$totalAverage =  $total / $totalQuotes;
	  	?>

	  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		 	<td><strong>Totals</td>
			<td align="right"><strong><?php echo $totalQuotes; ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalSubTotal, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalShipping, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalTax, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalAverage, 2, '.', ','); ?></strong></td>
		  </tr>
	  	<?php
	  } else {
	  	echo "<tr><td colspan=\"6\">No statistics available</td></tr>";
	  }
	  $rep->Disconnect();
	  ?>
	</table><br />

	<h3>Quote Convertions</h3>
	<p>Sale methods statistics on all converted quotes.</p>
	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Order Type</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Sub Total</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Shipping</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Tax</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Gross</strong></td>
		<td style="border-bottom:1px solid #aaaaaa">&nbsp;</td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>%</strong></td>
	  </tr>
	 <?php
	 $connections = getSyncConnections();
	 $totalOrders = 0;
	 $totalSubTotal = 0;
	 $totalShipping = 0;
	 $totalTax = 0;
	 $total = 0;

	 for($i=0;$i<count($connections);$i++) {

	 	$getTotalOrders = new DataQuery(sprintf("select count(Order_ID) as OrderCount from orders where Created_On between '%s' and '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N' AND Quote_ID>0", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$i]['Connection']);
	 	$totalOrders += $getTotalOrders->Row['OrderCount'];
	 	$getTotalOrders->Disconnect();
	 }

	 for($i=0;$i<count($connections);$i++) {
	 	$orders = new DataQuery(sprintf("select count(Order_ID) as OrderCount, Order_Prefix, sum(SubTotal) as SubTotal, sum(TotalShipping) as TotalShipping, sum(TotalTax) as TotalTax, sum(Total) as Total from orders where Created_On between '%s' and '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N' AND Quote_ID>0 group by Order_Prefix", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$i]['Connection']);
	 	while($orders->Row){
	 		$totalSubTotal += $orders->Row['SubTotal'];
	 		$totalShipping += $orders->Row['TotalShipping'];
	 		$totalTax += $orders->Row['TotalTax'];
	 		$total += $orders->Row['Total'];

	 		if($orders->Row['OrderCount'] > 0) {
				?>

			  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo $orderTypes[$orders->Row['Order_Prefix']]; ?></td>
				<td align="right"><?php echo $orders->Row['OrderCount']; ?></td>
				<td align="right">&pound;<?php echo number_format($orders->Row['SubTotal'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($orders->Row['TotalShipping'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($orders->Row['TotalTax'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($orders->Row['Total'], 2, '.', ','); ?></td>
				<td><img src="images/bar_green_1.gif" border="0" height="16" width="<?php echo round(($orders->Row['OrderCount']/$totalOrders)*100); ?>" /></td>
				<td align="right"><?php echo round(($orders->Row['OrderCount']/$totalOrders)*100); ?>%</td>
			  </tr>

				<?php
	 		}
	 		$orders->Next();
	 	}
	 	$orders->Disconnect();
	 }
	?>

	 <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
	 <td><strong>Totals</td>
		<td align="right"><strong><?php echo $totalOrders; ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalSubTotal, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalShipping, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalTax, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
		<td>&nbsp;</td>
		<td align="right"><strong>100%</strong></td>
	  </tr>
	</table><br />

	<h3>Sales Representatives</h3>
	<p>Sales representatives statistics on non-website orders.</p>
	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Name</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Sub Total </strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Shipping</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Tax</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Gross</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Average Gross</strong></td>
	  </tr>

	<?php
	$rep = new DataQuery(sprintf("select count(Order_ID) as Count, Owned_By, sum(Total) as Total, sum(SubTotal) as SubTotal, sum(TotalShipping) as TotalShipping, sum(TotalTax) as TotalTax from orders where Created_On between '%s' and '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix='T' GROUP BY Owned_By ORDER BY Owned_By DESC", mysql_real_escape_string($start), mysql_real_escape_string($end)));
	if($rep->TotalRows > 0) {
		while($rep->Row){
			if($rep->Row['Owned_By'] > 0) {
				$user = new User($rep->Row['Owned_By']);
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $user->Person->Name . ' ' . $user->Person->LastName; ?></td>
					<td align="right"><?php echo $rep->Row['Count']; ?></td>
					<td align="right">&pound;<?php echo number_format($rep->Row['SubTotal'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($rep->Row['TotalShipping'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($rep->Row['TotalTax'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($rep->Row['Total'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($rep->Row['Total']/$rep->Row['Count'], 2, '.', ','); ?></td>
				</tr>

				<?php
			}
			$rep->Next();
		}
	} else {
		echo "<tr><td colspan=\"6\">No statistics available</td></tr>";
	}
	$rep->Disconnect();
	?>
	</table><br />

	<h3>Coupon Contacts</h3>
	<p>Introductory coupons sent to potential customers.</p>
	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Name</strong></td>
		<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>

	  </tr>
	  <?php
	  $totalCoupons = 0;

	  $data = new DataQuery(sprintf("SELECT COUNT(Coupon_Contact_ID) AS Count, Created_By FROM coupon_contact WHERE Created_On BETWEEN '%s' AND '%s' GROUP BY Created_By ORDER BY Created_By DESC", mysql_real_escape_string($start), mysql_real_escape_string($end)));
	  if($data->TotalRows > 0) {
	  	while($data->Row){
	  		$user = new User($data->Row['Created_By']);
		  ?>
		  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><?php echo ($data->Row['Created_By'] > 0) ? ($user->Person->Name . ' ' . $user->Person->LastName) : 'Unknown'; ?></td>
			<td align="right"><?php echo $data->Row['Count']; ?></td>
		  </tr>
		  <?php
		  $totalCoupons += $data->Row['Count'];

		  $data->Next();
	  	}
	  	?>

	  	 <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><strong>Totals</strong></td>
			<td align="right"><strong><?php echo $totalCoupons; ?></strong></td>
		  </tr>

		  <?php
	  } else {
	  	echo "<tr><td colspan=\"2\">No statistics available</td></tr>";
	  }
	  $data->Disconnect();
	  ?>
	  </table><br />

	<?php
	$users = array();
	$enquiryIds = array();

	$data = new DataQuery(sprintf("SELECT User_ID FROM users"));
	while($data->Row){
		$item = array();
		$item['Created'] = 0;
		$item['Answered'] = 0;

		$enquiryIds[$data->Row['User_ID']] = '';

		$data2 = new DataQuery(sprintf("SELECT * FROM enquiry AS e INNER JOIN enquiry_line AS el ON e.Enquiry_ID=el.Enquiry_ID WHERE el.Is_Draft='N' AND e.Created_By=%d AND e.Created_On BETWEEN '%s' AND '%s' GROUP BY e.Enquiry_ID", $data->Row['User_ID'], mysql_real_escape_string($start), mysql_real_escape_string($end)));
		while($data2->Row) {
			$enquiryIds[$data->Row['User_ID']] .= sprintf(' e.Enquiry_ID<>%d AND ', $data2->Row['Enquiry_ID']);

			$item['Created']++;

			$data2->Next();
		}
		$data2->Disconnect();

		if(strlen($enquiryIds[$data->Row['User_ID']]) > 0) {
			$enquiryIds[$data->Row['User_ID']] = sprintf(' AND (%s) ', substr($enquiryIds[$data->Row['User_ID']], 0, -4));
		}

		$enquiries = array();

		$data2 = new DataQuery(sprintf("SELECT e.Enquiry_ID, e.Created_By FROM enquiry e INNER JOIN enquiry_line AS el ON e.Enquiry_ID=el.Enquiry_ID WHERE el.Is_Draft='N' AND el.Created_By=%d AND el.Created_On BETWEEN '%s' AND '%s' ORDER BY e.Enquiry_ID", $data->Row['User_ID'], mysql_real_escape_string($start), mysql_real_escape_string($end)));
		while($data2->Row) {
			if($data2->Row['Created_By'] != $data->Row['User_ID']) {
				if(!isset($enquiries[$data2->Row['Enquiry_ID']])) {
					$enquiries[$data2->Row['Enquiry_ID']] = true;

					$item['Answered']++;
				}
			}

			$data2->Next();
		}
		$data2->Disconnect();

		$users[$data->Row['User_ID']] = $item;

		$data->Next();
	}
	$data->Disconnect();

	foreach($users as $id=>$data) {
		if(($data['Created'] + $data['Answered']) == 0) {
			unset($users[$id]);
		}
	}
	?>

	<h3>Enquiries</h3>
	<p>Enquiry statistics for identifying ratio of enquiries created to enquiries answered.</p>
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Name</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Enquiries Created</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Enquiries Answered</strong></td>
		</tr>

		<?php
		if(count($users) > 0) {
			$totalCreated = 0;
			$totalAnswered = 0;

			foreach($users as $id=>$data) {
				$user = new User($id);
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)); ?></td>
					<td align="right"><?php echo $data['Created']; ?></td>
					<td align="right"><?php echo $data['Answered']; ?></td>
				</tr>

				<?php
				$totalCreated += $data['Created'];
				$totalAnswered += $data['Answered'];
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><strong>Totals</strong></td>
				<td align="right"><strong><?php echo $totalCreated; ?></strong></td>
				<td align="right"><strong><?php echo $totalAnswered; ?></strong></td>
			</tr>

			<?php
		} else {
			echo "<tr><td colspan=\"4\">No statistics available</td></tr>";
		}
		?>
	</table><br />

	<?php
	$connections = getSyncConnections();

	$orders = array();

	for($i=0;$i<count($connections);$i++) {
		$data = new DataQuery(sprintf("SELECT o.Order_Prefix, o.SubTotal, TotalShipping, TotalTax, Total, SUM(ol.Quantity * ol.Cost) AS Cost FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND (o.Order_Prefix='R' OR o.Order_Prefix='B' OR o.Order_Prefix='N') GROUP BY o.Order_ID", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$i]['Connection']);
		while($data->Row) {
			$orders[$data->Row['Order_Prefix']][] = $data->Row;

			$data->Next();
		}
		$data->Disconnect();
	}

	$totalOrders = 0;
	$totalSubTotal = 0;
	$totalShipping = 0;
	$totalTax = 0;
	$total = 0;
	$totalCost = 0;
	?>

	<h3>Return Orders</h3>
	<p>Listing return and not received order statistics.</p>
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Order Type</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Number</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Sub Total</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Shipping</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Tax</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Gross</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Cost</strong></td>
		</tr>

		<?php
		foreach($orders as $prefix=>$orderCollection) {
			$countOrder = 0;
			$countSubTotal = 0;
			$countShipping = 0;
			$countTax = 0;
			$countTotal = 0;
			$countCost = 0;

			foreach($orderCollection as $order) {
				$countOrder++;
				$countSubTotal += $order['SubTotal'];
				$countShipping += $order['TotalShipping'];
				$countTax += $order['TotalTax'];
				$countTotal += $order['Total'];
				$countCost += $order['Cost'];
			}

			$totalOrders += $countOrder;
			$totalSubTotal += $countSubTotal;
			$totalShipping += $countShipping;
			$totalTax += $countTax;
			$total += $countTotal;
			$totalCost += $countCost;
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo $prefix; ?></td>
				<td align="right"><?php echo $countOrder; ?></td>
				<td align="right">&pound;<?php echo number_format($countSubTotal, 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($countShipping, 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($countTax, 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($countTotal, 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($countCost, 2, '.', ','); ?></td>
			</tr>

			<?php
		}
		?>

	 <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><strong>Totals</td>
		<td align="right"><strong><?php echo $totalOrders; ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalSubTotal, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalShipping, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalTax, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
	  </tr>
	</table><br />

	<?php
	if(false) {
		?>

		<h3>Site Visits</h3>
		<p>Listing site visitors between the specified period.</p>
		<table width="100%" border="0">
			<tr>
				<td style="border-bottom:1px solid #aaaaaa"><strong>Type</strong></td>
				<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Frequency</strong></td>
			</tr>

			<?php
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM customer_session WHERE Created_On BETWEEN '%s' AND '%s'", mysql_real_escape_string($start), mysql_real_escape_string($end)));
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>Unique visitors</td>
				<td align="right"><?php echo $data->Row['Count']; ?></td>
			</tr>

			<?php
			$data->Disconnect();
			?>

			<?php
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM customer_session WHERE Created_On BETWEEN '%s' AND '%s' AND Customer_ID>0", mysql_real_escape_string($start), mysql_real_escape_string($end)));
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>Unique visitors who logged in</td>
				<td align="right"><?php echo $data->Row['Count']; ?></td>
			</tr>

			<?php
			$data->Disconnect();
			?>

		</table>

		<?php
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}