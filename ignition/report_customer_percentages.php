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
		$reportCache->Report->GetByReference('customerpercentages');
		
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
	$chart1Title = 'Monthly reorder percentages for all customers.';
	$chart1Reference = sprintf('temp/charts/chart_%s.png', $chart1FileName);
	
	$chart2FileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
	$chart2Width = 900;
	$chart2Height = 600;
	$chart2Title = 'Monthly number of registered customers.';
	$chart2Reference = sprintf('temp/charts/chart_%s.png', $chart2FileName);
	
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
	
	<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
		<img src="<?php echo $chart1Reference; ?>" width="<?php print $chart1Width; ?>" height="<?php print $chart1Height; ?>" alt="<?php print $chart1Title; ?>" />
	</div>

	<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
		<img src="<?php echo $chart2Reference; ?>" width="<?php print $chart2Width; ?>" height="<?php print $chart2Height; ?>" alt="<?php print $chart2Title; ?>" />
	</div>

	<script language="javascript">
	window.onload = function() {
		var httpRequest1 = new HttpRequest();
		httpRequest1.post('lib/util/removeChart.php', 'chart=<?php print $chart1Reference; ?>');

		var httpRequest2 = new HttpRequest();
		httpRequest2.post('lib/util/removeChart.php', 'chart=<?php print $chart2Reference; ?>');
	}
	</script>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}