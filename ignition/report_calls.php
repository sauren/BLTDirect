<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
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

	$page = new Page('Calls Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Calls.");
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

function report($start, $end) {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$calls = array();

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_person SELECT Person_ID, Name_First, Name_Last, REPLACE(Phone_1, ' ', '') AS Phone FROM person WHERE Phone_1<>''"));
	new DataQuery(sprintf("ALTER TABLE temp_person ADD INDEX Phone (Phone)"));

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_person_grouped SELECT * FROM temp_person GROUP BY Phone"));
	new DataQuery(sprintf("ALTER TABLE temp_person_grouped ADD PRIMARY KEY (Person_ID)"));
	new DataQuery(sprintf("ALTER TABLE temp_person_grouped ADD INDEX Phone (Phone)"));

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_contact SELECT cn.Contact_ID, COUNT(c.Call_ID) AS Count, SUM(c.Duration) AS Total_Duration, SUM(c.Cost) AS Total_Cost, ct.Phone_Number AS Call_To, ct.Description, ct.Type, cf.Phone_Number AS Call_From, p.Person_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Customer_Name, cn.Is_Customer, cn.Is_Supplier FROM `call` AS c INNER JOIN call_to AS ct ON ct.Call_To_ID=c.Call_To_ID INNER JOIN call_from AS cf ON cf.Call_From_ID=c.Call_From_ID LEFT JOIN temp_person_grouped AS p ON p.Phone=ct.Phone_Number LEFT JOIN contact AS cn ON cn.Person_ID=p.Person_ID LEFT JOIN contact AS cn2 ON cn.Parent_Contact_ID=cn2.Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=cn2.Org_ID WHERE c.Called_On>='%s' AND c.Called_On<'%s' GROUP BY c.Call_To_ID, c.Call_From_ID", $start, $end));

	$data = new DataQuery(sprintf("SELECT c.Contact_ID, c.Total_Duration, c.Total_Cost, c.Call_To, c.Description, c.Type, c.Call_From, c.Customer_Name, c.Is_Customer, c.Is_Supplier, SUM(c.Count) AS Count, COUNT(DISTINCT o.Order_ID) AS Orders, COUNT(DISTINCT cs.Contact_Schedule_ID) AS Schedules FROM temp_contact AS c LEFT JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID LEFT JOIN orders AS o ON o.Customer_ID=cu.Customer_ID AND o.Created_On>='%s' AND o.Created_On<'%s' LEFT JOIN contact_schedule AS cs ON cs.Contact_ID=c.Contact_ID AND cs.Is_Complete='Y' AND cs.Completed_On>='%s' AND cs.Completed_On<'%s' GROUP BY c.Call_To, c.Call_From ORDER BY Count DESC", $start, $end, $start, $end));
	while($data->Row) {
		$calls[] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

	$page = new Page('Calls Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');
	?>

	<br />
	<h3>All Calls</h3>
	<p>Listing duration of calls made to each phone number between the given dates.</p>

	<table width="100%" border="0">
		<tr>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Source No.</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Destination No.</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Destination</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Type</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Customer</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Calls Made</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Average Duration</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Total Duration</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Total Cost (&pound;)</strong></td>
		</tr>

		<?php
		if(count($calls) > 0) {
			$totalCount = 0;
			$totalDuration = 0;
			$totalCost = 0;

			foreach($calls as $call) {
				$totalCount += $call['Count'];
				$totalDuration += $call['Total_Duration'];
				$totalCost += $call['Total_Cost'];
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $call['Call_From']; ?></td>
					<td><?php echo $call['Call_To']; ?></td>
					<td><?php echo $call['Description']; ?></td>
					<td><?php echo $call['Type']; ?></td>
					<td><?php echo $call['Customer_Name']; ?></td>
					<td><?php echo $call['Count']; ?></td>
					<td align="right"><?php echo getDuration($call['Total_Duration'] / $call['Count']); ?></td>
					<td align="right"><?php echo getDuration($call['Total_Duration']); ?></td>
					<td align="right"><?php echo $call['Total_Cost']; ?></td>
				</tr>

				<?php
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td><strong><?php echo $totalCount; ?></strong></td>
				<td align="right"><strong><?php echo getDuration($totalDuration / $totalCount); ?></strong></td>
				<td align="right"><strong><?php echo getDuration($totalDuration); ?></strong></td>
				<td align="right"><strong><?php echo number_format($totalCost, 4, '.', ','); ?></strong></td>
			</tr>

			<?php
		} else {
			?>

			<tr>
				<td colspan="9" align="center">There are no items available for viewing.</td>
			</tr>

			<?php
		}
		?>

	</table>

	<br />
	<h3>Supplier Calls</h3>
	<p>Listing duration of calls made to each supplier phone number between the given dates.</p>

	<table width="100%" border="0">
		<tr>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Source No.</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Destination No.</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Destination</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Type</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Customer</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Calls Made</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Average Duration</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Total Duration</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Total Cost (&pound;)</strong></td>
		</tr>

		<?php
		$count = 0;

		foreach($calls as $call) {
			if($call['Is_Supplier'] == 'Y') {
				$count++;
				break;
			}
		}

		if($count > 0) {
			$totalCount = 0;
			$totalDuration = 0;
			$totalCost = 0;

			foreach($calls as $call) {
				if($call['Is_Supplier'] == 'Y') {
					$totalCount += $call['Count'];
					$totalDuration += $call['Total_Duration'];
					$totalCost += $call['Total_Cost'];
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo $call['Call_From']; ?></td>
						<td><?php echo $call['Call_To']; ?></td>
						<td><?php echo $call['Description']; ?></td>
						<td><?php echo $call['Type']; ?></td>
						<td><?php echo $call['Customer_Name']; ?></td>
						<td><?php echo $call['Count']; ?></td>
						<td align="right"><?php echo getDuration($call['Total_Duration'] / $call['Count']); ?></td>
						<td align="right"><?php echo getDuration($call['Total_Duration']); ?></td>
						<td align="right"><?php echo $call['Total_Cost']; ?></td>
					</tr>

					<?php
				}
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td><strong><?php echo $totalCount; ?></strong></td>
				<td align="right"><strong><?php echo getDuration($totalDuration / $totalCount); ?></strong></td>
				<td align="right"><strong><?php echo getDuration($totalDuration); ?></strong></td>
				<td align="right"><strong><?php echo number_format($totalCost, 4, '.', ','); ?></strong></td>
			</tr>

			<?php
		} else {
			?>

			<tr>
				<td colspan="9" align="center">There are no items available for viewing.</td>
			</tr>

			<?php
		}
		?>

	</table>

	<br />
	<h3>Customer Calls</h3>
	<p>Listing duration of calls made to each customer phone number between the given dates.</p>

	<table width="100%" border="0">
		<tr>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Source No.</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Destination No.</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Destination</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Type</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Customer</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Calls Made</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Average Duration</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Total Duration</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Total Cost (&pound;)</strong></td>
		</tr>

		<?php
		$count = 0;

		foreach($calls as $call) {
			if($call['Is_Customer'] == 'Y') {
				$count++;
				break;
			}
		}

		if($count > 0) {
			$totalCount = 0;
			$totalDuration = 0;
			$totalCost = 0;

			foreach($calls as $call) {
				if($call['Is_Customer'] == 'Y') {
					$totalCount += $call['Count'];
					$totalDuration += $call['Total_Duration'];
					$totalCost += $call['Total_Cost'];
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo $call['Call_From']; ?></td>
						<td><?php echo $call['Call_To']; ?></td>
						<td><?php echo $call['Description']; ?></td>
						<td><?php echo $call['Type']; ?></td>
						<td><?php echo $call['Customer_Name']; ?></td>
						<td><?php echo $call['Count']; ?></td>
						<td align="right"><?php echo getDuration($call['Total_Duration'] / $call['Count']); ?></td>
						<td align="right"><?php echo getDuration($call['Total_Duration']); ?></td>
						<td align="right"><?php echo $call['Total_Cost']; ?></td>
					</tr>

					<?php
				}
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td><strong><?php echo $totalCount; ?></strong></td>
				<td align="right"><strong><?php echo getDuration($totalDuration / $totalCount); ?></strong></td>
				<td align="right"><strong><?php echo getDuration($totalDuration); ?></strong></td>
				<td align="right"><strong><?php echo number_format($totalCost, 4, '.', ','); ?></strong></td>
			</tr>

			<?php
		} else {
			?>

			<tr>
				<td colspan="9" align="center">There are no items available for viewing.</td>
			</tr>

			<?php
		}
		?>

	</table>

	<br />
	<h3>Ordered Customer Calls</h3>
	<p>Listing duration of calls made to each ordered customer phone number between the given dates.</p>

	<table width="100%" border="0">
		<tr>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Source No.</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Destination No.</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Destination</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Type</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Customer</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Orders</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Calls Made</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Average Duration</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Total Duration</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Total Cost (&pound;)</strong></td>
		</tr>

		<?php
		$count = 0;

		foreach($calls as $call) {
			if(($call['Is_Customer'] == 'Y') && ($call['Orders'] > 0)) {
				$count++;
				break;
			}
		}

		if($count > 0) {
			$totalOrders = 0;
			$totalCount = 0;
			$totalDuration = 0;
			$totalCost = 0;

			foreach($calls as $call) {
				if(($call['Is_Customer'] == 'Y') && ($call['Orders'] > 0)) {
					$totalOrders += $call['Orders'];
					$totalCount += $call['Count'];
					$totalDuration += $call['Total_Duration'];
					$totalCost += $call['Total_Cost'];
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo $call['Call_From']; ?></td>
						<td><?php echo $call['Call_To']; ?></td>
						<td><?php echo $call['Description']; ?></td>
						<td><?php echo $call['Type']; ?></td>
						<td><?php echo $call['Customer_Name']; ?></td>
						<td><?php echo $call['Orders']; ?></td>
						<td><?php echo $call['Count']; ?></td>
						<td align="right"><?php echo getDuration($call['Total_Duration'] / $call['Count']); ?></td>
						<td align="right"><?php echo getDuration($call['Total_Duration']); ?></td>
						<td align="right"><?php echo $call['Total_Cost']; ?></td>
					</tr>

					<?php
				}
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td><strong><?php echo $totalOrders; ?></strong></td>
				<td><strong><?php echo $totalCount; ?></strong></td>
				<td align="right"><strong><?php echo getDuration($totalDuration / $totalCount); ?></strong></td>
				<td align="right"><strong><?php echo getDuration($totalDuration); ?></strong></td>
				<td align="right"><strong><?php echo number_format($totalCost, 4, '.', ','); ?></strong></td>
			</tr>

			<?php
		} else {
			?>

			<tr>
				<td colspan="9" align="center">There are no items available for viewing.</td>
			</tr>

			<?php
		}
		?>

	</table>

	<br />
	<h3>Completed Schedule Calls</h3>
	<p>Listing duration of calls made to each completed schedule phone number between the given dates.</p>

	<table width="100%" border="0">
		<tr>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Source No.</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Destination No.</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Destination</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Type</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Customer</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Schedules</strong></td>
			<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Calls Made</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Average Duration</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Total Duration</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Total Cost (&pound;)</strong></td>
		</tr>

		<?php
		$count = 0;

		foreach($calls as $call) {
			if($call['Schedules'] > 0) {
				$count++;
				break;
			}
		}

		if($count > 0) {
			$totalSchedules = 0;
			$totalCount = 0;
			$totalDuration = 0;
			$totalCost = 0;

			foreach($calls as $call) {
				if($call['Schedules'] > 0) {
					$totalSchedules += $call['Schedules'];
					$totalCount += $call['Count'];
					$totalDuration += $call['Total_Duration'];
					$totalCost += $call['Total_Cost'];
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo $call['Call_From']; ?></td>
						<td><?php echo $call['Call_To']; ?></td>
						<td><?php echo $call['Description']; ?></td>
						<td><?php echo $call['Type']; ?></td>
						<td><?php echo $call['Customer_Name']; ?></td>
						<td><?php echo $call['Schedules']; ?></td>
						<td><?php echo $call['Count']; ?></td>
						<td align="right"><?php echo getDuration($call['Total_Duration'] / $call['Count']); ?></td>
						<td align="right"><?php echo getDuration($call['Total_Duration']); ?></td>
						<td align="right"><?php echo $call['Total_Cost']; ?></td>
					</tr>

					<?php
				}
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td><strong><?php echo $totalSchedules; ?></strong></td>
				<td><strong><?php echo $totalCount; ?></strong></td>
				<td align="right"><strong><?php echo getDuration($totalDuration / $totalCount); ?></strong></td>
				<td align="right"><strong><?php echo getDuration($totalDuration); ?></strong></td>
				<td align="right"><strong><?php echo number_format($totalCost, 4, '.', ','); ?></strong></td>
			</tr>

			<?php
		} else {
			?>

			<tr>
				<td colspan="9" align="center">There are no items available for viewing.</td>
			</tr>

			<?php
		}
		?>

	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>