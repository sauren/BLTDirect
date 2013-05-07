<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReportCache.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$dataCache = new DataQuery(sprintf("SELECT ReportCacheID, CreatedOn FROM report_cache WHERE ReportID=%d ORDER BY CreatedOn DESC LIMIT 0, 1", 2));
	if($dataCache->TotalRows > 0) {
		$reportCache = new ReportCache();
		$reportCache->Get($dataCache->Row['ReportCacheID']);
		$reportCache->Report->Get();
		
		$data = $reportCache->GetData();
		
		$chart1FileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
		$chart1Width = 900;
		$chart1Height = 600;
		$chart1Title = 'Monthly reorder percentages for all customers.';
		$chart1Reference = sprintf('../ignition/temp/charts/chart_%s.png', $chart1FileName);
		
		$chart2FileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
		$chart2Width = 900;
		$chart2Height = 600;
		$chart2Title = 'Monthly number of registered customers.';
		$chart2Reference = sprintf('../ignition/temp/charts/chart_%s.png', $chart2FileName);
		
		$chart1 = new LineChart($chart1Width, $chart1Height, array('Reorder Percent'));
		$chart2 = new LineChart($chart2Width, $chart2Height, array('Customer Count'));
		
		for($i=0; $i<count($data); $i++) {
			$chart1->addPoint(new Point(date('M Y', strtotime($data[$i]['Start'])), array($data[$i]['ReorderPercentage'])));
			$chart2->addPoint(new Point(date('M Y', strtotime($data[$i]['Start'])), array($data[$i]['Customers'])));
		}
		
		$chart1->SetTitle($chart1Title);
		$chart1->SetLabelY('Percentage');
		$chart1->ShowText = false;
		$chart1->render($chart1Reference);
		
		$chart2->SetTitle($chart2Title);
		$chart2->SetLabelY('Number');
		$chart2->ShowText = false;
		$chart2->render($chart2Reference);
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
		
			<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
				<img src="<?php echo $chart1Reference; ?>" width="<?php print $chart1Width; ?>" height="<?php print $chart1Height; ?>" alt="<?php print $chart1Title; ?>" />
			</div>
		
			<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
				<img src="<?php echo $chart2Reference; ?>" width="<?php print $chart2Width; ?>" height="<?php print $chart2Height; ?>" alt="<?php print $chart2Title; ?>" />
			</div>
		
			<script language="javascript">
			window.onload = function() {
				var httpRequest1 = new HttpRequest();
				httpRequest1.post('../ignition/lib/util/removeChart.php', 'chart=<?php echo $chart1Reference; ?>');
		
				var httpRequest2 = new HttpRequest();
				httpRequest2.post('../ignition/lib/util/removeChart.php', 'chart=<?php echo $chart2Reference; ?>');
			}
			</script>
	
		</body>
		</html>
		
		<?php		
	} else {
		header("HTTP/1.0 404 Not Found");
	}
	$dataCache->Disconnect();
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();