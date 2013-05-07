<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReportCache.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
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
			<script language="javascript" type="text/javascript" src="../ignition/js/HttpRequest.js"></script>
		</head>
		<body>
		
		<?php
		$dataCache = new DataQuery(sprintf("SELECT rc.ReportCacheID, rc.CreatedOn FROM report_cache AS rc INNER JOIN report AS r ON r.ReportID=rc.ReportID WHERE r.Reference LIKE 'accountsales' ORDER BY rc.CreatedOn DESC LIMIT 0, 1"));
		if($dataCache->TotalRows > 0) {
			$reportCache = new ReportCache();
			$reportCache->Get($dataCache->Row['ReportCacheID']);
			$reportCache->Report->Get();
			
			$data = $reportCache->GetData();
			
			$chart1AFileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
			$chart1AWidth = 900;
			$chart1AHeight = 600;
			$chart1ATitle = 'Net order value of credit account orders.';
			$chart1AReference = sprintf('../ignition/temp/charts/chart_%s.png', $chart1AFileName);
			
			$chart1BFileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
			$chart1BWidth = 900;
			$chart1BHeight = 600;
			$chart1BTitle = 'Frequency of credit account orders.';
			$chart1BReference = sprintf('../ignition/temp/charts/chart_%s.png', $chart1BFileName);

			$chart1A = new LineChart($chart1AWidth, $chart1AHeight, array('Turnover'));
			$chart1B = new LineChart($chart1BWidth, $chart1BHeight, array('Sales'));

			foreach($data['AccountSales'] as $dataItem) {
				$chart1A->addPoint(new Point(date('M Y', strtotime($dataItem['Start'])), array($dataItem['Data'][1])));
				$chart1B->addPoint(new Point(date('M Y', strtotime($dataItem['Start'])), array($dataItem['Data'][0])));
			}

			for($i=1; $i<=(count($data['AccountSales']) / 12) - 1; $i++) {
				$chart1A->addSegment(new Segment(12 * $i));
				$chart1B->addSegment(new Segment(12 * $i));
			}

			$chart1A->SetTitle($chart1ATitle);
			$chart1A->SetLabelY('Net Order Value');
			$chart1A->ReduceLabels = true;
			$chart1A->ShowText = true;
			$chart1A->ShortenValues = true;
			$chart1A->render($chart1AReference);
			
			$chart1B->SetTitle($chart1BTitle);
			$chart1B->SetLabelY('Order Frequency');
			$chart1B->ReduceLabels = true;
			$chart1B->ShowText = false;
			$chart1B->ShortenValues = true;
			$chart1B->render($chart1BReference);
			?>

			<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
				<img src="<?php echo $chart1AReference; ?>" width="<?php print $chart1AWidth; ?>" height="<?php print $chart1AHeight; ?>" alt="<?php print $chart1ATitle; ?>" />
			</div>
			
			<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
				<img src="<?php echo $chart1BReference; ?>" width="<?php print $chart1BWidth; ?>" height="<?php print $chart1BHeight; ?>" alt="<?php print $chart1BTitle; ?>" />
			</div>

			<script language="javascript">
			window.onload = function() {
				var httpRequest1A = new HttpRequest();
				httpRequest1A.post('../ignition/lib/util/removeChart.php', 'chart=<?php echo $chart1AReference; ?>');
				
				var httpRequest1B = new HttpRequest();
				httpRequest1B.post('../ignition/lib/util/removeChart.php', 'chart=<?php echo $chart1BReference; ?>');
			}
			</script>
			
			<?php
		}
		$dataCache->Disconnect();
			
		$dataCache = new DataQuery(sprintf("SELECT rc.ReportCacheID, rc.CreatedOn FROM report_cache AS rc INNER JOIN report AS r ON r.ReportID=rc.ReportID WHERE r.Reference LIKE 'salesreorders' ORDER BY rc.CreatedOn DESC LIMIT 0, 1"));
		if($dataCache->TotalRows > 0) {
			$reportCache = new ReportCache();
			$reportCache->Get($dataCache->Row['ReportCacheID']);
			$reportCache->Report->Get();

			$data = $reportCache->GetData();
			
			$chart1FileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
			$chart1Width = 900;
			$chart1Height = 600;
			$chart1Title = 'Frequency of all orders against reorders.';
			$chart1Reference = sprintf('../ignition/temp/charts/chart_%s.png', $chart1FileName);

			$chart2FileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
			$chart2Width = 900;
			$chart2Height = 600;
			$chart2Title = 'Net order value of all orders against reorders.';
			$chart2Reference = sprintf('../ignition/temp/charts/chart_%s.png', $chart2FileName);

			$chart3FileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
			$chart3Width = 900;
			$chart3Height = 600;
			$chart3Title = 'Net order value of web orders against reorders.';
			$chart3Reference = sprintf('../ignition/temp/charts/chart_%s.png', $chart3FileName);
			
			$chart4FileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
			$chart4Width = 900;
			$chart4Height = 600;
			$chart4Title = 'Net order value of telesales orders against reorders.';
			$chart4Reference = sprintf('../ignition/temp/charts/chart_%s.png', $chart4FileName);

	        $chart5FileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
			$chart5Width = 900;
			$chart5Height = 600;
			$chart5Title = 'Quarterly new value of all orders against all orders.';
			$chart5Reference = sprintf('../ignition/temp/charts/chart_%s.png', $chart5FileName);

			$chart1 = new LineChart($chart1Width, $chart1Height, array('All Sales', 'Reorder Sales'));
			$chart2 = new LineChart($chart2Width, $chart2Height, array('All Turnover', 'Reorder Turnover'));
			$chart3 = new LineChart($chart3Width, $chart3Height, array('All Turnover', 'Reorder Turnover'));
			$chart4 = new LineChart($chart4Width, $chart4Height, array('All Turnover', 'Reorder Turnover'));
			$chart5 = new VerticalChart($chart5Width, $chart5Height, array('All Turnover', 'Reorder Turnover'));

			foreach($data['OrderFrequency'] as $dataItem) {
				$points = array();
				$points[] = $dataItem['Data'][0];
				$points[] = $dataItem['Data'][1];

				$chart1->addPoint(new Point(date('M Y', strtotime($dataItem['Start'])), $points));
			}

			for($i=1; $i<=(count($data['OrderFrequency']) / 12) - 1; $i++) {
				$chart1->addSegment(new Segment(12 * $i));
			}

			foreach($data['OrderTurnover'] as $dataItem) {
				$points = array();
				$points[] = $dataItem['Data'][0];
				$points[] = $dataItem['Data'][1];

				$chart2->addPoint(new Point(date('M Y', strtotime($dataItem['Start'])), $points));
			}

	        for($i=1; $i<=(count($data['OrderTurnover']) / 12) - 1; $i++) {
				$chart2->addSegment(new Segment(12 * $i));
			}
			
			foreach($data['OrderTurnover'] as $dataItem) {
				if(isset($dataItem['Data'][2]) && isset($dataItem['Data'][3])) {
					$points = array();
					$points[] = $dataItem['Data'][2];
					$points[] = $dataItem['Data'][3];

					$chart3->addPoint(new Point(date('M Y', strtotime($dataItem['Start'])), $points));
				}
			}

	        for($i=1; $i<=(count($data['OrderTurnover']) / 12) - 1; $i++) {
				$chart3->addSegment(new Segment(12 * $i));
			}
			
			foreach($data['OrderTurnover'] as $dataItem) {
				if(isset($dataItem['Data'][4]) && isset($dataItem['Data'][5])) {
					$points = array();
					$points[] = $dataItem['Data'][4];
					$points[] = $dataItem['Data'][5];

					$chart4->addPoint(new Point(date('M Y', strtotime($dataItem['Start'])), $points));
				}
			}

	        for($i=1; $i<=(count($data['OrderTurnover']) / 12) - 1; $i++) {
				$chart4->addSegment(new Segment(12 * $i));
			}

			$quarterlyData = array();

			foreach($data['OrderTurnover'] as $dataItem) {
				$year = date('Y', strtotime($dataItem['Start']));
				$quarter = ceil(date('m', strtotime($dataItem['Start'])) / 3) - 1;

				if(!isset($quarterlyData[$year])) {
					$quarterlyData[$year] = array();

					for($i=0; $i<4; $i++) {
						$quarterlyData[$year][$i] = array(0 => 0, 1 => 0);
					}
				}

				$quarterlyData[$year][$quarter][0] += $dataItem['Data'][0];
				$quarterlyData[$year][$quarter][1] += $dataItem['Data'][1];
			}

			foreach($quarterlyData as $year=>$yearData) {
				foreach($yearData as $quarter=>$quarterData) {
					$chart5->addPoint(new Point(sprintf('%d/4 %d', $quarter + 1, $year), $quarterData));
				}
			}

	        for($i=1; $i<=count($quarterlyData) - 1; $i++) {
				$chart5->addSegment(new Segment(4 * $i));
			}

			$chart1->SetTitle($chart1Title);
			$chart1->SetLabelY('Order Frequency');
			$chart1->ReduceLabels = true;
			$chart1->ShowText = false;
			$chart1->render($chart1Reference);

			$chart2->SetTitle($chart2Title);
			$chart2->SetLabelY('Net Order Value');
			$chart2->ReduceLabels = true;
			$chart2->ShowText = true;
			$chart2->ShortenValues = true;
			$chart2->render($chart2Reference);

			$chart3->SetTitle($chart3Title);
			$chart3->SetLabelY('Net Order Value');
			$chart3->ReduceLabels = true;
			$chart3->ShowText = true;
			$chart3->ShortenValues = true;
			$chart3->render($chart3Reference);
			
			$chart4->SetTitle($chart4Title);
			$chart4->SetLabelY('Net Order Value');
			$chart4->ReduceLabels = true;
			$chart4->ShowText = true;
			$chart4->ShortenValues = true;
			$chart4->render($chart4Reference);

	        $chart5->SetTitle($chart5Title);
			$chart5->SetLabelY('Net Order Value');
			$chart5->ShowText = true;
			$chart5->ShortenValues = true;
			$chart5->render($chart5Reference);
			?>

			<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
				<img src="<?php echo $chart1Reference; ?>" width="<?php print $chart1Width; ?>" height="<?php print $chart1Height; ?>" alt="<?php print $chart1Title; ?>" />
			</div>

			<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
				<img src="<?php echo $chart2Reference; ?>" width="<?php print $chart2Width; ?>" height="<?php print $chart2Height; ?>" alt="<?php print $chart2Title; ?>" />
			</div>

			<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
				<img src="<?php echo $chart3Reference; ?>" width="<?php print $chart3Width; ?>" height="<?php print $chart3Height; ?>" alt="<?php print $chart3Title; ?>" />
			</div>

            <div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
				<img src="<?php echo $chart4Reference; ?>" width="<?php print $chart4Width; ?>" height="<?php print $chart4Height; ?>" alt="<?php print $chart4Title; ?>" />
			</div>

            <div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
				<img src="<?php echo $chart5Reference; ?>" width="<?php print $chart5Width; ?>" height="<?php print $chart5Height; ?>" alt="<?php print $chart5Title; ?>" />
			</div>

			<script language="javascript">
			window.onload = function() {
				var httpRequest1 = new HttpRequest();
				httpRequest1.post('../ignition/lib/util/removeChart.php', 'chart=<?php echo $chart1Reference; ?>');

				var httpRequest2 = new HttpRequest();
				httpRequest2.post('../ignition/lib/util/removeChart.php', 'chart=<?php echo $chart2Reference; ?>');

				var httpRequest3 = new HttpRequest();
				httpRequest3.post('../ignition/lib/util/removeChart.php', 'chart=<?php echo $chart3Reference; ?>');

                var httpRequest4 = new HttpRequest();
				httpRequest4.post('../ignition/lib/util/removeChart.php', 'chart=<?php echo $chart4Reference; ?>');
				
				var httpRequest5 = new HttpRequest();
				httpRequest5.post('../ignition/lib/util/removeChart.php', 'chart=<?php echo $chart5Reference; ?>');
			}
			</script>
			
			<?php
		}
		$dataCache->Disconnect();
		?>

	</body>
	</html>
	
	<?php	
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();