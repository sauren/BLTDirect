<?php
require_once('lib/common/app_header.php');

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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('month', 'Month', 'select', date('m'), 'anything', 1, 2);
	$form->AddField('year', 'Year', 'select', date('Y'), 'anything', 4, 4);

	for($i=1; $i<=12; $i++) {
		$time = mktime(0, 0, 0, $i, 1, date('Y'));

		$form->AddOption('month', date('m', $time), date('M', $time));
	}

	for($i=date('Y')-5; $i<=date('Y'); $i++) {
		$form->AddOption('year', $i, $i);
	}

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			$start = sprintf('%s-%s-01 00:00:00', $form->GetValue('year'), $form->GetValue('month'));
			$end = sprintf('%s-%s-01 00:00:00', ($form->GetValue('year') + (($form->GetValue('month') == 12) ? 1 : 0)), date('m', mktime(0, 0, 0, $form->GetValue('month') + 1, 1, date('Y'))));

			redirect(sprintf("Location: %s?action=report&start=%s&end=%s", $_SERVER['PHP_SELF'], $start, $end));
		}
	}

	$page = new Page('Packing Report', 'Please choose a start and end date for your report');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Packing.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select one of the predefined date ranges for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('Date', $form->GetHTML('month') . $form->GetHTML('year'));
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Timesheet.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start', 'hidden', '', 'anything', 19, 19);
	$form->AddField('end', 'End', 'hidden', '', 'anything', 19, 19);

	$stats = array();

	$data = new DataQuery(sprintf("SELECT DATE(d.Created_On) AS Date, SUM(d.Boxes) AS Value FROM despatch AS d INNER JOIN warehouse AS w ON w.Warehouse_ID=d.Despatch_From_ID INNER JOIN branch AS b ON b.Branch_ID=w.Type_Reference_ID AND w.Type='B' WHERE d.Created_On>='%s' AND d.Created_On<'%s' GROUP BY DATE(d.Created_On)", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		$stats['ParcelsPacked'][$data->Row['Date']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT DATE(r.Received_On) AS Date, COUNT(r.Return_ID) AS Value FROM `return` AS r WHERE r.Authorisation='R' AND (r.Status LIKE 'Received' OR r.Status LIKE 'Resolved') AND r.Received_On>='%s' AND r.Received_On<'%s' GROUP BY DATE(r.Received_On)", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		$stats['ReturnsReceived'][$data->Row['Date']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT DATE(d.Created_On) AS Date, SUM(d.Boxes) AS Value FROM despatch AS d INNER JOIN warehouse AS w ON w.Warehouse_ID=d.Despatch_From_ID INNER JOIN branch AS b ON b.Branch_ID=w.Type_Reference_ID AND w.Type='B' INNER JOIN orders AS o ON o.Order_ID=d.Order_ID WHERE (o.Order_Prefix='R' OR o.Order_Prefix='B' OR o.Order_Prefix='N') AND d.Created_On>='%s' AND d.Created_On<'%s' GROUP BY DATE(d.Created_On)", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		$stats['ParcelsRepacked'][$data->Row['Date']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT DATE(pb.Created_On) AS Date, SUM(pbl.Quantity) AS Value FROM purchase AS p INNER JOIN purchase_batch AS pb ON pb.Purchase_ID=p.Purchase_ID INNER JOIN purchase_batch_line AS pbl ON pb.Purchase_Batch_ID=pbl.Purchase_Batch_ID WHERE pb.Created_On>='%s' AND p.Created_On<'%s' GROUP BY DATE(pb.Created_On)", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		$stats['ProductsBooked'][$data->Row['Date']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT DATE(pb.Created_On) AS Date, COUNT(pbl.Purchase_Batch_Line_ID) AS Value FROM purchase AS p INNER JOIN purchase_batch AS pb ON pb.Purchase_ID=p.Purchase_ID INNER JOIN purchase_batch_line AS pbl ON pb.Purchase_Batch_ID=pbl.Purchase_Batch_ID WHERE pb.Created_On>='%s' AND p.Created_On<'%s' GROUP BY DATE(pb.Created_On)", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		$stats['LinesBooked'][$data->Row['Date']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$hours = array();

	$data = new DataQuery(sprintf("SELECT t.Date, t.User_ID, SUM(t.Hours) AS Hours FROM timesheet AS t WHERE t.Date>='%s' AND t.Date<'%s' AND t.Type LIKE 'Packing' AND User_ID NOT IN (42, 50) GROUP BY t.Date, t.User_ID", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		$hours[$data->Row['Date']][$data->Row['User_ID']] = $data->Row['Hours'];

		$data->Next();
	}
	$data->Disconnect();

	$page = new Page('Packing Report: ' . cDatetime($form->GetValue('start'), 'longdatetime') . ' to ' . cDatetime($form->GetValue('end'), 'longdatetime'), '');
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
			<td style="border-bottom:1px solid #aaaaaa"><strong>Day</strong></td>
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

		$time = strtotime($form->GetValue('start'));

		for($i=1; $i<=date('t', $time); $i++) {
			$subTime = mktime(0, 0, 0, date('m', $time), $i, date('Y', $time));
			$style = (date('N', $subTime) >= 6) ? 'background-color: #ccc;' : '';

			$lineParcelsPacked = isset($stats['ParcelsPacked'][date('Y-m-d', $subTime)]) ? $stats['ParcelsPacked'][date('Y-m-d', $subTime)] : 0;
			$lineReturnsReceived = isset($stats['ReturnsReceived'][date('Y-m-d', $subTime)]) ? $stats['ReturnsReceived'][date('Y-m-d', $subTime)] : 0;
			$lineParcelsRepacked = isset($stats['ParcelsRepacked'][date('Y-m-d', $subTime)]) ? $stats['ParcelsRepacked'][date('Y-m-d', $subTime)] : 0;
			$lineProductsBooked = isset($stats['ProductsBooked'][date('Y-m-d', $subTime)]) ? $stats['ProductsBooked'][date('Y-m-d', $subTime)] : 0;
			$lineLinesBooked = isset($stats['LinesBooked'][date('Y-m-d', $subTime)]) ? $stats['LinesBooked'][date('Y-m-d', $subTime)] : 0;
			$lineTotalParcels = round($lineParcelsPacked + $lineReturnsReceived + $lineParcelsRepacked, 2);
			$lineTotalParcelsNew = round($lineParcelsPacked, 2);
			$lineTotalParcelsLines = round($lineParcelsPacked + $lineReturnsReceived + $lineParcelsRepacked + $lineLinesBooked, 2);

			$lineTotalHours = 0;

			if(isset($hours[date('Y-m-d H:i:s', $subTime)])) {
				foreach($hours[date('Y-m-d H:i:s', $subTime)] as $userId=>$userHours) {
					$lineTotalHours += $userHours;
				}
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td style="<?php echo $style; ?>"><?php echo date('jS F', $subTime); ?></td>
				<td style="<?php echo $style; ?>"><?php echo date('D', $subTime); ?></td>
				<td style="<?php echo $style; ?>" align="right"><?php echo ($lineParcelsPacked > 0) ? $lineParcelsPacked : ''; ?></td>
				<td style="<?php echo $style; ?>" align="right"><?php echo ($lineReturnsReceived > 0) ? $lineReturnsReceived : ''; ?></td>
				<td style="<?php echo $style; ?>" align="right"><?php echo ($lineParcelsRepacked > 0) ? $lineParcelsRepacked : ''; ?></td>
				<td style="<?php echo $style; ?>" align="right"><?php echo ($lineProductsBooked > 0) ? $lineProductsBooked : ''; ?></td>
				<td style="<?php echo $style; ?>" align="right"><?php echo ($lineLinesBooked > 0) ? $lineLinesBooked : ''; ?></td>
				<td style="<?php echo $style; ?>" align="right"><?php echo ($lineTotalHours > 0) ? $lineTotalHours : ''; ?></td>
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
		}

		$totalTotalParcels = round($totalParcelsPacked + $totalReturnsReceived + $totalParcelsRepacked, 2);
		$totalTotalParcelNew = round($totalParcelsPacked, 2);
		$totalTotalParcelLines = round($totalParcelsPacked + $totalReturnsReceived + $totalParcelsRepacked + $totalLinesBooked, 2);
		$totalTotalHours = round($totalHours, 2);
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>&nbsp;</td>
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