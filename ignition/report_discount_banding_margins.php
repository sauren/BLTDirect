<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Discount Banding Margins Report', 'Please choose a start and end date for your report');
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
	$form->AddField('band', 'Discount Band', 'select', '0', 'numeric_unsigned', 1, 11);
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

				report($start, $end, $form->GetValue('band'));
				exit;
			}
		} else {
			

			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('band'));
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

	$window = new StandardWindow("Report on Discount Banding Margins.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select the discount band to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
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

function report($start, $end, $band){
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DiscountBanding.php');

	$page = new Page('Discount Banding Margins Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

	$data = new DataQuery(sprintf("SELECT Discount_Banding_ID FROM discount_banding ORDER BY Name ASC", ($band > 0) ? sprintf('WHERE Discount_Banding_ID=%d', $band) : ''));
	while($data->Row) {
		$banding = new DiscountBanding($data->Row['Discount_Banding_ID']);
		?>

		<div style="background-color: #f6f6f6; padding: 10px;">
			<p><span class="pageSubTitle"><?php echo $banding->Name; ?></span><br /><span class="pageDescription">Listing orders between the specified period for the <?php echo $banding->Name; ?> discount banding.</span></p>

			<table width="100%" border="0" >
				<tr>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Order Date</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Order Ref.</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Original Sub Total</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Revised Sub Total</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Sub Total Difference</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Markup Discount</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Original Cost</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Revised Cost</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Original Discount</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Revised Discount</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Original Profit</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Revised Profit</strong></td>
					<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Profit Difference</strong></td>
				</tr>

				<?php
				$totalOrgSubTotal = 0;
				$totalRevSubTotal = 0;
				$totalDifSubTotal = 0;
				$totalOrgCost = 0;
				$totalRevCost = 0;
				$totalOrgDiscount = 0;
				$totalRevDiscount = 0;
				$totalOrgProfit = 0;
				$totalrevProfit = 0;
				$totalDifProfit = 0;

				$results = array();
				$data2 = new DataQuery(sprintf("SELECT o.Order_ID, bo.Banding_Discount, bo.SubTotal AS Original_Sub_Total, bo.Created_On, n.Contact_ID, p.Name_First, p.Name_Last, SUM(bol.Cost * bol.Quantity) AS Original_Cost, SUM(bol.Discount) AS Original_Discount, SUM(((bol.Price - bol.Cost) * bol.Quantity) - bol.Discount) AS Original_Profit FROM discount_banding_order AS bo LEFT JOIN discount_banding_order_line AS bol ON bo.Discount_Banding_Order_ID=bol.Discount_Banding_Order_ID INNER JOIN orders AS o ON bo.Order_ID=o.Order_ID INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON c.Contact_ID=n.Contact_ID INNER JOIN person AS p ON p.Person_ID=n.Person_ID WHERE o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND bol.Product_ID>0 AND bol.Cost>0 AND o.Created_On BETWEEN '%s' AND '%s' AND bo.Banding_ID=%d GROUP BY o.Order_ID ORDER BY o.Created_On ASC", $start, $end, mysql_real_escape_string($banding->ID)));
				while($data2->Row) {
					$results[$data2->Row['Order_ID']] = $data2->Row;

					$data2->Next();
				}
				$data2->Disconnect();

				$data2 = new DataQuery(sprintf("SELECT o.Order_ID, o.SubTotal AS Revised_Sub_Total, SUM(ol.Cost * ol.Quantity) AS Revised_Cost, SUM(ol.Line_Discount) AS Revised_Discount, SUM(((ol.Price - ol.Cost) * ol.Quantity) - ol.Line_Discount) AS Revised_Profit FROM discount_banding_order AS bo INNER JOIN orders AS o ON bo.Order_ID=o.Order_ID LEFT JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Product_ID>0 AND ol.Cost>0 AND o.Created_On BETWEEN '%s' AND '%s' AND bo.Banding_ID=%d GROUP BY o.Order_ID", $start, $end, mysql_real_escape_string($banding->ID)));
				while($data2->Row) {
					if(isset($results[$data2->Row['Order_ID']])) {
						foreach($data2->Row as $key=>$dataItem) {
							$results[$data2->Row['Order_ID']][$key] = $dataItem;
						}
					}

					$data2->Next();
				}
				$data2->Disconnect();

				if(count($results) > 0) {
					foreach($results as $result) {
						$totalOrgSubTotal += $result['Original_Sub_Total'];
						$totalRevSubTotal += $result['Revised_Sub_Total'];
						$totalDifSubTotal += $result['Revised_Sub_Total'] - $result['Original_Sub_Total'];
						$totalOrgCost += $result['Original_Cost'];
						$totalRevCost += $result['Revised_Cost'];
						$totalOrgDiscount += $result['Original_Discount'];
						$totalRevDiscount += $result['Revised_Discount'];
						$totalOrgProfit += $result['Original_Profit'];
						$totalRevProfit += $result['Revised_Profit'];
						$totalDifProfit += $result['Revised_Profit'] - $result['Original_Profit'];
						?>

						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td align="left"><?php print cDatetime($result['Created_On'], 'shortdatetime'); ?></td>
							<td align="left"><a href="order_details.php?orderid=<?php print $result['Order_ID']; ?>" target="_blank"><?php print $result['Order_Prefix'].$result['Order_ID']; ?></a></td>
							<td align="left"><a href="contact_profile.php?cid=<?php print $result['Contact_ID']; ?>" target="_blank"><?php print trim(sprintf('%s %s', $result['Name_First'], $result['Name_Last'])); ?></a></td>
							<td align="left">&pound;<?php print number_format($result['Original_Sub_Total'], 2, '.', ','); ?></td>
							<td align="left">&pound;<?php print number_format($result['Revised_Sub_Total'], 2, '.', ','); ?></td>
							<td align="left">&pound;<?php print number_format($result['Revised_Sub_Total'] - $result['Original_Sub_Total'], 2, '.', ','); ?></td>
							<td align="left"><?php print $result['Banding_Discount']; ?>%</td>
							<td align="left">&pound;<?php print number_format($result['Original_Cost'], 2, '.', ','); ?></td>
							<td align="left">&pound;<?php print number_format($result['Revised_Cost'], 2, '.', ','); ?></td>
							<td align="left">&pound;<?php print number_format($result['Original_Discount'], 2, '.', ','); ?></td>
							<td align="left">&pound;<?php print number_format($result['Revised_Discount'], 2, '.', ','); ?></td>
							<td align="left">&pound;<?php print number_format($result['Original_Profit'], 2, '.', ','); ?></td>
							<td align="left">&pound;<?php print number_format($result['Revised_Profit'], 2, '.', ','); ?></td>
							<td align="left">&pound;<?php print number_format($result['Revised_Profit'] - $result['Original_Profit'], 2, '.', ','); ?></td>
						</tr>

						<?php
						//$data2->Next();
					}
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td align="left"><strong>&pound;<?php print number_format($totalOrgSubTotal, 2, '.', ','); ?></strong></td>
						<td align="left"><strong>&pound;<?php print number_format($totalRevSubTotal, 2, '.', ','); ?></strong></td>
						<td align="left"><strong>&pound;<?php print number_format($totalDifSubTotal, 2, '.', ','); ?></strong></td>
						<td>&nbsp;</td>
						<td align="left"><strong>&pound;<?php print number_format($totalOrgCost, 2, '.', ','); ?></strong></td>
						<td align="left"><strong>&pound;<?php print number_format($totalRevCost, 2, '.', ','); ?></strong></td>
						<td align="left"><strong>&pound;<?php print number_format($totalOrgDiscount, 2, '.', ','); ?></strong></td>
						<td align="left"><strong>&pound;<?php print number_format($totalRevDiscount, 2, '.', ','); ?></strong></td>
						<td align="left"><strong>&pound;<?php print number_format($totalOrgProfit, 2, '.', ','); ?></strong></td>
						<td align="left"><strong>&pound;<?php print number_format($totalRevProfit, 2, '.', ','); ?></strong></td>
						<td align="left"><strong>&pound;<?php print number_format($totalDifProfit, 2, '.', ','); ?></strong></td>
					</tr>

					<?php
				} else {
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td colspan="14" align="center">No statistics to report on.</td>
					</tr>

					<?php
				}
				?>

			</table><br />

		</div><br />

		<?php
		$data->Next();
	}
	$data->Disconnect();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
?>