<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

$form = new Form($_SERVER['PHP_SELF'],'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('start', 'Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('end', 'End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		redirectTo(sprintf('?start=%s&end=%s&report=true', $form->GetValue('start'), $form->GetValue('end')));
	}
}

$page = new Page('Tax Free Google Payments Report', 'Please choose a start and end date for your report');
$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br / >';
}

$window = new StandardWindow("Report on Payments.");
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start'));
echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end'));
echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

if(isset($_REQUEST['report'])) {
	$startDate = sprintf('%s-%s-%s', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2));
	$endDate = (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2))))));
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
	$data = new DataQuery(sprintf("select p.Payment_ID, p.Amount, p.Order_ID, o.Order_ID, o.Order_Prefix, o.Billing_Title, o.Billing_First_Name, o.Billing_Initial, o.Billing_Last_Name, p.Created_On FROM orders as o INNER JOIN payment AS p ON p.Order_ID=o.Order_ID where p.Transaction_Type='PAYMENT' and p.Status='OK' and p.Created_On between '%s' and '%s' AND p.Security_Key LIKE '%%GoogleCheckout%%' AND o.TotalTax=0 GROUP BY p.Payment_ID order by p.Created_On asc", $startDate, $endDate));

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
	$sql = sprintf("select p.Payment_ID, p.Amount, p.Order_ID, o.* from payment as p left join orders as o on p.Order_ID=o.Order_ID where p.Transaction_Type='REFUND' and p.Status='OK' and p.Created_On between '%s' and '%s' AND p.Security_Key LIKE '%%GoogleCheckout%%' AND o.TotalTax=0 order by p.Created_On asc", $startDate, $endDate);
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
}

$page->Display('footer');
require_once('lib/common/app_footer.php');