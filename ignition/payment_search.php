<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('paymentid', 'Transaction ID', 'text', '', 'numeric_unsigned', 1, 11);

$page = new Page('Payment Search', 'Search for transactions here.');
$page->Display('header');

$window = new StandardWindow('Search transactions');
$webForm = new StandardForm();

echo $form->Open();
echo $window->Open();
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('paymentid'), $form->GetHTML('paymentid') . '<input type="submit" name="search" value="Search" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

echo '<br />';

if(isset($_REQUEST['paymentid'])) {
	$table = new DataTable('records');
	$table->SetSQL(sprintf("SELECT p.*, o.Billing_Organisation_Name, o.Billing_First_Name, o.Billing_Last_Name FROM payment AS p LEFT JOIN orders AS o ON o.Order_ID=p.Order_ID WHERE p.Payment_ID=%d", $form->GetValue('paymentid')));
	$table->AddField('ID#','Payment_ID');
	$table->AddField('Transaction Date','Created_On');
	$table->AddField('Type','Transaction_Type');
	$table->AddField('Status','Status');
	$table->AddField('Order ID', 'Order_ID');
    $table->AddField('Organisation','Billing_Organisation_Name');
	$table->AddField('First Name','Billing_First_Name');
	$table->AddField('Last Name','Billing_Last_Name');
	$table->AddField('Amount','Amount', 'right');
	$table->SetMaxRows(25);
	$table->SetOrderBy('Created_On');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
}

$page->Display('footer');

require_once('lib/common/app_footer.php');