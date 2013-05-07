<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$start = date('Y-m-01 00:00:00');
	$end = date('Y-m-d H:i:s');

	$stats = array();

	$data = new DataQuery(sprintf("SELECT DATE(d.Created_On) AS Date, SUM(d.Boxes) AS Value FROM despatch AS d INNER JOIN warehouse AS w ON w.Warehouse_ID=d.Despatch_From_ID INNER JOIN branch AS b ON b.Branch_ID=w.Type_Reference_ID AND w.Type='B' WHERE d.Created_On>='%s' AND d.Created_On<'%s' GROUP BY DATE(d.Created_On)", $start, $end));
	while($data->Row) {
		$stats['ParcelsPacked'][$data->Row['Date']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT DATE(r.Received_On) AS Date, COUNT(r.Return_ID) AS Value FROM `return` AS r WHERE r.Authorisation='R' AND (r.Status LIKE 'Received' OR r.Status LIKE 'Resolved') AND r.Received_On>='%s' AND r.Received_On<'%s' GROUP BY DATE(r.Received_On)", $start, $end));
	while($data->Row) {
		$stats['ReturnsReceived'][$data->Row['Date']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT DATE(d.Created_On) AS Date, SUM(d.Boxes) AS Value FROM despatch AS d INNER JOIN warehouse AS w ON w.Warehouse_ID=d.Despatch_From_ID INNER JOIN branch AS b ON b.Branch_ID=w.Type_Reference_ID AND w.Type='B' INNER JOIN orders AS o ON o.Order_ID=d.Order_ID WHERE (o.Order_Prefix='R' OR o.Order_Prefix='B' OR o.Order_Prefix='N') AND d.Created_On>='%s' AND d.Created_On<'%s' GROUP BY DATE(d.Created_On)", $start, $end));
	while($data->Row) {
		$stats['ParcelsRepacked'][$data->Row['Date']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT DATE(pb.Created_On) AS Date, SUM(pbl.Quantity) AS Value FROM purchase AS p INNER JOIN purchase_batch AS pb ON pb.Purchase_ID=p.Purchase_ID INNER JOIN purchase_batch_line AS pbl ON pb.Purchase_Batch_ID=pbl.Purchase_Batch_ID WHERE pb.Created_On>='%s' AND p.Purchased_On<'%s' GROUP BY DATE(pb.Created_On)", $start, $end));
	while($data->Row) {
		$stats['ProductsBooked'][$data->Row['Date']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT DATE(pb.Created_On) AS Date, COUNT(pbl.Purchase_Batch_Line_ID) AS Value FROM purchase AS p INNER JOIN purchase_batch AS pb ON pb.Purchase_ID=p.Purchase_ID INNER JOIN purchase_batch_line AS pbl ON pb.Purchase_Batch_ID=pbl.Purchase_Batch_ID WHERE pb.Created_On>='%s' AND p.Purchased_On<'%s' GROUP BY DATE(pb.Created_On)", $start, $end));
	while($data->Row) {
		$stats['LinesBooked'][$data->Row['Date']] = $data->Row['Value'];

		$data->Next();
	}
	$data->Disconnect();

	$hours = array();

	$data = new DataQuery(sprintf("SELECT t.Date, t.User_ID, SUM(t.Hours) AS Hours FROM timesheet AS t WHERE t.Date>='%s' AND t.Date<'%s' AND t.Type LIKE 'Packing' AND User_ID NOT IN (42, 50) GROUP BY t.Date, t.User_ID", $start, $end));
	while($data->Row) {
		$hours[$data->Row['Date']][$data->Row['User_ID']] = $data->Row['Hours'];

		$data->Next();
	}
	$data->Disconnect();

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
	?>

	<html>
	<head>
	<style>
		body, th, td {
			font-family: arial, sans-serif;
			font-size: 0.8em;
		}
		h1, h2, h3, h4, h5, h6 {
			margin-bottom: 0;
			padding-bottom: 0;
		}
		h1 {
			font-size: 1.6em;
		}
		h2 {
			font-size: 1.2em;
		}
		p {
			margin-top: 0;
		}
	</style>
	</head>
	<body>

	<h1>Packing Report</h1>

	<h2>Packing Summary</h2>
	<p></p>

	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color:#eeeeee;">
			<td style="border-bottom:1px solid #dddddd;"><strong>Item</strong></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Frequency</strong></td>
		</tr>
		<tr>
			<td style="border-top:1px solid #dddddd;">Parcels Packed (from BLT Direct)</td>
			<td style="border-top:1px solid #dddddd;" align="right"><?php echo $parcelsPacked; ?></td>
		</tr>
		<tr>
			<td style="border-top:1px solid #dddddd;">Returns Received</td>
			<td style="border-top:1px solid #dddddd;" align="right"><?php echo $returnsReceived; ?></td>
		</tr>
		<tr>
			<td style="border-top:1px solid #dddddd;">Parcels Repacked (from BLT Direct)</td>
			<td style="border-top:1px solid #dddddd;" align="right"><?php echo $parcelsRepacked; ?></td>
		</tr>
		<tr>
			<td style="border-top:1px solid #dddddd;">Products Booked In</td>
			<td style="border-top:1px solid #dddddd;" align="right"><?php echo $productsBooked; ?></td>
		</tr>
		<tr>
			<td style="border-top:1px solid #dddddd;">Lines Booked In</td>
			<td style="border-top:1px solid #dddddd;" align="right"><?php echo $linesBooked; ?></td>
		</tr>
	</table>

	<br />
	<h3>Packing Stats</h3>
	<p></p>

	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color:#eeeeee;">
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

		$time = strtotime($start);

		for($i=1; $i<=date('t', $time); $i++) {
			$subTime = mktime(0, 0, 0, date('m', $time), $i, date('Y', $time));
			$style = (date('N', $subTime) >= 6) ? ' background-color: #eeeeee;' : '';

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

			<tr>
				<td style="<?php echo $style; ?>"><?php echo date('jS F', $subTime); ?></td>
				<td style="<?php echo $style; ?>"><?php echo date('D', $subTime); ?></td>
				<td style="<?php echo $style; ?>" align="right"><?php echo ($lineParcelsPacked > 0) ? $lineParcelsPacked : ''; ?></td>
				<td style="<?php echo $style; ?>" align="right"><?php echo ($lineReturnsReceived > 0) ? $lineReturnsReceived : ''; ?></td>
				<td style="<?php echo $style; ?>" align="right"><?php echo ($lineParcelsRepacked > 0) ? $lineParcelsRepacked : ''; ?></td>
				<td style="<?php echo $style; ?>" align="right"><?php echo ($lineProductsBooked > 0) ? $lineProductsBooked : ''; ?></td>
				<td style="<?php echo $style; ?>" align="right"><?php echo ($lineLinesBooked > 0) ? $lineLinesBooked : ''; ?></td>
				<td style="<?php echo $style; ?>" align="right"><?php echo ($lineTotalHours > 0) ? $lineTotalHours : ''; ?></td>
				<td style="<?php echo 'background-color: #eee;'; ?>" align="right"><?php echo ($lineTotalHours > 0) ? ((($lineTotalParcels / $lineTotalHours) > 0) ? number_format($lineTotalParcels / $lineTotalHours, 2) : '') : ''; ?></td>
				<td style="<?php echo 'background-color: #eee;'; ?>" align="right"><?php echo ($lineTotalHours > 0) ? ((($lineTotalParcelsNew / $lineTotalHours) > 0) ? number_format($lineTotalParcelsNew / $lineTotalHours, 2) : '') : ''; ?></td>
				<td style="<?php echo 'background-color: #eee;'; ?>" align="right"><?php echo ($lineTotalHours > 0) ? ((($lineTotalParcelsLines / $lineTotalHours) > 0) ? number_format($lineTotalParcelsLines / $lineTotalHours, 2) : '') : ''; ?></td>
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

		<tr style="background-color:#eeeeee;">
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td align="right"><strong><?php echo ($totalParcelsPacked > 0) ? $totalParcelsPacked : ''; ?></strong></td>
			<td align="right"><strong><?php echo ($totalReturnsReceived > 0) ? $totalReturnsReceived : ''; ?></strong></td>
			<td align="right"><strong><?php echo ($totalParcelsRepacked > 0) ? $totalParcelsRepacked : ''; ?></strong></td>
			<td align="right"><strong><?php echo ($totalProductsBooked > 0) ? $totalProductsBooked : ''; ?></strong></td>
			<td align="right"><strong><?php echo ($totalLinesBooked > 0) ? $totalLinesBooked : ''; ?></strong></td>
			<td align="right"><strong><?php echo ($totalHours > 0) ? number_format($totalHours, 2) : ''; ?></strong></td>
			<td style="<?php echo 'background-color: #eee;'; ?>" align="right"><strong><?php echo ($totalTotalHours > 0) ? ((($totalTotalParcels / $totalTotalHours) > 0) ? number_format($totalTotalParcels / $totalTotalHours, 2) : '') : ''; ?></strong></td>
			<td style="<?php echo 'background-color: #eee;'; ?>" align="right"><strong><?php echo ($totalTotalHours > 0) ? ((($totalTotalParcels / $totalTotalHours) > 0) ? number_format($totalTotalParcelNew / $totalTotalHours, 2) : '') : ''; ?></strong></td>
			<td style="<?php echo 'background-color: #eee;'; ?>" align="right"><strong><?php echo ($totalTotalHours > 0) ? ((($totalTotalParcelLines / $totalTotalHours) > 0) ? number_format($totalTotalParcelLines / $totalTotalHours, 2) : '') : ''; ?></strong></td>
		</tr>
	</table>

	</body>
	</html>

	<?php
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();