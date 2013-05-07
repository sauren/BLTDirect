<?php
require_once('lib/common/app_header.php');

if($action == 'report') {
	$session->Secure(2);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('range', 'Date range', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('range', 'none', '-- None --');
	$form->AddOption('range', '', '');
	$form->AddOption('range', 'thismonth', 'This Month');
	$form->AddOption('range', 'thisyear', 'This Year');
	$form->AddOption('range', '', '');
	$form->AddOption('range', 'lastmonth', 'Last Month');
	$form->AddOption('range', 'last3months', 'Last 3 Months');
	$form->AddOption('range', 'last6months', 'Last 6 Months');
	$form->AddOption('range', 'last12months', 'Last 12 Months');
	$form->AddOption('range', '', '');
	$form->AddOption('range', 'lastyear', 'Last Year');
	$form->AddOption('range', 'last2years', 'Last 2 Years');
	$form->AddOption('range', 'last3years', 'Last 3 Years');
	$form->AddField('start', 'Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('prefix', 'Order Type', 'select', '', 'alpha', 0, 1);
	$form->AddOption('prefix', '', '');
	$form->AddOption('prefix', 'W', 'Website (bltdirect.com)');
	$form->AddOption('prefix', 'U', 'Website (bltdirect.co.uk)');
	$form->AddOption('prefix', 'L', 'Website (lightbulbsuk.co.uk)');
	$form->AddOption('prefix', 'M', 'Mobile');
	$form->AddOption('prefix', 'T', 'Telesales');
	$form->AddOption('prefix', 'F', 'Fax');
	$form->AddOption('prefix', 'E', 'Email');

	if(isset($_REQUEST['confirm'])) {
		if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 0)) {
			switch($form->GetValue('range')) {
				case 'all':
					$start = date('Y-m-d H:i:s', 0);
					$end = date('Y-m-d H:i:s');
					break;

				case 'thisminute':
					$start = date('Y-m-d H:i:00');
					$end = date('Y-m-d H:i:s');
					break;
				case 'thishour':
					$start = date('Y-m-d H:00:00');
					$end = date('Y-m-d H:i:s');
					break;
				case 'thisday':
					$start = date('Y-m-d 00:00:00');
					$end = date('Y-m-d H:i:s');
					break;
				case 'thismonth':
					$start = date('Y-m-01 00:00:00');
					$end = date('Y-m-d H:i:s');
					break;
				case 'thisyear':
					$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")));
					$end = date('Y-m-d H:i:s');
					break;
				case 'thisfinancialyear':
					$boundary = date('Y-m-d 00:00:00', mktime(0, 0, 0, 5, 1, date("Y")));

					if(time() < strtotime($boundary)) {
						$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 5, 1, date("Y")-1));
						$end = $boundary;
					} else {
						$start = $boundary;
						$end = date('Y-m-d 00:00:00', mktime(0, 0, 0, 5, 1, date("Y")+1));
					}

					break;

				case 'lasthour':
					$start = date('Y-m-d H:00:00', mktime(date("H")-1, 0, 0, date("m"), date("d"),  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
					break;
				case 'last3hours':
					$start = date('Y-m-d H:00:00', mktime(date("H")-3, 0, 0, date("m"), date("d"),  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
					break;
				case 'last6hours':
					$start = date('Y-m-d H:00:00', mktime(date("H")-6, 0, 0, date("m"), date("d"),  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
					break;
				case 'last12hours':
					$start = date('Y-m-d H:00:00', mktime(date("H")-12, 0, 0, date("m"), date("d"),  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
					break;

				case 'lastday':
					$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
					break;
				case 'last2days':
					$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-2, date("Y")));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
					break;
				case 'last3days':
					$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
					break;
				case 'last7days':
					$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
					break;


				case 'lastmonth':
					$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-1, 1,  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
					break;
				case 'last3months':
					$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-3, 1,  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
					break;
				case 'last6months':
					$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-6, 1,  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
					break;
				case 'last12months':
					$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-12, 1,  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
					break;

				case 'lastyear':
					$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-1));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
					break;
				case 'last2years':
					$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-2));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
					break;
				case 'last3years':
					$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-3));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
					break;
			}

			redirect(sprintf("Location: %s?action=report&start=%s&end=%s&prefix=%s", $_SERVER['PHP_SELF'], $start, $end, $form->GetValue('prefix')));
		} else {
			if($form->Validate()) {
				redirect(sprintf("Location: %s?action=report&start=%s&end=%s&prefix=%s", $_SERVER['PHP_SELF'], $form->GetValue('start'), $form->GetValue('end'), $form->GetValue('prefix')));
			}
		}
	}

	$page = new Page('Sales Orders Report', 'Please select the filters for your report.');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Sales Orders.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select one of the predefined date ranges for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('range'), $form->GetHTML('range'));
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start'));
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end'));
	echo $webForm->AddRow($form->GetLabel('prefix'), $form->GetHTML('prefix'));
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/chart/libchart.php');

	$page = new Page('Sales Orders Report', '');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
	$page->Display('header');

	$chartFileName = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
	$chartWidth = 900;
	$chartHeight = 600;
	$chartTitle = 'Sale Orders (Date Range Minus One Year)';
	$chartReference = sprintf('temp/charts/chart_%s.png', $chartFileName);

	$chartFileName2 = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
	$chartWidth2 = 900;
	$chartHeight2 = 600;
	$chartTitle2 = 'Sale Orders (Date Range)';
	$chartReference2 = sprintf('temp/charts/chart_%s.png', $chartFileName2);

	$connections = getSyncConnections();

	$chart = new VerticalChart($chartWidth, $chartHeight, array('No. Orders'));
	$chart2 = new VerticalChart($chartWidth, $chartHeight, array('No. Orders'));

	$orders = array();

	$start = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($_REQUEST['start'])), date('d', strtotime($_REQUEST['start'])), date('Y', strtotime($_REQUEST['start'])) - 1));
	$end = (strlen($_REQUEST['end']) > 0) ? date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($_REQUEST['end'])), date('d', strtotime($_REQUEST['end'])), date('Y', strtotime($_REQUEST['end'])) - 1)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($start)), date('d', strtotime($start)) + 1, date('Y', strtotime($start))));
	
	$endTime = strtotime($end);

	$tempTime = strtotime($start);
	$temp = date('Y-m-d', $tempTime);

	while($tempTime < $endTime) {
		$orders[$temp] = 0;

		$tempTime += 86400;
		$temp = date('Y-m-d', $tempTime);
	}

	for($j=0; $j<count($connections); $j++) {
		$data = new DataQuery(sprintf("SELECT COUNT(Order_ID) AS Count, DATE(Created_On) AS Date FROM orders WHERE Created_On>='%s' AND Created_On<'%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N' %s GROUP BY DATE(Created_On) ORDER BY Date ASC", mysql_real_escape_string($start), mysql_real_escape_string($end), (isset($_REQUEST['prefix']) && (strlen($_REQUEST['prefix']) > 0)) ? sprintf("AND Order_Prefix='%s'", mysql_real_escape_string($_REQUEST['prefix'])) : ''), $connections[$j]['Connection']);
		while($data->Row) {
			$orders[$data->Row['Date']] += $data->Row['Count'];

			$data->Next();
		}
		$data->Disconnect();
	}

	foreach($orders as $date=>$count) {
		$chart->addPoint(new Point(date('d/m (D)', strtotime($date)), array($count)));
	}

	$orders = array();

	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];

	$endTime = strtotime($end);

	$tempTime = strtotime($start);
	$temp = date('Y-m-d', $tempTime);

	while($tempTime < $endTime) {
		$orders[$temp] = 0;

		$tempTime += 86400;
		$temp = date('Y-m-d', $tempTime);
	}

	for($j=0; $j<count($connections); $j++) {
		$data = new DataQuery(sprintf("SELECT COUNT(Order_ID) AS Count, DATE(Created_On) AS Date FROM orders WHERE Created_On>='%s' AND Created_On<'%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N' %s GROUP BY DATE(Created_On) ORDER BY Date ASC", mysql_real_escape_string($start), mysql_real_escape_string($end), (isset($_REQUEST['prefix']) && (strlen($_REQUEST['prefix']) > 0)) ? sprintf("AND Order_Prefix='%s'", mysql_real_escape_string($_REQUEST['prefix'])) : ''), $connections[$j]['Connection']);
		while($data->Row) {
			$orders[$data->Row['Date']] += $data->Row['Count'];

			$data->Next();
		}
		$data->Disconnect();
	}

	foreach($orders as $date=>$count) {
		$chart2->addPoint(new Point(date('d/m (D)', strtotime($date)), array($count)));
	}

	$chart->SetTitle($chartTitle);
	$chart->SetLabelY('Orders');
	$chart->ShowText = false;
	$chart->ShowLabels = (count($orders) > 50) ? false : true;
	$chart->render($chartReference);

	$chart2->SetTitle($chartTitle2);
	$chart2->SetLabelY('Orders');
	$chart2->ShowText = false;
	$chart2->ShowLabels = (count($orders) > 50) ? false : true;
	$chart2->render($chartReference2);
	?>

	<br />
	<h3>Sale Orders</h3>
	<p>Sale orders statistics.</p>

	<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
		<img src="<?php echo $chartReference; ?>" width="<?php print $chartWidth; ?>" height="<?php print $chartHeight; ?>" alt="<?php print $chartTitle; ?>" />
	</div>

	<br />

	<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
		<img src="<?php echo $chartReference2; ?>" width="<?php print $chartWidth2; ?>" height="<?php print $chartHeight; ?>" alt="<?php print $chartTitle2; ?>" />
	</div>

	<script language="javascript">
		window.onload = function() {
			var httpRequest = new HttpRequest();
			httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $chartReference; ?>');

			var httpRequest = new HttpRequest();
			httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $chartReference2; ?>');
		}
	</script>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}