<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Discount Banding Report', 'Please choose a start and end date for your report');
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
	$form->AddField('prefix', 'Order Type', 'select', 'none', 'alpha_numeric', 0, 32, false);
	$form->AddOption('prefix', '', '-- All --');
	$form->AddOption('prefix', 'W', 'Website (.com)');
	$form->AddOption('prefix', 'U', 'Website (.co.uk)');
	$form->AddOption('prefix', 'T', 'Telesales');
	$form->AddOption('prefix', 'E', 'Email');
	$form->AddOption('prefix', 'F', 'Fax');
	$form->AddOption('prefix', 'M', 'Mobile');
	$form->AddField('band', 'Discount Band', 'select', 0, 'numeric_unsigned', 1, 11);
	$form->AddOption('band', '0', '-- All --');

	$data = new DataQuery(sprintf("SELECT * FROM discount_banding ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('band', $data->Row['Discount_Banding_ID'], $data->Row['Name']);
		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 1)) {
			if($form->Validate()){
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

				report($start, $end, $form->GetValue('prefix'), $form->GetValue('band'));
				exit;
			}
		} else {
			

			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('prefix'), $form->GetValue('band'));
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

	$window = new StandardWindow("Report on Discount Banding.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select the type of orders and an optional discount band to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('prefix'), $form->GetHTML('prefix'));
	echo $webForm->AddRow($form->GetLabel('band'), $form->GetHTML('band'));
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

function report($start, $end, $prefix, $band){
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DiscountBanding.php');

	$page = new Page('Discount Banding Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

	$bands = array();
	$banding = new DiscountBanding();
	$summarySubTotal = 0;
	$summaryCost = 0;
	$summaryProfit = 0;
	$summarySaving = 0;

	$sqlPrefix = (strlen($prefix) > 0) ? sprintf("AND o.Order_Prefix='%s'", mysql_real_escape_string($prefix)) : '';
	$sqlBand = ($band > 0) ? sprintf("AND o.Discount_Banding_ID=%d", mysql_real_escape_string($band)) : '';

	$data = new DataQuery(sprintf("SELECT o.Created_On, o.Order_ID, o.SubTotal, o.Discount_Banding_ID, n.Contact_ID, p.Name_First, p.Name_Last, SUM(ol.Cost * ol.Quantity) AS Cost, SUM(ol.Line_Discount) AS Saving, SUM((ol.Price - ol.Cost) * ol.Quantity) AS Profit FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON c.Contact_ID=n.Contact_ID INNER JOIN person AS p ON p.Person_ID=n.Person_ID LEFT JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Created_On BETWEEN '%s' AND '%s' AND Discount_Banding_ID>0 %s %s AND ol.Product_ID>0 AND ol.Cost>0 GROUP BY o.Order_ID ORDER BY o.Created_On ASC", $start, $end, $sqlPrefix, $sqlBand));
	while($data->Row) {
		$bands[$data->Row['Discount_Banding_ID']][] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

	foreach($bands as $id=>$items) {
		if($banding->Get($id)) {
			$index = 1;
			$combinedSubTotal = 0;
			$combinedCost = 0;
			$combinedProfit = 0;
			$combinedSaving = 0;
			?>

			<br />
			<h3><?php echo $banding->Name; ?></h3>
			<p>Listing orders between the specified period for the <?php echo $banding->Name; ?> discount banding.</p>

			<table width="100%" border="0" >
				<tr>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>No.</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Order Date</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Order Ref.</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Sub Total</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Cost</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Profit</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Saving</strong></td>
				</tr>

				<?php
				foreach($items as $item) {
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td align="left">#<?php print $index; ?></td>
						<td align="left"><?php print cDatetime($item['Created_On'], 'shortdatetime'); ?></td>
						<td align="left"><a href="order_details.php?orderid=<?php print $item['Order_ID']; ?>" target="_blank"><?php print $item['Order_Prefix'].$item['Order_ID']; ?></a></td>
						<td align="left"><a href="contact_profile.php?cid=<?php print $item['Contact_ID']; ?>" target="_blank"><?php print sprintf("%s %s", $item['Name_First'], $item['Name_Last']); ?></a></td>
						<td align="left">&pound;<?php print number_format($item['SubTotal'], 2, '.', ','); ?></td>
						<td align="left">&pound;<?php print number_format($item['Cost'], 2, '.', ','); ?></td>
						<td align="left">&pound;<?php print number_format($item['Profit'], 2, '.', ','); ?></td>
						<td align="left">&pound;<?php print number_format($item['Saving'], 2, '.', ','); ?></td>
					</tr>

					<?php
					$index++;
					$combinedSubTotal += $item['SubTotal'];
					$combinedCost += $item['Cost'];
					$combinedProfit += $item['Profit'];
					$combinedSaving += $item['Saving'];
				}
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td align="left">&nbsp;</td>
					<td align="left">&nbsp;</td>
					<td align="left">&nbsp;</td>
					<td align="left">&nbsp;</td>
					<td align="left"><strong>&pound;<?php print number_format($combinedSubTotal, 2, '.', ','); ?></strong></td>
					<td align="left"><strong>&pound;<?php print number_format($combinedCost, 2, '.', ','); ?></strong></td>
					<td align="left"><strong>&pound;<?php print number_format($combinedProfit, 2, '.', ','); ?></strong></td>
					<td align="left"><strong>&pound;<?php print number_format($combinedSaving, 2, '.', ','); ?></strong></td>
				</tr>
			</table><br />

			<?php
			$summarySubTotal += $combinedSubTotal;
			$summaryCost += $combinedCost;
			$summaryProfit += $combinedProfit;
			$summarySaving += $combinedSaving;
		}
	}
	?>

	<br />
	<h3>Overall Summary</h3>
	<p>The combined totals of all above discount bandings.</p>

	<table width="100%" border="0" >
		<tr>
			<td align="left" width="50%" style="background-color: #eee"><strong>Sub Total</strong></td>
			<td align="left" style="background-color: #eee">&pound;<?php print number_format($summarySubTotal, 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td align="left" style="background-color: #f9f9f9"><strong>Cost</strong></td>
			<td align="left" style="background-color: #f9f9f9">&pound;<?php print number_format($summaryCost, 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td align="left" style="background-color: #eee"><strong>Profit</strong></td>
			<td align="left" style="background-color: #eee">&pound;<?php print number_format($summaryProfit, 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td align="left" style="background-color: #f9f9f9"><strong>Saving</strong></td>
			<td align="left" style="background-color: #f9f9f9">&pound;<?php print number_format($summarySaving, 2, '.', ','); ?></td>
		</tr>
	</table>

	<?php
	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
?>