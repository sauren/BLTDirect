<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TimesheetLog.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TimesheetLogHour.php');

if($action == 'email') {
	$session->Secure(2);
	email();
	exit();
} elseif($action == 'report') {
	$session->Secure(2);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start() {
    $form = new Form($_SERVER['PHP_SELF']);
    $form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
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
    $form->AddField('user', 'User', 'select', '0', 'numeric_unsigned', 1, 11);
    $form->AddOption('user', '0', '-- All --');

    $data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User_Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID WHERE u.Is_Active='Y' AND u.Is_Payroll='Y' ORDER BY User_Name ASC"));
    while($data->Row) {
    	$form->AddOption('user', $data->Row['User_ID'], $data->Row['User_Name']);

    	$data->Next();
    }
    $data->Disconnect();

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
        if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 1)) {
            switch($form->GetValue('range')) {
                case 'all':         $start = date('Y-m-d H:i:s', 0);
                                    $end = date('Y-m-d H:i:s');
                                    break;

                case 'thisminute':	$start = date('Y-m-d H:i:00');
                                    $end = date('Y-m-d H:i:s');
                                    break;
                case 'thishour':     $start = date('Y-m-d H:00:00');
                                    $end = date('Y-m-d H:i:s');
                                    break;
                case 'thisday':     $start = date('Y-m-d 00:00:00');
                                    $end = date('Y-m-d H:i:s');
                                    break;
                case 'thismonth':   $start = date('Y-m-01 00:00:00');
                                    $end = date('Y-m-d H:i:s');
                                    break;
                case 'thisyear':    $start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")));
                                    $end = date('Y-m-d H:i:s');
                                    break;

                case 'lasthour':    $start = date('Y-m-d H:00:00', mktime(date("H")-1, 0, 0, date("m"), date("d"),  date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
                                    break;
                case 'last3hours':  $start = date('Y-m-d H:00:00', mktime(date("H")-3, 0, 0, date("m"), date("d"),  date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
                                    break;
                case 'last6hours':  $start = date('Y-m-d H:00:00', mktime(date("H")-6, 0, 0, date("m"), date("d"),  date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
                                    break;
                case 'last12hours': $start = date('Y-m-d H:00:00', mktime(date("H")-12, 0, 0, date("m"), date("d"),  date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
                                    break;

                case 'lastday':     $start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
                                    break;
                case 'last2days':	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-2, date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
                                    break;
                case 'last3days':   $start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
                                    break;

                case 'lastmonth':   $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-1, 1,  date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
                                    break;
                case 'last3months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-3, 1,  date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
                                    break;
                case 'last6months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-6, 1,  date("Y")));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
                                    break;

                case 'lastyear':    $start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-1));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
                                    break;
                case 'last2years':  $start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-2));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
                                    break;
                case 'last3years':  $start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-3));
                                    $end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
                                    break;
            }

            redirect(sprintf('Location: ?action=report&start=%s&end=%s&user=%d', $start, $end, $form->GetValue('user')));
        } else {
            if($form->Validate()){
                $start = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2));
                $end = (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2))))));

                redirect(sprintf('Location: ?action=report&start=%s&end=%s&user=%d', $start, $end, $form->GetValue('user')));
            }
        }
    }

    $page = new Page('Timesheets Report', 'Please choose a start and end date for your report');
    $page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
    $page->Display('header');

    if(!$form->Valid){
        echo $form->GetError();
        echo '<br />';
    }

    $window = new StandardWindow("Report on Timesheets.");
    $webForm = new StandardForm;

    echo $form->Open();
    echo $form->GetHTML('action');
    echo $form->GetHTML('confirm');

    echo $window->Open();
	echo $window->AddHeader('Select the user for this report.');
    echo $window->OpenContent();
    echo $webForm->Open();
    echo $webForm->AddRow($form->GetLabel('user'), $form->GetHTML('user'));
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

function report() {
	$user = array();

    $form = new Form($_SERVER['PHP_SELF']);
    $form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
    $form->AddField('start', 'Start Date', 'hidden', '0000-00-00 00:00:00', 'anything', 1, 19);
    $form->AddField('end', 'End Date', 'hidden', '0000-00-00 00:00:00', 'anything', 1, 19);
    $form->AddField('user', 'User', 'hidden', '0', 'numeric_unsigned', 1, 11);

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User_Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID WHERE u.Is_Active='Y' AND u.Is_Payroll='Y'%s ORDER BY User_Name ASC", ($form->GetValue('user') > 0) ? sprintf(' AND u.User_ID=%d', mysql_real_escape_string($form->GetValue('user'))) : ''));
	while($data->Row) {
		$user[$data->Row['User_ID']] = array('UserID' => $data->Row['User_ID'], 'Name' => $data->Row['User_Name'], 'Timesheets' => array(), 'Bonus' => array());

		$temp = $form->GetValue('start');
		$tempTime = strtotime($temp);
		$endTime = strtotime($form->GetValue('end'));

		while($tempTime < $endTime) {
			$user[$data->Row['User_ID']]['Timesheets'][$temp] = array();

			$temp = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $tempTime), date('d', $tempTime) + 1, date('Y', $tempTime)));
			$tempTime = strtotime($temp);
		}

		$data2 = new DataQuery(sprintf("SELECT Timesheet_ID, Type, Description, Date, Hours FROM timesheet WHERE User_ID=%d AND Date>='%s' AND Date<'%s' ORDER BY Date ASC", $data->Row['User_ID'], mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
		while($data2->Row) {
			if(!isset($user[$data->Row['User_ID']]['Timesheets'][$data2->Row['Date']])) {
            	$user[$data->Row['User_ID']]['Timesheets'][$data2->Row['Date']] = array();
			}
			
			$user[$data->Row['User_ID']]['Timesheets'][$data2->Row['Date']][] = array('Type' => $data2->Row['Type'], 'Description' => $data2->Row['Description'], 'Hours' => $data2->Row['Hours'], 'TimesheetID' => $data2->Row['Timesheet_ID']);

            $form->AddField(sprintf('hours_%d', $data2->Row['Timesheet_ID']), sprintf('Hours for \'%s\'', substr($data2->Row['Date'], 0, 10)), 'text', $data2->Row['Hours'], 'float', 1, 11, true, 'size="5"');

			$data2->Next();
		}
		$data2->Disconnect();

		$data2 = new DataQuery(sprintf('SELECT StartOn, EndOn, BonusAmount FROM bonus WHERE UserID=%1$d AND ((StartOn<\'%2$s\' AND EndOn>\'%2$s\' AND EndOn<=\'%3$s\') OR (StartOn>=\'%2$s\' AND StartOn<\'%3$s\' AND EndOn>=\'%3$s\') OR (StartOn<\'%2$s\' AND EndOn>=\'%3$s\') OR (StartOn>=\'%2$s\' AND StartOn<\'%3$s\' AND EndOn>=\'%2$s\' AND EndOn<\'%3$s\')) ORDER BY StartOn ASC', $data->Row['User_ID'], mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
		while($data2->Row) {
			$user[$data->Row['User_ID']]['Bonus'][] = $data2->Row;

			$data2->Next();
		}
		$data2->Disconnect();

		$data->Next();
	}
	$data->Disconnect();
	
    if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			foreach($user as $userKey => $userData) {
	        	foreach($userData['Timesheets'] as $date=>$timesheetData) {
					foreach($timesheetData as $dataItem) {
						$hours = $form->GetValue(sprintf('hours_%d', $dataItem['TimesheetID']));

						if($hours == 0) {
							new DataQuery(sprintf("DELETE FROM timesheet WHERE Timesheet_ID=%d", mysql_real_escape_string($dataItem['TimesheetID'])));
						} else {
							new DataQuery(sprintf("UPDATE timesheet SET Hours=%f WHERE Timesheet_ID=%d", mysql_real_escape_string($hours), mysql_real_escape_string($dataItem['TimesheetID'])));
						}
					}
				}
			}

			redirect(sprintf('Location: ?action=report&start=%s&end=%s&user=%d', $form->GetValue('start'), $form->GetValue('end'), $form->GetValue('user')));
		}
	}

	$page = new Page('Timesheets Report: ' . cDatetime($form->GetValue('start'), 'longdatetime') . ' to ' . cDatetime($form->GetValue('end'), 'longdatetime'), '');
    $page->Display('header');

    if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('start');
	echo $form->GetHTML('end');
	echo $form->GetHTML('user');

	foreach($user as $userKey => $userData) {
		?>

		<h3><?php echo $userData['Name']; ?></h3>
		<p>Listing timesheets between the specified dates.</p>

		<table width="100%" border="0">
			<tr>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Date</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Type</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Description</strong></td>
				<td align="right" style="border-bottom:1px solid #aaaaaa;"><strong>Hours</strong></td>
			</tr>

			<?php
			$totalHours = 0;

            foreach($userData['Timesheets'] as $date=>$timesheetData) {
				if(count($timesheetData) > 0) {
					foreach($timesheetData as $dataItem) {
						$weekDay = (date('N', strtotime($date)) >= 6) ? true : false;
						?>

						<tr style="height: 30px;<?php echo ($weekDay) ? 'background-color: #ffb;' : ''; ?>" class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td><?php echo substr($date, 0, 10); ?></td>
							<td><?php echo $dataItem['Type']; ?></td>
							<td><?php echo $dataItem['Description']; ?></td>
							<td align="right"><?php echo $form->GetHTML(sprintf('hours_%d', $dataItem['TimesheetID'])); ?></td>
						</tr>

						<?php
						$totalHours += $dataItem['Hours'];
					}
				} else {
					$weekDay = (date('N', strtotime($date)) >= 6) ? true : false;
					?>

					<tr style="height: 30px;<?php echo ($weekDay) ? 'background-color: #ffb;' : ''; ?>" class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo substr($date, 0, 10); ?></td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td align="right">&nbsp;</td>
					</tr>

					<?php
				}
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td align="right"><strong><?php echo number_format($totalHours, 2, '.', ''); ?></strong></td>
			</tr>
		</table><br />

		<table width="100%" border="0">
			<tr>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Start On</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>End On</strong></td>
				<td align="right" style="border-bottom:1px solid #aaaaaa;"><strong>Bonus</strong></td>
			</tr>

			<?php
			$totalBonus = 0;

			if(count($userData['Bonus']) > 0) {
				foreach($userData['Bonus'] as $bonus) {
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo cDatetime($bonus['StartOn'], 'shortdate'); ?></td>
						<td><?php echo cDatetime($bonus['EndOn'], 'shortdate'); ?></td>
						<td align="right">&pound;<?php echo number_format($bonus['BonusAmount'], 2, '.', ''); ?></td>
					</tr>

					<?php
					$totalBonus += $bonus['BonusAmount'];
				}
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td align="right"><strong>&pound;<?php echo number_format($totalBonus, 2, '.', ''); ?></strong></td>
				</tr>

				<?php
			} else {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td colspan="3" style="text-align: center;">No items available for viewing.</td>
				</tr>

				<?php
			}
			?>

		</table>
		<br />

		<?php
	}
	?>

	<table width="100%">
		<tr>
			<td align="left">
				<input type="submit" name="update" value="update" class="btn" />
			</td>
			<td align="right">
				<input type="button" class="btn" name="email" value="email" onclick="window.self.location.href = '?action=email&start=<?php echo $form->GetValue('start'); ?>&end=<?php echo $form->GetValue('end'); ?>&user=<?php echo $form->GetValue('user'); ?>';" />
			</td>
		</tr>
	</table>

	<?php
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function email() {
	$form = new Form($_SERVER['PHP_SELF']);
    $form->AddField('action', 'Action', 'hidden', 'email', 'alpha', 5, 5);
    $form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
    $form->AddField('start', 'Start Date', 'hidden', '0000-00-00 00:00:00', 'anything', 19, 19);
    $form->AddField('end', 'End Date', 'hidden', '0000-00-00 00:00:00', 'anything', 19, 19);
    $form->AddField('user', 'User ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
    $form->AddField('email', 'Email Address', 'text', '', 'email', 1, 255);

	if(($form->GetValue('start') == '0000-00-00 00:00:00') || ($form->GetValue('end') == '0000-00-00 00:00:00')) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$user = array();

			$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User_Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID WHERE u.Is_Active='Y' AND u.Is_Payroll='Y'%s ORDER BY User_Name ASC", ($form->GetValue('user') > 0) ? sprintf(' AND u.User_ID=%d', mysql_real_escape_string($form->GetValue('user'))) : ''));
			while($data->Row) {
				$user[$data->Row['User_ID']] = array('UserID' => $data->Row['User_ID'], 'Name' => $data->Row['User_Name'], 'Timesheets' => array(), 'Bonus' => array(), 'Hours' => array(), 'TotalHours' => 0, 'TotalBonus' => 0);

				$temp = $form->GetValue('start');
				$tempTime = strtotime($temp);
				$endTime = strtotime($form->GetValue('end'));

				while($tempTime < $endTime) {
					$user[$data->Row['User_ID']]['Timesheets'][$temp] = array();

					$temp = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $tempTime), date('d', $tempTime) + 1, date('Y', $tempTime)));
					$tempTime = strtotime($temp);
				}

				$data2 = new DataQuery(sprintf("SELECT Type, Date, Hours FROM timesheet WHERE User_ID=%d AND Date>='%s' AND Date<'%s' ORDER BY Date ASC", $data->Row['User_ID'], mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
				while($data2->Row) {
					if(!isset($user[$data->Row['User_ID']]['Timesheets'][$data2->Row['Date']][$data2->Row['Type']])) {
						$user[$data->Row['User_ID']]['Timesheets'][$data2->Row['Date']][$data2->Row['Type']] = 0;
					}

					if(!isset($user[$data->Row['User_ID']]['Hours'][$data2->Row['Type']])) {
						$user[$data->Row['User_ID']]['Hours'][$data2->Row['Type']] = 0;
					}

					$user[$data->Row['User_ID']]['Timesheets'][$data2->Row['Date']][$data2->Row['Type']] += $data2->Row['Hours'];
					$user[$data->Row['User_ID']]['TotalHours'] += $data2->Row['Hours'];

					$user[$data->Row['User_ID']]['Hours'][$data2->Row['Type']] += $data2->Row['Hours'];

					$data2->Next();
				}
				$data2->Disconnect();

				$data2 = new DataQuery(sprintf('SELECT StartOn, EndOn, BonusAmount FROM bonus WHERE UserID=%1$d AND ((StartOn<\'%2$s\' AND EndOn>\'%2$s\' AND EndOn<=\'%3$s\') OR (StartOn>=\'%2$s\' AND StartOn<\'%3$s\' AND EndOn>=\'%3$s\') OR (StartOn<\'%2$s\' AND EndOn>=\'%3$s\') OR (StartOn>=\'%2$s\' AND StartOn<\'%3$s\' AND EndOn>=\'%2$s\' AND EndOn<\'%3$s\')) ORDER BY StartOn ASC', $data->Row['User_ID'], mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
				while($data2->Row) {
					$user[$data->Row['User_ID']]['Bonus'][] = $data2->Row;
					$user[$data->Row['User_ID']]['TotalBonus'] += $data2->Row['BonusAmount'];

					$data2->Next();
				}
				$data2->Disconnect();

				$data->Next();
			}
			$data->Disconnect();

			$breakdown = '';

			foreach($user as $userKey => $userData) {
				if(($userData['TotalHours'] > 0) || ($userData['TotalBonus'] > 0)) {
					$breakdown .= sprintf('<p><strong>%s</strong>', $userData['Name']);

					if($userData['TotalHours'] > 0) {
						foreach($userData['Hours'] as $type=>$hours) {
							$breakdown .= sprintf('<br />%s: %s hours.', $type, number_format(round($hours, 1), 1, '.', ''));
						}
					}

					if($userData['TotalBonus'] > 0) {
						$breakdown .= sprintf('<br />&pound;%s bonus.', number_format(round($userData['TotalBonus'], 1), 1, '.', ''));
					}

					$breakdown .= sprintf('</p>');
					
					$log = new TimesheetLog();
					$log->user->ID = $userKey;
					$log->periodStartOn = $form->GetValue('start');
					$log->periodEndOn = $form->GetValue('end');
					$log->bonus = $userData['TotalBonus'];
					$log->add();
					
					foreach($userData['Hours'] as $type=>$hours) {
						$logHour = new TimesheetLogHour();
						$logHour->timesheetLogId = $log->id;
						$logHour->type = $type;
						$logHour->hours = $hours;
						$logHour->add();
					}
				}
			}

			$findReplace = new FindReplace();
			$findReplace->Add('/\[PERIOD_START\]/', date('d/m/Y', strtotime($form->GetValue('start'))));
			$findReplace->Add('/\[PERIOD_END\]/', date('d/m/Y', strtotime($form->GetValue('end')) - 86400));
			$findReplace->Add('/\[BREAKDOWN\]/', $breakdown);

			$emailTemplate = file('lib/templates/email/timesheet_accountant.tpl');
			$html = '';

			for($i=0; $i < count($emailTemplate); $i++){
				$html .= $findReplace->Execute($emailTemplate[$i]);
			}

			$queue = new EmailQueue();

			$data = new DataQuery(sprintf("SELECT Email_Queue_Module_ID FROM email_queue_module WHERE Reference LIKE 'timesheets' LIMIT 0, 1"));
			$queue->ModuleID = ($data->TotalRows > 0) ? $data->Row['Email_Queue_Module_ID'] : 0;
			$data->Disconnect();

			$queue->Subject = sprintf('%s Timesheets [%s - %s]', $GLOBALS['COMPANY'], date('d/m/Y', strtotime($form->GetValue('start'))), date('d/m/Y', strtotime($form->GetValue('end')) - 86400));
			$queue->Body = $html;
			$queue->ToAddress = $form->GetValue('email');
			$queue->FromAddress = $GLOBALS['EMAIL_FROM'];
			$queue->Priority = 'L';
			$queue->Type = 'H';
			$queue->Add();

			redirect(sprintf("Location: %s?confirm=true&start=%s&end=%s&user=%d", $_SERVER['PHP_SELF'], sprintf('%s/%s/%s', substr($form->GetValue('start'), 8, 2), substr($form->GetValue('start'), 5, 2), substr($form->GetValue('start'), 0, 4)), sprintf('%s/%s/%s', substr($form->GetValue('end'), 8, 2), substr($form->GetValue('end'), 5, 2), substr($form->GetValue('end'), 0, 4)), $form->GetValue('user')));
        }
    }

    $page = new Page('Timesheets Report', 'Email timesheets to the following address.');
    $page->Display('header');

    if(!$form->Valid){
        echo $form->GetError();
        echo '<br />';
    }

    $window = new StandardWindow('Email Timesheets.');
    $webForm = new StandardForm();

    echo $form->Open();
    echo $form->GetHTML('action');
    echo $form->GetHTML('confirm');
    echo $form->GetHTML('start');
    echo $form->GetHTML('end');
    echo $form->GetHTML('user');

    echo $window->Open();
	echo $window->AddHeader('Insert address to email.');
    echo $window->OpenContent();
    echo $webForm->Open();
    echo $webForm->AddRow($form->GetLabel('email'), $form->GetHTML('email'));
    echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
    echo $webForm->Close();
    echo $window->CloseContent();
    echo $window->Close();
    echo $form->Close();

    $page->Display('footer');

    require_once('lib/common/app_footer.php');
}