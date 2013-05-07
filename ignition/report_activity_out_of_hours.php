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
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'Report End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('range', 'Date range', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('range', 'none', '-- None --');
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
	$form->AddField('user', 'User', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('user', '', '');

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Person_Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY Person_Name ASC"));
	while($data->Row) {
		$form->AddOption('user', $data->Row['User_ID'], $data->Row['Person_Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 1)) {
			$form->Validate('user');

			if($form->Valid) {
				switch($form->GetValue('range')) {
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

				redirect(sprintf("Location: %s?action=report&start=%s&end=%s&userid=%d", $_SERVER['PHP_SELF'], $start, $end, $form->GetValue('user')));
			}
		} else {
			if($form->Validate()) {
				redirect(sprintf("Location: %s?action=report&start=%s&end=%s&userid=%d", $_SERVER['PHP_SELF'], sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('user')));
			}
		}
	}

	$page = new Page('Activity Out Of Hours Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Order Breakdown.");
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

	echo $window->AddHeader('Select the user to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('user'), $form->GetHTML('user'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : '0000-00-00 00:00:00';
	$end = isset($_REQUEST['end']) ? $_REQUEST['end'] : '0000-00-00 00:00:00';
	$userId = isset($_REQUEST['userid']) ? $_REQUEST['userid'] : 0;
	$session = isset($_REQUEST['session']) ? $_REQUEST['session'] : '';

	if(($start == '0000-00-00 00:00:00') || ($end == '0000-00-00 00:00:00') || ($userId == 0)) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$user = new User($userId);

	$script = sprintf('<script language="javascript" type="text/javascript">
		function showMore(id) {
			var e1 = document.getElementById(\'briefText-\' + id);
			var e2 = document.getElementById(\'fullText-\' + id);

			if(e1 && e2) {
				e1.style.display = \'none\';
				e2.style.display = \'\';
			}
		}
		</script>');

	$page = new Page('Activity Out Of Hours Report: ' . cDatetime($start, 'longdate') . ' to ' . cDatetime($end, 'longdate'), '');
	$page->AddToHead($script);
	$page->Display('header');

	$sessions = array();

	$data = new DataQuery(sprintf("SELECT s.Session_ID, s.User_ID, MIN(si.Created_On) AS Started_On, MAX(si.Created_On) AS Ended_On FROM sessions AS s INNER JOIN session_item AS si ON si.Session_ID=s.Session_ID WHERE si.Created_On>='%s' AND si.Created_On<'%s' AND s.User_ID=%d AND (((DATE_FORMAT(si.Created_On, '%%w')=0) OR (DATE_FORMAT(si.Created_On, '%%w')=6)) OR (((DATE_FORMAT(si.Created_On, '%%w')>0) AND (DATE_FORMAT(si.Created_On, '%%w')<6))) AND (TIME(si.Created_On) >= '17:30:00')) GROUP BY s.Session_ID ORDER BY Started_On ASC", $start, $end, mysql_real_escape_string($userId)));
	while($data->Row) {
		$sessions[] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();
	?>

	<br />
	<h3><?php echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)); ?>'s Sessions</h3>
	<p>Session statistics for the above user.</p>

	<table width="100%" border="0">
		<tr>
			<td valign="top" width="1%">
				<div style="padding: 10px; background-color: #eee; white-space: nowrap;">
					<p><span class="pageSubTitle">Sessions</span></p>

					<?php
					for($i=0; $i<count($sessions); $i++) {
						echo sprintf('<a href="?action=report&userid=%d&start=%s&end=%s&session=%s">%s - %s</a><br />', $user->ID, $start, $end, $sessions[$i]['Session_ID'], cDatetime($sessions[$i]['Started_On'], 'shortdatetime'), cDatetime($sessions[$i]['Ended_On'], 'shortdatetime'));
					}
					?>
				</div>
				<br />

				<div style="padding: 10px; background-color: #eee; white-space: nowrap;">
					<p><span class="pageSubTitle">Enquiries</span></p>

					<?php
					$data = new DataQuery(sprintf("SELECT e.*, el.Created_On FROM enquiry AS e INNER JOIN enquiry_line AS el ON el.Enquiry_ID=e.Enquiry_ID WHERE el.Created_On>='%s' AND el.Created_On<'%s' AND el.Created_By=%d AND (((DATE_FORMAT(el.Created_On, '%%w')=0) OR (DATE_FORMAT(el.Created_On, '%%w')=6)) OR (((DATE_FORMAT(el.Created_On, '%%w')>0) AND (DATE_FORMAT(el.Created_On, '%%w')<6))) AND (TIME(el.Created_On) >= '17:30:00'))", $start, $end, mysql_real_escape_string($user->ID)));
					while($data->Row) {
                        echo sprintf('<a href="enquiry_details.php?enquiryid=%d">%s%s</a> - %s<br />', $data->Row['Enquiry_ID'], $data->Row['Prefix'], $data->Row['Enquiry_ID'], cDatetime($data->Row['Created_On'], 'shortdatetime'));

						$data->Next();
					}
					$data->Disconnect();
					?>

				</div>
				<br />

                <div style="padding: 10px; background-color: #eee; white-space: nowrap;">
					<p><span class="pageSubTitle">Quotes</span></p>

					<?php
					$data = new DataQuery(sprintf("SELECT q.* FROM quote AS q WHERE q.Created_On>='%s' AND q.Created_On<'%s' AND q.Created_By=%d AND (((DATE_FORMAT(q.Created_On, '%%w')=0) OR (DATE_FORMAT(q.Created_On, '%%w')=6)) OR (((DATE_FORMAT(q.Created_On, '%%w')>0) AND (DATE_FORMAT(q.Created_On, '%%w')<6))) AND (TIME(q.Created_On) >= '17:30:00'))", $start, $end, mysql_real_escape_string($user->ID)));
					while($data->Row) {
                        echo sprintf('<a href="quote_details.php?quoteid=%d">%s%s</a> - %s<br />', $data->Row['Quote_ID'], $data->Row['Quote_Prefix'], $data->Row['Quote_ID'], cDatetime($data->Row['Created_On'], 'shortdatetime'));

						$data->Next();
					}
					$data->Disconnect();
					?>

				</div>
				<br />

			</td>
			<td valign="top" style="padding: 10px;">

                <?php
				$data = new DataQuery(sprintf("SELECT o.*, SUM((ol.Line_Total - ol.Line_Discount) - (ol.Cost * ol.Quantity)) AS Profit FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND o.Created_By=%d AND (((DATE_FORMAT(o.Created_On, '%%w')=0) OR (DATE_FORMAT(o.Created_On, '%%w')=6)) OR (((DATE_FORMAT(o.Created_On, '%%w')>0) AND (DATE_FORMAT(o.Created_On, '%%w')<6))) AND (TIME(o.Created_On) >= '17:30:00')) GROUP BY o.Order_ID ORDER BY o.Created_On ASC", $start, $end, mysql_real_escape_string($user->ID)));
				if($data->TotalRows > 0) {
					?>

                    <h3 style="font-size: 16px; font-weight: bold;">Orders (<?php echo $data->TotalRows; ?>)</h3>

                    <table width="100%" border="0">
						<tr>
							<td style="border-bottom:1px solid #aaaaaa;"><strong>Order</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;"><strong>Date</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Total</strong></td>
							<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Profit</strong></td>
						</tr>

						<?php
						$totalGross = 0;
						$totalProfit = 0;

						while($data->Row) {
							?>

                            <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td><a href="order_details.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo $data->Row['Order_Prefix'] . $data->Row['Order_ID']; ?></a></td>
								<td><?php echo $data->Row['Created_On']; ?></td>
								<td align="right"><?php echo $data->Row['Total']; ?></td>
								<td align="right"><?php echo $data->Row['Profit']; ?></td>
							</tr>

							<?php
                            $totalGross += $data->Row['Total'];
							$totalProfit += $data->Row['Profit'];

							$data->Next();
						}
						?>

                        <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td></td>
							<td></td>
							<td align="right"><strong><?php echo number_format(round($totalGross, 2), 2, '.', ','); ?></strong></td>
							<td align="right"><strong><?php echo number_format(round($totalProfit, 2), 2, '.', ','); ?></strong></td>
						</tr>
					</table>
					<br />

					<?php
				}
				$data->Disconnect();

				if(!empty($session)) {
					$items = array();

					$data = new DataQuery(sprintf("SELECT Session_Item_ID, Created_On, Page_Request FROM session_item WHERE Session_ID LIKE '%s' AND (((DATE_FORMAT(Created_On, '%%w')=0) OR (DATE_FORMAT(Created_On, '%%w')=6)) OR (((DATE_FORMAT(Created_On, '%%w')>0) AND (DATE_FORMAT(Created_On, '%%w')<6))) AND (TIME(Created_On) >= '17:30:00')) ORDER BY Created_On ASC", mysql_real_escape_string($session)));
					while($data->Row) {
						$items[] = $data->Row;

						$data->Next();
					}
					$data->Disconnect();

					$timeConnected = strtotime($items[count($items) - 1]['Created_On']) - strtotime($items[0]['Created_On']);
					$avgPageTime = number_format($timeConnected / (count($items) - 1), 1, '.', '');
					?>

					<h3 style="font-size: 16px; font-weight: bold;">Session Information</h3>
					<br />

					<table width="100%" border="0">
						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td width="20%"><strong>Session ID</strong></td>
							<td><?php echo $session; ?></td>
						</tr>
						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td><strong>Page Requests</strong></td>
							<td><?php echo count($items); ?></td>
						</tr>
						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td><strong>Time Connected</strong></td>
							<td><?php echo number_format($timeConnected/60, 1, '.', ''); ?> minutes</td>
						</tr>
						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td><strong>Average Page Time</strong></td>
							<td><?php echo $avgPageTime; ?> seconds</td>
						</tr>
					</table>
					<br />

					<h3 style="font-size: 16px; font-weight: bold;">Page Requests</h3>
					<br />

					<table width="100%" border="0">
						<tr>
							<td style="border-bottom:1px solid #aaaaaa"><strong>#</strong></td>
							<td style="border-bottom:1px solid #aaaaaa"><strong>Page Request</strong></td>
							<td style="border-bottom:1px solid #aaaaaa"><strong>Time Requested</strong></td>
						</tr>

						<?php
						for($i=0; $i<count($items); $i++) {
							?>

							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td><?php echo $i+1; ?></td>
								<td><span id="briefText-<?php echo $i; ?>"><?php echo (strlen($items[$i]['Page_Request']) > 100) ? sprintf('%s<a href="javascript:showMore(%d);" title="Show More">...</a>', substr($items[$i]['Page_Request'], 0, 100), $i) : $items[$i]['Page_Request']; ?></span><span style="display: none;" id="fullText-<?php echo $i; ?>"><?php echo $items[$i]['Page_Request']; ?></span></td>
								<td nowrap="nowrap"><?php echo cDatetime($items[$i]['Created_On'], 'shortdatetime'); ?></td>
							</tr>

							<?php
						}
						?>

					</table>

					<?php
				} else {
					?>

					<h3 style="font-size: 16px; font-weight: bold;">Sessions</h3>
					<br />

					<p>Select a session to the left to view its contents.</p>

					<?php
				}
				?>

			</td>
		</tr>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>