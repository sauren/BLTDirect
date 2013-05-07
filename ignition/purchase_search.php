<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('purchaseid', 'Purchase ID', 'text', '', 'numeric_unsigned', 1, 11);
$form->AddField('productid', 'Product ID', 'text', '', 'numeric_unsigned', 1, 11);
$form->AddField('excludeorders', 'Exclude Orders', 'checkbox', 'Y', 'boolean', 1, 1, false);

$page = new Page('Purchase Search', 'Search for purchase orders here.');
$page->Display('header');

$window = new StandardWindow('Search purchases');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('purchaseid'), $form->GetHTML('purchaseid'));
echo $webForm->AddRow($form->GetLabel('productid'), $form->GetHTML('productid'));
echo $webForm->AddRow($form->GetLabel('excludeorders'), $form->GetHTML('excludeorders'));
echo $webForm->AddRow('', '<input type="submit" name="search" value="Search" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

echo '<br />';

if(isset($_REQUEST['confirm'])) {
	$sqlSelect = 'SELECT p.* ';
	$sqlFrom = 'FROM purchase AS p ';
	$sqlWhere = 'WHERE (p.Purchase_Status NOT LIKE \'Irrelevant\' OR p.Order_ID>0) ';
	$sqlOther = 'GROUP BY p.Purchase_ID';
	
	if(strlen($form->GetValue('purchaseid')) > 0) {
		$sqlWhere .= sprintf('AND p.Purchase_ID=%d ', $form->GetValue('purchaseid'));
	}
	
	if(strlen($form->GetValue('productid')) > 0) {
		$sqlFrom .= sprintf('INNER JOIN purchase_line AS pl ON p.Purchase_ID=pl.Purchase_ID AND pl.Product_ID=%d ', $form->GetValue('productid'));
	}
	
	if($form->GetValue('excludeorders') == 'Y') {
		$sqlWhere .= sprintf('AND p.Order_ID=0 ');
	}

	$table = new DataTable('records');
	$table->SetSQL(sprintf("%s%s%s%s", $sqlSelect, $sqlFrom, $sqlWhere, $sqlOther));
	$table->AddField('ID#','Purchase_ID');
	$table->AddField('Date Ordered','Purchased_On');
	$table->AddField('Organisation','Supplier_Organisation_Name');
	$table->AddField('First Name','Supplier_First_Name');
	$table->AddField('Last Name','Supplier_Last_Name');
	$table->AddField('Status','Purchase_Status');
	$table->AddField('Custom Reference', 'Custom_Reference_Number');
	$table->AddField('Notes','Order_Note');
	$table->AddField('Order ID', 'Order_ID');
	$table->AddLink('purchase_open.php?pid=%s',"<img src=\"./images/folderopen.gif\" alt=\"Open this purchase order\" border=\"0\">",'Purchase_ID');
	$table->AddLink('purchase_administration.php?action=administer&pid=%s',"<img src=\"./images/icon_edit_1.gif\" alt=\"Administer this purchase order\" border=\"0\">",'Purchase_ID');
	$table->AddLink('purchase_edit.php?pid=%s', "<img src=\"./images/icon_stock.gif\" alt=\"Fulfil\" border=\"0\">",'Purchase_ID');
	$table->SetMaxRows(25);
	$table->SetOrderBy('Purchased_On');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
}

$page->Display('footer');

require_once('lib/common/app_footer.php');