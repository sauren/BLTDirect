<?php
function rgb2html($r, $g, $b) {
	$r = intval($r);
	$g = intval($g);
	$b = intval($b);
	$r = dechex($r<0?0:($r>255?255:$r));
	$g = dechex($g<0?0:($g>255?255:$g));
	$b = dechex($b<0?0:($b>255?255:$b));
	$c = (strlen($r) < 2?'0':'').$r;
	$c .= (strlen($g) < 2?'0':'').$g;
	$c .= (strlen($b) < 2?'0':'').$b;
	return $c;
}

require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Band Distribution Report', 'Please choose a start and end date for your report');
	$year = cDatetime(getDatetime(), 'y');
	$form = new Form($_SERVER['PHP_SELF'], 'get');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'Report End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('range', 'Date range', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('range', 'none', '-- None --');
	$form->AddOption('range', 'all', '-- All --');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'thisminute', 'This Minute');
	$form->AddOption('range', 'thishour', 'This Hour');
	$form->AddOption('range', 'thisday', 'This Day');
	$form->AddOption('range', 'thismonth', 'This Month');
	$form->AddOption('range', 'thisyear', 'This Year');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lasthour', 'Last Hour');
	$form->AddOption('range', 'last3hours', 'Last 3 Hours');
	$form->AddOption('range', 'last6hours', 'Last 6 Hours');
	$form->AddOption('range', 'last12hours', 'Last 12 Hours');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastday', 'Last Day');
	$form->AddOption('range', 'last2days', 'Last 2 Days');
	$form->AddOption('range', 'last3days', 'Last 3 Days');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastmonth', 'Last Month');
	$form->AddOption('range', 'last3months', 'Last 3 Months');
	$form->AddOption('range', 'last6months', 'Last 6 Months');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastyear', 'Last Year');
	$form->AddOption('range', 'last2years', 'Last 2 Years');
	$form->AddOption('range', 'last3years', 'Last 3 Years');
	$form->AddField('prefix', 'Order Type', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('prefix', '', '-- All --');
	$form->AddOption('prefix', 'W', 'Website');
	$form->AddOption('prefix', 'T', 'Telesales');
	$form->AddOption('prefix', 'E', 'Email');
	$form->AddOption('prefix', 'F', 'Fax');
	$form->AddOption('prefix', 'M', 'Mobile');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 1)) {
			switch($form->GetValue('range')) {
				case 'all': 		$start = date('Y-m-d H:i:s', 0);
				$end = date('Y-m-d H:i:s');
				break;

				case 'thisminute': 	$start = date('Y-m-d H:i:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thishour': 	$start = date('Y-m-d H:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thisday': 	$start = date('Y-m-d 00:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thismonth': 	$start = date('Y-m-01 00:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thisyear': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")));
				$end = date('Y-m-d H:i:s');
				break;

				case 'lasthour': 	$start = date('Y-m-d H:00:00', mktime(date("H")-1, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last3hours': 	$start = date('Y-m-d H:00:00', mktime(date("H")-3, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last6hours': 	$start = date('Y-m-d H:00:00', mktime(date("H")-6, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last12hours': $start = date('Y-m-d H:00:00', mktime(date("H")-12, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;

				case 'lastday': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last2days': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-2, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last3days': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;

				case 'lastmonth': 	$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-1, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;
				case 'last3months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-3, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;
				case 'last6months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-6, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;

				case 'lastyear': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-1));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
				case 'last2years': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-2));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
				case 'last3years': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-3));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
			}

			report($start, $end, $form->GetValue('prefix'));
			exit;
		} else {

			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('prefix'));
				exit;
			}
		}
	}

	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Band Distributions.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select the type of orders to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('prefix'), $form->GetHTML('prefix'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Select one of the predefined date ranges for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('range'), $form->GetHTML('range'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Or select the date range from below for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start'));
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end'));
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

function report($start, $end, $prefix){
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/chart/libchart.php');

	$totalFrequency = 0;
	$lastRange = 0;

	$ranges = array();
	$ranges[10] = 0;
	$ranges[20] = 0;
	$ranges[40] = 0;
	$ranges[60] = 0;
	$ranges[80] = 0;
	$ranges[100] = 0;
	$ranges[250] = 0;
	$ranges[500] = 0;
	$ranges[750] = 0;
	$ranges[1000] = 0;
	$ranges[2000] = 0;
	$ranges[3000] = 0;
	$ranges[5000] = 0;
	$ranges[10000] = 0;
	$ranges[1000000] = 0;

	$page = new Page('Band Distribution Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
	$page->Display('header');
	?>

	<h3>Band Distribution Percentages</h3>
	<p>The band distribution percentages of orders whose total value for the period lies between the below predefined ranges. Please note this report is based on order sub totals.</p>

	<?php
	$sqlPrefix = '';

	if(strlen($prefix) > 0) {
		$sqlPrefix = sprintf(" WHERE o.Order_Prefix='%s' AND o.Created_On BETWEEN '%s' AND '%s'", $prefix, $start, $end);
	} else {
		$sqlPrefix = sprintf(" WHERE o.Created_On BETWEEN '%s' AND '%s'", $start, $end);
	}

	$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_orders SELECT o.Order_ID, o.SubTotal, ol.Order_Line_ID, p.Product_Band_ID
										FROM orders AS o
										INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID
										INNER JOIN product AS p ON p.Product_ID=ol.Product_ID
										%s AND Product_Band_ID>0", $sqlPrefix));
	$data->Disconnect();

	$data = new DataQuery(sprintf("CREATE INDEX SubTotal ON temp_orders (SubTotal)"));
	$data->Disconnect();

	$bands = array();

	foreach($ranges as $key => $range) {
		$bands[$key]['Lines'] = 0;

		$data = new DataQuery(sprintf("SELECT * FROM temp_orders WHERE SubTotal>%d AND SubTotal<=%d", mysql_real_escape_string($lastRange), mysql_real_escape_string($key)));
		while($data->Row) {

			if(!isset($bands[$key]['Bands'][$data->Row['Product_Band_ID']])) {
				$bands[$key]['Bands'][$data->Row['Product_Band_ID']] = 0;
			}

			$bands[$key]['Bands'][$data->Row['Product_Band_ID']]++;
			$bands[$key]['Lines']++;

			$data->Next();
		}
		$data->Disconnect();

		$lastRange = $key;
	}

	$bandLegend = array();
	$lastRange = 0;
	?>

	<table width="100%" border="0" >
		<tr>
			<td align="left" style="border-bottom:1px solid #aaaaaa;"><strong>Start Sale Value Range</strong></td>
			<td align="left" style="border-bottom:1px solid #aaaaaa;"><strong>End Sale Value Range</strong></td>

			<?php
			$bandAssoc = array();

			$data = new DataQuery(sprintf("SELECT Product_Band_ID, Band_Ref, Band_Title FROM product_band ORDER BY Band_Ref ASC"));
			while($data->Row) {
				$bandAssoc[ucfirst($data->Row['Band_Ref'])] = $data->Row['Product_Band_ID'];
					?>

					<td align="left" style="border-bottom:1px solid #aaaaaa;"><strong><?php print ucfirst($data->Row['Band_Ref']); ?></strong></td>

					<?php
					$legend[] = ucfirst($data->Row['Band_Ref']);
					$bandLegend[ucfirst($data->Row['Band_Ref'])] = $data->Row['Band_Title'];

					$data->Next();
			}
			$data->Disconnect();

			ksort($bandAssoc);
			?>
		</tr>

		<?php
		foreach($ranges as $key => $range) {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td align="left">&pound;<?php print number_format($lastRange + 0.01, 2, '.', ','); ?></td>
				<td align="left">&pound;<?php print number_format($key, 2, '.', ','); ?></td>

				<?php
				foreach($bandAssoc as $bandRef => $id) {
					$p = ($bands[$key]['Lines'] > 0) ? (($bands[$key]['Bands'][$id] / $bands[$key]['Lines']) * 100) : 0;

					/*$r = 255 - (($p / 100) * 255);
					$r = (($r / 255) * 128) + $r;
					$g = ($p / 100) * 255;
					$g = (($g / 255) * 128) + $g;
					$b = 0;
					$c = rgb2html($r, $g, $b);*/

					/*$r = 255 - (($p / 100) * 255);
					$w = $r + 255;
					$r = (($r / 255) * 128) + $r;
					$w = $w / 2;
					$c = rgb2html($r, $w, $w);*/

					$o = 255;
					$s = 100 / 3;
					$a = $p;
					$p = ($p > $s) ? $s : $p;
					$d = ($p <= $s) ? $o * 4 : $o;
					$r = $d - (($p / 100) * $d);
					$r = (($r / $d) * ($d / 2)) + $r;
					$g = ($p / 100) * $d;
					$g = (($g / $d) * ($d / 2)) + $g;
					$b = 0;
					$r = ($p == 0) ? $o : $r;
					$g = ($p == 0) ? $o : $g;
					$b = ($p == 0) ? $o : $b;
					$c = rgb2html($r, $g, $b);
					?>

					<td align="left" style="background-color: #<?php print $c; ?>;"><?php print number_format($a, 2, '.', ','); ?>%</td>

					<?php
				}
				?>

			</tr>

			<?php
			$lastRange = $key;
		}
		?>

	</table><br />

	<table border="0">
		<tr>
			<td align="left" style="border-bottom:1px solid #aaaaaa;"><strong>Band Reference</strong></td>
			<td align="left" style="border-bottom:1px solid #aaaaaa;"><strong>Band Title</strong></td>
		</tr>

		<?php
		foreach($bandLegend as $ref => $title) {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td align="left"><?php print $ref; ?></td>
				<td align="left"><?php print $title; ?></td>
			</tr>

			<?php
		}
		?>

	</table>

	<?php
	/*$lastRange = 0;

	$chartFileName = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
	$chartWidth = 900;
	$chartHeight = 600;
	$chartTitle = 'Band Distribution Frequency';
	$chartReference = sprintf('temp/charts/chart_%s.png', $chartFileName);

	$chart = new LineChart(900,600,$legend);

	$interval = 5;

	for($i = 0; $i<(100/$interval); $i++) {
		$points = array();
		$min = $i*$interval;
		$max = ($i*$interval)+$interval;
		$bandPoints = array();

		foreach($ranges as $key => $range) {
			foreach($bandAssoc as $bandRef => $id) {
				$p = ($bands[$key]['Lines'] > 0) ? (($bands[$key]['Bands'][$id] / $bands[$key]['Lines']) * 100) : 0;
				$p = round($p / $interval) * $interval;

				if((($min == 0) && ($p >= $min) && ($p <= $max)) || (($min > 0) && ($p > $min) && ($p <= $max))) {
					$bandPoints[$bandRef]++;
				}
			}
		}

		ksort($bandPoints);

		foreach($bandPoints as $ref => $count) {
			$points[] = $count;
		}

		$chart->addPoint(new Point(sprintf("%s%% - %s%%", $min, $max), $points));
	}

	$chart->SetTitle($chartTitle);
	$chart->SetLabelY('Band Percentage Occurrence');
	$chart->render($chartReference);
	?>

	<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
		<img src="<?php echo $chartReference; ?>" width="<?php print $chartWidth; ?>" height="<?php print $chartHeight; ?>" alt="<?php print $chartTitle; ?>" />
	</div>

	<script language="javascript">
	window.onload = function() {
		var httpRequest = new HttpRequest();
		httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $chartReference; ?>');
	}
	</script>

	<?php
	*/
	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
?>