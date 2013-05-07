<?php
require_once('lib/common/app_header.php');

$summaryData = array();

$startYear = 0;
$endear = 0;

$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT d.Despatch_ID) AS Despatches, COUNT(DISTINCT d.Order_ID) AS Orders, SUM(d.Postage_Cost+d.Line_Cost) AS Cost, d.Created_Date FROM (SELECT d.Despatch_ID, d.Order_ID, d.Postage_Cost, SUM(ol.Cost*ol.Quantity) AS Line_Cost, DATE_FORMAT(d.Created_On, '%%Y-%%m') AS Created_Date FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' INNER JOIN despatch AS d ON d.Despatch_ID=ol.Despatch_ID GROUP BY d.Despatch_ID) AS d GROUP BY d.Created_Date ORDER BY d.Created_Date ASC"));
while($data->Row) {
	$dateYear = substr($data->Row['Created_Date'], 0, 4);
	$dateMonth = (int) substr($data->Row['Created_Date'], 5, 2);
	
	if(!isset($summaryData[$dateYear])) {
		$summaryData[$dateYear] = array();
	}
	
	$summaryData[$dateYear][$dateMonth] = $data->Row;
	
	if($startYear == 0) {
		$startYear = $dateYear;
	}
	
	$endYear = $dateYear;

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT Despatch_ID) AS Despatches, COUNT(DISTINCT Order_ID) AS Orders, DATE_FORMAT(Created_On, '%%Y-%%m') AS Created_Date FROM despatch GROUP BY Created_Date"));
while($data->Row) {
	$dateYear = substr($data->Row['Created_Date'], 0, 4);
	$dateMonth = (int) substr($data->Row['Created_Date'], 5, 2);

	if(isset($summaryData[$dateYear])) {
		if(isset($summaryData[$dateYear][$dateMonth])) {
			$summaryData[$dateYear][$dateMonth]['DespatchesTotal'] = $data->Row['Despatches'];
			$summaryData[$dateYear][$dateMonth]['OrdersTotal'] = $data->Row['Orders'];
		}
	}
	
	$data->Next();
}
$data->Disconnect();

$page = new Page('Supplier Drop Shipments Report');
$page->Display('header');
?>

<h3>Supplier Costs</h3>
<br />

<table width="100%" border="0">
	<tr>
		<th style="border-bottom: 1px solid #aaaaaa;"></th>
		
		<?php
		for($m=1; $m<=12; $m++) {
			echo sprintf('<th style="border-bottom: 1px solid #aaaaaa;" width="%s%%">%s</th>', (100/14), date('M', mktime(0, 0, 0, $m, 1, date('Y'))));
		}
		?>
		
		<th style="border-bottom: 1px solid #aaaaaa;">Total</th>
	</tr>
	
	<?php
	for($y=$startYear; $y<=$endYear; $y++) {
		?>
			
		<tr>
			<th style="border-bottom: 1px dotted #ccc;"><?php echo $y; ?></th>
		
			<?php
			$totalCost = 0;
			$totalDespatches = 0;
			$totalOrdersDespatched = 0;
			$totalOrdersCreated = 0;
			
			for($m=1; $m<=12; $m++) {
				if(isset($summaryData[$y][$m])) {
					?>
					
					<td style="border-bottom: 1px dotted #ccc;" align="right">
						&pound;<?php echo number_format($summaryData[$y][$m]['Cost'], 2, '.', ','); ?><br /><br />
						<small><span style="color: #999;">Dsp:</span> <?php echo $summaryData[$y][$m]['Despatches']; ?>x</small><br />
						<small><span style="color: #999;">Dsp Tot:</span> <?php echo $summaryData[$y][$m]['DespatchesTotal']; ?>x</small><br />
						<small><span style="color: #999;">Ord:</span> <?php echo $summaryData[$y][$m]['Orders']; ?>x</small><br />
						<small><span style="color: #999;">Ord Tot:</span> <?php echo $summaryData[$y][$m]['OrdersTotal']; ?>x</small><br />
						
					</td>
					
					<?php
					$totalCost += $summaryData[$y][$m]['Cost'];
					$totalDespatches += $summaryData[$y][$m]['Despatches'];
					$totalDespatchesTotal += $summaryData[$y][$m]['DespatchesTotal'];
					$totalOrders += $summaryData[$y][$m]['Orders'];
					$totalOrdersTotal += $summaryData[$y][$m]['OrdersTotal'];
				} else {
					echo '<td style="border-bottom: 1px dotted #ccc;"></td>';
				}
			}
			?>
			
			<td style="border-bottom: 1px dotted #ccc;" align="right">
				&pound;<?php echo number_format($totalCost, 2, '.', ','); ?><br /><br />
				<small><span style="color: #999;">Dsp:</span> <?php echo $totalDespatches; ?>x</small><br />
				<small><span style="color: #999;">Dsp Tot:</span> <?php echo $totalDespatchesTotal; ?>x</small><br />
				<small><span style="color: #999;">Ord:</span> <?php echo $totalOrders; ?>x</small><br />
				<small><span style="color: #999;">Ord Tot:</span> <?php echo $totalOrdersTotal; ?>x</small><br />
			</td>
		</tr>		
		
		<?php	
	}
	?>
	
</table>
		  
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');