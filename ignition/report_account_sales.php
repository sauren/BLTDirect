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
		$reportCache->Report->GetByReference('accountsales');
		
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
	$chart1itle = 'Net order value of credit account orders.';
	$chart1Reference = sprintf('temp/charts/chart_%s.png', $chart1FileName);

	$chart1 = new LineChart($chart1Width, $chart1Height, array('Turnover'));

	foreach($data['AccountSales'] as $dataItem) {
		$points = array();
		$points[] = $dataItem['Data'][1];

		$chart1->addPoint(new Point(date('M Y', strtotime($dataItem['Start'])), $points));
	}

	for($i=1; $i<=(count($data['AccountSales']) / 12) - 1; $i++) {
		$chart1->addSegment(new Segment(12 * $i));
	}

	$chart1->SetTitle($chart1Title);
	$chart1->SetLabelY('Net Order Value');
	$chart1->ReduceLabels = true;
	$chart1->ShowText = true;
	$chart1->ShortenValues = true;
	$chart1->render($chart1Reference);
	?>

	<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
		<img src="<?php echo $chart1Reference; ?>" width="<?php print $chart1Width; ?>" height="<?php print $chart1Height; ?>" alt="<?php print $chart1Title; ?>" />
	</div>

	<script language="javascript">
	window.onload = function() {
		var httpRequest1 = new HttpRequest();
		httpRequest1.post('lib/util/removeChart.php', 'chart=<?php print $chart1Reference; ?>');
	}
	</script>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}