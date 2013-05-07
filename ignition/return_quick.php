<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/AutomateReturn.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

if($action == 'find') {
	$session->Secure(2);
	find();
	exit();
} elseif($action == 'email') {
	$session->Secure(2);
	email();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('orderid', 'Order ID', 'text', '', 'numeric_unsigned', 1, 11);
	$form->AddField('typeid', 'Type', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('typeid', '', '');

	$data = new DataQuery(sprintf("SELECT Reason_ID, Reason_Title FROM return_reason ORDER BY Reason_Title ASC"));
	while($data->Row) {
		$form->AddOption('typeid', $data->Row['Reason_ID'], $data->Row['Reason_Title']);

		$data->Next();	
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$order = new Order();

			if(!$order->Get($form->GetValue('orderid'))) {
				$form->AddError('Order does not exist.', 'orderid');
			}

			if($form->Valid) {
				redirectTo(sprintf('?action=email&orderid=%d&typeid=%d', $form->GetValue('orderid'), $form->GetValue('typeid')));
			}
		}
	}

	$page = new Page('Quick Return', 'Register a quick return here.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	if(isset($_REQUEST['status']) && ($_REQUEST['status'] == 'completed')) {
		$bubble = new Bubble('Return Completed', 'The return has been registered and will be processed momentarily.');

		echo $bubble->GetHTML();
		echo '<br />';
	}

	$window = new StandardWindow("Return details.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	
	echo $window->Open();
	echo $window->AddHeader('Enter or find an order.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('orderid'), $form->GetHTML('orderid') . '<a href="?action=find"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>');
	echo $webForm->AddRow($form->GetLabel('typeid'), $form->GetHTML('typeid'));
	echo $webForm->AddRow('', '<input type="submit" name="continue" value="continue" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function find() {
	$sql = '';

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'find', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('postcode', 'Postcode', 'text', '', 'paragraph', 1, 60);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$sql = sprintf('SELECT o.*, CONCAT(o.Order_Prefix, o.Order_ID) AS Order_Number, CONCAT_WS(\' \', TRIM(CONCAT_WS(\' \', o.Billing_First_Name, o.Billing_Last_Name)), REPLACE(CONCAT(\'(\', o.Billing_Organisation_Name, \')\'), \'()\', \'\')) AS Billing_Contact, po.Postage_Title, po.Postage_Days, IF(ol.Backorder_Expected_On=\'0000-00-00 00:00:00\', \'\', ol.Backorder_Expected_On) AS Backorder_Date FROM orders AS o LEFT JOIN postage AS po ON o.Postage_ID=po.Postage_ID LEFT JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND ol.Despatch_ID=0 WHERE REPLACE(o.Billing_Zip, \' \', \'\') LIKE \'%1$s\' OR REPLACE(o.Invoice_Zip, \' \', \'\') LIKE \'%1$s\' OR REPLACE(o.Shipping_Zip, \' \', \'\') LIKE \'%1$s\' GROUP BY o.Order_ID', str_replace(' ', '', $form->GetValue('postcode')));
		}
	}

	$page = new Page('Quick Return', 'Register a quick return here.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Order finder.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	
	echo $window->Open();
	echo $window->AddHeader('Enter postcode to search orders.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('postcode'), $form->GetHTML('postcode'));
	echo $webForm->AddRow('', '<input type="submit" name="search" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	if(!empty($sql)) {
		echo '<br />';

		$table = new DataTable('orders');
		$table->SetSQL($sql);
		$table->SetExtractVars();
		$table->AddField('Order Date', 'Created_On', 'left');
		$table->AddField('Order Number', 'Order_Number', 'left');
		$table->AddField('Contact', 'Billing_Contact', 'left');
		$table->AddField('Status', 'Status', 'left');
		$table->AddField('Postage', 'Postage_Title', 'left');
		$table->AddField('Total', 'Total', 'right');
		$table->AddField('Backordered', 'Backordered', 'center');
		$table->AddField('Expected', 'Backorder_Date', 'left');
		$table->AddLink('?orderid=%s', '<img src="images/button-tick.gif" alt="Select" border="0" />', 'Order_ID');
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
}

function email() {
	$order = new Order();

	if(!isset($_REQUEST['orderid']) || !$order->Get($_REQUEST['orderid'])) {
		redirectTo('?action=start');
	}

	$order->Customer->Get();
	$order->Customer->Contact->Get();

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'email', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('orderid', 'Order ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('typeid', 'Type', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('email', 'Email Address', 'text', $order->Customer->Contact->Person->Email, 'email', 1, 255);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {	
			AutomateReturn::processRequest($form->GetValue('orderid'), $form->GetValue('typeid'), $form->GetValue('email'));

			redirectTo('?status=completed');
		}
	}

	$page = new Page('Quick Return', 'Register a quick return here.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Contact details.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('orderid');
	echo $form->GetHTML('typeid');

	echo $window->Open();
	echo $window->AddHeader('Change the first contact email address.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('Customer', sprintf('%s %s%s', $order->Billing->Name, $order->Billing->LastName, !empty($order->BillingOrg) ? sprintf(' (%s)', $order->BillingOrg) : ''));
	echo $webForm->AddRow($form->GetLabel('email'), $form->GetHTML('email'));
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}