<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

if($action == 'report') {
	$session->Secure(2);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start() {
	$form = new Form($_SERVER['PHP_SELF']);
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

			redirect(sprintf("Location: %s?action=report&start=%s&end=%s", $_SERVER['PHP_SELF'], $start, $end));
		} else {
			if($form->Validate()){
				redirect(sprintf("Location: %s?action=report&start=%s&end=%s", $_SERVER['PHP_SELF'], sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2))))))));
			}
		}
	}

	$page = new Page('Profit Control Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br / >';
	}

	$window = new StandardWindow("Report on Profit Control.");
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
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

    echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	$connections = getSyncConnections();

    $form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start Date', 'hidden', '0000-00-00 00:00:00', 'anything', 1, 19);
	$form->AddField('end', 'End Date', 'hidden', '0000-00-00 00:00:00', 'anything', 1, 19);

	$page = new Page('Profit Control Report: ' . cDatetime($form->GetValue('start'), 'longdatetime') . ' to ' . cDatetime($form->GetValue('end'), 'longdatetime'), '');
	$page->Display('header');

	$types = array();
    $types['T'] = 'Telesales';
	$types['W'] = 'Website (bltdirect.com)';
	$types['U'] = 'Website (bltdirect.co.uk)';

	$overheads = array();

    $data2 = new DataQuery(sprintf("SELECT * FROM overhead"));
	while($data2->Row) {
		$overheads[] = $data2->Row;

        $data2->Next();
	}
	$data2->Disconnect();
	?>

	<h3>Profit Summary</h3>
	<br />

    <table width="100%" border="0">
		<tr>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Orders</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>This Period</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>This Period Last Year</strong></td>
		</tr>

		<?php
		$totalTurnoverThis = 0;
		$totalProfitThis = 0;
		$totalTurnoverLast = 0;
		$totalProfitLast = 0;

		foreach($types as $prefix=>$type) {
			$data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID AND o.Order_Prefix='%s' WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Cost>0", mysql_real_escape_string($prefix), mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
			$turnoverThis = $data2->Row['Turnover'];
			$profitThis = $data2->Row['Profit'];
			$data2->Disconnect();

            $data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID AND o.Order_Prefix='%s' WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Cost>0", $prefix, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('start'))), date('d', strtotime($form->GetValue('start'))), date('Y', strtotime($form->GetValue('start'))) - 1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('end'))), date('d', strtotime($form->GetValue('end'))), date('Y', strtotime($form->GetValue('end'))) - 1))));
			$turnoverLast = $data2->Row['Turnover'];
			$profitLast = $data2->Row['Profit'];
			$data2->Disconnect();
			?>

			<tr>
				<td style="border-bottom:1px solid #dddddd;"><?php echo $type; ?></td>
				<td style="border-bottom:1px solid #dddddd;" align="right"><?php echo number_format(round($turnoverThis, 2), 2, '.', ','); ?><br /><small>Profit: <?php echo number_format(round($profitThis, 2), 2, '.', ','); ?></small><br /><small>Percent: <?php echo number_format(round(($turnoverThis > 0) ? (($profitThis / $turnoverThis) * 100) : 0, 2), 2, '.', ','); ?></small></td>
				<td style="border-bottom:1px solid #dddddd;" align="right"><?php echo number_format(round($turnoverLast, 2), 2, '.', ','); ?><br /><small>Profit: <?php echo number_format(round($profitLast, 2), 2, '.', ','); ?></small><br /><small>Percent: <?php echo number_format(round(($turnoverLast > 0) ? (($profitLast / $turnoverLast) * 100) : 0, 2), 2, '.', ','); ?></small></td>
			</tr>

			<?php
	        $totalTurnoverThis += $turnoverThis;
			$totalProfitThis += $profitThis;
            $totalTurnoverLast += $turnoverLast;
			$totalProfitLast += $profitLast;
		}
        $data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID AND o.Order_Prefix='%s' WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Cost>0", 'L', mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))), $connections[1]['Connection']);
		$turnoverThis = $data2->Row['Turnover'];
		$profitThis = $data2->Row['Profit'];
		$data2->Disconnect();

        $data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID AND o.Order_Prefix='%s' WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Cost>0", 'L', date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('start'))), date('d', strtotime($form->GetValue('start'))), date('Y', strtotime($form->GetValue('start'))) - 1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('end'))), date('d', strtotime($form->GetValue('end'))), date('Y', strtotime($form->GetValue('end'))) - 1))), $connections[1]['Connection']);
		$turnoverLast = $data2->Row['Turnover'];
		$profitLast = $data2->Row['Profit'];
		$data2->Disconnect();
        ?>

		<tr>
			<td style="border-bottom:1px solid #dddddd;">Website (lightbulbsuk.co.uk)</td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><?php echo number_format(round($turnoverThis, 2), 2, '.', ','); ?><br /><small>Profit: <?php echo number_format(round($profitThis, 2), 2, '.', ','); ?></small><br /><small>Percent: <?php echo number_format(round(($turnoverThis > 0) ? (($profitThis / $turnoverThis) * 100) : 0, 2), 2, '.', ','); ?></small></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><?php echo number_format(round($turnoverLast, 2), 2, '.', ','); ?><br /><small>Profit: <?php echo number_format(round($profitLast, 2), 2, '.', ','); ?></small><br /><small>Percent: <?php echo number_format(round(($turnoverLast > 0) ? (($profitLast / $turnoverLast) * 100) : 0, 2), 2, '.', ','); ?></small></td>
		</tr>

		<?php
        $totalTurnoverThis += $turnoverThis;
		$totalProfitThis += $profitThis;
        $totalTurnoverLast += $turnoverLast;
		$totalProfitLast += $profitLast;

		$overheadThis = calculateOverhead($overheads, strtotime(substr($form->GetValue('start'), 0, 10)), strtotime(substr($form->GetValue('end'), 0, 10)));
		$overheadLast = calculateOverhead($overheads, mktime(0, 0, 0, date('m', strtotime($form->GetValue('start'))), date('d', strtotime($form->GetValue('start'))), date('Y', strtotime($form->GetValue('start'))) - 1), mktime(0, 0, 0, date('m', strtotime($form->GetValue('end'))), date('d', strtotime($form->GetValue('end'))), date('Y', strtotime($form->GetValue('end'))) - 1));
		?>

        <tr>
			<td style="border-bottom:1px solid #dddddd;">&nbsp;</td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><?php echo number_format(round($totalTurnoverThis, 2), 2, '.', ','); ?><br /><small>Profit: <?php echo number_format(round($totalProfitThis, 2), 2, '.', ','); ?></small><br /><small>Percent: <?php echo number_format(round(($totalTurnoverThis > 0) ? (($totalProfitThis / $totalTurnoverThis) * 100) : 0, 2), 2, '.', ','); ?></small></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><?php echo number_format(round($totalTurnoverLast, 2), 2, '.', ','); ?><br /><small>Profit: <?php echo number_format(round($totalProfitLast, 2), 2, '.', ','); ?></small><br /><small>Percent: <?php echo number_format(round(($totalTurnoverLast > 0) ? (($totalProfitLast / $totalTurnoverLast) * 100) : 0, 2), 2, '.', ','); ?></small></td>
		</tr>
        <tr>
			<td style="border-bottom:1px solid #dddddd;">Overheads</td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><?php echo number_format(round($overheadThis, 2), 2, '.', ','); ?></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><?php echo number_format(round($overheadLast, 2), 2, '.', ','); ?></td>
		</tr>
        <tr>
			<td style="border-bottom:1px solid #dddddd;">Gross Profit</td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><strong><?php echo number_format(round($totalProfitThis - $overheadThis, 2), 2, '.', ','); ?><br /><small>Percent: <?php echo number_format(round((($totalProfitThis - $overheadThis) / $totalTurnoverThis) * 100, 2), 2, '.', ','); ?></small></strong></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><strong><?php echo number_format(round($totalProfitLast - $overheadLast, 2), 2, '.', ','); ?><br /><small>Percent: <?php echo number_format(round((($totalProfitLast - $overheadLast) / $totalTurnoverLast) * 100, 2), 2, '.', ','); ?></small></strong></td>
		</tr>
	</table>

	<br />
    <h3>Shipping Summary</h3>
	<br />
	
	<?php
	$postageStandard = array($GLOBALS['DEFAULT_POSTAGE']);
	$postageNext = array();
	
	$data = new DataQuery(sprintf("SELECT Postage_ID FROM postage WHERE Postage_Days=1"));
	while($data->Row) {
		$postageNext[] = $data->Row['Postage_ID'];
		
		$data->Next();
	}
	$data->Disconnect();
	
	$shippingItems = array();
	$totalIndex = 0;

	$sql = "SELECT SUM(o.TotalShipping) AS Shipping FROM orders AS o INNER JOIN (SELECT Order_ID, MAX(Created_On) FROM despatch WHERE Created_On>='%s' AND Created_On<'%s' GROUP BY Order_ID) AS d ON d.Order_ID=o.Order_ID WHERE o.Status LIKE 'Despatched'%s";
	
	$data2 = new DataQuery(sprintf($sql, mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end')), (count($postageStandard) > 0) ? sprintf(' AND (o.Postage_ID=%d)', implode(' OR o.Postage_ID=', $postageStandard)) : ''));
	$shippingThis = $data2->Row['Shipping'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('start'))), date('d', strtotime($form->GetValue('start'))), date('Y', strtotime($form->GetValue('start'))) - 1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('end'))), date('d', strtotime($form->GetValue('end'))), date('Y', strtotime($form->GetValue('end'))) - 1)), (count($postageStandard) > 0) ? sprintf(' AND (o.Postage_ID=%d)', implode(' OR o.Postage_ID=', $postageStandard)) : ''));
	$shippingLast = $data2->Row['Shipping'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingThis, $shippingLast), 'Name' => 'Shipping Charged (Standard Service)');
	
	$sql = "SELECT SUM(o.TotalShipping) AS Shipping FROM orders AS o INNER JOIN (SELECT Order_ID, MAX(Created_On) FROM despatch WHERE Created_On>='%s' AND Created_On<'%s' GROUP BY Order_ID) AS d ON d.Order_ID=o.Order_ID WHERE o.Status LIKE 'Despatched'%s";
	
	$data2 = new DataQuery(sprintf($sql, mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end')), (count($postageNext) > 0) ? sprintf(' AND (o.Postage_ID=%d)',  mysql_real_escape_string(implode(' OR o.Postage_ID=',$postageNext))) : ''));
	$shippingThis = $data2->Row['Shipping'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('start'))), date('d', strtotime($form->GetValue('start'))), date('Y', strtotime($form->GetValue('start'))) - 1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('end'))), date('d', strtotime($form->GetValue('end'))), date('Y', strtotime($form->GetValue('end'))) - 1)), (count($postageNext) > 0) ? sprintf(' AND (o.Postage_ID=%d)', implode(' OR o.Postage_ID=', $postageNext)) : ''));
	$shippingLast = $data2->Row['Shipping'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingThis, $shippingLast), 'Name' => 'Shipping Charged (Next Day Delivery)');
	
	$sql = "SELECT SUM(o.TotalShipping) AS Shipping FROM orders AS o INNER JOIN (SELECT Order_ID, MAX(Created_On) FROM despatch WHERE Created_On>='%s' AND Created_On<'%s' GROUP BY Order_ID) AS d ON d.Order_ID=o.Order_ID WHERE o.Status LIKE 'Despatched'%s%s";
	
	$data2 = new DataQuery(sprintf($sql, mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end')), (count($postageStandard) > 0) ? sprintf(' AND o.Postage_ID<>%d', mysql_real_escape_string(implode(' AND o.Postage_ID<>', $postageStandard))) : '', (count($postageNext) > 0) ? sprintf(' AND o.Postage_ID<>%d', mysql_real_escape_string(implode(' AND o.Postage_ID<>', $postageNext))) : ''));
	$shippingThis = $data2->Row['Shipping'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('start'))), date('d', strtotime($form->GetValue('start'))), date('Y', strtotime($form->GetValue('start'))) - 1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('end'))), date('d', strtotime($form->GetValue('end'))), date('Y', strtotime($form->GetValue('end'))) - 1)), (count($postageStandard) > 0) ? sprintf(' AND o.Postage_ID<>%d', mysql_real_escape_string(implode(' AND o.Postage_ID<>', $postageStandard))) : '', (count($postageNext) > 0) ? sprintf(' AND o.Postage_ID<>%d', mysql_real_escape_string(implode(' AND o.Postage_ID<>', $postageNext))) : ''));
	$shippingLast = $data2->Row['Shipping'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingThis, $shippingLast), 'Name' => 'Shipping Charged (Other Postage)');
	
	$totals = array();

	for($j=$totalIndex; $j<count($shippingItems); $j++) {
		for($i=0; $i<count($shippingItems[$j]['Columns']); $i++) {
			if(!isset($totals[$i])) {
				$totals[$i] = 0;
			}
			
			$totals[$i] += $shippingItems[$j]['Columns'][$i];
		}
	}
	
	$shippingItems[] = array('Columns' => $totals, 'Name' => 'Total Charged', 'Bold' => true);
	$totalIndex = 4;
	
	$sql = "SELECT SUM(d.Postage_Cost) AS Cost FROM despatch AS d INNER JOIN order_line AS ol ON d.Despatch_ID=ol.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='B' WHERE d.Created_On>='%s' AND d.Created_On<'%s'%s";
	
	$data2 = new DataQuery(sprintf($sql, mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end')), (count($postageStandard) > 0) ? sprintf(' AND (d.Postage_ID=%d)',  mysql_real_escape_string(implode(' OR d.Postage_ID=',$postageStandard))) : ''));
	$shippingThis = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('start'))), date('d', strtotime($form->GetValue('start'))), date('Y', strtotime($form->GetValue('start'))) - 1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('end'))), date('d', strtotime($form->GetValue('end'))), date('Y', strtotime($form->GetValue('end'))) - 1)), (count($postageStandard) > 0) ? sprintf(' AND (d.Postage_ID=%d)', mysql_real_escape_string(implode(' OR d.Postage_ID=', $postageStandard))) : ''));
	$shippingLast = $data2->Row['Cost'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingThis, $shippingLast), 'Name' => 'Shipping Branch Cost (Standard Service)');
	
	$sql = "SELECT SUM(d.Postage_Cost) AS Cost FROM despatch AS d INNER JOIN order_line AS ol ON d.Despatch_ID=ol.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='B' WHERE d.Created_On>='%s' AND d.Created_On<'%s'%s";
	
	$data2 = new DataQuery(sprintf($sql, mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end')), (count($postageNext) > 0) ? sprintf(' AND (d.Postage_ID=%d)',  mysql_real_escape_string(implode(' OR d.Postage_ID=',$postageNext))) : ''));
	$shippingThis = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('start'))), date('d', strtotime($form->GetValue('start'))), date('Y', strtotime($form->GetValue('start'))) - 1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('end'))), date('d', strtotime($form->GetValue('end'))), date('Y', strtotime($form->GetValue('end'))) - 1)), (count($postageNext) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageNext)) : ''));
	$shippingLast = $data2->Row['Cost'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingThis, $shippingLast), 'Name' => 'Shipping Branch Cost (Next Day Delivery)');
	
	$sql = "SELECT SUM(d.Postage_Cost) AS Cost FROM despatch AS d INNER JOIN order_line AS ol ON d.Despatch_ID=ol.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='B' WHERE d.Created_On>='%s' AND d.Created_On<'%s'%s%s";
	
	$data2 = new DataQuery(sprintf($sql, mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end')), (count($postageStandard) > 0) ? sprintf(' AND d.Postage_ID<>%d', mysql_real_escape_string(implode(' AND d.Postage_ID<>',$postageStandard))) : '', (count($postageNext) > 0) ? sprintf(' AND d.Postage_ID<>%d', mysql_real_escape_string(implode(' AND d.Postage_ID<>', $postageNext))) : ''));
	$shippingThis = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('start'))), date('d', strtotime($form->GetValue('start'))), date('Y', strtotime($form->GetValue('start'))) - 1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('end'))), date('d', strtotime($form->GetValue('end'))), date('Y', strtotime($form->GetValue('end'))) - 1)), (count($postageStandard) > 0) ? sprintf(' AND d.Postage_ID<>%d',  mysql_real_escape_string(implode(' AND d.Postage_ID<>',$postageStandard))) : '', (count($postageNext) > 0) ? sprintf(' AND d.Postage_ID<>%d', mysql_real_escape_string(implode(' AND d.Postage_ID<>',$postageNext))) : ''));
	$shippingLast = $data2->Row['Cost'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingThis, $shippingLast), 'Name' => 'Shipping Branch Cost (Other Postage)');
	
	$totals = array();
	
	for($j=$totalIndex; $j<count($shippingItems); $j++) {
		for($i=0; $i<count($shippingItems[$j]['Columns']); $i++) {
			if(!isset($totals[$i])) {
				$totals[$i] = 0;
			}
			
			$totals[$i] += $shippingItems[$j]['Columns'][$i];
		}
	}
	
	$shippingItems[] = array('Columns' => $totals, 'Name' => 'Total Branch Cost', 'Bold' => true);
	$totalIndex = 8;
	
	$sql = "SELECT SUM(d.Postage_Cost) AS Cost FROM despatch AS d INNER JOIN order_line AS ol ON d.Despatch_ID=ol.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' WHERE d.Created_On>='%s' AND d.Created_On<'%s'%s";

	$data2 = new DataQuery(sprintf($sql, mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end')), (count($postageStandard) > 0) ? sprintf(' AND (d.Postage_ID=%d)', mysql_real_escape_string(implode(' OR d.Postage_ID=', $postageStandard))) : ''));
	$shippingThis = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('start'))), date('d', strtotime($form->GetValue('start'))), date('Y', strtotime($form->GetValue('start'))) - 1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('end'))), date('d', strtotime($form->GetValue('end'))), date('Y', strtotime($form->GetValue('end'))) - 1)), (count($postageStandard) > 0) ? sprintf(' AND (d.Postage_ID=%d)', mysql_real_escape_string(implode(' OR d.Postage_ID=', $postageStandard))) : ''));
	$shippingLast = $data2->Row['Cost'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingThis, $shippingLast), 'Name' => 'Shipping Supplier Cost (Standard Service)');
	
	$sql = "SELECT SUM(d.Postage_Cost) AS Cost FROM despatch AS d INNER JOIN order_line AS ol ON d.Despatch_ID=ol.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' WHERE d.Created_On>='%s' AND d.Created_On<'%s'%s";
	
	$data2 = new DataQuery(sprintf($sql, mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end')), (count($postageNext) > 0) ? sprintf(' AND (d.Postage_ID=%d)', mysql_real_escape_string(implode(' OR d.Postage_ID=', $postageNext))) : ''));
	$shippingThis = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('start'))), date('d', strtotime($form->GetValue('start'))), date('Y', strtotime($form->GetValue('start'))) - 1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('end'))), date('d', strtotime($form->GetValue('end'))), date('Y', strtotime($form->GetValue('end'))) - 1)), (count($postageNext) > 0) ? sprintf(' AND (d.Postage_ID=%d)', mysql_real_escape_string(implode(' OR d.Postage_ID=', $postageNext))) : ''));
	$shippingLast = $data2->Row['Cost'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingThis, $shippingLast), 'Name' => 'Shipping Supplier Cost (Next Day Delivery)');
	
	$sql = "SELECT SUM(d.Postage_Cost) AS Cost FROM despatch AS d INNER JOIN order_line AS ol ON d.Despatch_ID=ol.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' WHERE d.Created_On>='%s' AND d.Created_On<'%s'%s%s";
	
	$data2 = new DataQuery(sprintf($sql, mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end')), (count($postageStandard) > 0) ? sprintf(' AND d.Postage_ID<>%d', mysql_real_escape_string(implode(' AND d.Postage_ID<>',$postageStandard))) : '', (count($postageNext) > 0) ? sprintf(' AND d.Postage_ID<>%d', mysql_real_escape_string(implode(' AND d.Postage_ID<>',$postageNext))) : ''));
	$shippingThis = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('start'))), date('d', strtotime($form->GetValue('start'))), date('Y', strtotime($form->GetValue('start'))) - 1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('end'))), date('d', strtotime($form->GetValue('end'))), date('Y', strtotime($form->GetValue('end'))) - 1)), (count($postageStandard) > 0) ? sprintf(' AND d.Postage_ID<>%d', mysql_real_escape_string(implode(' AND d.Postage_ID<>', $postageStandard))) : '', (count($postageNext) > 0) ? sprintf(' AND d.Postage_ID<>%d', mysql_real_escape_string(implode(' AND d.Postage_ID<>',$postageNext))) : ''));
	$shippingLast = $data2->Row['Cost'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingThis, $shippingLast), 'Name' => 'Shipping Supplier Cost (Other Postage)');
	
	$totals = array();

	for($j=$totalIndex; $j<count($shippingItems); $j++) {
		for($i=0; $i<count($shippingItems[$j]['Columns']); $i++) {
			if(!isset($totals[$i])) {
				$totals[$i] = 0;
			}
			
			$totals[$i] += $shippingItems[$j]['Columns'][$i];
		}
	}
	
	$shippingItems[] = array('Columns' => $totals, 'Name' => 'Total Supplier Cost', 'Bold' => true);
	?>
	
    <table width="100%" border="0">
		<tr>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Orders</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>This Period</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>This Period Last Year</strong></td>
		</tr>

		<?php
		foreach($shippingItems as $shippingItem) {
			echo '<tr>';
			echo sprintf('<td style="border-bottom:1px solid #dddddd;">%s</td>', $shippingItem['Name']);
			
			foreach($shippingItem['Columns'] as $item) {
				echo sprintf('<td style="border-bottom: 1px solid #dddddd;%s" align="right">%s</td>', (isset($shippingItem['Bold']) && $shippingItem['Bold']) ? ' font-weight: bold;' : '', number_format(round($item, 2), 2, '.', ','));
			}
			
			echo '</tr>';
		}
		?>
		
	</table>

    <br />
	<h3>Products</h3>
	<br />

	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Product ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>This Period</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>This Period Last Year</strong></td>
		</tr>

		<?php
		$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title FROM product AS p WHERE p.Is_Profit_Control='Y' ORDER BY p.Product_ID ASC"));
		while($data->Row) {
            $data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", $data->Row['Product_ID'], mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
			$turnoverThis = $data2->Row['Turnover'];
			$profitThis = $data2->Row['Profit'];
			$data2->Disconnect();

            $data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", $data->Row['Product_ID'], date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('start'))), date('d', strtotime($form->GetValue('start'))), date('Y', strtotime($form->GetValue('start'))) - 1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($form->GetValue('end'))), date('d', strtotime($form->GetValue('end'))), date('Y', strtotime($form->GetValue('end'))) - 1))));
			$turnoverLast = $data2->Row['Turnover'];
			$profitLast = $data2->Row['Profit'];
			$data2->Disconnect();
			?>

            <tr>
				<td style="border-bottom:1px solid #dddddd;"><?php echo strip_tags($data->Row['Product_Title']); ?></td>
				<td style="border-bottom:1px solid #dddddd;"><?php echo $data->Row['Product_ID']; ?></td>
				<td style="border-bottom:1px solid #dddddd;" align="right"><?php echo number_format(round($turnoverThis, 2), 2, '.', ','); ?><br /><small>Profit: <?php echo number_format(round($profitThis, 2), 2, '.', ','); ?></small><br /><small>Percent: <?php echo number_format(round(($turnoverThis > 0) ? (($profitThis / $turnoverThis) * 100) : 0, 2), 2, '.', ','); ?></small></td>
				<td style="border-bottom:1px solid #dddddd;" align="right"><?php echo number_format(round($turnoverLast, 2), 2, '.', ','); ?><br /><small>Profit: <?php echo number_format(round($profitLast, 2), 2, '.', ','); ?></small><br /><small>Percent: <?php echo number_format(round(($turnoverLast > 0) ? (($profitLast / $turnoverLast) * 100) : 0, 2), 2, '.', ','); ?></small></td>
			</tr>

			<?php

			$data->Next();
		}
		$data->Disconnect();
		?>

	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function calculateOverhead($overheads = array(), $startTime, $endTime) {
	$tempTime = $startTime;
	$value = 0;

	while($tempTime<$endTime) {
		foreach($overheads as $overhead) {
			$itemValue = 0;

			if((strtotime($overhead['Start_Date']) <= $tempTime) && ($tempTime < strtotime($overhead['End_Date']))) {
				switch($overhead['Is_Working_Days_Only']) {
					case 'N':
						switch($overhead['Period']) {
							case 'Y':
                                $itemValue += $overhead['Value'] / 365;
								break;
							case 'M':
                                $itemValue += $overhead['Value'] / date('t', $tempTime);
								break;
							case 'D':
								$itemValue += $overhead['Value'];
								break;
						}

						$value += $itemValue;

						break;

					case 'Y':
						if(date('N', $tempTime) < 6) {
	                        switch($overhead['Period']) {
								case 'Y':
                                	$itemValue += $overhead['Value'] / ((365 / 7) * 5);
									break;
								case 'M':
                                	$itemValue += ($overhead['Value'] * 12) / ((365 / 7) * 5);
									break;
								case 'D':
									$itemValue += $overhead['Value'];
									break;
							}

							$value += $itemValue;
						}

						break;
				}
			}
		}

		$tempTime = mktime(0, 0, 0, date('m', $tempTime), date('d', $tempTime) + 1, date('Y', $tempTime));
	}

	return $value;
}