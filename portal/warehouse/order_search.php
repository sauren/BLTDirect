<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure();

$form = new Form($_SERVER['PHP_SELF'], 'get');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('order', 'Order Number', 'text', '', 'paragraph', 1, 255, false);
$form->AddField('customreference', 'Custom Reference', 'text', '', 'paragraph', 1, 255, false);
$form->AddField('total', 'Total', 'text', '', 'paragraph', 1, 255, false);
$form->AddField('despatchid', 'Despatch Number', 'text', '', 'paragraph', 1, 255, false);
$form->AddField('invoiceid', 'Invoice Number', 'text', '', 'paragraph', 1, 255, false);
$form->AddField('name', 'Name', 'text', '', 'paragraph', 1, 255, false);
$form->AddField('postcode', 'Postcode', 'text', '', 'paragraph', 1, 255, false);
$form->AddField('productid', 'Product Number', 'text', '', 'paragraph', 1, 255, false);

$sqlSelect = '';
$sqlFrom = '';
$sqlWhere = '';
$sqlOther = '';

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()){
		$sqlSelect = sprintf("SELECT o.*, CONCAT_WS(' ', o.Billing_First_Name, o.Billing_Last_Name) AS Billing_Name, CONCAT_WS(' ', o.Shipping_First_Name, o.Shipping_Last_Name) AS Shipping_Name ");
		$sqlFrom = sprintf("FROM orders AS o ");
		$sqlWhere = sprintf("WHERE o.Status NOT IN ('Incomplete', 'Unauthenticated') ");
		$sqlOther = sprintf("GROUP BY o.Order_ID ");

		if(strlen($form->GetValue('order')) > 0) {
			$sqlWhere .= sprintf('AND o.Order_ID=%1$d ', $form->GetValue('order'));
		}
		
		if(strlen($form->GetValue('customreference')) > 0) {
			$sqlWhere .= sprintf('AND o.Custom_Order_No_Search LIKE \'%1$s\' ', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $form->GetValue('customreference'))));
		}
		
		if(strlen($form->GetValue('total')) > 0) {
			$sqlWhere .= sprintf('AND o.Total=%1$f ', $form->GetValue('total'));
		}
		
		if((strlen($form->GetValue('despatchid')) > 0) || (strlen($form->GetValue('invoiceid')) > 0) || (strlen($form->GetValue('productid')) > 0)) {
			$sqlFrom .= sprintf('INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID ');
			
			if(strlen($form->GetValue('despatchid')) > 0) {
				$sqlWhere .= sprintf('AND ol.Despatch_ID=%1$d ', $form->GetValue('despatchid'));
			}
			
			if(strlen($form->GetValue('invoiceid')) > 0) {
				$sqlWhere .= sprintf('AND ol.Invoice_ID=%1$d ', $form->GetValue('invoiceid'));
			}
			
			if(strlen($form->GetValue('productid')) > 0) {
				$sqlWhere .= sprintf('AND ol.Product_ID=%1$d ', $form->GetValue('productid'));
			}
		}
		
		if(strlen($form->GetValue('name')) > 0) {
			$sqlWhere .= sprintf('AND (o.Billing_First_Name_Search LIKE \'%1$s%%\' OR o.Billing_Last_Name_Search LIKE \'%1$s%%\' OR o.Billing_Organisation_Name_Search LIKE \'%1$s%%\' OR o.Shipping_First_Name_Search LIKE \'%1$s%%\' OR o.Shipping_Last_Name_Search LIKE \'%1$s%%\' OR o.Shipping_Organisation_Name_Search LIKE \'%1$s%%\' OR o.Invoice_First_Name_Search LIKE \'%1$s%%\' OR o.Invoice_Last_Name_Search LIKE \'%1$s%%\' OR o.Invoice_Organisation_Name_Search LIKE \'%1$s%%\') ', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $form->GetValue('name'))));
		}
			
		if(strlen($form->GetValue('postcode')) > 0) {
			$sqlWhere .= sprintf('AND (o.Billing_Zip_Search LIKE \'%1$s%%\' OR o.Shipping_Zip_Search LIKE \'%1$s%%\' OR o.Invoice_Zip_Search LIKE \'%1$s%%\') ', mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $form->GetValue('postcode'))));
		}
	}
}

$page = new Page('Order Search', '');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Search for an Order.');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Search for orders by any of the below fields.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('order'), $form->GetHTML('order'));
echo $webForm->AddRow($form->GetLabel('customreference'), $form->GetHTML('customreference'));
echo $webForm->AddRow($form->GetLabel('total'), $form->GetHTML('total'));
echo $webForm->AddRow($form->GetLabel('despatchid'), $form->GetHTML('despatchid'));
echo $webForm->AddRow($form->GetLabel('invoiceid'), $form->GetHTML('invoiceid'));
echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name'));
echo $webForm->AddRow($form->GetLabel('postcode'), $form->GetHTML('postcode'));
echo $webForm->AddRow($form->GetLabel('productid'), $form->GetHTML('productid'));
echo $webForm->AddRow('', '<input type="submit" name="search" value="search" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

echo '<br />';

if(isset($_REQUEST['confirm'])) {
	$table = new DataTable('records');
	$table->SetExtractVars();
	$table->SetSQL(sprintf("%s%s%s%s", $sqlSelect, $sqlFrom, $sqlWhere, $sqlOther));
	$table->AddField('ID#', 'Order_ID', 'left');
	$table->AddField('Date', 'Created_On', 'left');
	$table->AddField('Status', 'Status', 'left');
	$table->AddField('Organisation', 'Shipping_Organisation_Name', 'left');
	$table->AddField('Name', 'Billing_Name', 'left');
	$table->AddField('Zip', 'Billing_Zip', 'left');
	$table->AddField('Shipping Name', 'Shipping_Name', 'left');
	$table->AddField('Shipping Zip', 'Shipping_Zip', 'left');
	$table->AddLink("order_package_details.php?orderid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Order_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Order_ID");
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
}

$page->Display('footer');
require_once('lib/common/app_footer.php');