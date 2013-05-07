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

$page = new Page('Report Sage (Manual)', 'Listing sage amounts per month.');
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

	$connection = new MySQLConnection($GLOBALS['SYNC_DB_HOST'][0], $GLOBALS['SYNC_DB_NAME'][0], $GLOBALS['SYNC_DB_USERNAME'][0], $GLOBALS['SYNC_DB_PASSWORD'][0]);
	?>
	
	<br />
	<h3>Summary</h3>
	<p></p>
	
	<?php
	$data = new DataQuery(sprintf("SELECT SUM(Invoice_Total-Invoice_Tax) AS Total FROM invoice WHERE Integration_ID<>'' AND Created_On>='%s' AND Created_On<'%s' AND Integration_Reference=''", mysql_real_escape_string($startDate), mysql_real_escape_string($endDate)));
	$invoiceTotalBlt = $data->Row['Total'];
	$data->Disconnect();
	

	
	$data = new DataQuery(sprintf("SELECT SUM(Total-TotalTax) AS Total FROM credit_note WHERE Integration_ID<>'' AND Credited_On>='%s' AND Credited_On<'%s' AND Integration_Reference=''", mysql_real_escape_string($startDate), mysql_real_escape_string($endDate)));
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
	$days = array();
	
	for($i=1; $i<=date('t', strtotime($startDate)); $i++) {
		$days[$i] = array();
	}
	
	$data = new DataQuery(sprintf("SELECT Integration_ID, Integration_Reference, SUM(Invoice_Total-Invoice_Tax) AS Total, DATE_FORMAT(Created_On, '%%d') AS Day, CONCAT('BLT') AS Type, IF(LENGTH(Invoice_Organisation)>0, Invoice_Organisation, CONCAT_WS(' ', Invoice_First_Name, Invoice_Last_Name)) AS Contact_Name FROM invoice WHERE Integration_ID<>'' AND Created_On>='%s' AND Created_On<'%s' AND Integration_Reference='' GROUP BY Integration_ID", mysql_real_escape_string($startDate), mysql_real_escape_string($endDate)));
	while($data->Row) {
		$index = (int) $data->Row['Day'];
		
		$days[$index][] = $data->Row;
		
		$data->Next();	
	}
	$data->Disconnect();
	
	?>
	
	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color:#eeeeee;">
			<td style="border-bottom:1px solid #dddddd;"><strong>Day</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Type</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Integration ID</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Integration Reference</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Contact Name</strong></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Amount</strong></td>
		</tr>

		<?php
		$total = 0;
		
		foreach($days as $index=>$day) {
			?>
			
			<tr>
				<td style="border-top:1px solid #dddddd;"><strong><?php echo $index; ?></strong></td>
				<td style="border-top:1px solid #dddddd;" colspan="5">&nbsp;</td>
			</tr>

			<?php
			foreach($day as $item) {
				?>
				
				<tr>
					<td style="border-top:1px solid #dddddd;">&nbsp;</td>
					<td style="border-top:1px solid #dddddd;"><?php echo $item['Type']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo $item['Integration_ID']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo $item['Integration_Reference']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo !stristr($item['Integration_Reference'], 'batch') ? $item['Contact_Name'] : ''; ?></td>
					<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($item['Total'], 2), 2, '.', ','); ?></td>
				</tr>

				<?php
				$total += $item['Total'];
			}
		}
		?>
		
		<tr>
			<td style="border-top:1px solid #dddddd;" colspan="5">&nbsp;</td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong>&pound;<?php echo number_format(round($total, 2), 2, '.', ','); ?></strong></td>
		</tr>
	</table>
	<br />
	
	<br />
	<h3>Credit Breakdown</h3>
	<p></p>
	
	<?php
	$days = array();
	
	for($i=1; $i<=date('t', strtotime($startDate)); $i++) {
		$days[$i] = array();
	}
	
	$data = new DataQuery(sprintf("SELECT cn.Integration_ID, cn.Integration_Reference, SUM(cn.Total-cn.TotalTax) AS Total, DATE_FORMAT(cn.Created_On, '%%d') AS Day, CONCAT('BLT') AS Type, IF(LENGTH(o.Invoice_Organisation_Name)>0, o.Invoice_Organisation_Name, CONCAT_WS(' ', o.Invoice_First_Name, o.Invoice_Last_Name)) AS Contact_Name FROM credit_note AS cn LEFT JOIN orders AS o ON o.Order_ID=cn.Order_ID WHERE cn.Integration_ID<>'' AND cn.Credited_On>='%s' AND cn.Credited_On<'%s' AND cn.Integration_Reference='' GROUP BY cn.Integration_ID", mysql_real_escape_string($startDate), mysql_real_escape_string($endDate)));
	while($data->Row) {
		$index = (int) $data->Row['Day'];
		
		$days[$index][] = $data->Row;
		
		$data->Next();	
	}
	$data->Disconnect();
	
	?>
	
	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color:#eeeeee;">
			<td style="border-bottom:1px solid #dddddd;"><strong>Day</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Type</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Integration ID</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Integration Reference</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Contact Name</strong></td>
			<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Amount</strong></td>
		</tr>

		<?php
		$total = 0;
		
		foreach($days as $index=>$day) {
			?>
			
			<tr>
				<td style="border-top:1px solid #dddddd;"><strong><?php echo $index; ?></strong></td>
				<td style="border-top:1px solid #dddddd;" colspan="5">&nbsp;</td>
			</tr>

			<?php
			foreach($day as $item) {
				?>
				
				<tr>
					<td style="border-top:1px solid #dddddd;">&nbsp;</td>
					<td style="border-top:1px solid #dddddd;"><?php echo $item['Type']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo $item['Integration_ID']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo $item['Integration_Reference']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo !stristr($item['Integration_Reference'], 'batch') ? $item['Contact_Name'] : ''; ?></td>
					<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($item['Total'], 2), 2, '.', ','); ?></td>
				</tr>

				<?php
				$total += $item['Total'];
			}
		}
		?>
		
		<tr>
			<td style="border-top:1px solid #dddddd;" colspan="5">&nbsp;</td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong>&pound;<?php echo number_format(round($total, 2), 2, '.', ','); ?></strong></td>
		</tr>
	</table>
	
	<?php
}

$page->Display('footer');