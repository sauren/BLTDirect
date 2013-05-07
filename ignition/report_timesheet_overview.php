<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/EmailQueue.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Template.php');

if($action == 'email') {
	$session->Secure(2);
	email();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function email() {
	$data = new DataQuery(sprintf("SELECT u.User_Name, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID WHERE u.Is_Payroll='Y'"));
	while($data->Row) {
		$findReplace = new FindReplace();

		$html = $findReplace->Execute(Template::GetContent('email_timesheet_reminder'));
		
		$findReplace = new FindReplace();
		$findReplace->Add('/\[NAME\]/', $data->Row['Name']);
		$findReplace->Add('/\[BODY\]/', $html);

		$html = $findReplace->Execute(Template::GetContent('email_template_standard'));

		$queue = new EmailQueue();
		$queue->GetModuleID('timesheets');
		$queue->Subject = sprintf('%s Timesheets [%s]', $GLOBALS['COMPANY'], date('d/m/Y'));
		$queue->Body = $html;
		$queue->ToAddress = $data->Row['User_Name'];
		$queue->FromAddress = $GLOBALS['EMAIL_FROM'];
		$queue->Priority = 'L';
		$queue->Type = 'H';
		$queue->Add();
		
		$data->Next();
	}
	$data->Disconnect();

	redirectTo('?action=view');
}

function view() {
	//$connection = new MySQLConnection($GLOBALS['ELLWOOD_DB_HOST'], $GLOBALS['ELLWOOD_DB_NAME'], $GLOBALS['ELLWOOD_DB_USERNAME'], $GLOBALS['ELLWOOD_DB_PASSWORD']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'view', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'End Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

	$customDate = false;
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$customDate = true;		
		}
	}
	
	$objects = array();
	
	if(!$customDate) {
		$y = isset($_REQUEST['year'])? $_REQUEST['year'] : date('Y');
		$m = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : date('m');
		$d = date('d');
		$w = date('w', mktime(0, 0, 0, $m, 1, $y));
		
		$start = date('Y-m-d H:i:s', mktime(0, 0, 0, $m, 1, $y));
		$end = date('Y-m-d H:i:s', mktime(0, 0, 0, $m + 1, 1, $y));
	} else {
		$y = date('Y');
		$m = date('m');
		$d = date('d');
		
		$start = sprintf('%s-%s-%s 00:00:00', substr($_REQUEST['start'], 6, 4), substr($_REQUEST['start'], 3, 2), substr($_REQUEST['start'], 0, 2));
		$end = sprintf('%s-%s-%s 00:00:00', substr($_REQUEST['end'], 6, 4), substr($_REQUEST['end'], 3, 2), substr($_REQUEST['end'], 0, 2));
	}
	
	$data = new DataQuery(sprintf("SELECT t.Timesheet_ID, t.Type, t.Date, t.Hours, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User FROM timesheet AS t INNER JOIN users AS u ON u.User_ID=t.User_ID AND u.Is_Payroll='Y' INNER JOIN person AS p ON p.Person_ID=u.Person_ID WHERE t.Date>='%s' AND t.Date<'%s' ORDER BY t.Date ASC", mysql_real_escape_string($start), mysql_real_escape_string($end)));
	while($data->Row) {
		$objects[] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

	/*$data = new DataQuery(sprintf("SELECT ted.Work_Date AS Date, ted.Num_Hours AS Hours, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User FROM timesheet_entry AS te INNER JOIN timesheet_entry_details AS ted ON ted.TimeSheet_Entry_ID=te.Timesheet_Entry_ID INNER JOIN electrician AS e ON e.Electrician_ID=ted.Electrician_ID INNER JOIN contact AS c ON c.Contact_ID=e.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE te.Electrician_Holiday_ID>0 AND ted.Work_Date>='%s' AND ted.Work_Date<'%s' ORDER BY ted.Work_Date ASC", $start, $end), $connection);
	while($data->Row) {
		$objects[] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();*/

	$data = new DataQuery(sprintf("SELECT * FROM public_holiday"));
	while($data->Row) {
		$holidays[$data->Row['Holiday_Date']] = $data->Row['Title'];

		$data->Next();
	}
	$data->Disconnect();

	if(!$customDate) {
		$dayCount = date('t', mktime(0, 0, 0, $m, 1, $y)) * 2;
		$dayObjects = array();

		for($i=0; $i<$dayCount; $i++){
			$dayObjects[$i] = array();
		}

		foreach($objects as $object) {
			$currentYear = substr($object['Date'], 0, 4);
			$currentMonth = substr($object['Date'], 5, 2);
			$currentDay = substr($object['Date'], 8, 2);

			if(($currentYear == $y) && ($currentMonth == $m)) {
				$dayObjects[$currentDay - 1][] = $object;
			}
		}
	} else {
		$dates = array();
		
		$tempDate = strtotime($start);
		$endDate = strtotime($end);
		
		while($tempDate<=$endDate) {
			$dates[$tempDate] = array();
			
			$tempDate = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		}
		
		print_r($dates);
	}
	
	$script = sprintf('<script language="javascript" type="text/javascript">
		function lastMonth() {
			window.self.location.href = \'?offset=%1$s&year=%2$s\';
		}

		function nextMonth() {
			window.self.location.href = \'?offset=%3$s&year=%4$s\';
		}
		</script>', ($m == 1) ? 12 : $m - 1, ($m == 1) ? $y - 1 : $y, ($m == 12) ? 1 : $m + 1, ($m == 12) ? $y + 1 : $y);

	$page = new Page('Timesheet Overview Report');
	$page->LinkCSS('css/Calendar.css');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow('Select Period');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start') . $form->GetIcon('start'));
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end') . $form->GetIcon('end'));
	echo $webForm->AddRow('', sprintf('<input type="submit" name="submit" value="submit" tabindex="%s" class="btn" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo '<br />';
	
	if(!$customDate) {
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
									echo sprintf('<option value="%d" %s>%s</option>', $i, ($m == $i) ? 'selected="selected"' : '', date('M', mktime(0, 0, 0, $i, date('d'), date('Y'))));
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
						if(dateDiff(substr($object['Date'], 0, 10), $date, 'd') == 0) {
							echo '<tr>';
							echo sprintf('<td valign="top">%s: <span style="color: #999;">%s</span></td>', $object['User'], $object['Type']);
							echo sprintf('<td valign="top" align="right">%s</td>', number_format($object['Hours'], 2, '.', ''));
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
		
		<input type="button" class="btn" name="email" value="email" onclick="window.self.location.href = '?action=email';" />
		
		<?php
	} else {
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
									echo sprintf('<option value="%d" %s>%s</option>', $i, ($m == $i) ? 'selected="selected"' : '', date('M', mktime(0, 0, 0, $i, date('d'), date('Y'))));
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
					$startDay = date('w', strtotime($start));
					$endDay = date('w', strtotime($end));
					
					$isStarted = false;
					
					while(true) {
						echo '<tr>';
						
						for($i=0; $i<7; $i++) {
							if(!$isStarted) {
								if(true) {
									
								} else {
									
								}
							} else {
								echo sprintf('<td class="calendar-day day-standard %s">', $i);
								
								
								echo '</td>';
							}
						}
						
						echo '</tr>';

						break;
					}
					

					//for($i=1; $i<=$dayCount; $i++) {
						/*if($i == 1) {
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
							if(dateDiff(substr($object['Date'], 0, 10), $date, 'd') == 0) {
								echo '<tr>';
								echo sprintf('<td valign="top">%s: <span style="color: #999;">%s</span></td>', $object['User'], $object['Type']);
								echo sprintf('<td valign="top" align="right">%s</td>', number_format($object['Hours'], 2, '.', ''));
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
					}*/

					//echo '</tr>';
					?>

				</table>
			</div>
		</div>
		
		<?php
	}
	
	echo $form->Close();
		
	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}