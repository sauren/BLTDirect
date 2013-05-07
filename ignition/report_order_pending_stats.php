<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/chart/libchart.php');

$page = new Page('Order Pending Stats Report', '');
$page->Display('header');

$chartFileName = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
$chartWidth = 900;
$chartHeight = 600;
$chartTitle = 'Number of orders packable or unpackable';
$chartReference = sprintf('temp/charts/chart_%s.png', $chartFileName);

$chart = new LineChart($chartWidth, $chartHeight, array('Packable', 'Unpackable'));

$orders = array();

$data = new DataQuery(sprintf('SELECT * FROM order_pending_stat'));
while($data->Row) {
	$orders[substr($data->Row['createdOn'], 0, 10)] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$start = date('Y-m-d', strtotime('-1 month'));
$end = date('Y-m-d');

$endTime = strtotime($end);

$temp = date('Y-m-d', strtotime($start));
$tempTime = strtotime($temp);

while($tempTime <= $endTime) {
	$points = array();
	$points[] = isset($orders[$temp]) ? $orders[$temp]['ordersPackable'] : 0;
	$points[] = isset($orders[$temp]) ? $orders[$temp]['ordersUnpackable'] : 0;

	$chart->addPoint(new Point(date('d/m (D)', strtotime($temp)), $points));

	$temp = date('Y-m-d', strtotime('+1 day', strtotime($temp)));
	$tempTime = strtotime($temp);
}

$chart->SetTitle($chartTitle);
$chart->SetLabelY('Orders');
$chart->ShowText = false;
$chart->ShowLabels = (count($orders) > 50) ? false : true;
$chart->render($chartReference);
?>

<br />
<h3>Sale Orders</h3>
<p>Sale orders statistics.</p>

<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
	<img src="<?php echo $chartReference; ?>" width="<?php print $chartWidth; ?>" height="<?php print $chartHeight; ?>" alt="<?php print $chartTitle; ?>" />
</div>

<table width="100%" border="0">
	<tr>
		<th style="border-bottom:1px solid #000; text-align: left;"><strong>Day</strong></td>
		<th style="border-bottom:1px solid #000; text-align: right;"><strong>Packable</strong></td>
		<th style="border-bottom:1px solid #000; text-align: right;"><strong>Unpackable</strong></td>
	</tr>

	<?php
	$start = date('Y-m-d', strtotime('-1 month'));
	$end = date('Y-m-d');

	$endTime = strtotime($end);

	$temp = date('Y-m-d', strtotime($start));
	$tempTime = strtotime($temp);

	while($tempTime <= $endTime) {
		$weekend = (date('N', strtotime($temp)) > 5);
		?>

		<tr class="dataRow" <?php echo ($weekend) ? 'style="background-color: #ddd;"' : ''; ?>>
			<td><?php echo date('d/m/Y', strtotime($temp)); ?></td>
			<td align="right"><?php echo isset($orders[$temp]) ? $orders[$temp]['ordersPackable'] : 0; ?></td>
			<td align="right"><?php echo isset($orders[$temp]) ? $orders[$temp]['ordersUnpackable'] : 0; ?></td>
		</tr>

		<?php
		$temp = date('Y-m-d', strtotime('+1 day', strtotime($temp)));
		$tempTime = strtotime($temp);
	}
	?>

</table>
<br />

<script language="javascript">
	window.onload = function() {
		var httpRequest = new HttpRequest();
		httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $chartReference; ?>');
	}
</script>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');