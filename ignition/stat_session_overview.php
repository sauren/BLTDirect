<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

if(isset($_REQUEST['report'])) {
	if($_REQUEST['report'] == 'today') {
		$_REQUEST['start'] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
		$_REQUEST['end'] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')));
		$_REQUEST['period'] = 'H';

	} elseif($_REQUEST['report'] == 'thismonth') {
		$_REQUEST['start'] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')));
		$_REQUEST['end'] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m') + 1, 1, date('Y')));
		$_REQUEST['period'] = 'D';

	} elseif($_REQUEST['report'] == 'thisyear') {
		$_REQUEST['start'] = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, date('Y')));
		$_REQUEST['end'] = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, date('Y') + 1));
		$_REQUEST['period'] = 'M';
	}
}

$start = '0000-00-00 00:00:00';
$end = '0000-00-00 00:00:00';
$period = 'Y';
$sessions = array();
$summary = array();
$dates = array();
$customers = array();
$pageTitle = 'Statistics';

if(!isset($_REQUEST['start']) || !isset($_REQUEST['end']) || !isset($_REQUEST['period'])) {
	$data = new DataQuery(sprintf("SELECT MIN(Created_On) AS Start_Date, MAX(Created_On) AS End_Date FROM customer_session_archive"));
	if($data->TotalRows > 0) {
		$start = $data->Row['Start_Date'];
		$end = $data->Row['End_Date'];
	}
	$data->Disconnect();
} else {
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	$period = $_REQUEST['period'];
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'filter', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('start', 'Start', 'hidden', $start, 'anything', 19, 19);
$form->AddField('end', 'End', 'hidden', $end, 'anything', 19, 19);
$form->AddField('period', 'Period', 'hidden', $period, 'alpha', 1, 1);
$form->AddField('showcustomers', 'Show Customers', 'checkbox', ($period != 'D') ? 'Y' : 'N', 'boolean', 1, 1);

switch(strtoupper($period)) {
	case 'Y':
		$pageTitle = 'Yearly Statistics';

		for($i=date('Y', strtotime($start)); $i<=date('Y', strtotime($end)); $i++) {
			$dates[] = array(
				'Start' => date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, $i)),
				'End' => date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, $i + 1)));
		}

		break;

	case 'M':
		$pageTitle = 'Monthly Statistics';

		$currDate = $start;
		$index = 1;

		while($currDate < $end) {
			$dates[] = array(
				'Start' => date('Y-m-d H:i:s', mktime(0, 0, 0, $index, 1, date('Y', strtotime($start)))),
				'End' => date('Y-m-d H:i:s', mktime(0, 0, 0, $index + 1, 1, date('Y', strtotime($start)))));

			$currDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($currDate)) + 1, 1, date('Y', strtotime($currDate))));
			$index++;
		}

		break;

	case 'D':
		$pageTitle = 'Daily Statistics';

		$currDate = $start;
		$index = 1;

		while($currDate < $end) {
			$dates[] = array(
				'Start' => date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($start)), $index, date('Y', strtotime($start)))),
				'End' => date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($start)), $index + 1, date('Y', strtotime($start)))));

			$currDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($currDate)), date('d', strtotime($currDate)) + 1, date('Y', strtotime($currDate))));
			$index++;
		}

		break;

	case 'H':
		$pageTitle = 'Hourly Statistics';

		$currDate = $start;
		$index = 0;

		while($currDate < $end) {
			$dates[] = array(
				'Start' => date('Y-m-d H:i:s', mktime($index, 0, 0, date('m', strtotime($start)), date('d', strtotime($start)), date('Y', strtotime($start)))),
				'End' => date('Y-m-d H:i:s', mktime($index + 1, 0, 0, date('m', strtotime($start)), date('d', strtotime($start)), date('Y', strtotime($start)))));

			$currDate = date('Y-m-d H:i:s', mktime(date('H', strtotime($currDate)) + 1, 0, 0, date('m', strtotime($currDate)), date('d', strtotime($currDate)), date('Y', strtotime($currDate))));
			$index++;
		}

		break;

	case 'I':
		$pageTitle = 'Minutely Statistics';

		$currDate = $start;
		$index = 0;

		while($currDate < $end) {
			$dates[] = array(
				'Start' => date('Y-m-d H:i:s', mktime(date('H', strtotime($start)), $index, 0, date('m', strtotime($start)), date('d', strtotime($start)), date('Y', strtotime($start)))),
				'End' => date('Y-m-d H:i:s', mktime(date('H', strtotime($start)), $index + 1, 0, date('m', strtotime($start)), date('d', strtotime($start)), date('Y', strtotime($start)))));

			$currDate = date('Y-m-d H:i:s', mktime(date('H', strtotime($currDate)), date('i', strtotime($currDate)) + 1, 0, date('m', strtotime($currDate)), date('d', strtotime($currDate)), date('Y', strtotime($currDate))));
			$index++;
		}

		break;

	case 'S':
		$pageTitle = 'Secondly Statistics';

		$currDate = $start;
		$index = 0;

		while($currDate < $end) {
			$dates[] = array(
				'Start' => date('Y-m-d H:i:s', mktime(date('H', strtotime($start)), date('i', strtotime($start)), $index, date('m', strtotime($start)), date('d', strtotime($start)), date('Y', strtotime($start)))),
				'End' => date('Y-m-d H:i:s', mktime(date('H', strtotime($start)), date('i', strtotime($start)), $index + 1, date('m', strtotime($start)), date('d', strtotime($start)), date('Y', strtotime($start)))));

			$currDate = date('Y-m-d H:i:s', mktime(date('H', strtotime($currDate)), date('i', strtotime($currDate)), date('s', strtotime($currDate)) + 1, date('m', strtotime($currDate)), date('d', strtotime($currDate)), date('Y', strtotime($currDate))));
			$index++;
		}

		break;
}

$sqlFrom = "";
$sqlWhere = "";

for($i=0; $i<count($dates); $i++) {
	$data = new DataQuery(sprintf("SELECT COUNT(cs.Session_ID) AS Count FROM customer_session_archive AS cs WHERE cs.Created_On>='%s' AND cs.Created_On<'%s' %s", mysql_real_escape_string($dates[$i]['Start']), mysql_real_escape_string($dates[$i]['End']), $sqlWhere));
	$sessionCount = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(cs.Session_ID) AS Count FROM customer_session_archive AS cs INNER JOIN user_agent AS ua ON ua.User_Agent_ID=cs.User_Agent_ID AND ua.Is_Bot='Y' WHERE cs.Created_On>='%s' AND cs.Created_On<'%s' %s", mysql_real_escape_string($dates[$i]['Start']), mysql_real_escape_string($dates[$i]['End']), $sqlWhere));
	$botSessionCount = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(csi.Session_Item_ID) AS Count FROM customer_session_item_archive AS csi %s WHERE csi.Created_On>='%s' AND csi.Created_On<'%s' %s", $sqlFrom, mysql_real_escape_string($dates[$i]['Start']), mysql_real_escape_string($dates[$i]['End']), $sqlWhere));
	$sessionItemCount = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(csi.Session_Item_ID) AS Count FROM customer_session_item_archive AS csi %s INNER JOIN user_agent AS ua ON ua.User_Agent_ID=csi.User_Agent_ID AND ua.Is_Bot='Y' WHERE csi.Created_On>='%s' AND csi.Created_On<'%s' %s", $sqlFrom, mysql_real_escape_string($dates[$i]['Start']), mysql_real_escape_string($dates[$i]['End']), $sqlWhere));
	$botSessionItemCount = $data->Row['Count'];
	$data->Disconnect();

	$sessions[] = array(
		'Start' => $dates[$i]['Start'],
		'End' => $dates[$i]['End'],
		'Sessions' => $sessionCount,
		'Sessions_Bots' => $botSessionCount,
		'Sessions_NonBots' => $sessionCount - $botSessionCount,
		'SessionItems' => $sessionItemCount,
		'SessionItems_Bots' => $botSessionItemCount,
		'SessionItems_NonBots' => $sessionItemCount - $botSessionItemCount);
}

$totalSessions = 0;
$totalSessionsBots = 0;
$totalSessionsNonBots = 0;
$totalSessionItems = 0;
$totalSessionItemsBots = 0;
$totalSessionItemsNonBots = 0;

for($i=0; $i<count($sessions); $i++) {
	$totalSessions += $sessions[$i]['Sessions'];
	$totalSessionsBots += $sessions[$i]['Sessions_Bots'];
	$totalSessionsNonBots += $sessions[$i]['Sessions_NonBots'];
	$totalSessionItems += $sessions[$i]['SessionItems'];
	$totalSessionItemsBots += $sessions[$i]['SessionItems_Bots'];
	$totalSessionItemsNonBots += $sessions[$i]['SessionItems_NonBots'];
}

$total = $totalSessions + $totalSessionItems;

for($i=0; $i<count($sessions); $i++) {
	$sessions[$i]['SessionPercent'] = ($total > 0) ? number_format(($sessions[$i]['Sessions'] / $total) * 100, 1, '.', '') : 0;
	$sessions[$i]['SessionPercent_Bots'] = ($total > 0) ? number_format(($sessions[$i]['Sessions_Bots'] / $total) * 100, 1, '.', '') : 0;
	$sessions[$i]['SessionPercent_NonBots'] = ($total > 0) ? number_format(($sessions[$i]['Sessions_NonBots'] / $total) * 100, 1, '.', '') : 0;
	$sessions[$i]['SessionItemPercent'] = ($total > 0) ? number_format(($sessions[$i]['SessionItems'] / $total) * 100, 1, '.', '') : 0;
	$sessions[$i]['SessionItemPercent_Bots'] = ($total > 0) ? number_format(($sessions[$i]['SessionItems_Bots'] / $total) * 100, 1, '.', '') : 0;
	$sessions[$i]['SessionItemPercent_NonBots'] = ($total > 0) ? number_format(($sessions[$i]['SessionItems_NonBots'] / $total) * 100, 1, '.', '') : 0;

	switch(strtoupper($period)) {
		case 'Y':
			$sessions[$i]['Period'] = date('Y', strtotime($dates[$i]['Start']));
			$sessions[$i]['Link'] = sprintf('%s?start=%s&end=%s&period=%s', $_SERVER['PHP_SELF'], $sessions[$i]['Start'], $sessions[$i]['End'], 'M');
			break;

		case 'M':
			$sessions[$i]['Period'] = date('F', strtotime($dates[$i]['Start']));
			$sessions[$i]['Link'] = sprintf('%s?start=%s&end=%s&period=%s', $_SERVER['PHP_SELF'], $sessions[$i]['Start'], $sessions[$i]['End'], 'D');
			break;

		case 'D':
			$sessions[$i]['Period'] = date('jS (D)', strtotime($dates[$i]['Start']));
			$sessions[$i]['Link'] = sprintf('%s?start=%s&end=%s&period=%s', $_SERVER['PHP_SELF'], $sessions[$i]['Start'], $sessions[$i]['End'], 'H');
			break;

		case 'H':
			$sessions[$i]['Period'] = date('H:i:s (gA)', strtotime($dates[$i]['Start']));
			$sessions[$i]['Link'] = sprintf('%s?start=%s&end=%s&period=%s', $_SERVER['PHP_SELF'], $sessions[$i]['Start'], $sessions[$i]['End'], 'I');
			break;

		case 'I':
			$sessions[$i]['Period'] = date('H:i:s', strtotime($dates[$i]['Start']));
			$sessions[$i]['Link'] = sprintf('%s?start=%s&end=%s&period=%s', $_SERVER['PHP_SELF'], $sessions[$i]['Start'], $sessions[$i]['End'], 'S');
			break;

		case 'S':
			$sessions[$i]['Period'] = date('H:i:s', strtotime($dates[$i]['Start']));
			$sessions[$i]['Link'] = '';
			break;
	}
}

if($period == 'Y') {
	$summaryStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
	$summaryEndDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')));

	$data = new DataQuery(sprintf("SELECT COUNT(cs.Session_ID) AS Count FROM customer_session_archive AS cs WHERE cs.Created_On>='%s' AND cs.Created_On<'%s' %s", $summaryStartDate, $summaryEndDate, $sqlWhere));
	$sessionCount = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(cs.Session_ID) AS Count FROM customer_session_archive AS cs INNER JOIN user_agent AS ua ON ua.User_Agent_ID=cs.User_Agent_ID AND ua.Is_Bot='Y' WHERE cs.Created_On>='%s' AND cs.Created_On<'%s' %s", $summaryStartDate, $summaryEndDate, $sqlWhere));
	$botSessionCount = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(csi.Session_Item_ID) AS Count FROM customer_session_item_archive AS csi %s WHERE csi.Created_On>='%s' AND csi.Created_On<'%s' %s", $sqlFrom, $summaryStartDate, $summaryEndDate, $sqlWhere));
	$sessionItemCount = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(csi.Session_Item_ID) AS Count FROM customer_session_item_archive AS csi %s INNER JOIN user_agent AS ua ON ua.User_Agent_ID=csi.User_Agent_ID AND ua.Is_Bot='Y' WHERE csi.Created_On>='%s' AND csi.Created_On<'%s' %s",  $sqlFrom, $summaryStartDate, $summaryEndDate, $sqlWhere));
	$botSessionItemCount = $data->Row['Count'];
	$data->Disconnect();

	$summary[] = array(
		'Start' => $summaryStartDate,
		'End' => $summaryEndDate,
		'Period' => 'Today',
		'Link' => sprintf('%s?start=%s&end=%s&period=%s', $_SERVER['PHP_SELF'], $summaryStartDate, $summaryEndDate, 'H'),
		'Sessions' => $sessionCount,
		'Sessions_Bots' => $botSessionCount,
		'Sessions_NonBots' => $sessionCount - $botSessionCount,
		'SessionItems' => $sessionItemCount,
		'SessionItems_Bots' => $botSessionItemCount,
		'SessionItems_NonBots' => $sessionItemCount - $botSessionItemCount);

	$summaryStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')));
	$summaryEndDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m') + 1, 1, date('Y')));

	$data = new DataQuery(sprintf("SELECT COUNT(cs.Session_ID) AS Count FROM customer_session_archive AS cs WHERE cs.Created_On>='%s' AND cs.Created_On<'%s' %s", $summaryStartDate, $summaryEndDate, $sqlWhere));
	$sessionCount = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(cs.Session_ID) AS Count FROM customer_session_archive AS cs INNER JOIN user_agent AS ua ON ua.User_Agent_ID=cs.User_Agent_ID AND ua.Is_Bot='Y' WHERE cs.Created_On>='%s' AND cs.Created_On<'%s' %s", $summaryStartDate, $summaryEndDate, $sqlWhere));
	$botSessionCount = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(csi.Session_Item_ID) AS Count FROM customer_session_item_archive AS csi %s WHERE csi.Created_On>='%s' AND csi.Created_On<'%s' %s", $sqlFrom, $summaryStartDate, $summaryEndDate, $sqlWhere));
	$sessionItemCount = $data->Row['Count'];
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(csi.Session_Item_ID) AS Count FROM customer_session_item_archive AS csi %s INNER JOIN user_agent AS ua ON ua.User_Agent_ID=csi.User_Agent_ID AND ua.Is_Bot='Y' WHERE csi.Created_On>='%s' AND csi.Created_On<'%s' %s",  $sqlFrom, $summaryStartDate, $summaryEndDate, $sqlWhere));
	$botSessionItemCount = $data->Row['Count'];
	$data->Disconnect();

	$summary[] = array(
		'Start' => $summaryStartDate,
		'End' => $summaryEndDate,
		'Period' => 'This Month',
		'Link' => sprintf('%s?start=%s&end=%s&period=%s', $_SERVER['PHP_SELF'], $summaryStartDate, $summaryEndDate, 'D'),
		'Sessions' => $sessionCount,
		'Sessions_Bots' => $botSessionCount,
		'Sessions_NonBots' => $sessionCount - $botSessionCount,
		'SessionItems' => $sessionItemCount,
		'SessionItems_Bots' => $botSessionItemCount,
		'SessionItems_NonBots' => $sessionItemCount - $botSessionItemCount);

	$yearlySummary = $sessions[count($sessions) - 1];
	$yearlySummary['Period'] = 'This Year';

	$summary[] = $yearlySummary;

	$totalSummarys = 0;
	$totalSummaryItems = 0;

	for($i=0; $i<count($summary); $i++) {
		$totalSummarys += $summary[$i]['Sessions'];
		$totalSummaryItems += $summary[$i]['SessionItems'];
	}

	$total = $totalSummarys + $totalSummaryItems;

	for($i=0; $i<count($summary); $i++) {
		$summary[$i]['SessionPercent'] = ($total > 0) ? number_format(($summary[$i]['Sessions'] / $total) * 100, 1, '.', '') : 0;
		$summary[$i]['SessionItemPercent'] = ($total > 0) ? number_format(($summary[$i]['SessionItems'] / $total) * 100, 1, '.', '') : 0;
	}
}

if(($period != 'Y') && ($period != 'M')) {
	if($form->GetValue('showcustomers') == 'Y') {
		$data = new DataQuery(sprintf("SELECT csi.Customer_ID, c.Contact_ID, CONCAT_WS(' ', p.Name_Title, p.Name_First, p.Name_Initial, p.Name_Last) AS Name, COUNT(DISTINCT cs.Session_ID) AS Sessions, COUNT(DISTINCT csi2.Session_Item_ID) AS Session_Items, MAX(csi2.Created_On) AS Last_Seen_On FROM customer_session_archive AS cs INNER JOIN customer_session_item_archive AS csi ON cs.Session_ID=csi.Session_ID AND csi.Customer_ID>0 INNER JOIN customer_session_item_archive AS csi2 ON cs.Session_ID=csi2.Session_ID INNER JOIN customer AS c ON c.Customer_ID=csi.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p ON p.Person_ID=n.Person_ID WHERE cs.Created_On>='%s' AND cs.Created_On<'%s' %s GROUP BY Customer_ID ORDER BY Last_Seen_On DESC", mysql_real_escape_string($start), mysql_real_escape_string($end), $sqlWhere));
		while($data->Row) {
			$customers[] = array(
				'CustomerID' => $data->Row['Customer_ID'],
				'ContactID' => $data->Row['Contact_ID'],
				'Name' => $data->Row['Name'],
				'Sessions' => $data->Row['Sessions'],
				'SessionItems' => $data->Row['Session_Items'],
				'LastSeenOn' => $data->Row['Last_Seen_On']);

			$data->Next();
		}
		$data->Disconnect();

		$totalCustomerSessions = 0;
		$totalCustomerSessionItems = 0;

		for($i=0; $i<count($customers); $i++) {
			$totalCustomerSessions += $customers[$i]['Sessions'];
			$totalCustomerSessionItems += $customers[$i]['SessionItems'];
		}

		$total = $totalCustomerSessions + $totalCustomerSessionItems;

		for($i=0; $i<count($customers); $i++) {
			$customers[$i]['SessionPercent'] = ($total > 0) ? number_format(($customers[$i]['Sessions'] / $total) * 100, 1, '.', '') : 0;
			$customers[$i]['SessionItemPercent'] = ($total > 0) ? number_format(($customers[$i]['SessionItems'] / $total) * 100, 1, '.', '') : 0;
		}
	}
}

$page = new Page('Session Overview', 'Statistics for all sessions.');
$page->Display('header');

$window = new StandardWindow("Statistic options");
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('start');
echo $form->GetHTML('end');
echo $form->GetHTML('period');

if(($period != 'Y') && ($period != 'M')) {
	echo $window->Open();
	echo $window->AddHeader('Select your options for viewing these statistics');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('showcustomers'), $form->GetHTML('showcustomers'));
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
}

echo $form->Close();

echo '<br />';

if($period == 'Y') {
	?>

	<h3>Summary</h3>
	<br />

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa" valign="top"><strong>Period</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" valign="top" align="right"><strong>Sessions</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" valign="top" align="right"><strong>Requests</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" width="80%">&nbsp;</td>
			<td style="border-bottom:1px solid #aaaaaa">&nbsp;</td>
		</tr>

		<?php
		for($i=0; $i<count($summary); $i++) {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td nowrap="nowrap">
					<?php
					if(isset($summary[$i]['Link']) && !empty($summary[$i]['Link'])) {
						echo sprintf('<a href="%s">%s</a>', $summary[$i]['Link'], $summary[$i]['Period']);
					} else {
						echo $summary[$i]['Period'];
					}
					?>
				</td>
				<td nowrap="nowrap" align="right"><?php echo number_format($summary[$i]['Sessions'], 0, '.', ','); ?></td>
				<td nowrap="nowrap" align="right"><?php echo number_format($summary[$i]['SessionItems'], 0, '.', ','); ?></td>
				<td></td>
				<td nowrap="nowrap" align="center">
					<a target="blank" href="stat_sessions.php?start=<?php echo $summary[$i]['Start']; ?>&end=<?php echo $summary[$i]['End']; ?>&bots=N"><img src="images/icon_search_3.gif" width="16" height="16" alt="View Non Bot Sessions" border="0" /></a>
					<a target="blank" href="stat_sessions.php?start=<?php echo $summary[$i]['Start']; ?>&end=<?php echo $summary[$i]['End']; ?>&bots=Y"><img src="images/icon_search_2.gif" width="16" height="16" alt="View Bot Sessions" border="0" /></a>
					<a target="blank" href="stat_sessions.php?start=<?php echo $summary[$i]['Start']; ?>&end=<?php echo $summary[$i]['End']; ?>"><img src="images/icon_search_1.gif" width="16" height="16" alt="View Sessions" border="0" /></a>
				</td>
			</tr>

			<?php
		}
		?>

	</table>
	<br />

	<?php
	}
?>

<h3><?php echo $pageTitle; ?></h3>
<br />

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa" valign="top"><strong>Period</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" valign="top" align="right"><strong>Sessions</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" valign="top" align="right"><strong>Requests</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" width="80%">&nbsp;</td>
		<td style="border-bottom:1px solid #aaaaaa">&nbsp;</td>
	</tr>

	<?php
	for($i=0; $i<count($sessions); $i++) {
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td nowrap="nowrap">
				<?php
				if(isset($sessions[$i]['Link']) && !empty($sessions[$i]['Link'])) {
					echo sprintf('<a href="%s">%s</a>', $sessions[$i]['Link'], $sessions[$i]['Period']);
				} else {
					echo $sessions[$i]['Period'];
				}
				?>
			</td>
			<td nowrap="nowrap" align="right"><?php echo number_format($sessions[$i]['Sessions'], 0, '.', ','); ?></td>
			<td nowrap="nowrap" align="right"><?php echo number_format($sessions[$i]['SessionItems'], 0, '.', ','); ?></td>
			<td>
				<?php
				if($sessions[$i]['SessionPercent'] > 0) {
					echo sprintf('<div style="font-size: 0; height: 5px; width: %s%%; background-color: #888888;"></div>', $sessions[$i]['SessionPercent']);
				} else {
					echo '<div style="font-size: 0; height: 5px;"></div>';
				}
				if($sessions[$i]['SessionItemPercent'] > 0) {
					echo sprintf('<div style="font-size: 0; height: 5px; width: %s%%; background-color: #000000;"></div>', $sessions[$i]['SessionItemPercent']);
				} else {
					echo '<div style="font-size: 0; height: 5px;"></div>';
				}

				if($sessions[$i]['SessionPercent_Bots'] > 0) {
					echo sprintf('<div style="font-size: 0; height: 5px; width: %s%%; background-color: #d0bc13;"></div>', $sessions[$i]['SessionPercent_Bots']);
				} else {
					echo '<div style="font-size: 0; height: 5px;"></div>';
				}
				if($sessions[$i]['SessionItemPercent_Bots'] > 0) {
					echo sprintf('<div style="font-size: 0; height: 5px; width: %s%%; background-color: #b09d00;"></div>', $sessions[$i]['SessionItemPercent_Bots']);
				} else {
					echo '<div style="font-size: 0; height: 5px;"></div>';
				}

				if($sessions[$i]['SessionPercent_NonBots'] > 0) {
					echo sprintf('<div style="font-size: 0; height: 5px; width: %s%%; background-color: #d54b1d;"></div>', $sessions[$i]['SessionPercent_NonBots']);
				} else {
					echo '<div style="font-size: 0; height: 5px;"></div>';
				}
				if($sessions[$i]['SessionItemPercent_NonBots'] > 0) {
					echo sprintf('<div style="font-size: 0; height: 5px; width: %s%%; background-color: #9a3d1f;"></div>', $sessions[$i]['SessionItemPercent_NonBots']);
				} else {
					echo '<div style="font-size: 0; height: 5px;"></div>';
				}
				?>
			</td>
			<td nowrap="nowrap" align="center">
				<a target="blank" href="stat_sessions.php?start=<?php echo $sessions[$i]['Start']; ?>&end=<?php echo $sessions[$i]['End']; ?>&bots=N"><img src="images/icon_search_3.gif" width="16" height="16" alt="View Non Bot Sessions" border="0" /></a>
				<a target="blank" href="stat_sessions.php?start=<?php echo $sessions[$i]['Start']; ?>&end=<?php echo $sessions[$i]['End']; ?>&bots=Y"><img src="images/icon_search_2.gif" width="16" height="16" alt="View Bot Sessions" border="0" /></a>
				<a target="blank" href="stat_sessions.php?start=<?php echo $sessions[$i]['Start']; ?>&end=<?php echo $sessions[$i]['End']; ?>"><img src="images/icon_search_1.gif" width="16" height="16" alt="View Sessions" border="0" /></a>
			</td>
		</tr>

		<?php
	}
	?>

</table>
<br />

<?php
if(($period != 'Y') && ($period != 'M')) {
	if($form->GetValue('showcustomers') == 'Y') {
		?>

		<h3>Logged On Customers</h3>
		<br />

		<table width="100%" border="0">
			<tr>
				<td style="border-bottom:1px solid #aaaaaa" valign="top"><strong>Customer</strong></td>
				<td style="border-bottom:1px solid #aaaaaa" valign="top" align="right"><strong>Sessions</strong></td>
				<td style="border-bottom:1px solid #aaaaaa" valign="top" align="right"><strong>Requests</strong></td>
				<td style="border-bottom:1px solid #aaaaaa" width="50%">&nbsp;</td>
				<td style="border-bottom:1px solid #aaaaaa"><strong>Last Seen On</strong></td>
				<td style="border-bottom:1px solid #aaaaaa">&nbsp;</td>
			</tr>

			<?php
			for($i=0; $i<count($customers); $i++) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td nowrap="nowrap"><?php echo sprintf('<a href="contact_profile.php?cid=%s" target="_blank">%s</a>', $customers[$i]['ContactID'], $customers[$i]['Name']); ?></td>
					<td nowrap="nowrap" align="right"><?php echo number_format($customers[$i]['Sessions'], 0, '.', ','); ?></td>
					<td nowrap="nowrap" align="right"><?php echo number_format($customers[$i]['SessionItems'], 0, '.', ','); ?></td>
					<td>
						<?php
						if($customers[$i]['SessionPercent'] > 0) {
							echo sprintf('<div style="font-size: 0; height: 5px; width: %s%%; background-color: #888888;"></div>', $customers[$i]['SessionPercent']);
						} else {
							echo '<div style="font-size: 0; height: 5px;"></div>';
						}
						if($customers[$i]['SessionItemPercent'] > 0) {
							echo sprintf('<div style="font-size: 0; height: 5px; width: %s%%; background-color: #000000;"></div>', $customers[$i]['SessionItemPercent']);
						} else {
							echo '<div style="font-size: 0; height: 5px;"></div>';
						}
						?>
					</td>
					<td nowrap="nowrap"><?php echo cDatetime($customers[$i]['LastSeenOn'], 'shortdatetime'); ?></td>
				</tr>

				<?php
			}
			?>

		</table>
		<br />

		<?php
	}
}

require_once('lib/common/app_footer.php');
?>