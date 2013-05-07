<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReportCache.php');

$session->Secure(2);
view();
exit();

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');

	$reportCache = new ReportCache();

	if(!isset($_REQUEST['id']) || !$reportCache->Get($_REQUEST['id'])) {
		$reportCache->Report->GetByReference('salesreorders');
		
		if(!$reportCache->GetMostRecent()) {
			redirect('Location: report.php');
		}
	}

	$reportCache->Report->Get();

	$page = new Page(sprintf('<a href="reports.php">Reports</a> &gt; <a href="reports.php?action=open&id=%d">Open Report</a> &gt; %s', $reportCache->Report->ID, $reportCache->Report->Name), sprintf('Report data for the \'%s\'', cDatetime($reportCache->CreatedOn, 'shortdatetime')));
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
	$page->Display('header');

	$data = $reportCache->GetData();

	$chart1FileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
	$chart1Width = 900;
	$chart1Height = 600;
	$chart1Title = 'Frequency of all orders against reorders.';
	$chart1Reference = sprintf('temp/charts/chart_%s.png', $chart1FileName);

	$chart2FileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
	$chart2Width = 900;
	$chart2Height = 600;
	$chart2Title = 'Net order value of all orders against reorders.';
	$chart2Reference = sprintf('temp/charts/chart_%s.png', $chart2FileName);

	$chart3FileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
	$chart3Width = 900;
	$chart3Height = 600;
	$chart3Title = 'Percentage ratio of reorder statistics against all orders.';
	$chart3Reference = sprintf('temp/charts/chart_%s.png', $chart3FileName);

	$chart1 = new LineChart($chart1Width, $chart1Height, array('All Sales', 'Reorder Sales'));
	$chart2 = new LineChart($chart2Width, $chart2Height, array('All Turnover', 'Reorder Turnover'));
	$chart3 = new LineChart($chart3Width, $chart3Height, array('Sales Ratio', 'Turnover Ratio'));

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

	for($i=1; $i<=(count($data['OrderFrequency']) / 12) - 1; $i++) {
		$chart2->addSegment(new Segment(12 * $i));
	}

	foreach($data['PercentageRatio'] as $dataItem) {
		$points = array();
		$points[] = $dataItem['Data'][0];
		$points[] = $dataItem['Data'][1];

		$chart3->addPoint(new Point(date('M Y', strtotime($dataItem['Start'])), $points));
	}

	for($i=1; $i<=(count($data['OrderFrequency']) / 12) - 1; $i++) {
		$chart3->addSegment(new Segment(12 * $i));
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
	$chart3->SetLabelY('Percentage (%)');
	$chart3->ReduceLabels = true;
	$chart3->ShowText = false;
	$chart3->render($chart3Reference);
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

	<script language="javascript">
	window.onload = function() {
		var httpRequest1 = new HttpRequest();
		httpRequest1.post('lib/util/removeChart.php', 'chart=<?php print $chart1Reference; ?>');

		var httpRequest2 = new HttpRequest();
		httpRequest2.post('lib/util/removeChart.php', 'chart=<?php print $chart2Reference; ?>');

		var httpRequest3 = new HttpRequest();
		httpRequest3.post('lib/util/removeChart.php', 'chart=<?php print $chart3Reference; ?>');
	}
	</script>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}