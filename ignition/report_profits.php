<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
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

	$page = new Page('Profit Report', 'Please choose a start and end date for your report');
    $page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Profits.");
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
}

function report($start, $end){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$orderTypes = array();
	$orderTypes['W'] = "Website (bltdirect.com)";
	$orderTypes['U'] = "Website (bltdirect.co.uk)";
	$orderTypes['L'] = "Website (lightbulbsuk.co.uk)";
	$orderTypes['M'] = "Mobile";
	$orderTypes['T'] = "Telesales";
	$orderTypes['F'] = "Fax";
	$orderTypes['E'] = "Email";
	$orderTypes['N'] = "Not Received";
	$orderTypes['R'] = "Return";
	$orderTypes['B'] = "Broken";

	$page = new Page('Profit Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');
	?>

	<br />

	<h3>Sale Methods</h3>
	<p>Sale methods statistics on all orders. Values are exclusive of VAT.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Type</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Orders</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Sub Total</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Total</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Profit</strong></td>
		</tr>

		<?php
		new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_order SELECT o.Order_ID, o.Order_Prefix, o.SubTotal AS Total_Sub, o.Total, SUM((ol.Line_Total - ol.Line_Discount) - (ol.Cost * ol.Quantity)) AS Total_Profit FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Order_ID", mysql_real_escape_string($start), mysql_real_escape_string($end)));

		$data = new DataQuery(sprintf("SELECT o.Order_Prefix, SUM(o.Total_Sub) AS Total_Sub, SUM(o.Total) AS Total, COUNT(o.Order_ID) AS Count, SUM(o.Total_Profit) AS Total_Profit FROM temp_order AS o GROUP BY o.Order_Prefix"));
		if($data->TotalRows > 0) {
			$total = 0;
			$totalOrders = 0;
			$totalSub = 0;
			$totalProfit = 0;

			while($data->Row) {
				$total += $data->Row['Total'];
				$totalOrders += $data->Row['Count'];
				$totalSub += $data->Row['Total_Sub'];
				$totalProfit += $data->Row['Total_Profit'];
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $orderTypes[$data->Row['Order_Prefix']]; ?></td>
					<td align="right"><?php echo $data->Row['Count']; ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['Total_Sub'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['Total_Profit'], 2, '.', ','); ?></td>
				</tr>

				<?php
				$data->Next();
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td align="right"><strong><?php echo $totalOrders; ?></strong></td>
				<td align="right"><strong>&pound;<?php echo number_format($totalSub, 2, '.', ','); ?></strong></td>
				<td align="right"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
				<td align="right"><strong>&pound;<?php echo number_format($totalProfit, 2, '.', ','); ?></strong></td>
			</tr>

			<?php
		} else {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="5" align="center">There are no items available for viewing.</td>
			</tr>

			<?php
		}
		$data->Disconnect();

		new DataQuery(sprintf("DROP TABLE temp_order"));
		?>

	</table>
	<br />

	<h3>Sales Representatives</h3>
	<p>Sales representatives statistics on orders. Values are exclusive of VAT.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Sales Representative</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Orders</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Sub Total</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Total</strong></td>
			<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Profit</strong></td>
		</tr>

		<?php
		new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_order SELECT o.Order_ID, o.Owned_By, o.SubTotal AS Total_Sub, o.Total, SUM((ol.Line_Total - ol.Line_Discount) - (ol.Cost * ol.Quantity)) AS Total_Profit FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Order_ID", mysql_real_escape_string($start), mysql_real_escape_string($end)));

		$data = new DataQuery(sprintf("SELECT o.Owned_By, SUM(o.Total_Sub) AS Total_Sub, SUM(o.Total) AS Total, COUNT(o.Order_ID) AS Count, SUM(o.Total_Profit) AS Total_Profit FROM temp_order AS o GROUP BY o.Owned_By"));
		if($data->TotalRows > 0) {
			$total = 0;
			$totalOrders = 0;
			$totalSub = 0;
			$totalProfit = 0;

			while($data->Row) {
				$user = new User($data->Row['Owned_By']);

				$total += $data->Row['Total'];
				$totalOrders += $data->Row['Count'];
				$totalSub += $data->Row['Total_Sub'];
				$totalProfit += $data->Row['Total_Profit'];
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)); ?></td>
					<td align="right"><?php echo $data->Row['Count']; ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['Total_Sub'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['Total_Profit'], 2, '.', ','); ?></td>
				</tr>

				<?php
				$data->Next();
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td align="right"><strong><?php echo $totalOrders; ?></strong></td>
				<td align="right"><strong>&pound;<?php echo number_format($totalSub, 2, '.', ','); ?></strong></td>
				<td align="right"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
				<td align="right"><strong>&pound;<?php echo number_format($totalProfit, 2, '.', ','); ?></strong></td>
			</tr>

			<?php
		} else {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="5" align="center">There are no items available for viewing.</td>
			</tr>

			<?php
		}
		$data->Disconnect();

		new DataQuery(sprintf("DROP TABLE temp_order"));
		?>

	</table>

	<?php
	$page->Display('footer');
}

require_once('lib/common/app_footer.php');