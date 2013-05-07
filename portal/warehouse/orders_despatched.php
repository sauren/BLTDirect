<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
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
	$form->AddField('action', 'Action', 'hidden', 'view', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('despatchfrom', 'Despatched From', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('despatchto', 'Despatched To', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	
	$sqlSelect = sprintf("SELECT o.Order_ID, o.Order_Prefix, o.Despatched_On, o.Total, o.TaxExemptCode, CONCAT(o.Order_Prefix, o.Order_ID) AS Order_Number, CONCAT_WS(' ', TRIM(CONCAT_WS(' ', o.Billing_First_Name, o.Billing_Last_Name)), REPLACE(CONCAT('(', o.Billing_Organisation_Name, ')'), '()', '')) AS Billing_Contact, CONCAT_WS(' ', TRIM(CONCAT_WS(' ', o.Shipping_First_Name, o.Shipping_Last_Name)), REPLACE(CONCAT('(', o.Shipping_Organisation_Name, ')'), '()', '')) AS Shipping_Contact, o.Shipping_Zip, p.Postage_Title, p.Postage_Days ");
	$sqlFrom = sprintf("FROM orders AS o LEFT JOIN postage AS p ON o.Postage_ID=p.Postage_ID ");
	$sqlWhere = sprintf("WHERE o.Status LIKE 'Despatched' ");

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			if(strlen($form->GetValue('despatchfrom')) > 0) {
				$sqlOther .= sprintf("AND o.Despatched_On>='%s' ", sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('despatchfrom'), 6, 4), substr($form->GetValue('despatchfrom'), 3, 2), substr($form->GetValue('despatchfrom'), 0, 2)));
			}
			
			if(strlen($form->GetValue('despatchto')) > 0) {
				$sqlOther .= sprintf("AND o.Despatched_On<'%s' ", sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('despatchto'), 6, 4), substr($form->GetValue('despatchto'), 3, 2), substr($form->GetValue('despatchto'), 0, 2)));
			}
		}
	}

	$page = new Page('Orders Despatched', 'Below is a list of all the despatched orders available for viewing.');
	$page->LinkScript('js/scw.js');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Search for an Order.');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Search for orders by any of the below fields.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('despatchfrom'), $form->GetHTML('despatchfrom'));
	echo $webForm->AddRow($form->GetLabel('despatchto'), $form->GetHTML('despatchto'));
	echo $webForm->AddRow('', '<input type="submit" name="search" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	echo '<br />';

	$table = new DataTable('orders');
	$table->SetSQL($sqlSelect.$sqlFrom.$sqlWhere.$sqlOther);
	$table->SetExtractVars();
	$table->AddBackgroundCondition('TaxExemptCode', '', '!=', '#FEFDB2', '#FEFC6B');
	$table->AddBackgroundCondition('Postage_Days', '1', '==', '#FFF499', '#EEE177');
	$table->AddField('', 'TaxExemptCode', 'hidden');
	$table->AddField('', 'Postage_Days', 'hidden');
	$table->AddField('Despatch Date', 'Despatched_On', 'left');
	$table->AddField('Order Number', 'Order_Number', 'left');
	$table->AddField('Contact', 'Billing_Contact', 'left');
	$table->AddField('Postage', 'Postage_Title', 'left');
	$table->AddField('Shipping Contact', 'Shipping_Contact', 'left');
	$table->AddField('Shipping Zip', 'Shipping_Zip', 'left');
	$table->AddField('Total', 'Total', 'right');
	$table->AddLink("order_details.php?orderid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\">", "Order_ID");
	$table->AddLink("javascript:popUrl('order_cancel.php?orderid=%s', 800, 600);", "<img src=\"images/aztector_6.gif\" alt=\"Cancel\" border=\"0\">", "Order_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Despatched_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}