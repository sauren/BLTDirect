<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure(3);

$y = isset($_REQUEST['year'])? $_REQUEST['year'] : date('Y');
$m = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : date('m');
$d = date('d');
$w = date('w', mktime(0, 0, 0, $m, 1, $y));
	
$objects = array();
	
$start = date('Y-m-d H:i:s', mktime(0, 0, 0, $m, 1, $y));
$end = date('Y-m-d H:i:s', mktime(0, 0, 0, $m + 1, 1, $y));

$editThreshold = strtotime(Setting::GetValue('timesheet_edit_threshold'));

$temp = $start;
$tempTime = strtotime($temp);
$endTime = strtotime($end);

$user = new User($GLOBALS['SESSION_USER_ID']);


$accessRole = $user->GetAccess($user->ID);
$adminAccess = false;  

foreach ($accessRole as $access){

	if($access['AccessId'] == 1)
	{
		$adminAccess = true;
	}
}

$userNames = $user->GetAllUsers();

$form = new Form($_SERVER['PHP_SELF'],'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);


if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {		
        foreach($objects as $objectItem) {
			if($editThreshold <= strtotime($objectItem->Date)) {
                $hours = $form->GetValue(sprintf('hours_%d', $objectItem->Data['Timesheet_ID']));
				if($hours == 0) {
					new DataQuery(sprintf("DELETE FROM timesheet WHERE Timesheet_ID=%d", mysql_real_escape_string($objectItem->Data['Timesheet_ID'])));
				} else {
					new DataQuery(sprintf("UPDATE timesheet SET Hours=%f WHERE Timesheet_ID=%d", $hours, mysql_real_escape_string($objectItem->Data['Timesheet_ID'])));
				}
			}
		}

		redirectTo(sprintf('?offset=%d&year=%d&userID=%d', $m, $y,$_REQUEST['person']));
	}
}

if(!param('userID') && $adminAccess == false){
	$personID = $GLOBALS['SESSION_USER_ID'];
}
else if (!param('userID') && $adminAccess == true){
	$personID = $GLOBALS['SESSION_USER_ID'];
}
else{
 	$personID = param('userID');
}

$data = new DataQuery(sprintf("SELECT Timesheet_ID, Type, Date, Hours FROM timesheet WHERE User_ID=%d AND Date>='%s' AND Date<'%s' ORDER BY Date ASC", mysql_real_escape_string($personID), $start, $end));
while($data->Row) {
	$object = new stdClass();
	$object->Data = $data->Row;
	$object->Description = $data->Row['Type'];
	$object->Date = substr($data->Row['Date'], 0, 10);

	$objects[] = $object;

	$form->AddField(sprintf('hours_%d', $data->Row['Timesheet_ID']), sprintf('Hours for \'%s\'', substr($data->Row['Date'], 0, 10)), 'text', $data->Row['Hours'], 'float', 1, 11, true, 'size="5"');

	$data->Next();
}
$data->Disconnect();

$holidays = array();

$data = new DataQuery(sprintf("SELECT * FROM public_holiday"));
while($data->Row) {
	$holidays[$data->Row['Holiday_Date']] = $data->Row['Title'];

	$data->Next();
}
$data->Disconnect();

$script = sprintf('<script language="javascript" type="text/javascript">
	function lastMonth() {
		window.location = \'?offset=%1$d&year=%2$d\';
	}

	function nextMonth() {
		window.location = \'?offset=%3$d&year=%4$d\';
	}
	</script>', ($m == 1) ? 12 : $m - 1, ($m == 1) ? $y - 1 : $y, ($m == 12) ? 1 : $m+1, ($m == 12) ? $y + 1 : $y);

$page = new Page('Timesheets', 'This area allows you to view timesheets.');
$page->LinkCSS('css/Calendar.css');
$page->AddToHead($script);
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');

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

<div id="calendar">
	<div id="calendar-top">
		<table class="calendar" width="100%">
			<tr>
				<td align="left"><a href="javascript:lastMonth()"><img src="images/calendar-last.gif" alt="Last month" border="0"/></a></td>
				<td>&nbsp;</td>
				<td align="center">
					<?php if($adminAccess == true){?>
						<select id="person" tabindex="3" name="person" onchange="form1.submit();">
							<?php foreach ($userNames as $userName) {
								$uName = $userName["First_Name"]." ".$userName["Last_Name"];
								$uNum = $userName["User_ID"];
									if(isset($userName["First_Name"]) || ($userName["First_Name"])){ ?>
										<? 

											if(!param("offset") && !param("year") && !param("userID")){
												$selected = ($GLOBALS['SESSION_USER_ID'] == $uNum )? 'selected="selected"':'';
											}else{
											$selected = (param("userID") == $uNum )? 'selected="selected"':'';
											}
										?>
										<option <?php echo $selected; ?>value="<?php echo $uNum; ?>"><?php echo $uName; ?></option>
								<?php } 
							}?>
						</select>
					<?php } ?>
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
					echo sprintf('<td align="right">%s</td>', ($editThreshold > strtotime($object->Date)) ? number_format($object->Data['Hours'], 2, '.', '') : $form->GetHTML(sprintf('hours_%d', $object->Data['Timesheet_ID'])));
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
	
<input type="submit" name="update" value="update" class="btn" />

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');