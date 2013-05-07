<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	
$session->Secure(2);
view();
exit;

function view() {
	$form = new Form($_SERVER['PHP_SELF'], 'get');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('status', 'Status', 'select', 'N', 'alpha', 1, 1);
	$form->AddOption('status', 'N', 'Non-Despatched');
	$form->AddOption('status', 'Y', 'Despatched');

	$page = new Page('Proforma Orders', 'Listing all proforma orders.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow("Filter orders");
	$webForm = new StandardForm();
	
	echo $form->Open();
	echo $form->GetHTML('confirm');
	
	echo $window->Open();
	echo $window->AddHeader('You can enter a sentence below. The more words you include the closer your results will be.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('status'), $form->GetHTML('status') . $form->GetIcon('status'));
	echo $webForm->AddRow('', '<input type="submit" name="filter" value="filter" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();
	
	echo '<br />';
	
	$table = new DataTable('orders');
	$table->SetSQL(sprintf("SELECT o.*, pg.Postage_Title, pg.Postage_Days FROM orders AS o LEFT JOIN postage AS pg ON o.Postage_ID=pg.Postage_ID WHERE o.Status NOT IN ('Cancelled') AND o.ProformaID>0%s", (($form->GetValue('status') == 'Y') ? ' AND o.Status IN (\'Despatched\')' : ' AND o.Status NOT IN (\'Despatched\')')));
	$table->AddBackgroundCondition('Order_Prefix', 'N', '==', '#BB99FF', '#9F77EE');
	$table->AddBackgroundCondition('Postage_Days', '1', '==', '#FFF499', '#EEE177');
	$table->AddField('', 'Postage_Days', 'hidden');
	$table->AddField('Order Date', 'Ordered_On', 'left');
	$table->AddField('Organisation', 'Billing_Organisation_Name', 'left');
	$table->AddField('Name', 'Billing_First_Name', 'left');
	$table->AddField('Surname', 'Billing_Last_Name', 'left');
	$table->AddField('Order Prefix', 'Order_Prefix', 'left');
	$table->AddField('Order Number', 'Order_ID', 'right');
	$table->AddField('Order Total', 'Total', 'right');
	$table->AddField('Postage', 'Postage_Title', 'left');
	$table->AddLink("order_details.php?orderid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\" />", "Order_ID");
	$table->AddLink("javascript:popUrl('order_cancel.php?orderid=%s', 800, 600);", "<img src=\"images/aztector_6.gif\" alt=\"Cancel\" border=\"0\">", "Order_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Ordered_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}