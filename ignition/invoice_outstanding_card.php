<?php
require_once ('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');

if($action == 'printall') {
	$session->Secure(3);
	printAll();
	exit();
} else {
	$session->Secure(2);
	view();
	exit();
}

function view() {
	$invoiceSql = sprintf("SELECT i.*, CONCAT_WS(' ', i.Invoice_First_Name, i.Invoice_Last_Name) AS Invoice_Name FROM invoice AS i INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=i.Payment_Method_ID WHERE i.Integration_ID='' AND pm.Reference LIKE 'card' AND i.Created_On>='%s' AND i.Invoice_Tax=0", mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE_BATCH']));

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha_numeric', 4, 4);
	
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

	$data = new DataQuery($newSql);
	while ($data->Row) {
		$form->AddField(sprintf('integration_%d', $data->Row['Invoice_ID']), sprintf('Transaction Reference for Invoice %d', $data->Row['Invoice_ID']), 'text', '', 'anything', 0, 64, false, 'size="10"');

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			$invoice = new Invoice();

			foreach($_REQUEST as $key=>$value) {
				if(strlen($value) > 0) {
					if(strlen($key) >= 12) {
						if(substr($key, 0, 12) == 'integration_') {
							$id = substr($key, 12);

							if(is_numeric($id)) {
								$invoice->Get($id);
								$invoice->IsPaid = 'Y';
								$invoice->IntegrationID = $value;
								$invoice->Update();
							}
						}
					}
				}
			}

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('Pending Invoices (Credit Card Only)', 'Below is a list of all invoices currently outstanding.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Outstanding Invoices');
	$webForm = new StandardForm();

	echo $form->Open();

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM invoice AS i INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=i.Payment_Method_ID WHERE i.Integration_ID='' AND pm.Reference LIKE 'card' AND i.Created_On>='%s' AND i.Invoice_Tax=0", mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE_BATCH'])));
	if($data->Row['Counter'] > 0) {
		echo $window->Open();
		echo $window->AddHeader('Print all outstanding invoices together.');
		echo $window->OpenContent();
		echo $webForm->Open();
		echo $webForm->AddRow('', '<input type="button" name="print" value="print all" class="btn" onclick="popUrl(\'?action=printall\', 650, 500);">');
		echo $webForm->Close();
		echo $window->CloseContent();
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
						<td align="left"><?php print $data->Row['Invoice_Organisation']; ?>&nbsp;</td>
						<td align="left"><?php print $data->Row['Invoice_Name']; ?>&nbsp;</td>
						<td align="left"><?php print $data->Row['Order_ID']; ?>&nbsp;</td>
						<td align="right">&pound;<?php print $data->Row['Invoice_Total']; ?>&nbsp;</td>
						<td align="center" width="16"><?php echo $form->GetHTML(sprintf('integration_%d', $data->Row['Invoice_ID'])); ?></td>
						<td nowrap align="center" width="16"><a href="invoice.php?invoiceid=<?php echo $data->Row['Invoice_ID']; ?>"><img src="images/folderopen.gif" alt="View Invoice" border="0" /></a></td>
						<td nowrap align="center" width="16"><a href="javascript:popUrl('invoice_view.php?invoiceid=<?php echo $data->Row['Invoice_ID']; ?>', 650, 500);"><img src="images/icon_print_1.gif" alt="Print Invoice" border="0" /></a></td>
					</tr>

					<?php
					$data->Next();
				}
			} else {
				?>

				<tr class="dataRow">
					<td colspan="8">No Records Found</td>
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
}

function printAll() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');

	$data = new DataQuery(sprintf("SELECT i.Invoice_ID FROM invoice AS i INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=i.Payment_Method_ID WHERE i.Integration_ID='' AND pm.Reference LIKE 'card' AND i.Created_On>='%s' AND i.Invoice_Tax=0", mysql_real_escape_string($GLOBALS['SAGE_INTEGRATION_DATE_BATCH'])));
	if($data->TotalRows > 0) {
		echo '<html><head><script language="javascript" type="text/javascript">
			window.onload = function(){
				window.self.print();
				window.self.close();
			}
			</script></head><body>';

		while($data->Row) {
			$invoice = new Invoice();
			$invoice->Get($data->Row['Invoice_ID']);

			echo $invoice->GetDocument();
			echo '<br style="page-break-after: always;" />';

			$data->Next();
		}
		$data->Disconnect();

		echo '</body></html>';
	} else {
		echo '<script language="javascript" type="text/javascript">window.self.close();</script>';
	}

	require_once ('lib/common/app_footer.php');
}