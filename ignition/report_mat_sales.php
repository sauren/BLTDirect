<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
report();
exit();

function report(){
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/chart/libchart.php');

	$orderTypes = array();
	$orderTypes['W'] = "Website (bltdirect.com)";
	$orderTypes['U'] = "Website (bltdirect.co.uk)";
	$orderTypes['L'] = "Website (lightbulbsuk.co.uk)";
	$orderTypes['T'] = "Telesales";

	$page = new Page(sprintf('MAT Sales Report'), '');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	$forwardMonths = 48;
	$backCheckMonths = 5;
	$percentageCutOffDate = '2007-09-01 00:00:00';

	$chartFileName1 = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
	$chartWidth1 = 900;
	$chartHeight1 = 600;
	$chartTitle1 = 'MAT Total Sales';
	$chartReference1 = sprintf('temp/charts/chart_%s.png', $chartFileName1);

	$chartFileName2 = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
	$chartWidth2 = 900;
	$chartHeight2 = 600;
	$chartTitle2 = 'MAT Total Sales Percentage';
	$chartReference2 = sprintf('temp/charts/chart_%s.png', $chartFileName2);

	$legend1 = array('Growth');
	$legend2 = array('Growth (%)');

	$data = new DataQuery(sprintf("SELECT MIN(Created_On) AS Start_Date, MAX(Created_On) AS End_Date FROM orders"));
	$startDate = date('Y-m-01 00:00:00', strtotime($data->Row['Start_Date']));
	$endDate = date('Y-m-01 00:00:00', strtotime($data->Row['End_Date']));
	$data->Disconnect();

	$chart1 = new LineChart($chartWidth1, $chartHeight1, $legend1);
	$chart2 = new LineChart($chartWidth2, $chartHeight2, $legend2);

	$turnoverData = array();

	$index = 0;
	$minIndex = 0;

	// gather actual monthly turnover data
	while(true) {
		$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date('m') + $index, 0, date('Y')));
		$end = date('Y-m-01 00:00:00', mktime(0, 0, 0, date('m') + $index + 1, 0, date('Y')));

		if($start < $startDate) {
			break;
		}

		$turnoverData[$index] = array();
		$turnoverData[$index]['Start'] = $start;
		$turnoverData[$index]['End'] = $end;

		$data = new DataQuery(sprintf("SELECT SUM(SubTotal - TotalDiscount) AS Order_Turnover FROM orders WHERE Created_On BETWEEN '%s' AND '%s' AND Status<>'Cancelled'", $start, $end));
		$turnoverData[$index]['Turnover'] = (strlen($data->Row['Order_Turnover']) > 0) ? $data->Row['Order_Turnover'] : 0;
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT SUM(TotalNet) AS Total_Net FROM credit_note WHERE Credited_On BETWEEN '%s' AND '%s'", $start, $end));
		$turnoverData[$index]['Turnover'] -= $data->Row['Total_Net'];
		$data->Disconnect();

		$minIndex = $index;
		$index--;
	}

	// calculate actual turnover and percentage growth, plot actual turnover growth
	for($i=$minIndex; $i<=0; $i++) {
		$growthTurnover = 0;
		$growthPercentage = 0;

		for($j=$i; $j>$i-12; $j--) {
			$growthTurnover += isset($turnoverData[$j]['Turnover']) ? $turnoverData[$j]['Turnover'] : 0;
		}

		$key = $i - 12;

		if(isset($turnoverData[$key])) {
			$growthPercentage = ($turnoverData[$key]['Turnover'] > 0) ? (($turnoverData[$i]['Turnover'] - $turnoverData[$key]['Turnover']) / $turnoverData[$key]['Turnover']) * 100 : 0;
		}

		$turnoverData[$i]['Growth'] = $growthPercentage;

		$chart1->addPoint(new Point(date('M Y', strtotime($turnoverData[$i]['Start'])), array($growthTurnover)));
	}

	// plot actual percentage growth
	for($i=$minIndex; $i<=0; $i++) {
		if(strtotime($turnoverData[$i]['Start']) >= strtotime($percentageCutOffDate)) {
			$chart2->addPoint(new Point(date('M Y', strtotime($turnoverData[$i]['Start'])), array($turnoverData[$i]['Growth'])));
		}
	}

	// calculate predicted percentage and turnover growth, plot predicted percentage and turnover growth
	for($i=1; $i<=$forwardMonths; $i++) {
		$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date('m') + $i, 0, date('Y')));
		$end = date('Y-m-01 00:00:00', mktime(0, 0, 0, date('m') + $i + 1, 0, date('Y')));

		$turnover = 0;
		$growthPercentage = 0;
		$growthTurnover = 0;

		for($j=($backCheckMonths*-1)+$i; $j<$i; $j++) {
			$growthPercentage += $turnoverData[$j]['Growth'];
			$turnover += $turnoverData[$j]['Turnover'];
		}

		$turnoverData[$i] = array();
		$turnoverData[$i]['Start'] = $start;
		$turnoverData[$i]['End'] = $end;
		$turnoverData[$i]['Growth'] = $growthPercentage / $backCheckMonths;
		$turnoverData[$i]['Turnover'] = $turnoverData[$i - 1]['Turnover'] + (((($turnover / $backCheckMonths) / 100) * $turnoverData[$i]['Growth']) / 12);

		for($j=$i; $j>$i-12; $j--) {
			$growthTurnover += isset($turnoverData[$j]['Turnover']) ? $turnoverData[$j]['Turnover'] : 0;
		}

		$chart1->addPoint(new Point(date('M Y', strtotime($turnoverData[$i]['Start'])), array($growthTurnover)));
		$chart2->addPoint(new Point(date('M Y', strtotime($turnoverData[$i]['Start'])), array($turnoverData[$i]['Growth'])));
	}

	$chart1->SetTitle($chartTitle1);
	$chart1->SetLabelY('Turnover');
	$chart1->ShowText = false;
	$chart1->render($chartReference1);

	$chart2->SetTitle($chartTitle2);
	$chart2->SetLabelY('Percentage');
	$chart2->ShowText = false;
	$chart2->render($chartReference2);

	$charts = array();

	foreach($orderTypes as $prefix=>$orderType) {
		$chartFileName1a = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
		$chartWidth1a = 900;
		$chartHeight1a = 600;
		$chartTitle1a = sprintf('MAT %s Sales', $orderType);
		$chartReference1a = sprintf('temp/charts/chart_%s.png', $chartFileName1a);

		$chartFileName2b = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
		$chartWidth2b = 900;
		$chartHeight2b = 600;
		$chartTitle2b = sprintf('MAT %s Sales Percentage', $orderType);
		$chartReference2b = sprintf('temp/charts/chart_%s.png', $chartFileName2b);

		$charts[$prefix]['Turnover']['FileName'] = $chartFileName1a;
		$charts[$prefix]['Turnover']['Width'] = $chartWidth1a;
		$charts[$prefix]['Turnover']['Height'] = $chartHeight1a;
		$charts[$prefix]['Turnover']['Title'] = $chartTitle1a;
		$charts[$prefix]['Turnover']['Reference'] = $chartReference1a;
		$charts[$prefix]['Percentage']['FileName'] = $chartFileName2b;
		$charts[$prefix]['Percentage']['Width'] = $chartWidth2b;
		$charts[$prefix]['Percentage']['Height'] = $chartHeight2b;
		$charts[$prefix]['Percentage']['Title'] = $chartTitle2b;
		$charts[$prefix]['Percentage']['Reference'] = $chartReference2b;

		$chart1a = new LineChart($chartWidth1a, $chartHeight1a, $legend1a);
		$chart2b = new LineChart($chartWidth2b, $chartHeight2b, $legend2b);

		$turnoverData = array();

		$index = 0;
		$minIndex = 0;

		// gather actual monthly turnover data
		while(true) {
			$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date('m') + $index, 0, date('Y')));
			$end = date('Y-m-01 00:00:00', mktime(0, 0, 0, date('m') + $index + 1, 0, date('Y')));

			if($start < $startDate) {
				break;
			}

			$turnoverData[$index] = array();
			$turnoverData[$index]['Start'] = $start;
			$turnoverData[$index]['End'] = $end;

			$data = new DataQuery(sprintf("SELECT SUM(SubTotal - TotalDiscount) AS Order_Turnover FROM orders WHERE Created_On BETWEEN '%s' AND '%s' AND Status<>'Cancelled' AND Order_Prefix='%s'", $start, $end, $prefix));
			$turnoverData[$index]['Turnover'] = (strlen($data->Row['Order_Turnover']) > 0) ? $data->Row['Order_Turnover'] : 0;
			$data->Disconnect();

			$data = new DataQuery(sprintf("SELECT SUM(c.TotalNet) AS Total_Net FROM credit_note AS c INNER JOIN orders AS o ON c.Order_ID=o.Order_ID WHERE c.Created_On BETWEEN '%s' AND '%s' AND o.Order_Prefix='%s'", $start, $end, $prefix));
			$turnoverData[$index]['Turnover'] -= $data->Row['Total_Net'];
			$data->Disconnect();

			$minIndex = $index;
			$index--;
		}

		// calculate actual turnover and percentage growth, plot actual turnover growth
		for($i=$minIndex; $i<=0; $i++) {
			$growthTurnover = 0;
			$growthPercentage = 0;

			for($j=$i; $j>$i-12; $j--) {
				$growthTurnover += isset($turnoverData[$j]['Turnover']) ? $turnoverData[$j]['Turnover'] : 0;
			}

			$key = $i - 12;

			if(isset($turnoverData[$key])) {
				$growthPercentage = ($turnoverData[$key]['Turnover'] > 0) ? (($turnoverData[$i]['Turnover'] - $turnoverData[$key]['Turnover']) / $turnoverData[$key]['Turnover']) * 100 : 0;
			}

			$turnoverData[$i]['Growth'] = $growthPercentage;

			$chart1a->addPoint(new Point(date('M Y', strtotime($turnoverData[$i]['Start'])), array($growthTurnover)));
		}

		// plot actual percentage growth
		for($i=$minIndex; $i<=0; $i++) {
			if(strtotime($turnoverData[$i]['Start']) >= strtotime($percentageCutOffDate)) {
				$chart2->addPoint(new Point(date('M Y', strtotime($turnoverData[$i]['Start'])), array($turnoverData[$i]['Growth'])));
			}
		}

		// calculate predicted percentage and turnover growth, plot predicted percentage and turnover growth
		for($i=1; $i<=$forwardMonths; $i++) {
			$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date('m') + $i, 0, date('Y')));
			$end = date('Y-m-01 00:00:00', mktime(0, 0, 0, date('m') + $i + 1, 0, date('Y')));

			$turnover = 0;
			$growthPercentage = 0;
			$growthTurnover = 0;

			for($j=($backCheckMonths*-1)+$i; $j<$i; $j++) {
				$growthPercentage += $turnoverData[$j]['Growth'];
				$turnover += $turnoverData[$j]['Turnover'];
			}

			$turnoverData[$i] = array();
			$turnoverData[$i]['Start'] = $start;
			$turnoverData[$i]['End'] = $end;
			$turnoverData[$i]['Growth'] = $growthPercentage / $backCheckMonths;
			$turnoverData[$i]['Turnover'] = $turnoverData[$i - 1]['Turnover'] + (((($turnover / $backCheckMonths) / 100) * $turnoverData[$i]['Growth']) / 12);

			for($j=$i; $j>$i-12; $j--) {
				$growthTurnover += isset($turnoverData[$j]['Turnover']) ? $turnoverData[$j]['Turnover'] : 0;
			}

			$chart1a->addPoint(new Point(date('M Y', strtotime($turnoverData[$i]['Start'])), array($growthTurnover)));
			$chart2b->addPoint(new Point(date('M Y', strtotime($turnoverData[$i]['Start'])), array($turnoverData[$i]['Growth'])));
		}

		$chart1a->SetTitle($chartTitle1a);
		$chart1a->SetLabelY('Turnover');
		$chart1a->ShowText = false;
		$chart1a->render($chartReference1a);

		$chart2b->SetTitle($chartTitle2b);
		$chart2b->SetLabelY('Percentage');
		$chart2b->ShowText = false;
		$chart2b->render($chartReference2b);
	}
	?>

	<div style="background-color: #f6f6f6; padding: 10px;">
		<p><span class="pageSubTitle">Growth</span><br /><span class="pageDescription">Growth based on turnover for all data.</span></p>

		<img src="<?php echo $chartReference1; ?>" width="<?php print $chartWidth1; ?>" height="<?php print $chartHeight1; ?>" alt="<?php print $chartTitle1; ?>" /><br /><br />
		<img src="<?php echo $chartReference2; ?>" width="<?php print $chartWidth2; ?>" height="<?php print $chartHeight2; ?>" alt="<?php print $chartTitle2; ?>" /><br /><br />

	</div><br />

	<?php
	foreach($orderTypes as $prefix=>$orderType) {
		?>

		<div style="background-color: #f6f6f6; padding: 10px;">
			<p><span class="pageSubTitle"><?php echo $orderType; ?> Growth</span><br /><span class="pageDescription">Growth based on turnover for all data.</span></p>

			<img src="<?php echo $charts[$prefix]['Turnover']['Reference']; ?>" width="<?php print $charts[$prefix]['Turnover']['Width']; ?>" height="<?php print $charts[$prefix]['Turnover']['Height']; ?>" alt="<?php print $charts[$prefix]['Turnover']['Title']; ?>" /><br /><br />
			<img src="<?php echo $charts[$prefix]['Percentage']['Reference']; ?>" width="<?php print $charts[$prefix]['Percentage']['Width']; ?>" height="<?php print $charts[$prefix]['Percentage']['Height']; ?>" alt="<?php print $charts[$prefix]['Percentage']['Title']; ?>" /><br /><br />

		</div><br />

		<?php
	}
	?>

	<script language="javascript">
	window.onload = function() {
		var httpRequest = new HttpRequest();
		httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $chartReference1; ?>');

		var httpRequest = new HttpRequest();
		httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $chartReference2; ?>');

		<?php
		foreach($orderTypes as $prefix=>$orderType) {
			?>

			var httpRequest = new HttpRequest();
			httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $charts[$prefix]['Turnover']['Reference']; ?>');

			var httpRequest = new HttpRequest();
			httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $charts[$prefix]['Percentage']['Reference']; ?>');

			<?php
		}
		?>
	}
	</script>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>