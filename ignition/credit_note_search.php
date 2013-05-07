<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('creditnoteid', 'Credit Note ID', 'text', '', 'numeric_unsigned', 1, 11, false);

$sql = "SELECT cn.*, o.Billing_Organisation_Name, o.Billing_First_Name, o.Billing_Last_Name, o.Billing_Zip FROM credit_note AS cn INNER JOIN orders AS o ON o.Order_ID=cn.Order_ID ";

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		if(strlen($form->GetValue('creditnoteid')) > 0) {
			$sql .= sprintf("WHERE cn.Credit_Note_ID=%d", $form->GetValue('creditnoteid'));
		}
	}
}

$page = new Page('Credit Note Search', 'Find your credit notes here.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow("Search for a credit note.");
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('confirm');
echo $window->Open();
echo $window->AddHeader('Enter the credit note ID.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('creditnoteid'), $form->GetHTML('creditnoteid'));
echo $webForm->AddRow('', '<input type="submit" name="search" value="search" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

echo '<br />';

if(isset($_REQUEST['confirm'])) {
	$table = new DataTable("creditNotes");
	$table->SetSQL($sql);
	$table->AddField('ID#', 'Credit_Note_ID', 'left');
	$table->AddField('Date', 'Credited_On', 'left');
	$table->AddField('Order ID#', 'Order_ID', 'left');
	$table->AddField('Credit Type', 'Credit_Type', 'left');
	$table->AddField('Organisation', 'Billing_Organisation_Name', 'left');
	$table->AddField('Name', 'Billing_First_Name', 'left');
	$table->AddField('Surname', 'Billing_Last_Name', 'left');
	$table->AddField('Zip', 'Billing_Zip', 'left');
	$table->AddLink("credit_note_view.php?cnid=%s", "<img src=\"images/folderopen.gif\" alt=\"Update Settings\" border=\"0\">", "Credit_Note_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
}

$page->Display('footer');
require_once('lib/common/app_footer.php');