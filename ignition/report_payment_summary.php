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

$page = new Page('Payment Summary Report', 'Summarising SagePay payments.');
$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow("Report on Payments");
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
	$endDate = sprintf('%s-%s-%s', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2));
	?>
	
	<br />
	<h3>Summary</h3>
	<p></p>
	
	<?php

	function statQuery($types, $start, $end) {
		$typeQuery = array();

		foreach ($types as $type) {
			$typeQuery[] = "'{$type}'";
		}

		$typeQuery = "(" . join(", ", $typeQuery) . ")";

		return new RowSet(sprintf(<<<SQL
SELECT
	if(o.Order_Prefix = 'T', 'Moto', 'E-Commerce') orderType,
	if(o.Card_Type = 'American Express', 'Amex', 'Credit Cards') cardType,
	sum(if(o.Billing_Country_ID=222 and o.TotalTax>0, p.Amount, 0)) taxedUk,
	sum(if(o.Billing_Country_ID!=222 and o.TotalTax>0, p.Amount, 0)) taxedNonUk,
	sum(if(o.Billing_Country_ID=222 and o.TotalTax=0, p.Amount, 0)) nonTaxedUk,
	sum(if(o.Billing_Country_ID!=222 and o.TotalTax=0, p.Amount, 0)) nonTaxedNonUk,
	sum(p.Amount) total
FROM payment AS p
INNER JOIN orders AS o ON p.Order_ID=o.Order_ID
WHERE
	p.Transaction_Type IN {$typeQuery}
	AND p.Status='OK'
	AND p.Created_On>='%s'
	AND p.Created_On<'%s'
	AND p.Security_Key NOT LIKE '%%GoogleCheckout%%'
GROUP BY orderType, cardType
SQL
		, $start, $end));
	}

	$invoices = statQuery(array('PAYMENT', 'AUTHORISE'), $startDate, $endDate);
	$credits = statQuery(array('REFUND'), $startDate, $endDate);


function outputTable($invoices) {
	?>
	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color:#eeeeee;">
			<td style="border-bottom:1px solid #dddddd;"><strong>Name</strong></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Taxed UK</strong></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Taxed Non-UK</strong></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Tax Free UK</strong></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Tax Free Non-UK</strong></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Total</strong></td>
		</tr>
		<?php foreach ($invoices as $invoice) { ?>
		<tr>
			<td style="border-top:1px solid #dddddd;"><?php echo $invoice->orderType ?> <?php echo $invoice->cardType ?></td>
			<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($invoice->taxedUk, 2), 2, '.', ','); ?></td>
			<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($invoice->taxedNonUk, 2), 2, '.', ','); ?></td>
			<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($invoice->nonTaxedUk, 2), 2, '.', ','); ?></td>
			<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($invoice->nonTaxedNonUk, 2), 2, '.', ','); ?></td>
			<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($invoice->total, 2), 2, '.', ','); ?></td>
		</tr>
		<?php } ?>
		<tr>
			<td style="border-top:1px solid #cccccc;"><strong>All</strong></td>
			<td style="border-top:1px solid #cccccc;" align="right"><strong>&pound;<?php echo number_format(round(arraySumInner($invoices, "taxedUk"), 2), 2, '.', ','); ?></strong></td>
			<td style="border-top:1px solid #cccccc;" align="right"><strong>&pound;<?php echo number_format(round(arraySumInner($invoices, "taxedNonUk"), 2), 2, '.', ','); ?></strong></td>
			<td style="border-top:1px solid #cccccc;" align="right"><strong>&pound;<?php echo number_format(round(arraySumInner($invoices, "nonTaxedUk"), 2), 2, '.', ','); ?></strong></td>
			<td style="border-top:1px solid #cccccc;" align="right"><strong>&pound;<?php echo number_format(round(arraySumInner($invoices, "nonTaxedNonUk"), 2), 2, '.', ','); ?></strong></td>
			<td style="border-top:1px solid #cccccc; background-color: #000000; color: #ffffff;" align="right"><strong>&pound;<?php echo number_format(round(arraySumInner($invoices, "total"), 2), 2, '.', ','); ?></strong></td>
		</tr>
	</table>
	<?php
}
	?>
	
	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color:#eeeeee;">
			<td style="border-bottom:1px solid #dddddd;"><strong>Name</strong></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Amount</strong></td>
		</tr>
		<tr>
			<td style="border-top:1px solid #dddddd;">Invoices</td>
			<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round(arraySumInner($invoices, "total"), 2), 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td style="border-top:1px solid #dddddd;">Credits</td>
			<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round(arraySumInner($credits, "total"), 2), 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td style="border-top:1px solid #dddddd;">&nbsp;</td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong>&pound;<?php echo number_format(round(arraySumInner($invoices, "total") - arraySumInner($credits, "total"), 2), 2, '.', ','); ?></strong></td>
		</tr>
	</table>
	<br />
	
	<br />
	<h3>Invoices</h3>
	<p></p>

	<?php outputTable($invoices) ?>
	<br />
	
	<br />
	<h3>Credits</h3>
	<p></p>
	
	<?php outputTable($credits) ?>
	
	<?php
}

$page->Display('footer');