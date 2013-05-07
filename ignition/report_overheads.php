<?php
require_once('lib/common/app_header.php');

if($action == 'export') {
	$session->Secure(2);
	export();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function export() {
	if(isset($_REQUEST['file'])) {
		redirect(sprintf("Location: %s%s", $GLOBALS['TEMP_REPORT_DIR_WS'], $_REQUEST['file']));
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function breakRow($row) {
	$str = '';

	foreach($row as $cell) {
		$str .= $cell;
	}

	return sprintf("%s\n", substr($str, 0, -1));
}

function start() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
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
	$form->AddOption('range', 'thisfinancialyear', 'This Financial Year');
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
	$form->AddOption('range', 'lastweek', 'Last Week (Last 7 Days)');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastmonth', 'Last Month');
	$form->AddOption('range', 'last3months', 'Last 3 Months');
	$form->AddOption('range', 'last6months', 'Last 6 Months');
	$form->AddOption('range', 'last12months', 'Last 12 Months');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastyear', 'Last Year');
	$form->AddOption('range', 'last2years', 'Last 2 Years');
	$form->AddOption('range', 'last3years', 'Last 3 Years');

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

				case 'lastweek': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
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
				case 'last12months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-12, 1,  date("Y")));
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

			report($start, $end);
			exit;
		} else {

			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))));
				exit;
			}
		}
	}

	$page = new Page('Overheads Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Overheads.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
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

function report($start, $end){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	if($start > date('Y-m-d 00:00:00', strtotime($start))) {
		$start = date('Y-m-d 00:00:00', strtotime($start));
	}

	if($end > date('Y-m-d 00:00:00', strtotime($end))) {
		$end = date('Y-m-d 00:00:00', strtotime(date('Y-m-d 00:00:00')) + 86400);
	}

	$overheads = array();

	$data = new DataQuery(sprintf("SELECT o.Name AS Overhead, o.Value, o.Period, o.Start_Date, o.End_Date, ot.Name FROM overhead AS o INNER JOIN overhead_type AS ot ON o.Overhead_Type_ID=ot.Overhead_Type_ID WHERE ((o.Start_Date BETWEEN '%s' AND '%s') AND (o.End_Date BETWEEN '%s' AND '%s')) OR ('%s' BETWEEN o.Start_Date AND o.End_Date) OR ('%s' BETWEEN o.Start_Date AND o.End_Date)", $start, $end, $start, $end, $start, $end));
	while($data->Row) {
		if(!isset($overheads[$data->Row['Name']])) {
			$overheads[$data->Row['Name']] = array();
		}

		$overheads[$data->Row['Name']]['Items'][] = array('Overhead' => $data->Row['Overhead'], 'Value' => $data->Row['Value'], 'Period' => $data->Row['Period'], 'Start' => $data->Row['Start_Date'], 'End' => $data->Row['End_Date']);

		$data->Next();
	}
	$data->Disconnect();

	foreach($overheads as $name=>$overhead) {
		$overheads[$name]['Total'] = 0;

		foreach($overhead['Items'] as $key=>$item) {
			$overheads[$name]['Items'][$key]['Total'] = 0;

			if($item['Start'] < $start) {
				$overheads[$name]['Items'][$key]['Start'] = $item['Start'] = $start;
			}

			if($item['End'] > $end) {
				$overheads[$name]['Items'][$key]['End'] = $item['End'] = $end;
			}

			$days = 0;
			$startDate = $item['Start'];

			while($startDate < $item['End']) {
				$days++;
				$startDate = date('Y-m-d 00:00:00', mktime(0, 0, 0, date('m', strtotime($startDate)), date('d', strtotime($startDate)) + 1, date('Y', strtotime($startDate))));
			}

			if($item['Period'] == 'D') {
				$overheads[$name]['Items'][$key]['Total'] += $days * $item['Value'];

			} elseif($item['Period'] == 'M') {
				$months = 0;
				$startDate = $item['Start'];

				while($startDate < $item['End']) {
					$months++;
					$startDate = date('Y-m-d 00:00:00', mktime(0, 0, 0, date('m', strtotime($startDate)) + 1, date('d', strtotime($startDate)), date('Y', strtotime($startDate))));
				}

				$startTime = strtotime($item['Start']);
				$endTime = strtotime($item['End']);

				if($months == 1) {
					$overheads[$name]['Items'][$key]['Total'] += $days * ($item['Value'] / date('t', $startTime));
				} elseif($months >= 2) {
					$overheads[$name]['Items'][$key]['Total'] += (date('t', $startTime) - (((int) date('d', $startTime)) - 1)) * ($item['Value'] / date('t', $startTime));
					$overheads[$name]['Items'][$key]['Total'] += date('d', $endTime - 86400) * ($item['Value'] / date('t', $endTime - 86400));
				}

				if($months >= 3) {
					$months -= 2;

					for($i=0; $i<$months; $i++) {
						$overheads[$name]['Items'][$key]['Total'] += $item['Value'];
					}
				}
			} elseif($item['Period'] == 'Y') {
				$years = 0;

				$startDate = $item['Start'];

				while($startDate < $item['End']) {
					$years++;
					$startDate = date('Y-m-d 00:00:00', mktime(0, 0, 0, date('m', strtotime($startDate)), date('d', strtotime($startDate)), date('Y', strtotime($startDate)) + 1));
				}

				$startTime = strtotime($item['Start']);
				$endTime = strtotime($item['End']);

				if($years == 1) {
					$overheads[$name]['Items'][$key]['Total'] += $days * ($item['Value'] / 365);
				} elseif($years >= 2) {
					$overheads[$name]['Items'][$key]['Total'] += (365 - (((int) date('d', $startTime)) - 1)) * ($item['Value'] / 365);
					$overheads[$name]['Items'][$key]['Total'] += (date('z', $endTime - 86400) + 1) * ($item['Value'] / 365);
				}

				if($years >= 3) {
					$years -= 2;

					for($i=0; $i<$years; $i++) {
						$overheads[$name]['Items'][$key]['Total'] += $item['Value'];
					}
				}
			}

			$overheads[$name]['Total'] += $overheads[$name]['Items'][$key]['Total'];
		}
	}

	$contents = '';

	$line = array();
	$line[] = sprintf('"Overhead Type",');
	$line[] = sprintf('"Overheads",');
	$line[] = sprintf('"Total",');

	$contents .= breakRow($line);

	foreach($overheads as $name=>$overhead) {
		foreach($overhead['Items'] as $item) {
			$line = array();
			$line[] = sprintf('"%s",', $name);
			$line[] = sprintf('"%s",', $item['Overhead']);
			$line[] = sprintf('"%s",', number_format($item['Total'], 2, '.', ','));

			$contents .= breakRow($line);
		}
	}

	$fileName = sprintf('overheads_%s.csv', date('ymdHis'));

	$fh = fopen($GLOBALS['TEMP_REPORT_DIR_FS'].$fileName, 'w') or die("Can't open file");
	fwrite($fh, $contents);
	fclose($fh);

	$page = new Page('Overheads Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');
	?>

	<br />
	<h3>Overheads</h3>
	<p>Overheads statistics for the given period.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Overhead Type</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Overheads</strong></td>
			<td align="right" style="border-bottom:1px solid #aaaaaa"><strong>Total</strong></td>
		</tr>

		<?php
		$total = 0;

		foreach($overheads as $name=>$overhead) {
			$total += $overhead['Total'];
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><strong><?php echo $name; ?></strong></td>
				<td>&nbsp;</td>
				<td align="right"><strong>&pound;<?php echo number_format($overhead['Total'], 2, '.', ','); ?></strong></td>
			</tr>

			<?php
			foreach($overhead['Items'] as $item) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td>&nbsp;</td>
					<td><?php echo $item['Overhead']; ?></td>
					<td align="right">&pound;<?php echo number_format($item['Total'], 2, '.', ','); ?></td>
				</tr>

				<?php
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>

			<?php
		}
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td align="right"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
		</tr>
	</table><br />

	<input type="button" class="btn" value="export to csv" onclick="window.self.location.href='<?php print $_SERVER['PHP_SELF']; ?>?action=export&file=<?php echo $fileName; ?>'" />

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>