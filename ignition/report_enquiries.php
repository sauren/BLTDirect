<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Enquiry Report', 'Please choose a start and end date for your report');
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

			report($start, $end, $form->GetValue('parent'));
			exit;
		} else {
			
			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))));
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

	$window = new StandardWindow("Report on Enquiries.");
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$page = new Page('Enquiry Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
	?>

	<br />
	<h3>Response Times</h3>
	<p>Enquiry reponse times created between the specified period. A response is defined as being the first public reply to the first customer message of an enquiry.</p>

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>User</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Enquiries</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Average Response Time</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Quickest Response Time</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Slowest Response Time</strong></td>
		</tr>

		<?php
		$responses = array();
		$results = array();

		$data = new DataQuery(sprintf("SELECT e.Owned_By, e.Enquiry_ID, el.Created_On, el.Is_Customer_Message FROM enquiry AS e INNER JOIN enquiry_line AS el ON el.Enquiry_ID=e.Enquiry_ID WHERE e.Created_On BETWEEN '%s' AND '%s' AND e.Owned_By>0 AND el.Is_Draft='N' AND el.Is_Public='Y' ORDER BY el.Created_On ASC", $start, $end));
		while($data->Row) {
			if(!isset($responses[$data->Row['Owned_By']])) {
				$responses[$data->Row['Owned_By']] = array();
			}

			if(!isset($responses[$data->Row['Owned_By']][$data->Row['Enquiry_ID']])) {
				$responses[$data->Row['Owned_By']][$data->Row['Enquiry_ID']] = array();
			}

			$line = array();
			$line['Created_On'] = $data->Row['Created_On'];
			$line['Is_Customer_Message'] = $data->Row['Is_Customer_Message'];

			$responses[$data->Row['Owned_By']][$data->Row['Enquiry_ID']][] = $line;

			$data->Next();
		}
		$data->Disconnect();

		$cutOffStart = 8.5;
		$cutOffEnd = 17.0;
		$cutOffStartDate = '08:30:00';
		$cutOffEndDate = '17:00:00';

		foreach($responses as $owner=>$enquiries) {
			foreach($enquiries as $key=>$lines) {
				if(count($lines) >= 2) {
					$index = 0;
					$startInd = -1;
					$endInd = -1;

					for($i=0; $i<count($lines); $i++) {
						$index++;

						$startInd = $i;
						break;
					}

					if(($startInd >= 0) && ($index < count($lines))) {
						for($i=$index; $i<count($lines); $i++) {

							if($lines[$i]['Is_Customer_Message'] == 'N') {
								$endInd = $i;
								break;
							}
						}

						if($endInd >= 0) {
							if(!isset($results[$owner])) {
								$results[$owner] = array();
							}

							$startTime = substr($lines[$startInd]['Created_On'], 11, 2) + (substr($lines[$startInd]['Created_On'], 14, 2) / 60);
							$endTime = substr($lines[$endInd]['Created_On'], 11, 2) + (substr($lines[$endInd]['Created_On'], 14, 2) / 60);

							if($startTime < $cutOffStart) {
								$lines[$startInd]['Created_On'] = date(sprintf('Y-m-d %s', $cutOffStartDate), strtotime($lines[$startInd]['Created_On']));
							} elseif($startTime > $cutOffEnd) {
								$lines[$startInd]['Created_On'] = date(sprintf('Y-m-d %s', $cutOffEndDate), strtotime($lines[$startInd]['Created_On']));
							}

							if($endTime > $cutOffEnd) {
								$lines[$endInd]['Created_On'] = date(sprintf('Y-m-d %s', $cutOffEndDate), strtotime($lines[$endInd]['Created_On']));
							} elseif($endTime < $cutOffStart) {
								$lines[$endInd]['Created_On'] = date(sprintf('Y-m-d %s', $cutOffStartDate), strtotime($lines[$endInd]['Created_On']));
							}

							$startTime = substr($lines[$startInd]['Created_On'], 11, 2) + (substr($lines[$startInd]['Created_On'], 14, 2) / 60);
							$endTime = substr($lines[$endInd]['Created_On'], 11, 2) + (substr($lines[$endInd]['Created_On'], 14, 2) / 60);

							if(trim(substr($lines[$startInd]['Created_On'], 0, 10)) == trim(substr($lines[$endInd]['Created_On'], 0, 10))) {
								$results[$owner][] = strtotime($lines[$endInd]['Created_On']) - strtotime($lines[$startInd]['Created_On']);
							} else {
								$accumulated = ($cutOffEnd - $startTime) * 3600;
								$accumulated += ($endTime - $cutOffStart) * 3600;

								$currentDate = trim(substr($lines[$startInd]['Created_On'], 0, 10));
								$days = -1;

								while($currentDate != trim(substr($lines[$endInd]['Created_On'], 0, 10))) {
									$currentDate = date('Y-m-d', strtotime(sprintf('%s 00:00:00', $currentDate)) + 86400);

									if((strtolower(date('D', strtotime($currentDate))) != 'sat') && (strtolower(date('D', strtotime($currentDate))) != 'sun')) {
										$days++;
									}
								}

								if($days > 0) {
									for($i=0; $i<$days; $i++) {
										$accumulated += ($cutOffEnd - $cutOffStart) * 3600;;
									}
								}

								$results[$owner][] = $accumulated;
							}
						}
					}
				}
			}
		}

		if(count($results) > 0) {
			$user = new User();

			foreach($results as $key=>$result) {
				if(count($result) > 0) {
					$user->ID = $key;
					$user->Get();

					$quickest = $result[0];
					$slowest = $result[0];
					$average = 0;

					foreach($result as $time) {
						$average += $time;

						if($time < $quickest) {
							$quickest = $time;
						}

						if($time > $slowest) {
							$slowest = $time;
						}
					}

					$average = $average / count($result);

					$averageStr = 'Out of range';
					$quickestStr = 'Out of range';
					$slowestStr = 'Out of range';

					if($average < 60) {
						$averageStr = sprintf('%s seconds', number_format($average, 1, '.', ','));

					} elseif(($average/60) < 60) {
						$averageStr = sprintf('%s minutes', number_format(($average/60), 1, '.', ','));

					} elseif(($average/60/60) < 24) {
						$averageStr = sprintf('%s hours', number_format(($average/60/60), 1, '.', ','));

					} elseif(($average/60/60/24) < 365.25) {
						$averageStr = sprintf('%s days', number_format(($average/60/60/24), 1, '.', ','));

					} elseif(($average/60/60/24/365.25) < 365.25) {
						$averageStr = sprintf('%s years', number_format(($average/60/60/24/365.25), 1, '.', ','));
					}

					if($quickest < 60) {
						$quickestStr = sprintf('%s seconds', number_format($quickest, 1, '.', ','));

					} elseif(($quickest/60) < 60) {
						$quickestStr = sprintf('%s minutes', number_format(($quickest/60), 1, '.', ','));

					} elseif(($quickest/60/60) < 24) {
						$quickestStr = sprintf('%s hours', number_format(($quickest/60/60), 1, '.', ','));

					} elseif(($quickest/60/60/24) < 365.25) {
						$quickestStr = sprintf('%s days', number_format(($quickest/60/60/24), 1, '.', ','));

					} elseif(($quickest/60/60/24/365.25) < 365.25) {
						$quickestStr = sprintf('%s years', number_format(($quickest/60/60/24/365.25), 1, '.', ','));
					}

					if($slowest < 60) {
						$slowestStr = sprintf('%s seconds', number_format($slowest, 1, '.', ','));

					} elseif(($slowest/60) < 60) {
						$slowestStr = sprintf('%s minutes', number_format(($slowest/60), 1, '.', ','));

					} elseif(($slowest/60/60) < 24) {
						$slowestStr = sprintf('%s hours', number_format(($slowest/60/60), 1, '.', ','));

					} elseif(($slowest/60/60/24) < 365.25) {
						$slowestStr = sprintf('%s days', number_format(($slowest/60/60/24), 1, '.', ','));

					} elseif(($slowest/60/60/24/365.25) < 365.25) {
						$slowestStr = sprintf('%s years', number_format(($slowest/60/60/24/365.25), 1, '.', ','));
					}
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)); ?></td>
						<td><?php echo count($result); ?></td>
						<td><?php echo $averageStr; ?></td>
						<td><?php echo $quickestStr; ?></td>
						<td><?php echo $slowestStr; ?></td>
					</tr>

					<?php
				}
			}
		} else {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="5" align="center">No statistics to report on.</td>
			</tr>

			<?php
		}
		?>

	</table><br />

	<br />
	<h3>Open Times</h3>
	<p>Listing open time statistics of enquiries created between the specified period.</p>

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>User</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Enquiries</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Average Open Time</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Shortest Open Time</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Longest Open Time</strong></td>
		</tr>

		<?php
		$open = array();

		$data = new DataQuery(sprintf("SELECT Created_On, Closed_On, Owned_By FROM enquiry WHERE Created_On BETWEEN '%s' AND '%s' AND Owned_By>0 AND Status LIKE 'Closed'", $start, $end));
		while($data->Row) {
			if(!isset($open[$data->Row['Owned_By']])) {
				$open[$data->Row['Owned_By']] = array();
			}

			$open[$data->Row['Owned_By']][] = strtotime($data->Row['Closed_On']) - strtotime($data->Row['Created_On']);

			$data->Next();
		}
		$data->Disconnect();

		if(count($open) > 0) {
			$user = new User();

			foreach($open as $key=>$result) {
				$user->ID = $key;
				$user->Get();

				$shortest = $result[0];
				$longest = $result[0];
				$average = 0;

				foreach($result as $time) {
					$average += $time;

					if($time < $shortest) {
						$shortest = $time;
					}

					if($time > $longest) {
						$longest = $time;
					}
				}

				$average = $average / count($result);

				$averageStr = 'Out of range';
				$shortestStr = 'Out of range';
				$longestStr = 'Out of range';

				if($average < 60) {
					$averageStr = sprintf('%s seconds', number_format($average, 1, '.', ','));

				} elseif(($average/60) < 60) {
					$averageStr = sprintf('%s minutes', number_format(($average/60), 1, '.', ','));

				} elseif(($average/60/60) < 24) {
					$averageStr = sprintf('%s hours', number_format(($average/60/60), 1, '.', ','));

				} elseif(($average/60/60/24) < 365.25) {
					$averageStr = sprintf('%s days', number_format(($average/60/60/24), 1, '.', ','));

				} elseif(($average/60/60/24/365.25) < 365.25) {
					$averageStr = sprintf('%s years', number_format(($average/60/60/24/365.25), 1, '.', ','));
				}

				if($shortest < 60) {
					$shortestStr = sprintf('%s seconds', number_format($shortest, 1, '.', ','));

				} elseif(($shortest/60) < 60) {
					$shortestStr = sprintf('%s minutes', number_format(($shortest/60), 1, '.', ','));

				} elseif(($shortest/60/60) < 24) {
					$shortestStr = sprintf('%s hours', number_format(($shortest/60/60), 1, '.', ','));

				} elseif(($shortest/60/60/24) < 365.25) {
					$shortestStr = sprintf('%s days', number_format(($shortest/60/60/24), 1, '.', ','));

				} elseif(($shortest/60/60/24/365.25) < 365.25) {
					$shortestStr = sprintf('%s years', number_format(($shortest/60/60/24/365.25), 1, '.', ','));
				}

				if($longest < 60) {
					$longestStr = sprintf('%s seconds', number_format($longest, 1, '.', ','));

				} elseif(($longest/60) < 60) {
					$longestStr = sprintf('%s minutes', number_format(($longest/60), 1, '.', ','));

				} elseif(($longest/60/60) < 24) {
					$longestStr = sprintf('%s hours', number_format(($longest/60/60), 1, '.', ','));

				} elseif(($longest/60/60/24) < 365.25) {
					$longestStr = sprintf('%s days', number_format(($longest/60/60/24), 1, '.', ','));

				} elseif(($longest/60/60/24/365.25) < 365.25) {
					$longestStr = sprintf('%s years', number_format(($longest/60/60/24/365.25), 1, '.', ','));
				}
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)); ?></td>
					<td><?php echo count($result); ?></td>
					<td><?php echo $averageStr; ?></td>
					<td><?php echo $shortestStr; ?></td>
					<td><?php echo $longestStr; ?></td>
				</tr>

				<?php
			}
		} else {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="4" align="center">No statistics to report on.</td>
			</tr>

			<?php
		}
		?>

	</table><br />

	<br />
	<h3>User Ratings</h3>
	<p>The following statistics are representative of performance for the given period for enquiries belonging to a particular user.</p>

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>User</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Ratings</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Average Rating</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Accumulated Rating</strong></td>
		</tr>

		<?php
		$ratings = array();

		$data = new DataQuery(sprintf("SELECT * FROM enquiry WHERE Created_On BETWEEN '%s' AND '%s' AND Owned_By>0 AND Rating>0", $start, $end));
		while($data->Row) {
			if(!isset($ratings[$data->Row['Owned_By']])) {
				$item = array();
				$item['Ratings'] = 0;
				$item['Accumulated'] = 0;

				$ratings[$data->Row['Owned_By']] = $item;
			}

			$item = $ratings[$data->Row['Owned_By']];
			$item['Ratings']++;
			$item['Accumulated'] += $data->Row['Rating'];

			$ratings[$data->Row['Owned_By']] = $item;

			$data->Next();
		}
		$data->Disconnect();

		if(count($ratings) > 0) {
			$user = new User();

			foreach($ratings as $key=>$rating) {
				$ratings[$key]['Average'] = $ratings[$key]['Accumulated'] / $ratings[$key]['Ratings'];

				$user->ID = $key;
				$user->Get();
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)); ?></td>
					<td><?php echo $ratings[$key]['Ratings']; ?></td>
					<td><?php echo number_format($ratings[$key]['Average'], 1, '.', ''); ?></td>
					<td><?php echo number_format($ratings[$key]['Accumulated'], 1, '.', ''); ?></td>
				</tr>

				<?php
			}
		} else {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="4" align="center">No statistics to report on.</td>
			</tr>

			<?php
		}
		?>

	</table><br />

	<br />
	<h3>Order Conversions</h3>
	<p>Conversion statistics for orders placed between the open and closed date of enquiries for each user.</p>

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>User</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Conversions</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Turnover</strong></td>
		</tr>

		<?php
		$data = new DataQuery(sprintf("SELECT e.Owned_By, COUNT(o.Order_ID) AS Conversions, SUM(o.Total) AS Turnover FROM enquiry AS e INNER JOIN orders AS o ON o.Customer_ID=e.Customer_ID AND o.Created_On>e.Created_On AND ((e.Status LIKE 'Closed' AND o.Created_On<e.Closed_On) OR (e.Status NOT LIKE 'Closed' AND o.Created_On<NOW())) WHERE e.Created_On BETWEEN '%s' AND '%s' AND e.Owned_By>0 GROUP BY e.Owned_By", $start, $end));
		if($data->TotalRows > 0) {
			$user = new User();

			while($data->Row) {
				$user->ID = $data->Row['Owned_By'];
				$user->Get();
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo trim(sprintf('%s %s', $user->Person->Name, $user->Password->NameLast)); ?></td>
					<td><?php echo $data->Row['Conversions']; ?></td>
					<td>&pound;<?php echo $data->Row['Turnover']; ?></td>
				</tr>

				<?php

				$data->Next();
			}
			$data->Disconnect();
		} else {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="3" align="center">No statistics to report on.</td>
			</tr>

			<?php
		}
		?>

	</table><br />

	<br />
	<h3>Customer Comments</h3>
	<p>Rating comments from customers as a direct result of customer service performance.</p>

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Rating</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Comment</strong></td>
		</tr>

		<?php
		$data = new DataQuery(sprintf("SELECT e.Rating, e.Rating_Comment, p.Name_First, p.Name_Last, n.Contact_ID FROM enquiry AS e INNER JOIN customer AS c ON c.Customer_ID=e.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p ON p.Person_ID=n.Person_ID WHERE e.Created_On BETWEEN '%s' AND '%s' AND e.Rating>0 ORDER BY e.Rating DESC", $start, $end));
		if($data->TotalRows > 0) {
			while($data->Row) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><a href="contact_profile.php?cid=<?php echo $data->Row['Contact_ID']; ?>"><?php echo trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])); ?></a></td>
					<td><?php echo number_format($data->Row['Rating'], 1, '.', ''); ?></td>
					<td><?php echo nl2br($data->Row['Rating_Comment']); ?></td>
				</tr>

				<?php

				$data->Next();
			}
			$data->Disconnect();
		} else {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="3" align="center">No statistics to report on.</td>
			</tr>

			<?php
		}
		?>

	</table><br />

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>