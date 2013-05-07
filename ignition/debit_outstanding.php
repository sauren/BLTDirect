<?php
require_once ('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha_numeric', 4, 4);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$debit = new Debit();

		foreach($_REQUEST as $key=>$value) {
			if(strlen($value) > 0) {
				if(strlen($key) >= 12) {
					if(substr($key, 0, 12) == 'integration_') {
						$id = substr($key, 12);

						if(is_numeric($id)) {
							$debit->Get($id);
							$debit->IntegrationID = $value;
							$debit->Update();
						}
					}
				}
			}
		}

		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
}

$page = new Page('Outstanding Debits', 'Below is a list of all debits missing transaction references.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');

$table = new DataTable('records');
$table->SetSQL("SELECT d.*, CONCAT(d.Prefix, d.Debit_ID) AS Debit_Reference FROM debit AS d LEFT JOIN supplier_invoice_query AS siq ON siq.DebitID=d.Debit_ID WHERE d.Is_Paid='Y' AND d.Integration_ID='' AND siq.SupplierInvoiceQueryID IS NULL GROUP BY d.Debit_ID");
$table->AddField('Date Debited','Created_On');
$table->AddField('Reference', 'Debit_Reference');
$table->AddField('Organisation','Debit_Organisation');
$table->AddField('First Name','Debit_First_Name');
$table->AddField('Last Name','Debit_Last_Name');
$table->AddField('Amount Due','Debit_Total');
$table->AddInput('Integration ID', 'Y', 'Integration_ID', 'integration', 'Debit_ID', 'text');
$table->AddLink('javascript:popUrl(\'debit_awaiting_payment.php?action=open&print=true&id=%s\', 650, 500);', '<img src="images/icon_print_1.gif" />', 'Debit_ID');
$table->SetMaxRows(25);
$table->SetOrderBy('Created_On');
$table->Finalise();
$table->DisplayTable();
echo '<br />';
$table->DisplayNavigation();

echo '<br />';
echo '<input type="submit" name="update" value="update" class="btn">';

echo $form->Close();

$page->Display('footer');
require_once ('lib/common/app_footer.php');