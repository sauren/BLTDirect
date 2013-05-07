<?php
require_once ('lib/common/app_header.php');

if ($action == 'print') {
	$session->Secure(3);
	printAll();
	exit();
} else {
	$session->Secure(2);
	view();
	exit();
}

function view() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CreditNote.php');

	$table = new DataTable("notes");
	//show credit notes created only on or after May 1st 2008(as Steve asked for)
	$whereSql = 'AND c.Created_On >= \'2008-05-01 00:00:00\'';
	$table->SetSQL(sprintf("SELECT c.*, o.Billing_Organisation_Name, CONCAT_WS(' ', o.Billing_First_Name, o.Billing_Last_Name) AS Billing_Name FROM credit_note AS c INNER JOIN orders AS o ON o.Order_ID=c.Order_ID INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE c.Integration_ID='' AND c.Credit_Type LIKE 'Account Credited'%s",$whereSql));
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Order = "DESC";
	$table->Finalise();

	$tempSql = $table->SQL;
	$table->FormatSQL();
	$newSql = $table->SQL;
	$table->SQL = $tempSql;

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha_numeric', 4, 4);

	$data = new DataQuery($newSql);
	while ($data->Row) {
		$form->AddField(sprintf('integration_%d', $data->Row['Credit_Note_ID']), sprintf('Transaction Reference for Credit Note %d', $data->Row['Credit_Note_ID']), 'text', '', 'anything', 0, 64, false, 'size="10"');

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			$note = new CreditNote();

			foreach($_REQUEST as $key=>$value) {
				if(strlen($value) > 0) {
					if(strlen($key) >= 12) {
						if(substr($key, 0, 12) == 'integration_') {
							$id = substr($key, 12);

							if(is_numeric($id)) {
								$note->Get($id);
								$note->IntegrationID = $value;
								$note->Update();
							}
						}
					}
				}
			}

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('Outstanding Credit Notes', 'Below is a list of all account credit notes missing transaction references.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Outstanding Credit Notes');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM credit_note AS c INNER JOIN orders AS o ON o.Order_ID=c.Order_ID WHERE c.Integration_ID='' AND c.Credit_Type LIKE 'Account Credited' GROUP BY c.Credit_Note_ID"));
	if($data->Row['Counter'] > 0) {
		echo $window->Open();
		echo $window->AddHeader('Print all outstanding credit notes together.');
		echo $window->OpenContent();
		echo $webForm->Open();
		echo $webForm->AddRow('', sprintf('<input type="button" name="print" value="print all" class="btn" onclick="popUrl(\'%s?action=print\', 650, 500);">', $_SERVER['PHP_SELF']));
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
				<th nowrap="nowrap" class="dataHeadOrdered">Credit Date</th>
				<th nowrap="nowrap">Credit Note ID</th>
				<th nowrap="nowrap">Organisation</th>
				<th nowrap="nowrap">Name</th>
				<th nowrap="nowrap">Order ID</th>
				<th nowrap="nowrap" align="right">Credit Total</th>
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
						<td align="left"><?php echo $data->Row['Credit_Note_ID']; ?>&nbsp;</td>
						<td align="left"><?php print $data->Row['Billing_Organisation_Name']; ?>&nbsp;</td>
						<td align="left"><?php print $data->Row['Billing_Name']; ?>&nbsp;</td>
						<td align="left"><?php print $data->Row['Order_ID']; ?>&nbsp;</td>
						<td align="right">&pound;<?php print $data->Row['Total']; ?>&nbsp;</td>
						<td align="center" width="16"><?php echo $form->GetHTML(sprintf('integration_%d', $data->Row['Credit_Note_ID'])); ?></td>
						<td nowrap align="center" width="16"><a href="credit_note.php?cnid=<?php echo $data->Row['Credit_Note_ID']; ?>"><img src="images/folderopen.gif" alt="View Invoice" border="0" /></a></td>
						<td nowrap align="center" width="16"><a href="javascript:popUrl('credit_note_view.php?cnid=<?php echo $data->Row['Credit_Note_ID']; ?>', 650, 500);"><img src="images/icon_print_1.gif" alt="Print Credit Note" border="0" /></a></td>
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

	echo '<br /><input type="submit" name="update" value="update" class="btn">';
	echo $form->Close();

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function printAll() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CreditNote.php');

	$data = new DataQuery(sprintf("SELECT Credit_Note_ID FROM credit_note WHERE Integration_ID='' AND Credit_Type LIKE 'Account Credited'"));
	if($data->TotalRows > 0) {
		$note = new CreditNote();

		echo '<html><head><script language="javascript" type="text/javascript">
			window.onload = function(){
				window.self.print();
				window.self.close();
			}
			</script></head><body>';

		while($data->Row) {
			$note->Get($data->Row['Credit_Note_ID']);

			echo $note->GetDocument();
			echo '<br style="page-break-after: always;" />';

			$data->Next();
		}
		$data->Disconnect();

		echo '</body></html>';
	} else {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	require_once ('lib/common/app_footer.php');
}