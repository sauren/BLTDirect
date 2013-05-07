<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReportCache.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$chart1FileName = 'welcome'. rand(0, 9999999);
	$chart1Width = 900;
	$chart1Height = 600;
	$chart1Title = 'Turnover from all invoices';
	$chart1Reference = sprintf('../ignition/temp/charts/chart_%s.png', $chart1FileName);

	$chart1 = new VerticalChart($chart1Width, $chart1Height, array());

	$startDate = date('Y-m-01 00:00:00');
	$endDate = date('Y-m-01 00:00:00');

	$connection = new MySQLConnection($GLOBALS['ELLWOOD_DB_HOST'], $GLOBALS['ELLWOOD_DB_NAME'], $GLOBALS['ELLWOOD_DB_USERNAME'], $GLOBALS['ELLWOOD_DB_PASSWORD']);

	$data = new DataQuery(sprintf("SELECT DATE_FORMAT(MIN(Created_On), '%%Y-%%m-01 00:00:00') AS Month FROM job_invoice"), $connection);
	if($data->TotalRows > 0) {
		if(strtotime($data->Row['Month']) < strtotime($startDate)) {
			$startDate = $data->Row['Month'];
		}	
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT DATE_FORMAT(MIN(Created_On), '%%Y-%%m-01 00:00:00') AS Month FROM job_credit_note"), $connection);
	if($data->TotalRows > 0) {
		if(strtotime($data->Row['Month']) < strtotime($startDate)) {
			$startDate = $data->Row['Month'];
		}	
	}
	$data->Disconnect();

	$cacheInvoice = array();
	$cacheCredit = array();

	$data = new DataQuery(sprintf("SELECT SUM(Total_Cost) AS Total, DATE_FORMAT(Created_On, '%%Y-%%m-01 00:00:00') AS Month FROM job_invoice GROUP BY Month ORDER BY Month ASC"), $connection);
	while($data->Row) {
		$cacheInvoice[$data->Row['Month']] = $data->Row['Total'];
			
		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT SUM(Total_Net) AS Total, DATE_FORMAT(Created_On, '%%Y-%%m-01 00:00:00') AS Month FROM job_credit_note GROUP BY Month ORDER BY Month ASC"), $connection);
	while($data->Row) {
		$cacheCredit[$data->Row['Month']] = $data->Row['Total'];
		
		$data->Next();
	}
	$data->Disconnect();

	$averageCache = array();

	$tempTime = strtotime($startDate);
	$endTime = strtotime($endDate);

	while($tempTime <= $endTime) {
		$tempDate = date('Y-m-d H:i:s', $tempTime);

		$total = 0;
		$total += isset($cacheInvoice[$tempDate]) ? $cacheInvoice[$tempDate] : 0;
		$total -= isset($cacheCredit[$tempDate]) ? $cacheCredit[$tempDate] : 0;
		
		$averageCache[] = $total;
		
		$chart1->addPoint(new Point(date('Y-m', $tempTime), array($total)));

		$tempTime = mktime(0, 0, 0, date('m', $tempTime) + 1, 1, date('Y', $tempTime));	
	}

	$average3Months = 0;

	for($i=count($averageCache)-2; $i>=0; $i--) {
		if(((count($averageCache)-1) - $i) <= 3) {
			$average3Months += $averageCache[$i];
		}
	}

	$chart1->SetTitle($chart1Title);
	$chart1->SetLabelY('Turnover');
	$chart1->ShowLabels = true;
	$chart1->LabelInterval = 1;
	$chart1->render($chart1Reference);
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
	
		<script language="javascript">
		window.onload = function() {
			var httpRequest1 = new HttpRequest();
			httpRequest1.post('../ignition/lib/util/removeChart.php', 'chart=<?php echo $chart1Reference; ?>');
		}
		</script>

	</body>
	</html>
	
	<?php		
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();