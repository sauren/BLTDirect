<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Form.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$connection = new MySQLConnection($GLOBALS['ELLWOOD_DB_HOST'], $GLOBALS['ELLWOOD_DB_NAME'], $GLOBALS['ELLWOOD_DB_USERNAME'], $GLOBALS['ELLWOOD_DB_PASSWORD']);
	
	$y = isset($_REQUEST['year'])? $_REQUEST['year'] : date('Y');
	$m = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : date('m');
	$d = date('d');
	$w = date('w', mktime(0, 0, 0, $m, 1, $y));
		
	$objects = array();
		
	$start = date('Y-m-d H:i:s', mktime(0, 0, 0, $m, 1, $y));
	$end = date('Y-m-d H:i:s', mktime(0, 0, 0, $m + 1, 1, $y));
	
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('p', 'Password', 'hidden', '', 'anything');

	$data = new DataQuery(sprintf("SELECT t.Timesheet_ID, t.Type, t.Date, t.Hours, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User FROM timesheet AS t INNER JOIN users AS u ON u.User_ID=t.User_ID INNER JOIN person AS p ON p.Person_ID=u.Person_ID WHERE t.Date>='%s' AND t.Date<'%s' AND t.User_Holiday_ID>0 ORDER BY t.Date ASC", $start, $end));
	while($data->Row) {
		$object = new stdClass();
		$object->Data = $data->Row;
		$object->Description = $data->Row['User'];
		$object->Date = substr($data->Row['Date'], 0, 10);

		$objects[] = $object;

		$data->Next();
	}
	$data->Disconnect();
	
	$data = new DataQuery(sprintf("SELECT ted.Work_Date AS Date, ted.Num_Hours AS Hours, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User FROM timesheet_entry AS te INNER JOIN timesheet_entry_details AS ted ON ted.TimeSheet_Entry_ID=te.Timesheet_Entry_ID INNER JOIN electrician AS e ON e.Electrician_ID=ted.Electrician_ID AND e.Is_Active='Y' INNER JOIN contact AS c ON c.Contact_ID=e.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE te.Electrician_Holiday_ID>0 AND ted.Work_Date>='%s' AND ted.Work_Date<'%s' ORDER BY ted.Work_Date ASC", $start, $end), $connection);
	while($data->Row) {
		$object = new stdClass();
		$object->Data = $data->Row;
		$object->Description = $data->Row['User'];
		$object->Date = substr($data->Row['Date'], 0, 10);

		$objects[] = $object;

		$data->Next();
	}
	$data->Disconnect();
	
	$data = new DataQuery(sprintf("SELECT * FROM public_holiday"));
	while($data->Row) {
		$holidays[$data->Row['Holiday_Date']] = $data->Row['Title'];

		$data->Next();
	}
	$data->Disconnect();
	
	$dayCount = date('t', mktime(0, 0, 0, $m, 1, $y));
	$dayObjects = array();

	for($i=0; $i<$dayCount; $i++){
		$dayObjects[$i] = array();
	}

	foreach($objects as $object) {
		$currentYear = substr($object->Date, 0, 4);
		$currentMonth = substr($object->Date, 5, 2);
		$currentDay = substr($object->Date, 8, 2);

		if(($currentYear == $y) && ($currentMonth == $m)) {
			$dayObjects[$currentDay - 1][] = $object;
		}
	}
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
		<link rel="stylesheet" type="text/css" href="css/Calendar.css" />
		<script language="javascript" type="text/javascript">
		function lastMonth() {
			window.self.location.href = '?p=<?php echo $_REQUEST['p']; ?>&offset=<?php echo ($m == 1) ? 12 : $m - 1; ?>&year=<?php echo ($m == 1) ? $y - 1 : $y; ?>';
		}

		function nextMonth() {
			window.self.location.href = '?p=<?php echo $_REQUEST['p']; ?>&offset=<?php echo ($m == 12) ? 1 : $m + 1; ?>&year=<?php echo ($m == 12) ? $y + 1 : $y; ?>';
		}
		</script>
	</head>
	<body>
	
		<?php
		if(!$form->Valid) {
			echo $form->GetError();
			echo '<br />';
		}

		echo $form->Open();
		echo $form->GetHTML('p');
		?>
		
		<div id="calendar">
			<div id="calendar-top">
				<table class="calendar" width="100%">
					<tr>
						<td align="left"><a href="javascript:lastMonth();"><img src="images/calendar-last.gif" alt="Last month" border="0"/></a></td>
						<td>&nbsp;</td>
						<td align="center">
							<select id="offset" tabindex="1" name="offset" onchange="form1.submit();">
								<?php
								for($i=1; $i<=12; $i++) {
									echo sprintf('<option value="%d" %s>%s</option>', $i, ($m == $i) ? 'selected="selected"' : '', date('M', mktime(0, 0, 0, $i, 1, date('Y'))));
								}
								?>
							</select>
							<select id="year" tabindex="2" name="year" onchange="form1.submit();">
								<?php
								for($i=date('Y')-1; $i<=date('Y')+2; $i++) {
									echo sprintf('<option value="%d" %s>%d</option>', $i, ($y== $i) ? 'selected=selected' : '', $i);
								}
								?>
							</select>
						</td>
						<td>&nbsp;</td>
						<td align="right"><a href="javascript:nextMonth();"><img src="images/calendar-next.gif" alt="Next month" border="0" /></a></td>
					</tr>
				</table>
			</div>
			<div id="calendar-bottom">
				<table class="calendar" width="100%">
					<tr>
						<th>Sun</th>
						<th>Mon</th>
						<th>Tue</th>
						<th>Wed</th>
						<th>Thu</th>
						<th>Fri</th>
						<th>Sat</th>
					</tr>

				<?php
				echo '<tr>';

				for($i=1; $i<=$dayCount; $i++) {
					if($i == 1) {
						for($j=1; $j<=$w; $j++) {
							echo '<td class="calendar-day day-standard void"></td>';
						}
					}

					$date = date('Y-m-d', mktime(0, 0, 0, $m, $i, $y));

					$isHoliday = false;
					$holidayTitle = '';

					foreach($holidays as $holidayDate => $title) {
						if(dateDiff($holidayDate, $date, 'd') == 0) {
							$isHoliday = true;
							$holidayTitle = $title;

							break;
						}
					}

					echo sprintf('<td class="calendar-day day-standard %s %s">', $i, ($isHoliday) ? 'holiday' : '');
					echo sprintf('<p><span style="color: #f00;">%d</span>%s</p>', $i, ($isHoliday) ? sprintf(': <strong>%s</strong>', $holidayTitle) : '');
					
					echo '<table width="100%">';
					
					foreach($dayObjects[$i - 1] as $object) {
						if(dateDiff($object->Date, $date, 'd') == 0) {
							echo '<tr>';
							echo sprintf('<td>%s</td>', $object->Description);
							echo sprintf('<td align="right">%s</td>', number_format($object->Data['Hours'], 2, '.', ''));
							echo '</tr>';
						}
					}
					
					echo '</table>';
					
					echo '</td>';

					$w++;

					if($w == 7) {
						echo '</tr>';
						$w = 0;;
					}
				}

				if(($w < 7) && ($w > 0)) {
					for($j = $w; $j < 7; $j++) {
						echo '<td class="calendar-day day-standard void"></td>';
					}
				}

				echo '</tr>';
				?>

				</table>
			</div>
		</div>
		
		<?php
		echo $form->Close();
		?>

	</body>
	</html>
	<?php	
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();