<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
report();
exit();

function report(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/chart/libchart.php');

	$months = 36;

	$orderTypes = array();
	$orderTypes['W'] = "Website (bltdirect.com)";
	$orderTypes['U'] = "Website (bltdirect.co.uk)";
	$orderTypes['L'] = "Website (lightbulbsuk.co.uk)";
	$orderTypes['M'] = "Mobile";
	$orderTypes['T'] = "Telesales";
	$orderTypes['F'] = "Fax";
	$orderTypes['E'] = "Email";

	$page = new Page('Sales Period Report: Last '.$months.' Months', '');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	$chartFileName = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
	$chartWidth = 900;
	$chartHeight = 600;
	$chartTitle = 'Sale Methods';
	$chartReference = sprintf('temp/charts/chart_%s.png', $chartFileName);

	$chartFileName2 = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
	$chartWidth2 = 900;
	$chartHeight2 = 600;
	$chartTitle2 = 'Sale Methods';
	$chartReference2 = sprintf('temp/charts/chart_%s.png', $chartFileName2);

	$legend = array();

	foreach($orderTypes as $key=>$prefix) {
		$legend[] = $prefix;
	}

	$legend = array_merge(array('All'), $legend);

	$connections = getSyncConnections();

	for($i=1; $i<count($connections); $i++) {
		$legend = array_merge($legend, array(sprintf('Website (%s)', $connections[$i]['Domain'])));
	}

	$chart = new LineChart(900, 600, $legend);
	$chart2 = new LineChart(900, 600, $legend);

	for($i = $months-1; $i >= 0; $i--) {
		$points = array();
		$points2 = array();

		$start = date('Y-m-00 00:00:00', mktime(0, 0, 0, date('m') + 1 - $i, 0, date('Y')));
		$end = date('Y-m-00 00:00:00', mktime(0, 0, 0, date('m') + 2 - $i, 0, date('Y')));

		$tempPoints = 0;
		$tempPoints2 = 0;

		for($j=0; $j<count($connections); $j++) {
			$data = new DataQuery(sprintf("select count(Order_ID) as OrderCount, SUM(Total-TotalTax) AS Total from orders where Created_On between '%s' and '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N'", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$j]['Connection']);
			$tempPoints += $data->Row['OrderCount'];
			$tempPoints2 += $data->Row['Total'];
			$data->Disconnect();
		}

		$points[] = $tempPoints;
		$points2[] = $tempPoints2;

		foreach($orderTypes as $key=>$prefix) {
			$data = new DataQuery(sprintf("select count(Order_ID) as OrderCount, SUM(Total-TotalTax) AS Total from orders where Created_On between '%s' and '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N' AND Order_Prefix='%s'", mysql_real_escape_string($start), mysql_real_escape_string($end), $key));
			$points[] = $data->Row['OrderCount'];
			$points2[] = $data->Row['Total'];
			$data->Disconnect();
		}

		for($j=1; $j<count($connections); $j++) {
			$data = new DataQuery(sprintf("select count(Order_ID) as OrderCount, SUM(Total-TotalTax) AS Total from orders where Created_On between '%s' and '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N'", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$j]['Connection']);
			$points[] = $data->Row['OrderCount'];
			$points2[] = $data->Row['Total'];
			$data->Disconnect();
		}

		$chart->addPoint(new Point(cDatetime($end, 'shortdate'), $points));
		$chart2->addPoint(new Point(cDatetime($end, 'shortdate'), $points2));
	}

	$chart->SetTitle($chartTitle);
	$chart->SetLabelY('Order Frequency');
	$chart->ReduceLabels = false;
	$chart->ShowText = false;
	$chart->render($chartReference);

	$chart2->SetTitle($chartTitle2);
	$chart2->SetLabelY('Net Order Value');
	$chart2->ReduceLabels = false;
	$chart2->ShowText = false;
	$chart2->render($chartReference2);
	?>

	<br />
	<h3>Sale Methods</h3>
	<p>Sale methods statistics on all orders for the last <?php print $months; ?> months.</p>

	<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
		<img src="<?php echo $chartReference; ?>" width="<?php print $chartWidth; ?>" height="<?php print $chartHeight; ?>" alt="<?php print $chartTitle; ?>" />
	</div>

	<br />

	<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
		<img src="<?php echo $chartReference2; ?>" width="<?php print $chartWidth2; ?>" height="<?php print $chartHeight2; ?>" alt="<?php print $chartTitle2; ?>" />
	</div>

	<script language="javascript">
		window.onload = function() {
			var httpRequest = new HttpRequest();
			httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $chartReference; ?>');

			var httpRequest2 = new HttpRequest();
			httpRequest2.post('lib/util/removeChart.php', 'chart=<?php print $chartReference2; ?>');
		}
	</script>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>