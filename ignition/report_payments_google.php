<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Google Payments Report', 'Please choose a start and end date for your report');
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

			report($start, $end, $form->GetValue('parent'), ($form->GetValue('subfolders') =='Y') ? true : false);
			exit;
		} else {
			
			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))),$form->GetValue('parent'),($form->GetValue('subfolders') =='Y')?true:false);
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

	$window = new StandardWindow("Report on Google Payments.");
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

	$page = new Page('Google Payments Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), 'This report is only applicable if you are using a payment gateway to take credit card payments automatically.');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

	?>
	<br />
	<h3>Payments Taken During Period</h3>
	<table width="100%" border="0" cellpadding="3" cellspacing="0">
	  <tr style="background-color:#eeeeee;">
	  	<td style="border-bottom:1px solid #dddddd;"><strong>Payment ID</strong></td>
	  	<td style="border-bottom:1px solid #dddddd;"><strong>Order ID</strong></td>
		<td style="border-bottom:1px solid #dddddd;"><strong>Date</strong></td>
		<td style="border-bottom:1px solid #dddddd;"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #dddddd;"><strong>Payment Amount</strong></td>
		<td style="border-bottom:1px solid #dddddd;"><strong>Invoice Amount</strong></td>
		<td style="border-bottom:1px solid #dddddd;"><strong>Difference</strong></td>
	  </tr>
<?php
// get all payments taken over time period
$data = new DataQuery(sprintf("select p.Payment_ID, p.Amount, p.Order_ID, o.Order_ID, o.Order_Prefix, o.Billing_Title, o.Billing_First_Name, o.Billing_Initial, o.Billing_Last_Name, p.Created_On FROM orders as o INNER JOIN payment AS p ON p.Order_ID=o.Order_ID where p.Transaction_Type='PAYMENT' and p.Status='OK' and p.Created_On between '%s' and '%s' AND p.Security_Key LIKE '%%GoogleCheckout%%' GROUP BY p.Payment_ID order by p.Created_On asc", mysql_real_escape_string($start), mysql_real_escape_string($end)));

$total = 0;
$invoiceNet = 0;
$invoiceShipping = 0;
$invoiceDiscount = 0;
$invoiceTax = 0;
$invoiceTotal = 0;
$net = 0;

$differenceTotal = 0;

while($data->Row){
	$data2 = new DataQuery(sprintf("select SUM(i.Invoice_Net) AS Invoice_Net, SUM(i.Invoice_Shipping) AS Invoice_Shipping, SUM(i.Invoice_Discount) AS Invoice_Discount, SUM(i.Invoice_Tax) AS Invoice_Tax, SUM(i.Invoice_Total) AS Invoice_Total from invoice AS i where i.Order_ID=%d GROUP BY i.Order_ID", $data->Row['Order_ID']));

	$diff = number_format($data2->Row['Invoice_Total'] - $data->Row['Amount'], 2, '.', '');
	if(($diff > 0.02) || ($diff < -0.02)) {
		$differenceTotal += $data2->Row['Invoice_Total'] - $data->Row['Amount'];
		$styling = 'style="color:#ff0000; font-weight:bold;"';
		$styling2 = 'style="background-color:#FFE5E6;"';
		$difference = '&pound;'.number_format(($data2->Row['Invoice_Total'] - $data->Row['Amount']), 2, '.', ',');
	} else {
		$styling = '';
		$styling2 = '';
		$difference = '&nbsp;';
	}

	echo sprintf('<tr %s><td>%d</td><td><a href="order_details.php?orderid=%d">%s%d</a></td><td>%s</td><td>%s %s %s %s</td><td align="right" %s>&pound;%s</td><td align="right" %s>&pound;%s</td><td align="right" %s>%s</td></tr>',
	$styling2,
	$data->Row['Payment_ID'],
	$data->Row['Order_ID'],
	$data->Row['Order_Prefix'],
	$data->Row['Order_ID'],
	cDatetime($data->Row['Created_On'], 'longdatetime'),
	$data->Row['Billing_Title'],
	$data->Row['Billing_First_Name'],
	$data->Row['Billing_Initial'],
	$data->Row['Billing_Last_Name'],
	$styling,
	number_format($data->Row['Amount'], 2, '.', ','),
	$styling,
	number_format($data2->Row['Invoice_Total'], 2, '.', ','),
	$styling,
	$difference);
	$total += $data->Row['Amount'];
	$invoiceNet += $data2->Row['Invoice_Net'];
	$invoiceShipping += $data2->Row['Invoice_Shipping'];
	$invoiceDiscount += $data2->Row['Invoice_Discount'];
	$invoiceTax += $data2->Row['Invoice_Tax'];
	$invoiceTotal += $data2->Row['Invoice_Total'];

	$data2->Disconnect();

	$data->Next();
}
$data->Disconnect();
// get all refunds taken over period
// get all preAuthorisations taken over period
// get all failed transactions
	?>
	   <tr style="background-color:#eeeeee;">
	  	<td style="border-top:1px solid #dddddd;" colspan="4"><strong>Payments Total:</strong></td>
		<td align="right" style="border-top:1px solid #dddddd;"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>

		<?php
		if(($differenceTotal > 0.02) || ($differenceTotal < -0.02)) {
			echo '<td align="right" style="border-top:1px solid #dddddd; background-color:#FFE5E6;" colspan="2">&pound;' . number_format($differenceTotal, 2, '.', ',') . '</strong></td>';
		} else {
		?>
			<td style="border-top:1px solid #dddddd;" colspan="2">&nbsp;</td>
		<?php
		}
		?>
		</td>
	  </tr>
	  <tr style="background-color:#eeeeee;">
	  	<td style="border-top:1px solid #dddddd;" colspan="4"><strong>Payments Invoiced - Subtotal:</strong></td>
		<td align="right" style="border-top:1px solid #dddddd;"><strong>&pound;<?php echo number_format($invoiceNet, 2, '.', ','); ?></strong></td>
		<td style="border-top:1px solid #dddddd;" colspan="2">&nbsp;</td>
	  </tr>
	  <tr style="background-color:#eeeeee;">
	  	<td style="border-top:1px solid #dddddd;" colspan="4"><strong>Payments Invoiced - Shipping Total:</strong></td>
		<td align="right" style="border-top:1px solid #dddddd;"><strong>&pound;<?php echo number_format($invoiceShipping, 2, '.', ','); ?></strong></td>
		<td style="border-top:1px solid #dddddd;" colspan="2">&nbsp;</td>
	  </tr>
	  <tr style="background-color:#eeeeee;">
	  	<td style="border-top:1px solid #dddddd;" colspan="4"><strong>Payments Invoiced - Discount Total:</strong></td>
		<td align="right" style="border-top:1px solid #dddddd;"><strong>-&pound;<?php echo number_format($invoiceDiscount, 2, '.', ','); ?></strong></td>
		<td style="border-top:1px solid #dddddd;" colspan="2">&nbsp;</td>
	  </tr>
	  <tr style="background-color:#eeeeee;">
	  	<td style="border-top:1px solid #dddddd;" colspan="4"><strong>Payments Invoiced - Net Total:</strong></td>
		<td align="right" style="border-top:1px solid #dddddd;"><strong>&pound;<?php echo number_format(($invoiceNet + $invoiceShipping - $invoiceDiscount), 2, '.', ','); ?></strong></td>
		<td style="border-top:1px solid #dddddd;" colspan="2">&nbsp;</td>
	  </tr>
	  <tr style="background-color:#eeeeee;">
	  	<td style="border-top:1px solid #dddddd;" colspan="4"><strong>Payments Invoiced - Total Tax:</strong></td>
		<td align="right" style="border-top:1px solid #dddddd;"><strong>&pound;<?php echo number_format($invoiceTax, 2, '.', ','); ?></strong></td>
		<td style="border-top:1px solid #dddddd;" colspan="2">&nbsp;</td>
	  </tr>
	  <tr style="background-color:#eeeeee;">
	  	<td style="border-top:1px solid #dddddd;" colspan="4"><strong>Payments Invoiced - Gross Total:</strong></td>
		<td align="right" style="border-top:1px solid #dddddd;"><strong>&pound;<?php echo number_format($invoiceTotal, 2, '.', ','); ?></strong></td>
		<td style="border-top:1px solid #dddddd;" colspan="2">&nbsp;</td>
	  </tr>
	</table>

	<p><br /></p>
	<h3>Refunds During Period</h3>
	<table width="100%" border="0" cellpadding="3" cellspacing="0">
	  <tr style="background-color:#eeeeee;">
	  	<td style="border-bottom:1px solid #dddddd;"><strong>Payment ID</strong></td>
	  	<td style="border-bottom:1px solid #dddddd;"><strong>Order ID</strong></td>
		<td style="border-bottom:1px solid #dddddd;"><strong>Date</strong></td>
		<td style="border-bottom:1px solid #dddddd;"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #dddddd;"><strong>Amount</strong></td>
	  </tr>
<?php

// get all payments taken over time period
$sql = sprintf("select p.Payment_ID, p.Amount, p.Order_ID, o.* from payment as p left join orders as o on p.Order_ID=o.Order_ID where p.Transaction_Type='REFUND' and p.Status='OK' and p.Created_On between '%s' and '%s' AND p.Security_Key LIKE '%%GoogleCheckout%%' order by p.Created_On asc", mysql_real_escape_string($start), mysql_real_escape_string($end));
$data = new DataQuery($sql);
$total = 0;
while($data->Row){
	echo sprintf('<tr><td>%d</td><td><a href="order_details.php?orderid=%d">%s%d</a></td><td>%s</td><td>%s %s %s %s</td><td align="right">&pound;%s</td></tr>',
	$data->Row['Payment_ID'],
	$data->Row['Order_ID'],
	$data->Row['Order_Prefix'],
	$data->Row['Order_ID'],
	cDatetime($data->Row['Created_On'], 'longdatetime'),
	$data->Row['Billing_Title'],
	$data->Row['Billing_First_Name'],
	$data->Row['Billing_Initial'],
	$data->Row['Billing_Last_Name'],
	number_format($data->Row['Amount'], 2, '.', ','));
	$total += $data->Row['Amount'];
	$data->Next();
}
$data->Disconnect();
// get all refunds taken over period
// get all preAuthorisations taken over period
// get all failed transactions
	?>
		<tr style="background-color:#eeeeee;">
			<td style="border-top:1px solid #dddddd;"><strong>Total:</strong></td>
			<td style="border-top:1px solid #dddddd;">&nbsp;</td>
			<td style="border-top:1px solid #dddddd;">&nbsp;</td>
			<td style="border-top:1px solid #dddddd;">&nbsp;</td>
			<td align="right" style="border-top:1px solid #dddddd;"><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
		</tr>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>
