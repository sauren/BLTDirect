<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/chart/libchart.php');

$types = array();
$typeData = array();

$allData = array();

$data = new DataQuery(sprintf("SELECT psc.name FROM product_specification_group AS psg INNER JOIN product_specification_combine AS psc ON psc.productSpecificationGroupId=psg.Group_ID WHERE psg.Reference LIKE 'Type' ORDER BY psc.id ASC"));
while($data->Row) {
	$types[] = $data->Row['name'];

	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT DATE_FORMAT(o.Created_On, '%%Y-%%m') AS Date, SUM(ol.Quantity) AS Quantity, SUM(ol.Line_Total-ol.Line_Discount) AS Turnover FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID GROUP BY Date ORDER BY Date ASC"));
while($data->Row) {
	$allData[$data->Row['Date']] = array();
	$allData[$data->Row['Date']]['Quantity'] = $data->Row['Quantity'];
	$allData[$data->Row['Date']]['Turnover'] = $data->Row['Turnover'];

	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT DATE_FORMAT(o.Created_On, '%%Y-%%m') AS Date, psc.name, SUM(ol.Quantity) AS Quantity, SUM(ol.Line_Total-ol.Line_Discount) AS Turnover FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN product_specification AS ps ON ps.Product_ID=ol.Product_ID INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID INNER JOIN product_specification_combine_value AS pscv ON pscv.productSpecificationValueId=psv.Value_ID INNER JOIN product_specification_combine AS psc ON psc.id=pscv.productSpecificationCombineId INNER JOIN product_specification_group AS psg ON psg.Group_ID=psc.productSpecificationGroupId AND psg.Reference LIKE 'Type' GROUP BY Date, psc.id ORDER BY Date ASC"));
while($data->Row) {
	if(!isset($typeData[$data->Row['Date']])) {
		$typeData[$data->Row['Date']] = array();
	}
	
	$typeData[$data->Row['Date']][$data->Row['name']]['Quantity'] = $data->Row['Quantity'];
	$typeData[$data->Row['Date']][$data->Row['name']]['Turnover'] = $data->Row['Turnover'];
	
	$data->Next();	
}
$data->Disconnect();

$months = 36;

$chartFileName = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
$chartWidth = 900;
$chartHeight = 600;
$chartTitle = 'LED Sale Quantities';
$chartReference = sprintf('temp/charts/chart_%s.png', $chartFileName);

$chart = new LineChart(900, 600, array('All Products', 'LED Products'));

for($i=$months-1; $i>=0; $i--) {
	$date = date('Y-m', mktime(0, 0, 0, date('m') - $i, 1, date('Y')));

	$points = array();
	$points[] = isset($allData[$date]) ? $allData[$date]['Quantity'] : 0;
	$points[] = isset($typeData[$date]) ? (isset($typeData[$date]['LED']) ? $typeData[$date]['LED']['Quantity'] : 0) : 0;

	$chart->addPoint(new Point($date, $points));
}

$chart->SetTitle($chartTitle);
$chart->SetLabelY('Quantities');
$chart->ReduceLabels = false;
$chart->ShowText = false;
$chart->render($chartReference);

$page = new Page('LED Sales Report', '');
$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
$page->Display('header');
?>

<br />
<h3>LED Sale Quantities</h3>
<p>Quantities for type of products sold on all orders for the last <?php print $months; ?> months.</p>

<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
	<img src="<?php echo $chartReference; ?>" width="<?php print $chartWidth; ?>" height="<?php print $chartHeight; ?>" alt="<?php print $chartTitle; ?>" />
</div>

<script language="javascript">
	window.onload = function() {
		var httpRequest = new HttpRequest();
		httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $chartReference; ?>');
	}
</script>

<br />
<h3>Sale Quantities</h3>
<p>Quantities for many types of products sold on all orders for the last <?php print $months; ?> months.</p>

<table width="100%" border="0">
	<tr>
		<td style="border-bottom: 1px solid #aaaaaa;"></td>
		
		<?php
		foreach($types as $type) {
			?>
			
			<td style="border-bottom: 1px solid #aaaaaa;" align="right" nowrap="nowrap"><strong><?php echo $type; ?></strong></td>
			
			<?php
		}
		?>
	</tr>
	
	<?php
	$light = true;
	
	for($i=$months-1; $i>=0; $i--) {
		$date = date('Y-m', mktime(0, 0, 0, date('m') - $i, 1, date('Y')));
		
		$total = isset($allData[$date]) ? $allData[$date]['Quantity'] : 0;
		?>
		
		<tr <?php echo $light ? '' : 'style="background-color: #eee;"'; ?>>
			<td nowrap="nowrap"><?php echo $date; ?></td>
		
			<?php
			foreach($types as $type) {
				$value = isset($typeData[$date]) ? (isset($typeData[$date][$type]) ? $typeData[$date][$type]['Quantity'] : 0) : 0;
				$valuePercent = ($total > 0) ? ($value / $total) * 100 : 0;
				?>
				
				<td align="right" nowrap="nowrap">
					<?php
					if($value > 0) {
						echo $value, '<br />';
						echo sprintf('<span style="color: #999;">%s%%</span>', number_format(round($valuePercent, 2), 2, '.', ','));
					}
					?>
				</td>
				
				<?php
			}
			?>
			
		</tr>		

		<?php
		$light = !$light;
	}
	?>

</table>
<br />

<br />
<h3>Sale Turnover</h3>
<p>Turnover for many types of products sold on all orders for the last <?php print $months; ?> months.</p>

<table width="100%" border="0">
	<tr>
		<td style="border-bottom: 1px solid #aaaaaa;"></td>
		
		<?php
		foreach($types as $type) {
			?>
			
			<td style="border-bottom: 1px solid #aaaaaa;" align="right" nowrap="nowrap"><strong><?php echo $type; ?></strong></td>
			
			<?php
		}
		?>
	</tr>
	
	<?php
	$light = true;
	
	for($i=$months-1; $i>=0; $i--) {
		$date = date('Y-m', mktime(0, 0, 0, date('m') - $i, 1, date('Y')));
		
		$total = isset($allData[$date]) ? $allData[$date]['Turnover'] : 0;
		?>
		
		<tr <?php echo $light ? '' : 'style="background-color: #eee;"'; ?>>
			<td nowrap="nowrap"><?php echo $date; ?></td>
		
			<?php
			foreach($types as $type) {
				$value = isset($typeData[$date]) ? (isset($typeData[$date][$type]) ? $typeData[$date][$type]['Turnover'] : 0) : 0;
				$valuePercent = ($total > 0) ? ($value / $total) * 100 : 0;
				?>
				
				<td align="right" nowrap="nowrap">
					<?php
					if($value > 0) {
						echo '&pound', number_format(round($value, 2), 2, '.', ','), '<br />';
						echo sprintf('<span style="color: #999;">%s%%</span>', number_format(round($valuePercent, 2), 2, '.', ','));
					}
					?>
				</td>
				
				<?php
			}
			?>
			
		</tr>		

		<?php
		$light = !$light;
	}
	?>

</table>
<br />

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');