<?php
require_once ('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

$connection = new MySQLConnection($GLOBALS['SYNC_DB_HOST'][0], $GLOBALS['SYNC_DB_NAME'][0], $GLOBALS['SYNC_DB_USERNAME'][0], $GLOBALS['SYNC_DB_PASSWORD'][0]);
	
$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha_numeric', 4, 4);
$form->AddField('filter', 'Show invoices from', 'select', 'true', 'alpha_numeric', 4, 4);
$form->AddOption('filter', 'all', 'All');
$form->AddOption('filter', 'uk', 'UK');
$form->AddOption('filter', 'elsewhere', 'Rest Of World');

$invoiceSql = sprintf("SELECT *, CONCAT_WS(' ', Invoice_First_Name, Invoice_Last_Name) AS Invoice_Name FROM invoice WHERE Integration_ID='' AND (Invoice_Tax=0 OR Invoice_Country NOT LIKE 'United Kingdom') AND Created_On>='%s' AND Invoice_Total>0", mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE_LBUK']));

if($form->GetValue('filter')) {
	if ($form->GetValue('filter') == 'uk') {
		$invoiceSql .= " AND Invoice_Country LIKE 'United Kingdom'";
	}
	else if ($form->GetValue('filter') == 'elsewhere') {
		$invoiceSql .= " AND Invoice_Country NOT LIKE 'United Kingdom'";
	}
}

$table = new DataTable("invoice", $connection);
$table->SetSQL($invoiceSql);
$table->SetMaxRows(25);
$table->SetOrderBy("Created_On");
$table->Order = "DESC";
$table->Finalise();

$tempSql = $table->SQL;
$table->FormatSQL();
$newSql = $table->SQL;
$table->SQL = $tempSql;

$data = new DataQuery($newSql, $connection);
while ($data->Row) {
	$form->AddField(sprintf('integration_%d', $data->Row['Invoice_ID']), sprintf('Transaction Reference for Invoice %d', $data->Row['Invoice_ID']), 'text', '', 'anything', 0, 64, false, 'size="10"');

	$data->Next();
}
$data->Disconnect();

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		foreach($_REQUEST as $key=>$value) {
			if(strlen($value) > 0) {
				if(strlen($key) >= 12) {
					if(substr($key, 0, 12) == 'integration_') {
						$id = substr($key, 12);

						if(is_numeric($id)) {
							new DataQuery(sprintf("UPDATE invoice SET Is_Paid='Y', Integration_ID='%s' WHERE Invoice_ID=%d", mysql_real_escape_string($value), mysql_real_escape_string($id)), $connection);
						}
					}
				}
			}
		}

		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
}

$page = new Page('Pending Invoices (Non UK or Non VAT - LBUK)', 'Below is a list of all invoices currently outstanding.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Outstanding Invoices');
$webForm = new StandardForm();

echo $form->Open();

$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM invoice WHERE Integration_ID='' AND Created_On>='%s' AND Invoice_Total>0", mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE_LBUK'])), $connection);
if($data->Row['Counter'] > 0) {
	echo $window->Open();
	echo $window->AddHeader('Filter invoices');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('filter'), $form->GetHTML('filter') . '<input type="submit" value="update" class="btn">');
	echo $webForm->Close();
	echo $window->Close();
}
$data->Disconnect();
?>

<br />

<?php
echo $form->Close();

echo $form->Open();
echo $form->GetHTML('confirm');
?>

<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
	<thead>
		<tr>
			<th nowrap="nowrap" class="dataHeadOrdered">Invoice Date</th>
			<th nowrap="nowrap">Invoice ID</th>
			<th nowrap="nowrap">Organisation</th>
			<th nowrap="nowrap">Name</th>
			<th nowrap="nowrap">Order ID</th>
			<th nowrap="nowrap" align="right">Invoice Total</th>
			<th nowrap="nowrap">Integration ID</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>

		<?php
		$data = new DataQuery($newSql, $connection);
		if($data->TotalRows > 0) {
			while ($data->Row) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td class="dataOrdered" align="left"><?php echo $data->Row['Created_On']; ?></td>
					<td align="left"><?php echo $data->Row['Invoice_ID']; ?>&nbsp;</td>
					<td align="left"><?php print $data->Row['Invoice_Organisation']; ?>&nbsp;</td>
					<td align="left"><?php print $data->Row['Invoice_Name']; ?>&nbsp;</td>
					<td align="left"><?php print $data->Row['Order_ID']; ?>&nbsp;</td>
					<td align="right">&pound;<?php print $data->Row['Invoice_Total']; ?>&nbsp;</td>
					<td nowrap align="center" width="16"><?php echo $form->GetHTML(sprintf('integration_%d', $data->Row['Invoice_ID'])); ?></td>
					<td nowrap align="center" width="16"><a href="javascript:popUrl('invoice_view_lbuk.php?invoiceid=<?php echo $data->Row['Invoice_ID']; ?>', 650, 500);"><img src="images/icon_print_1.gif" alt="Print Invoice" border="0" /></a></td>
				</tr>

				<?php
				$data->Next();
			}
		} else {
			?>

			<tr class="dataRow">
				<td colspan="7">No Records Found</td>
			</tr>

			<?php
		}
		?>

	</tbody>
</table><br />

<?php
$table->DisplayNavigation();

echo '<br /><input type="submit" name="update" value="update" class="btn">';
echo $form->Close();

$page->Display('footer');
require_once ('lib/common/app_footer.php');