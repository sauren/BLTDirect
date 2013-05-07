<?php
require_once ('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha_numeric', 4, 4);
$form->AddField('filter', 'Show invoices from', 'select', 'true', 'alpha_numeric', 4, 4);
$form->AddOption('filter', 'all', 'All');
$form->AddOption('filter', 'uk', 'UK');
$form->AddOption('filter', 'elsewhere', 'Rest Of World');

$invoiceSql = sprintf("SELECT i.*, CONCAT_WS(' ', i.Invoice_First_Name, i.Invoice_Last_Name) AS Invoice_Name FROM invoice AS i INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=i.Payment_Method_ID WHERE i.Integration_ID<>'' AND pm.Reference NOT LIKE 'card' AND pm.Reference NOT LIKE 'google' AND pm.Reference NOT LIKE 'paypal' AND (i.Invoice_Tax=0 OR i.Invoice_Country NOT LIKE 'United Kingdom') AND i.Invoice_Total>0");

if ($form->GetValue('filter')) {
	if ($form->GetValue('filter') == 'uk') {
		$invoiceSql .= " AND Invoice_Country LIKE 'United Kingdom'";
	}
	else if ($form->GetValue('filter') == 'elsewhere') {
		$invoiceSql .= " AND Invoice_Country NOT LIKE 'United Kingdom'";
	}
}

$table = new DataTable("invoice");
$table->SetSQL($invoiceSql);
$table->SetMaxRows(25);
$table->SetOrderBy("Created_On");
$table->Order = "DESC";
$table->Finalise();

$tempSql = $table->SQL;
$table->FormatSQL();
$newSql = $table->SQL;
$table->SQL = $tempSql;

$page = new Page('Completed Invoices (Non UK or Non VAT)', 'Below is a list of all invoices currently completed.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Completed Invoices');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');

$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM invoice WHERE Integration_ID<>''"));
if($data->Row['Counter'] > 0) {
	echo $window->Open();
	echo $window->AddHeader('Filter invoices');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('filter'), $form->GetHTML('filter') . '<input type="submit" value="update" class="btn">');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
}
$data->Disconnect();
?>

<br />

<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
	<thead>
		<tr>
			<th nowrap="nowrap" class="dataHeadOrdered">Invoice Date</th>
			<th nowrap="nowrap">Invoice ID</th>
			<th nowrap="nowrap">Organisation</th>
			<th nowrap="nowrap">Name</th>
			<th nowrap="nowrap">Order ID</th>
			<th nowrap="nowrap" align="right">Invoice Total</th>
			<th nowrap="nowrap" align="right">Integration ID</th>
			<th>&nbsp;</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>

		<?php
		$data = new DataQuery($newSql);
		if($data->TotalRows > 0) {
			while ($data->Row) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td class="dataOrdered" align="left"><?php echo $data->Row['Created_On']; ?></td>
					<td align="left"><?php echo $data->Row['Invoice_ID']; ?>&nbsp;</td>
					<td align="left"><?php echo $data->Row['Invoice_Organisation']; ?>&nbsp;</td>
					<td align="left"><?php echo $data->Row['Invoice_Name']; ?>&nbsp;</td>
					<td align="left"><?php echo $data->Row['Order_ID']; ?>&nbsp;</td>
					<td align="right">&pound;<?php echo $data->Row['Invoice_Total']; ?>&nbsp;</td>
					<td align="left"><?php echo $data->Row['Integration_ID']; ?>&nbsp;</td>
					<td nowrap align="center" width="16"><a href="invoice.php?invoiceid=<?php echo $data->Row['Invoice_ID']; ?>"><img src="images/folderopen.gif" alt="View Invoice" border="0" /></a></td>
					<td nowrap align="center" width="16"><a href="javascript:popUrl('invoice_view.php?invoiceid=<?php echo $data->Row['Invoice_ID']; ?>', 650, 500);"><img src="images/icon_print_1.gif" alt="Print Invoice" border="0" /></a></td>
				</tr>

				<?php
				$data->Next();
			}
		} else {
			?>

			<tr class="dataRow">
				<td colspan="9">No Records Found</td>
			</tr>

			<?php
		}
		?>

	</tbody>
</table><br />

<?php
$table->DisplayNavigation();

echo $form->Close();

$page->Display('footer');
require_once ('lib/common/app_footer.php');