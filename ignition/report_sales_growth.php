<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Sales Growth Report', 'Please choose a start and end date for your report');
	$year = cDatetime(getDatetime(), 'y');
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('growth', 'Growth', 'select', '0', 'numeric_unsigned', 1, 11);

	for($i=0; $i<=100; $i+=5) {
		$form->AddOption('growth', $i, sprintf('%d%%', $i));
	}

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		report($form->GetValue('growth'));
		exit;
	}

	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Sales Growth.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Select a percentage for forecasting growth.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('growth'), $form->GetHTML('growth'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($growth = 0){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');

	$page = new Page('Sales Growth Report', '');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

	$chartFileName = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
	$chartWidth = 900;
	$chartHeight = 600;
	$chartTitle = 'All Sales';
	$chartReference = sprintf('temp/charts/chart_%s.png', $chartFileName);

	$chartFileName2 = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
	$chartWidth2 = 900;
	$chartHeight2 = 600;
	$chartTitle2 = 'All Sales';
	$chartReference2 = sprintf('temp/charts/chart_%s.png', $chartFileName2);

	$connections = getSyncConnections();

	$chart = new LineChart(900, 600, array('Actual Sales', sprintf('%s%% Growth Sales', $growth)));
	$chart2 = new LineChart(900, 600, array('Actual Turnover', sprintf('%s%% Growth Turnover', $growth)));

	$startYear = -2;
	$endYear = 1;

	$pastData['Sales'] = array();
	$growthData['Sales'] = array();

	for($i=$startYear; $i<=$endYear; $i++) {
		for($j=1; $j<=12; $j++) {
			$points = array();
			$points2 = array();

			$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, $j + 1, 0, date('Y') + $i));
			$end = date('Y-m-01 00:00:00', mktime(0, 0, 0, $j + 2, 0, date('Y') + $i));

			$pastData['Sales'][$j][$i] = 0;
			$pastData['Turnover'][$j][$i] = 0;

			for($k=0; $k<count($connections); $k++) {
				$data = new DataQuery(sprintf("SELECT COUNT(Order_ID) AS OrderCount, SUM(Total) AS Total FROM orders WHERE Created_On BETWEEN '%s' AND '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N'", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$k]['Connection']);
				$pastData['Sales'][$j][$i] += $data->Row['OrderCount'];
				$pastData['Turnover'][$j][$i] += $data->Row['Total'];
				$data->Disconnect();
			}

			$points[] = $pastData['Sales'][$j][$i];
			$points2[] = number_format($pastData['Turnover'][$j][$i], 2, '.', '');

			if($i > 0) {
				$growthData['Sales'][$j][$i] = $growthData['Sales'][$j][$i - 1] * (1 + ($growth / 100));
				$growthData['Turnover'][$j][$i] = $growthData['Turnover'][$j][$i - 1] * (1 + ($growth / 100));
			} elseif($i > $startYear) {
				$growthData['Sales'][$j][$i] = $pastData['Sales'][$j][$i - 1] * (1 + ($growth / 100));
				$growthData['Turnover'][$j][$i] = $pastData['Turnover'][$j][$i - 1] * (1 + ($growth / 100));
			} else {
				$growthData['Sales'][$j][$i] = 0;
				$growthData['Turnover'][$j][$i] = 0;
			}

			$points[] = $growthData['Sales'][$j][$i];
			$points2[] = number_format($growthData['Turnover'][$j][$i], 2, '.', '');

			$chart->addPoint(new Point(date('M Y', strtotime($start)), $points));
			$chart2->addPoint(new Point(date('M Y', strtotime($start)), $points2));
		}

		$chart->addSegment(new Segment(count($chart->point)));
		$chart2->addSegment(new Segment(count($chart2->point)));
	}

	$chart->SetTitle($chartTitle);
	$chart->SetLabelY('Order Frequency');
	$chart->ReduceLabels = true;
	$chart->ShowText = false;
	$chart->render($chartReference);

	$chart2->SetTitle($chartTitle2);
	$chart2->SetLabelY('Gross Order Value');
	$chart2->ReduceLabels = true;
	$chart2->ShowText = true;
	$chart2->ShortenValues = true;
	$chart2->render($chartReference2);

	$chartFileName3 = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
	$chartWidth3 = 900;
	$chartHeight3 = 600;
	$chartTitle3 = 'Telesales';
	$chartReference3 = sprintf('temp/charts/chart_%s.png', $chartFileName3);

	$chartFileName4 = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
	$chartWidth4 = 900;
	$chartHeight4 = 600;
	$chartTitle4 = 'Telesales';
	$chartReference4 = sprintf('temp/charts/chart_%s.png', $chartFileName4);

	$connections = getSyncConnections();

	$chart3 = new LineChart(900, 600, array('Actual Sales', sprintf('%s%% Growth Sales', $growth)));
	$chart4 = new LineChart(900, 600, array('Actual Turnover', sprintf('%s%% Growth Turnover', $growth)));

	$pastData['Sales'] = array();
	$growthData['Sales'] = array();

	for($i=$startYear; $i<=$endYear; $i++) {
		for($j=1; $j<=12; $j++) {
			$points = array();
			$points2 = array();

			$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, $j + 1, 0, date('Y') + $i));
			$end = date('Y-m-01 00:00:00', mktime(0, 0, 0, $j + 2, 0, date('Y') + $i));

			$pastData['Sales'][$j][$i] = 0;
			$pastData['Turnover'][$j][$i] = 0;

			for($k=0; $k<count($connections); $k++) {
				$data = new DataQuery(sprintf("SELECT COUNT(Order_ID) AS OrderCount, SUM(Total) AS Total FROM orders WHERE Created_On BETWEEN '%s' AND '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix='T'", mysql_real_escape_string($start), mysql_real_escape_string($end)), $connections[$k]['Connection']);
				$pastData['Sales'][$j][$i] += $data->Row['OrderCount'];
				$pastData['Turnover'][$j][$i] += $data->Row['Total'];
				$data->Disconnect();
			}

			$points[] = $pastData['Sales'][$j][$i];
			$points2[] = number_format($pastData['Turnover'][$j][$i], 2, '.', '');

			if($i > 0) {
				$growthData['Sales'][$j][$i] = $growthData['Sales'][$j][$i - 1] * (1 + ($growth / 100));
				$growthData['Turnover'][$j][$i] = $growthData['Turnover'][$j][$i - 1] * (1 + ($growth / 100));
			} elseif($i > $startYear) {
				$growthData['Sales'][$j][$i] = $pastData['Sales'][$j][$i - 1] * (1 + ($growth / 100));
				$growthData['Turnover'][$j][$i] = $pastData['Turnover'][$j][$i - 1] * (1 + ($growth / 100));
			} else {
				$growthData['Sales'][$j][$i] = 0;
				$growthData['Turnover'][$j][$i] = 0;
			}

			$points[] = $growthData['Sales'][$j][$i];
			$points2[] = number_format($growthData['Turnover'][$j][$i], 2, '.', '');

			$chart3->addPoint(new Point(date('M Y', strtotime($start)), $points));
			$chart4->addPoint(new Point(date('M Y', strtotime($start)), $points2));
		}

		$chart3->addSegment(new Segment(count($chart3->point)));
		$chart4->addSegment(new Segment(count($chart4->point)));
	}

	$chart3->SetTitle($chartTitle3);
	$chart3->SetLabelY('Order Frequency');
	$chart3->ReduceLabels = true;
	$chart3->ShowText = false;
	$chart3->render($chartReference3);

	$chart4->SetTitle($chartTitle4);
	$chart4->SetLabelY('Gross Order Value');
	$chart4->ReduceLabels = true;
	$chart4->ShowText = true;
	$chart4->ShortenValues = true;
	$chart4->render($chartReference4);
	?>

	<br />
	<h3>Sale Growth</h3>
	<p>Sale growth statistics on all orders.</p>

	<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
		<img src="<?php echo $chartReference; ?>" width="<?php print $chartWidth; ?>" height="<?php print $chartHeight; ?>" alt="<?php print $chartTitle; ?>" />
	</div>

	<br />

	<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
		<img src="<?php echo $chartReference2; ?>" width="<?php print $chartWidth2; ?>" height="<?php print $chartHeight2; ?>" alt="<?php print $chartTitle2; ?>" />
	</div>

	<br />

	<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
		<img src="<?php echo $chartReference3; ?>" width="<?php print $chartWidth3; ?>" height="<?php print $chartHeight3; ?>" alt="<?php print $chartTitle3; ?>" />
	</div>

	<br />

	<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
		<img src="<?php echo $chartReference4; ?>" width="<?php print $chartWidth4; ?>" height="<?php print $chartHeight4; ?>" alt="<?php print $chartTitle4; ?>" />
	</div>

	<script language="javascript">
	window.onload = function() {
		var httpRequest = new HttpRequest();
		httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $chartReference; ?>');

		var httpRequest2 = new HttpRequest();
		httpRequest2.post('lib/util/removeChart.php', 'chart=<?php print $chartReference2; ?>');

		var httpRequest3 = new HttpRequest();
		httpRequest3.post('lib/util/removeChart.php', 'chart=<?php print $chartReference3; ?>');

		var httpRequest4 = new HttpRequest();
		httpRequest4.post('lib/util/removeChart.php', 'chart=<?php print $chartReference4; ?>');
	}
	</script>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>