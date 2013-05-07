<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF']);
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
	$form->AddField('type', 'Type', 'selectmultiple', '', 'numeric_unsigned', 1, 11, true, 'size="10"');

	$data = new DataQuery(sprintf("SELECT Contact_Schedule_Type_ID, Name FROM contact_schedule_type ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('type', $data->Row['Contact_Schedule_Type_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

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

			report($start, $end);
			exit;

		} else {
			if($form->Validate()) {
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))));
				exit;
			}
		}
	}

	$page = new Page('Schedule Cross Reference Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Schedules.");
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

	echo $window->AddHeader('Select at least one type to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type'));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$accountManagers = array();
	$scheduleTypes = array();

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Account_Manager FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN contact AS c ON c.Account_Manager_ID=u.User_ID ORDER BY Account_Manager ASC"));
	while($data->Row) {
		$accountManagers[$data->Row['User_ID']] = $data->Row['Account_Manager'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT cst.Contact_Schedule_Type_ID, cst.Name FROM contact_schedule_type AS cst ORDER BY Name ASC"));
	while($data->Row) {
		$scheduleTypes[$data->Row['Contact_Schedule_Type_ID']] = $data->Row['Name'];

		$data->Next();
	}
	$data->Disconnect();

	$schedules = array();

	foreach($accountManagers as $accountManagerId=>$accountManagerName) {
		$schedules[$accountManagerId] = array();

		$data = new DataQuery(sprintf("SELECT cs.*, DATE(cs.Completed_On) AS Completed_On_Date, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Contact_Name, c.Parent_Contact_ID, REPLACE(o.Phone_1, ' ', '') AS Organisation_Phone_Number, REPLACE(p.Phone_1, ' ', '') AS Person_Phone_Number FROM contact_schedule AS cs INNER JOIN contact_schedule_type AS cst ON cs.Contact_Schedule_Type_ID=cst.Contact_Schedule_Type_ID INNER JOIN contact AS c ON c.Contact_ID=cs.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE cs.Owned_By=%d AND cs.Is_Complete='Y' AND cs.Completed_On BETWEEN '%s' AND '%s' ORDER BY cs.Completed_On ASC", mysql_real_escape_string($accountManagerId), mysql_real_escape_string($start), mysql_real_escape_string($end)));
		while($data->Row) {
			if(!isset($schedules[$accountManagerId][$data->Row['Completed_On_Date']])) {
				$schedules[$accountManagerId][$data->Row['Completed_On_Date']] = array();
			}

			if(!isset($schedules[$accountManagerId][$data->Row['Completed_On_Date']][$data->Row['Contact_Schedule_Type_ID']])) {
				$schedules[$accountManagerId][$data->Row['Completed_On_Date']][$data->Row['Contact_Schedule_Type_ID']] = array();
			}

			$schedules[$accountManagerId][$data->Row['Completed_On_Date']][$data->Row['Contact_Schedule_Type_ID']][] = $data->Row;

			$data->Next();
		}
		$data->Disconnect();

		if(count($schedules[$accountManagerId]) > 0) {
			foreach($schedules[$accountManagerId] as $completedDate=>$scheduleItem) {
				foreach($scheduleTypes as $scheduleTypeId=>$scheduleType) {
					if(isset($scheduleItem[$scheduleTypeId])) {
						foreach($scheduleItem[$scheduleTypeId] as $contactIndex=>$scheduleData) {
							$phoneNumbers = array();

							if($scheduleData['Parent_Contact_ID'] > 0) {
								if(!empty($scheduleData['Organisation_Phone_Number'])) {
									$phoneNumbers[$scheduleData['Organisation_Phone_Number']] = sprintf('\'%s\'', $scheduleData['Organisation_Phone_Number']);
								}

								$data2 = new DataQuery(sprintf("SELECT REPLACE(p.Phone_1, ' ', '') AS Phone_Number FROM contact AS c INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE c.Parent_Contact_ID=%d AND p.Phone_1<>''", mysql_real_escape_string($scheduleData['Parent_Contact_ID'])));
								while($data2->Row) {
									$phoneNumbers[$data2->Row['Phone_Number']] = sprintf('\'%s\'', $data2->Row['Phone_Number']);

									$data2->Next();
								}
								$data2->Disconnect();
							} else{
								if(!empty($scheduleData['Person_Phone_Number'])) {
									$phoneNumbers[$scheduleData['Person_Phone_Number']] = sprintf('\'%s\'', $scheduleData['Person_Phone_Number']);
								}
							}

							$schedules[$accountManagerId][$completedDate][$scheduleTypeId][$contactIndex]['Phone_Calls'] = array();

							if(count($phoneNumbers) > 0) {
								$data2 = new DataQuery(sprintf("SELECT c.Duration, c.Called_On, ct.Phone_Number FROM `call` AS c INNER JOIN call_to AS ct ON ct.Call_To_ID=c.Call_To_ID WHERE c.Called_On>='%s 00:00:00' AND c.Called_On<'%s' AND (ct.Phone_Number LIKE %s)", mysql_real_escape_string($scheduleData['Completed_On_Date']), mysql_real_escape_string($scheduleData['Completed_On']), implode(' OR ct.Phone_Number LIKE ',$phoneNumbers)));
								while($data2->Row) {
									$schedules[$accountManagerId][$completedDate][$scheduleTypeId][$contactIndex]['Phone_Calls'][] = $data2->Row;

									$data2->Next();
								}
								$data2->Disconnect();
							}
						}
					}
				}
			}
		}
	}

	$page = new Page('Schedule Cross Reference Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');
	?>

	<br />
	<h3>Schedule Summary</h3>
	<p>Listing summary statistics for affected account managers.</p>

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Account Manager</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Average Duration</strong></td>
		</tr>

		<?php
		foreach($accountManagers as $accountManagerId=>$accountManagerName) {
			if(count($schedules[$accountManagerId]) > 0) {
				$combinedDuration = 0;
				$combinedCalls = 0;

				foreach($schedules[$accountManagerId] as $completedDate=>$scheduleItem) {
					foreach($scheduleTypes as $scheduleTypeId=>$scheduleType) {
						if(isset($scheduleItem[$scheduleTypeId])) {
							foreach($scheduleItem[$scheduleTypeId] as $scheduleData) {
								if(count($scheduleData['Phone_Calls']) > 0) {
									foreach($scheduleData['Phone_Calls'] as $callItem) {
										$combinedDuration += $callItem['Duration'];
										$combinedCalls++;
									}
								}
							}
						}
					}
				}
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $accountManagerName; ?></td>
					<td><?php 
					if($combinedCalls == 0 ){
						echo 0;
					}else{
					echo getDuration($combinedDuration/$combinedCalls);} ?></td>
				</tr>

				<?php
			}
		}
		?>

	</table>
	<br />

	<?php
	foreach($accountManagers as $accountManagerId=>$accountManagerName) {
		if(count($schedules[$accountManagerId]) > 0) {
			?>

			<br />
			<h3><?php echo $accountManagerName; ?></h3>
			<p>Listing all completed contact schedules between the given period for this account manager.</p>

			<table width="100%" border="0" >
				<tr>
					<td style="border-bottom: 1px solid #aaaaaa;" colspan="7"><strong>Completed</strong></td>
				</tr>

				<?php
				foreach($schedules[$accountManagerId] as $completedDate=>$scheduleItem) {
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td colspan="6"><strong><?php echo $completedDate; ?></strong></td>
					</tr>

					<?php
					foreach($scheduleTypes as $scheduleTypeId=>$scheduleType) {
						if(isset($scheduleItem[$scheduleTypeId])) {
							$totalDuration = 0;
							?>

							<tr>
								<td width="2%">&nbsp;</td>
								<td style="border-bottom: 1px solid #aaaaaa;" nowrap="nowrap" colspan="2"><strong><?php echo $scheduleTypes[$scheduleTypeId]; ?></strong></td>
								<td style="border-bottom: 1px solid #aaaaaa;"><strong>Contact</strong></td>
								<td style="border-bottom: 1px solid #aaaaaa;"><strong>Status</strong></td>
								<td style="border-bottom: 1px solid #aaaaaa;"><strong>Message</strong></td>
							</tr>

							<?php
							foreach($scheduleItem[$scheduleTypeId] as $scheduleData) {
								?>

								<tr>
									<td valign="top">&nbsp;</td>
									<td valign="top" colspan="2"><?php echo date('H:i:s', strtotime($scheduleData['Completed_On'])); ?></td>
									<td valign="top"><a href="contact_profile.php?cid=<?php echo $scheduleData['Contact_ID']; ?>"><?php echo $scheduleData['Contact_Name']; ?></a></td>
									<td valign="top" nowrap="nowrap"><?php echo $scheduleData['Status']; ?></td>
									<td valign="top"><?php echo nl2br($scheduleData['Message']); ?></td>
								</tr>

								<?php
								if(count($scheduleData['Phone_Calls']) > 0) {
									foreach($scheduleData['Phone_Calls'] as $callItem) {
										$totalDuration += $callItem['Duration'];
										?>

										<tr>
											<td valign="top">&nbsp;</td>
											<td valign="top" width="2%">&nbsp;</td>
											<td valign="top"><?php echo date('H:i:s', strtotime($callItem['Called_On'])); ?></td>
											<td valign="top"><?php echo $callItem['Phone_Number']; ?></td>
											<td valign="top" colspan="2" align="right"><?php echo getDuration($callItem['Duration']); ?></td>
										</tr>

										<?php
									}
								}
							}
							?>

							<tr>
								<td valign="top">&nbsp;</td>
								<td valign="top" colspan="2">&nbsp;</td>
								<td valign="top">&nbsp;</td>
								<td valign="top">&nbsp;</td>
								<td valign="top" align="right"><strong><?php echo getDuration($totalDuration); ?></strong></td>
							</tr>

							<?php
						}
					}
				}
				?>

			</table><br />

			<?php
		}
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>