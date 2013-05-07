<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('start', 'Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('end', 'End Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('supplier', 'Supplier', 'select', '0', 'numeric_unsigned', 1, 11);
$form->AddGroup('supplier', 'Y', 'Favourite Suppliers');
$form->AddGroup('supplier', 'N', 'Standard Suppliers');
$form->AddOption('supplier', '0', '');

$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier_Name, s.Is_Favourite FROM supplier AS s INNER JOIN contact AS c ON s.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID ORDER BY Supplier_Name ASC"));
while($data->Row) {
	$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier_Name'], $data->Row['Is_Favourite']);

	$data->Next();
}
$data->Disconnect();
	
$page = new Page('Purchase Search (Supplier)', 'Search for purchase orders here by supplier.');
$page->LinkScript('js/scw.js');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();	
	echo '<br />';
}

$window = new StandardWindow('Search purchases');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start'));
echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end'));
echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier'));
echo $webForm->AddRow('', '<input type="submit" name="search" value="search" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

echo '<br />';

if(isset($_REQUEST['confirm'])) {
	$table = new DataTable('records');
	$table->SetSQL(sprintf("SELECT * FROM purchase AS p WHERE (p.Purchase_Status NOT LIKE 'Irrelevant' OR p.Order_ID>0)%s%s%s", ($form->GetValue('supplier') > 0) ? sprintf(' AND p.Supplier_ID=%d', $form->GetValue('supplier')) : '', (strlen($form->GetValue('start')) > 0) ? sprintf(' AND p.Created_On>=\'%s-%s-%s 00:00:00\'', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)) : '', (strlen($form->GetValue('end')) > 0) ? sprintf(' AND p.Created_On<\'%s-%s-%s 00:00:00\'', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : ''));
	$table->AddField('ID#','Purchase_ID');
	$table->AddField('Date Ordered','Purchased_On');
	$table->AddField('Organisation','Supplier_Organisation_Name');
	$table->AddField('First Name','Supplier_First_Name');
	$table->AddField('Last Name','Supplier_Last_Name');
	$table->AddField('Status','Purchase_Status');
	$table->AddField('Custom Reference', 'Custom_Reference_Number');
	$table->AddField('Notes','Order_Note');
	$table->AddField('Order ID', 'Order_ID');
	$table->AddLink('purchase_open.php?pid=%s', "<img src=\"./images/folderopen.gif\" alt=\"Open\" border=\"0\">",'Purchase_ID');
	$table->AddLink('purchase_administration.php?action=administer&pid=%s', "<img src=\"./images/icon_edit_1.gif\" alt=\"Administer\" border=\"0\">",'Purchase_ID');
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