<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$maximumDate = date('Y-m-d');
$maximumDateMonth = substr($maximumDate, 5, 2);
$maximumDateYear = substr($maximumDate, 0, 4);

$minimumDate = date('Y-m-d', mktime(0, 0, 0, 1, 1, 2010));
$minimumDateMonth = substr($minimumDate, 5, 2);
$minimumDateYear = substr($minimumDate, 0, 4);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('month', 'Month', 'select', date('m'), 'anything', 1, 11);
$form->AddOption('month', '', '');

for($i=1; $i<=12; $i++) {          
	$form->AddOption('month', date('m', mktime(0, 0, 0, $i, 1, date('Y'))), date('F', mktime(0, 0, 0, $i, 1, date('Y'))));
}

$form->AddField('year', 'Year', 'select', date('Y'), 'anything', 1, 11);
$form->AddOption('year', '', '');

for($i=$minimumDateYear; $i<=$maximumDateYear; $i++) {
	$form->AddOption('year', $i, $i);
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		redirect(sprintf('Location: %s?year=%s&month=%s&report=true', $_SERVER['PHP_SELF'], $form->GetValue('year'), $form->GetValue('month')));
	}
}

$page = new Page('Report Sage (Export)', 'Listing sage amounts per month.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Select period');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow('Period', $form->GetHTML('month') . $form->GetHTML('year') . $form->GetIcon('year'));
echo $webForm->AddRow('', sprintf('<input type="submit" name="submit" value="submit" class="btn" tabindex="%s" />', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();



function fetchInvoices($start, $end, $connection=null) {
	return new RowSet(sprintf(<<<SQL
SELECT
	DATE_FORMAT(i.Created_On, '%%d') AS DAY,
	if(o.Order_Prefix = 'T', 'Moto', 'E-Commerce') orderType,
	if(o.Card_Type = 'American Express', 'Amex', 'Credit Cards') cardType,
	GROUP_CONCAT(DISTINCT Integration_ID) integrationIds,
	GROUP_CONCAT(DISTINCT Integration_Reference) integrationRefs,
	SUM(Invoice_Total-Invoice_Tax) AS Total
FROM invoice i
JOIN orders o on o.Order_ID = i.Order_ID
WHERE
	i.Integration_ID != ''
	AND i.Created_On >= '%s'
	AND i.Created_On < '%s'
	AND i.Integration_Reference != ''
GROUP BY DAY, orderType, cardType
SQL
	, mysql_real_escape_string($start), mysql_real_escape_string($end)), $connection);
}

function fetchCreditNotes($start, $end, $connection=null) {
	return new RowSet(sprintf(<<<SQL
SELECT
	DATE_FORMAT(cn.Created_On, '%%d') AS DAY,
	if(o.Order_Prefix = 'T', 'Moto', 'E-Commerce') orderType,
	if(o.Card_Type = 'American Express', 'Amex', 'Credit Cards') cardType,
	GROUP_CONCAT(DISTINCT Integration_ID) integrationIds,
	GROUP_CONCAT(DISTINCT Integration_Reference) integrationRefs,
	SUM(cn.TotalNet) AS Total
FROM credit_note cn
JOIN orders o on o.Order_ID = cn.Order_ID
WHERE
	cn.Integration_ID != ''
	AND cn.Created_On >= '%s'
	AND cn.Created_On < '%s'
	AND cn.Integration_Reference != ''
GROUP BY DAY, orderType, cardType
SQL
	, mysql_real_escape_string($start), mysql_real_escape_string($end)), $connection);
}

function outputTable($daysInMonth, $invoicesList) {
	?>
	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color:#eeeeee;">
			<td style="border-bottom:1px solid #dddddd;"><strong>Day</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Site</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Type</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Integration IDs</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Integration References</strong></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Amount</strong></td>
		</tr>

		<?php
		foreach (range(1, $daysInMonth) as $day) {
			$total = 0;
			$day = str_pad($day, 2, "0", STR_PAD_LEFT);
		?>
			<tr>
				<td style="border-top:1px solid #888888;"><strong><?php echo $day; ?></strong></td>
				<td style="border-top:1px solid #888888;" colspan="5">&nbsp;</td>
			</tr>

			<?php foreach($invoicesList as $invoices) { ?>
				<?php foreach($invoices->byGroup("DAY", $day) as $invoice) { ?>
			<tr>
				<td style="border-top:1px solid #dddddd;">&nbsp;</td>
				<td style="border-top:1px solid #dddddd;"><?php echo $invoice->TYPE ?></td>
				<td style="border-top:1px solid #dddddd;"><?php echo $invoice->orderType ?> <?php echo $invoice->cardType ?></td>
				<td style="border-top:1px solid #dddddd;"><?php echo str_replace(",", ",<br />", $invoice->integrationIds) ?></td>
				<td style="border-top:1px solid #dddddd;"><?php echo str_replace(",", ",<br />", $invoice->integrationRefs) ?></td>
				<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($invoice->Total, 2), 2, '.', ','); ?></td>
			</tr>
			<?php
					$total += $invoice->Total;
				}
			}
			?>
			<tr>
				<td style="border-top:1px solid #dddddd; background-color:#f8f8f8;" colspan="5">&nbsp;</td>
				<td style="border-top:1px solid #dddddd; background-color:#f8f8f8;" align="right"><strong>&pound;<?php echo number_format(round($total, 2), 2, '.', ','); ?></strong></td>
			</tr>
		<?php
		}
		?>
	</table>
	<?php
}

if(isset($_REQUEST['report'])) {
	$startYear = $form->GetValue('year');
	$startMonth = $form->GetValue('month');

	$endYear = $form->GetValue('year');
	$endMonth = $form->GetValue('month') + 1;

	if($endMonth > 12) {
		$endMonth -= 12;
		$endYear++;
	}

	$startDate = sprintf('%s-%s-01 00:00:00', $startYear, $startMonth);
	$endDate = sprintf('%s-%s-01 00:00:00', $endYear, $endMonth);
	$daysInMonth = date('t', strtotime($startDate));

	$connection = new MySQLConnection($GLOBALS['SYNC_DB_HOST'][0], $GLOBALS['SYNC_DB_NAME'][0], $GLOBALS['SYNC_DB_USERNAME'][0], $GLOBALS['SYNC_DB_PASSWORD'][0]);
	?>
	
	<br />
	<h3>Summary</h3>
	<p></p>
	
	<?php
	$data = new DataQuery(sprintf("SELECT SUM(Invoice_Total-Invoice_Tax) AS Total FROM invoice WHERE Integration_ID<>'' AND Created_On>='%s' AND Created_On<'%s' AND Integration_Reference<>''", mysql_real_escape_string($startDate), mysql_real_escape_string($endDate)));
	$invoiceTotalBlt = $data->Row['Total'];
	$data->Disconnect();
	

	$data = new DataQuery(sprintf("SELECT SUM(Total-TotalTax) AS Total FROM credit_note WHERE Integration_ID<>'' AND Credited_On>='%s' AND Credited_On<'%s' AND Integration_Reference<>''", mysql_real_escape_string($startDate), mysql_real_escape_string($endDate)));
	$creditTotalBlt = $data->Row['Total'];
	$data->Disconnect();
	?>

	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color:#eeeeee;">
			<td style="border-bottom:1px solid #dddddd;"><strong>Name</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Type</strong></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Amount</strong></td>
		</tr>
		<tr>
			<td style="border-top:1px solid #dddddd;">Invoices</td>
			<td style="border-top:1px solid #dddddd;">BLT</td>
			<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($invoiceTotalBlt, 2), 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td style="border-top:1px solid #dddddd;">Credits</td>
			<td style="border-top:1px solid #dddddd;">BLT</td>
			<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($creditTotalBlt, 2), 2, '.', ','); ?></td>
		</tr>
		
		<tr>
			<td style="border-top:1px solid #dddddd;" colspan="2">&nbsp;</td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong>&pound;<?php echo number_format(round($invoiceTotalBlt - $creditTotalBlt, 2), 2, '.', ','); ?></strong></td>
		</tr>
	</table>
	<br />
	
	<br />
	<h3>Invoice Breakdown</h3>
	<p></p>
	
	<?php
	
	$invoicesBLT = fetchInvoices($startDate, $endDate);
	outputTable($daysInMonth, array($invoicesBLT));

	?>
	<br />
	
	<br />
	<h3>Credit Breakdown</h3>
	<p></p>
	
	<?php
	
	$creditsBLT = fetchCreditNotes($startDate, $endDate);
	outputTable($daysInMonth, array($creditsBLT));

}

$page->Display('footer');