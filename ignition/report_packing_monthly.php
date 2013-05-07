<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Timesheet.php');

if($action == 'report') {
	$session->Secure(3);
	report();
	exit;
} else {
	$session->Secure(2);
	start();
	exit;
}

function start() {
	$form = new Form($_SERVER['PHP_SELF'], 'get');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'Report End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('range', 'Date range', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('range', 'none', '-- None --');
	$form->AddOption('range', 'all', '-- All --');
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

			redirect(sprintf("Location: ?action=report&start=%s&end=%s", $start, $end));
		} else {
			if($form->Validate()) {
				redirect(sprintf("Location: ?action=report&start=%s&end=%s", sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2))))))));
			}
		}
	}

	$page = new Page('Packing Summary Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Packing Summary.");
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
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start', 'hidden', '', 'anything', 19, 19);
	$form->AddField('end', 'End', 'hidden', '', 'anything', 19, 19);

	$stats = array();

	$data = new DataQuery(sprintf("SELECT DATE_FORMAT(d.Created_On, '%%Y-%%m') AS DateMonth, SUM(d.Boxes) AS Value FROM despatch AS d INNER JOIN warehouse AS w ON w.Warehouse_ID=d.Despatch_From_ID INNER JOIN branch AS b ON b.Branch_ID=w.Type_Reference_ID AND w.Type='B' WHERE d.Created_On>='%s' AND d.Created_On<'%s' GROUP BY DateMonth", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		$stats['ParcelsPacked'][$data->Row['DateMonth']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT DATE_FORMAT(r.Received_On, '%%Y-%%m') AS DateMonth, COUNT(r.Return_ID) AS Value FROM `return` AS r WHERE r.Authorisation='R' AND (r.Status LIKE 'Received' OR r.Status LIKE 'Resolved') AND r.Received_On>='%s' AND r.Received_On<'%s' GROUP BY DateMonth", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		$stats['ReturnsReceived'][$data->Row['DateMonth']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT DATE_FORMAT(d.Created_On, '%%Y-%%m') AS DateMonth, SUM(d.Boxes) AS Value FROM despatch AS d INNER JOIN warehouse AS w ON w.Warehouse_ID=d.Despatch_From_ID INNER JOIN branch AS b ON b.Branch_ID=w.Type_Reference_ID AND w.Type='B' INNER JOIN orders AS o ON o.Order_ID=d.Order_ID WHERE (o.Order_Prefix='R' OR o.Order_Prefix='B' OR o.Order_Prefix='N') AND d.Created_On>='%s' AND d.Created_On<'%s' GROUP BY DateMonth", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		$stats['ParcelsRepacked'][$data->Row['DateMonth']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT DATE_FORMAT(pb.Created_On, '%%Y-%%m') AS DateMonth, SUM(pbl.Quantity) AS Value FROM purchase AS p INNER JOIN purchase_batch AS pb ON pb.Purchase_ID=p.Purchase_ID INNER JOIN purchase_batch_line AS pbl ON pb.Purchase_Batch_ID=pbl.Purchase_Batch_ID WHERE pb.Created_On>='%s' AND p.Created_On<'%s' GROUP BY DateMonth", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		$stats['ProductsBooked'][$data->Row['DateMonth']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT DATE_FORMAT(pb.Created_On, '%%Y-%%m') AS DateMonth, COUNT(pbl.Purchase_Batch_Line_ID) AS Value FROM purchase AS p INNER JOIN purchase_batch AS pb ON pb.Purchase_ID=p.Purchase_ID INNER JOIN purchase_batch_line AS pbl ON pb.Purchase_Batch_ID=pbl.Purchase_Batch_ID WHERE pb.Created_On>='%s' AND p.Created_On<'%s' GROUP BY DateMonth", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		$stats['LinesBooked'][$data->Row['DateMonth']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$hours = array();

	$data = new DataQuery(sprintf("SELECT DATE_FORMAT(t.Date, '%%Y-%%m') AS DateMonth, SUM(t.Hours) AS Value FROM timesheet AS t WHERE t.Date>='%s' AND t.Date<'%s' AND t.Type LIKE 'Packing' AND User_ID NOT IN (42, 50) GROUP BY DateMonth", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		$stats['Hours'][$data->Row['DateMonth']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$page = new Page('Packing Summary Report: ' . cDatetime($form->GetValue('start'), 'longdatetime') . ' to ' . cDatetime($form->GetValue('end'), 'longdatetime'), '');
	$page->Display('header');

	$parcelsPacked = 0;
	$returnsReceived = 0;
	$parcelsRepacked = 0;
	$productsBooked = 0;
	$linesBooked = 0;

	foreach($stats as $key=>$stat) {
		foreach($stat as $date=>$value) {
			switch($key) {
				case 'ParcelsPacked':
					$parcelsPacked += $value;
					break;
				case 'ReturnsReceived':
					$returnsReceived += $value;
					break;
				case 'ParcelsRepacked':
					$parcelsRepacked += $value;
					break;
				case 'ProductsBooked':
					$productsBooked += $value;
					break;
				case 'LinesBooked':
					$linesBooked += $value;
					break;
			}
		}
	}

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('start');
	echo $form->GetHTML('end');
	?>

	<br />
	<h3>Packing Summary</h3>
	<p>Packing summary for the select period.</p>

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Item</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Frequency</strong></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>Parcels Packed (from BLT Direct)</td>
			<td align="right"><?php echo $parcelsPacked; ?></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>Returns Received</td>
			<td align="right"><?php echo $returnsReceived; ?></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>Parcels Repacked (from BLT Direct)</td>
			<td align="right"><?php echo $parcelsRepacked; ?></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>Products Booked In</td>
			<td align="right"><?php echo $productsBooked; ?></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>Lines Booked In</td>
			<td align="right"><?php echo $linesBooked; ?></td>
		</tr>
	</table>

	<br />
	<h3>Packing Stats</h3>
	<p>Packing statistics for days within the selected period.</p>

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Date</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Parcels Packed</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Returns Processed</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Parcels Repacked</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Products Booked In</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Lines Booked In</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Hours</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Avg Parcels</strong><br />Per Hour</td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Avg Parcels</strong><br />Per Hour (New Only)</td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Avg Parcels</strong><br />Per Hour (Inc. Lines)</td>
		</tr>

		<?php
		$totalParcelsPacked = 0;
		$totalReturnsReceived = 0;
		$totalParcelsRepacked = 0;
		$totalProductsBooked = 0;
		$totalLinesBooked = 0;
		$totalHours = 0;

		$tempDate = date('Y-m-01 00:00:00', strtotime($form->GetValue('start')));
		$endDate = date('Y-m-01 00:00:00', strtotime($form->GetValue('end')));

		while(strtotime($tempDate) < strtotime($endDate)) {
			$lineParcelsPacked = isset($stats['ParcelsPacked'][date('Y-m', strtotime($tempDate))]) ? $stats['ParcelsPacked'][date('Y-m', strtotime($tempDate))] : 0;
			$lineReturnsReceived = isset($stats['ReturnsReceived'][date('Y-m', strtotime($tempDate))]) ? $stats['ReturnsReceived'][date('Y-m', strtotime($tempDate))] : 0;
			$lineParcelsRepacked = isset($stats['ParcelsRepacked'][date('Y-m', strtotime($tempDate))]) ? $stats['ParcelsRepacked'][date('Y-m', strtotime($tempDate))] : 0;
			$lineProductsBooked = isset($stats['ProductsBooked'][date('Y-m', strtotime($tempDate))]) ? $stats['ProductsBooked'][date('Y-m', strtotime($tempDate))] : 0;
			$lineLinesBooked = isset($stats['LinesBooked'][date('Y-m', strtotime($tempDate))]) ? $stats['LinesBooked'][date('Y-m', strtotime($tempDate))] : 0;
			$lineTotalHours = isset($stats['Hours'][date('Y-m', strtotime($tempDate))]) ? $stats['Hours'][date('Y-m', strtotime($tempDate))] : 0;
			$lineTotalParcels = round($lineParcelsPacked + $lineReturnsReceived + $lineParcelsRepacked, 2);
			$lineTotalParcelsNew = round($lineParcelsPacked, 2);
			$lineTotalParcelsLines = round($lineParcelsPacked + $lineReturnsReceived + $lineParcelsRepacked + $lineLinesBooked, 2);
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo date('Y-m', strtotime($tempDate)); ?></td>
				<td align="right"><?php echo ($lineParcelsPacked > 0) ? $lineParcelsPacked : ''; ?></td>
				<td align="right"><?php echo ($lineReturnsReceived > 0) ? $lineReturnsReceived : ''; ?></td>
				<td align="right"><?php echo ($lineParcelsRepacked > 0) ? $lineParcelsRepacked : ''; ?></td>
				<td align="right"><?php echo ($lineProductsBooked > 0) ? $lineProductsBooked : ''; ?></td>
				<td align="right"><?php echo ($lineLinesBooked > 0) ? $lineLinesBooked : ''; ?></td>
				<td align="right"><?php echo ($lineTotalHours > 0) ? $lineTotalHours : ''; ?></td>
				<td style="<?php echo 'background-color: #ccc;'; ?>" align="right"><?php echo ($lineTotalHours > 0) ? ((($lineTotalParcels / $lineTotalHours) > 0) ? number_format($lineTotalParcels / $lineTotalHours, 2): '') : ''; ?></td>
				<td style="<?php echo 'background-color: #ccc;'; ?>" align="right"><?php echo ($lineTotalHours > 0) ? ((($lineTotalParcelsNew / $lineTotalHours) > 0) ? number_format($lineTotalParcelsNew / $lineTotalHours, 2): '') : ''; ?></td>
				<td style="<?php echo 'background-color: #ccc;'; ?>" align="right"><?php echo ($lineTotalHours > 0) ? ((($lineTotalParcelsLines / $lineTotalHours) > 0) ? number_format($lineTotalParcelsLines / $lineTotalHours, 2): '') : ''; ?></td>
			</tr>

			<?php
			$totalParcelsPacked += $lineParcelsPacked;
			$totalReturnsReceived += $lineReturnsReceived;
			$totalParcelsRepacked += $lineParcelsRepacked;
			$totalProductsBooked += $lineProductsBooked;
			$totalLinesBooked += $lineLinesBooked;
			$totalHours += $lineTotalHours;

			$tempDate = date('Y-m-01 00:00:00', strtotime('+1 month', strtotime($tempDate)));
		}

		$totalTotalParcels = round($totalParcelsPacked + $totalReturnsReceived + $totalParcelsRepacked, 2);
		$totalTotalParcelNew = round($totalParcelsPacked, 2);
		$totalTotalParcelLines = round($totalParcelsPacked + $totalReturnsReceived + $totalParcelsRepacked + $totalLinesBooked, 2);
		$totalTotalHours = round($totalHours, 2);
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>&nbsp;</td>
			<td align="right"><strong><?php echo ($totalParcelsPacked > 0) ? $totalParcelsPacked : ''; ?></strong></td>
			<td align="right"><strong><?php echo ($totalReturnsReceived > 0) ? $totalReturnsReceived : ''; ?></strong></td>
			<td align="right"><strong><?php echo ($totalParcelsRepacked > 0) ? $totalParcelsRepacked : ''; ?></strong></td>
			<td align="right"><strong><?php echo ($totalProductsBooked > 0) ? $totalProductsBooked : ''; ?></strong></td>
			<td align="right"><strong><?php echo ($totalLinesBooked > 0) ? $totalLinesBooked : ''; ?></strong></td>
			<td align="right"><strong><?php echo ($totalHours > 0) ? number_format($totalHours, 2) : ''; ?></strong></td>
			<td style="<?php echo 'background-color: #ccc;'; ?>" align="right"><strong><?php echo ($totalTotalHours > 0) ? ((($totalTotalParcels / $totalTotalHours) > 0) ? number_format($totalTotalParcels / $totalTotalHours, 2) : '') : ''; ?></strong></td>
			<td style="<?php echo 'background-color: #ccc;'; ?>" align="right"><strong><?php echo ($totalTotalHours > 0) ? ((($totalTotalParcels / $totalTotalHours) > 0) ? number_format($totalTotalParcelNew / $totalTotalHours, 2) : '') : ''; ?></strong></td>
			<td style="<?php echo 'background-color: #ccc;'; ?>" align="right"><strong><?php echo ($totalTotalHours > 0) ? ((($totalTotalParcelLines / $totalTotalHours) > 0) ? number_format($totalTotalParcelLines / $totalTotalHours, 2) : '') : ''; ?></strong></td>
		</tr>
	</table>

	<?php
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}