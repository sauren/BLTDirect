<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Region.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');

$page = new Page('Schedules Report', '');
$page->Display('header');

$data = new DataQuery(sprintf("SELECT MIN(Completed_On) AS Start_Date, MAX(Completed_On) AS End_Date FROM contact_schedule WHERE Is_Complete='Y' AND Completed_On<>'0000-00-00 00:00:00'"));
$startDate = date('Y-m-01 00:00:00', strtotime($data->Row['Start_Date']));
$endDate = date('Y-m-01 00:00:00', strtotime($data->Row['End_Date']));
$data->Disconnect();

$accountManagers = array();

$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Account_Manager FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN contact AS c ON c.Account_Manager_ID=u.User_ID"));
while($data->Row) {
	$accountManagers[$data->Row['User_ID']] = $data->Row['Account_Manager'];

	$data->Next();
}
$data->Disconnect();

ksort($accountManagers);

$legend = array();

foreach($accountManagers as $manager) {
	$legend[] = $manager;
}

$chart1FileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
$chart1Width = 900;
$chart1Height = 600;
$chart1Title = 'Average Message Length';
$chart1Reference = sprintf('temp/charts/chart_%s.png', $chart1FileName);

$chart2FileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
$chart2Width = 900;
$chart2Height = 600;
$chart2Title = 'Outstanding Schedules';
$chart2Reference = sprintf('temp/charts/chart_%s.png', $chart2FileName);

$chart1 = new VerticalChart($chart1Width, $chart1Height, $legend);
$chart2 = new VerticalChart($chart2Width, $chart2Height, $legend);

$graphData = array();
$index = 0;
$minIndex = 0;

while(true) {
	$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date('m') + $index, 0, date('Y')));
	$end = date('Y-m-01 00:00:00', mktime(0, 0, 0, date('m') + $index + 1, 0, date('Y')));

	if($start < $startDate) {
		break;
	}

	$graphData[$index] = array();
	$graphData[$index]['Start'] = $start;
	$graphData[$index]['End'] = $end;
	$graphData[$index]['Length'] = array();

	$data = new DataQuery(sprintf("SELECT cs.Owned_By, AVG(CHAR_LENGTH(cs.Message)) AS Average_Length FROM contact_schedule AS cs WHERE cs.Is_Complete='Y' AND cs.Completed_On>='%s' AND cs.Completed_On<'%s' GROUP BY cs.Owned_By", mysql_real_escape_string($start), mysql_real_escape_string($end)));
	while($data->Row) {
		$graphData[$index]['Length'][$data->Row['Owned_By']] = number_format($data->Row['Average_Length'], 0, '.', ',');

		$data->Next();
	}
	$data->Disconnect();

	$temp = array();

	foreach($accountManagers as $userId=>$manager) {
		$temp[] = isset($graphData[$index]['Length'][$userId]) ? $graphData[$index]['Length'][$userId] : 0;
	}

	$chart1->addPoint(new Point(date('M Y', strtotime($graphData[$index]['Start'])), $temp));

	$minIndex = $index;
	$index--;
}

$graphData = array();
$redDate = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d') - 5, date('Y')));
$yellowDate = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d') - 4, date('Y')));

foreach($accountManagers as $userId=>$manager) {
	$graphData[$userId] = array();
	$graphData[$userId]['Red'] = 0;
	$graphData[$userId]['Yellow'] = 0;
	$graphData[$userId]['Green'] = 0;

	$data = new DataQuery(sprintf("SELECT cs.Scheduled_On FROM contact_schedule AS cs WHERE cs.Owned_By=%d AND cs.Is_Complete='N' AND cs.Scheduled_On<ADDDATE(NOW(), INTERVAL 1 DAY)", mysql_real_escape_string($userId)));
	while($data->Row) {
		if($data->Row['Scheduled_On'] < $redDate) {
			$graphData[$userId]['Red']++;
		} elseif($data->Row['Scheduled_On'] < $yellowDate) {
			$graphData[$userId]['Yellow']++;
		} else {
			$graphData[$userId]['Green']++;
		}

		$data->Next();
	}
	$data->Disconnect();
}

$tempRed = array();
$tempYellow = array();
$tempGreen = array();

foreach($accountManagers as $userId=>$manager) {
	$tempRed[] = $graphData[$userId]['Red'];
	$tempYellow[] = $graphData[$userId]['Yellow'];
	$tempGreen[] = $graphData[$userId]['Green'];
}

$chart2->addPoint(new Point('Red', $tempRed));
$chart2->addPoint(new Point('Yellow', $tempYellow));
$chart2->addPoint(new Point('Green', $tempGreen));

$chart1->SetTitle($chart1Title);
$chart1->SetLabelY('Characters');
$chart1->ShowText = false;
$chart1->render($chart1Reference);

$chart2->SetTitle($chart2Title);
$chart2->SetLabelY('#');
$chart2->ShowText = false;
$chart2->render($chart2Reference);

$scheduleType = array();
$scheduleType[0] = 'Unassigned';

$scheduleData = array();
$scheduleTotal = array();

$data = new DataQuery(sprintf("SELECT * FROM contact_schedule_type ORDER BY Name ASC"));
while($data->Row) {
	$scheduleType[$data->Row['Contact_Schedule_Type_ID']] = $data->Row['Name'];

	$data->Next();
}
$data->Disconnect();

asort($scheduleType);

$data = new DataQuery(sprintf("SELECT u.User_ID, cs.Contact_Schedule_Type_ID, COUNT(cs.Contact_Schedule_Type_ID) AS Schedules FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN contact_schedule AS cs ON cs.Owned_By=u.User_ID AND cs.Is_Complete='Y' AND cs.Completed_On>'%s' GROUP BY u.User_ID, cs.Contact_Schedule_Type_ID", date('Y-m-d 00:00:00')));
while($data->Row) {
	if(!isset($scheduleData[$data->Row['User_ID']])) {
		$scheduleData[$data->Row['User_ID']] = array();
	}

	$scheduleData[$data->Row['User_ID']][$data->Row['Contact_Schedule_Type_ID']] = $data->Row['Schedules'];

	$data->Next();
}
$data->Disconnect();
?>

<div style="background-color: #f6f6f6; padding: 10px;">
	<p><span class="pageSubTitle">Schedule Message Length</span><br /><span class="pageDescription">Monthly average length of schedule messages for each account manager.</span></p>

	<img src="<?php echo $chart1Reference; ?>" width="<?php print $chart1Width; ?>" height="<?php print $chart1Height; ?>" alt="<?php print $chart1Title; ?>" /><br /><br />
</div><br />

<div style="background-color: #f6f6f6; padding: 10px;">
	<p><span class="pageSubTitle">Outstanding Schedules</span><br /><span class="pageDescription">Number of outstanding schedules for each account manager.</span></p>

	<img src="<?php echo $chart2Reference; ?>" width="<?php print $chart2Width; ?>" height="<?php print $chart2Height; ?>" alt="<?php print $chart2Title; ?>" /><br /><br />
</div><br />

<div style="background-color: #f6f6f6; padding: 10px 0 10px 0;">
	<p><span class="pageSubTitle">Schedules Statistics</span><br /><span class="pageDescription">Number of schedules completed per user this day.</span></p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>User</strong></td>

			<?php
			foreach($scheduleType as $typeItem) {
				echo sprintf('<td style="border-bottom: 1px solid #aaaaaa;"><strong>%s</strong></td>', $typeItem);
			}
			?>
		</tr>

		<?php
		if(count($scheduleData) > 0) {
			foreach($scheduleData as $accountManagerId=>$dataItem) {
				?>

				<tr>
					<td><?php echo $accountManagers[$accountManagerId]; ?></td>

					<?php
					foreach($scheduleType as $key=>$typeItem) {
						$frequency = isset($dataItem[$key]) ? $dataItem[$key] : 0;

						echo sprintf('<td>%d</td>', $frequency);

						if(!isset($scheduleTotal[$key])) {
							$scheduleTotal[$key] = 0;
						}

						$scheduleTotal[$key] += $frequency;
					}
					?>
				</tr>

				<?php
			}
			?>

			<tr>
				<td>&nbsp;</td>

				<?php
				foreach($scheduleType as $key=>$typeItem) {
					echo sprintf('<td><strong>%d</strong></td>', isset($scheduleTotal[$key]) ? $scheduleTotal[$key] : 0);
				}
				?>
			</tr>

			<?php
		} else {
			?>

			<tr>
				<td colspan="<?php echo count($scheduleType) + 1; ?>" align="center">There are no statistics available for viewing.</td>
			</tr>

			<?php
		}
		$data->Disconnect();
		?>
	</table>

</div>

<script language="javascript">
	window.onload = function() {
		var httpRequest = new HttpRequest();
		httpRequest.post('lib/util/removeChart.php', 'chart=<?php echo $chart1Reference; ?>');

		var httpRequest = new HttpRequest();
		httpRequest.post('lib/util/removeChart.php', 'chart=<?php echo $chart2Reference; ?>');
	}
</script>

<?php
$page->Display('footer');

require_once('lib/common/app_footer.php');
?>