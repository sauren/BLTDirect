<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Introductory Coupons Report', 'Please choose a start and end date for your report');
	$year = cDatetime(getDatetime(), 'y');
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

	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Introductory Coupons.");
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

	$page = new Page('Introductory Coupons Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

	$customers = array();

	$firstOrders = 0;
	$repeatOrders = 0;
	$firstTotal = 0;
	$repeatTotal = 0;
	?>

	<br />

	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Coupon</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="center"><strong>Introduction Count</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Reward Accumulated</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Reward Remaining</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Order Total</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Profit Total</strong></td>
	  </tr>

		<?php
		$totalCouponCounter = 0;
		$totalAccumulated = 0;
		$totalRemaining = 0;
		$totalOrderTotal = 0;
		$totalProfitTotal = 0;

		$customer = new Customer();

		$data = new DataQuery(sprintf("SELECT cu.Customer_ID, SUM(SubTotal) AS OrderTotal, c.Coupon_Ref, COUNT(*) AS Counter, p.Name_First, p.Name_Last FROM orders AS o INNER JOIN coupon AS c ON c.Coupon_ID=o.Coupon_ID INNER JOIN customer AS cu ON cu.Customer_ID=c.Introduced_By INNER JOIN contact AS n ON n.Contact_ID=cu.Contact_ID INNER JOIN person AS p ON p.Person_ID=n.Person_ID WHERE c.Introduced_By>0 AND o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY c.Introduced_By", $start, $end));
		while($data->Row) {
			$customer->Get($data->Row['Customer_ID']);

			$data2 = new DataQuery(sprintf("SELECT SUM((ol.Line_Total-ol.Line_Discount)-(ol.Cost*ol.Quantity)) AS Profit FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN coupon AS c ON c.Coupon_ID=o.Coupon_ID WHERE c.Introduced_By=%d AND o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", $data->Row['Customer_ID'], $start, $end));
			$data3 = new DataQuery(sprintf("SELECT SUM(Discount_Reward) AS Discount_Reward FROM orders WHERE Discount_Reward>0 AND Customer_ID=%d", $data->Row['Customer_ID']));
			?>

		  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td align="left"><?php echo trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])); ?></td>
				<td align="left"><?php echo $data->Row['Coupon_Ref']; ?></td>
				<td align="center"><?php echo $data->Row['Counter']; ?></td>
				<td align="right">&pound;<?php echo number_format($customer->AvailableDiscountReward + $data3->Row['Discount_Reward'], 2, '.', ''); ?></td>
				<td align="right">&pound;<?php echo number_format($customer->AvailableDiscountReward, 2, '.', ''); ?></td>
				<td align="right">&pound;<?php echo number_format($data->Row['OrderTotal'], 2, '.', ''); ?></td>
				<td align="right">&pound;<?php echo number_format($data2->Row['Profit'], 2, '.', ''); ?></td>
			</tr>

			<?php
			$totalCouponCounter += $data->Row['Counter'];
			$totalAccumulated += $customer->AvailableDiscountReward + $data3->Row['Discount_Reward'];
			$totalRemaining += $customer->AvailableDiscountReward;
			$totalOrderTotal += $data->Row['OrderTotal'];
			$totalProfitTotal += $data2->Row['Profit'];

			$data3->Disconnect();
			$data2->Disconnect();
			$data->Next();
		}
		$data->Disconnect();
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td align="left">&nbsp;</td>
			<td align="left">&nbsp;</td>
			<td align="center"><strong><?php echo $totalCouponCounter; ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalAccumulated, 2, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalRemaining, 2, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalOrderTotal, 2, '.', ''); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalProfitTotal, 2, '.', ''); ?></strong></td>
		</tr>
	</table>

	<?php
	$page->Display('footer');
}
?>