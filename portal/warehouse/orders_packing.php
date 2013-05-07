<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

$session->Secure();

$page = new Page('Orders Packing', 'Below is a list of all ready to be packed.');
$page->Display('header');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('status','Filter by status','select','N','alpha_numeric',0,40,false);
$form->AddOption('status','N','No filter');
$form->AddOption('status','PK','View Packing Orders');
$form->AddOption('status','PD','View Partially Despatched Orders');
$form->AddOption('status','BO','View Backordered Orders');

$window = new StandardWindow('Filter orders');
$webForm = new StandardForm();

echo $form->Open();
echo $window->Open();
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('status'), $form->GetHTML('status').'<input type="submit" name="search" value="Search" class="btn">');
echo $webForm->AddRow('', '<input name="Print orders" type="button" id="Print Orders" value="Print Orders Together" class="btn" onclick="popUrl(\'print_picking.php?style=break&status='.$form->GetValue('status').'\',800, 600)"><input name="Print orders" type="button" id="Print Orders" value="Print Orders Individually" class="btn" onclick="popUrl(\'print_picking.php?style=page&status='.$form->GetValue('status') .'\',800, 600)">');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$sqlSelect = sprintf('SELECT o.*, pg.Postage_Title, pg.Postage_Days, SUM(ol.Line_Total-ol.Line_Discount) AS Total_Net ');
$sqlFrom = sprintf('FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID INNER JOIN warehouse AS w ON ol.Despatch_From_ID=w.Warehouse_ID LEFT JOIN supplier AS s ON s.Supplier_ID=w.Type_Reference_ID AND w.Type=\'S\' LEFT JOIN postage AS pg ON o.Postage_ID=pg.Postage_ID ');
$sqlWhere = sprintf('WHERE (w.Type=\'B\' OR (w.Type=\'S\' AND s.Is_Drop_Shipper=\'N\')) AND ol.Despatch_ID=0 AND ((o.Total=0) OR (o.Total>0 AND o.TotalTax>0)) AND o.Is_Declined=\'N\' AND o.Is_Failed=\'N\' AND o.Is_Warehouse_Declined=\'N\' AND o.Is_Awaiting_Customer=\'N\' AND o.Is_Collection=\'N\' AND o.Status NOT IN (\'Despatched\', \'Cancelled\', \'Incomplete\', \'Unauthenticated\')');
$sqlGroup = sprintf('GROUP BY o.Order_ID ');

if($form->GetValue('status') == 'N') {
	$sqlWhere .= sprintf('AND o.Status IN (\'Partially Despatched\', \'Packing\') ');
	
} elseif($form->GetValue('status') == 'PK') {
	$sqlWhere .= sprintf('AND o.Status IN (\'Packing\') ');
				
} elseif($form->GetValue('status') == 'PD') {
	$sqlWhere .= sprintf('AND o.Status IN (\'Partially Despatched\') ');
	
} else {
	$sqlWhere .= sprintf('AND (o.Backordered=\'Y\' OR ol.Line_Status LIKE \'Backordered\') ');
}

echo '<br />';

$window = new StandardWindow('Order legend');
$webForm = new StandardForm();

echo $form->Open();
echo $window->Open();
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow('Keys', '<div style="padding: 2px 0;"><img src="images/legend/red.gif" alt="" align="absmiddle" /> VAT free orders.</div><div style="padding: 2px 0;"><img src="images/legend/orange.gif" alt="" align="absmiddle" /> Restocked undeclined warehouse orders.</div><div style="padding: 2px 0;"><img src="images/legend/yellow.gif" alt="" align="absmiddle" /> Next day delivery orders.</div><div style="padding: 2px 0;"><img src="images/legend/green.gif" alt="" align="absmiddle" /> Undeclined warehouse orders.</div><div style="padding: 2px 0;"><img src="images/legend/cyan.gif" alt="" align="absmiddle" /> Value over &pound;100.00 orders.</div><div style="padding: 2px 0;"><img src="images/legend/blue.gif" alt="" /> Plain despatch label orders.</div><div style="padding: 2px 0;"><img src="images/legend/purple.gif" alt="" /> Contains absent stock for products in profile.</div>');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

echo '<br />';

$table = new DataTable("orders");
$table->SetSQL($sqlSelect.$sqlFrom.$sqlWhere.$sqlGroup);
$table->AddBackgroundCondition('Is_Restocked', 'Y', '==', '#FFD399', '#EEB577');
$table->AddBackgroundCondition('Postage_Days', '1', '==', '#FFF499', '#EEE177');
$table->AddBackgroundCondition('Is_Absent_Stock_Profile', 'Y', '==', '#9F77EE', '#BB99FF');
$table->AddBackgroundCondition('Is_Warehouse_Undeclined', 'Y', '==', '#99FF99', '#77EE77');
$table->AddBackgroundCondition('TaxExemptCode', '', '!=', '#FF9999', '#EE7777');
$table->AddBackgroundCondition('Is_Plain_Label', 'Y', '==', '#99C5FF', '#77B0EE');
$table->AddBackgroundCondition('Total_Net', '100.00', '>', '#99FFFB', '#8EECE8');
$table->AddField('', 'Is_Restocked', 'hidden');
$table->AddField('', 'TaxExemptCode', 'hidden');
$table->AddField('', 'Is_Warehouse_Undeclined', 'hidden');
$table->AddField('', 'Postage_Days', 'hidden');
$table->AddField('', 'Is_Plain_Label', 'hidden');
$table->AddField('', 'Total_Net', 'hidden');
$table->AddField('', 'Is_Absent_Stock_Profile', 'hidden');
$table->AddField('Order Date', 'Ordered_On', 'left');
$table->AddField('Organisation', 'Billing_Organisation_Name', 'left');
$table->AddField('Name', 'Billing_First_Name', 'left');
$table->AddField('Surname', 'Billing_Last_Name', 'left');
$table->AddField('Prefix', 'Order_Prefix', 'left');
$table->AddField('Number', 'Order_ID', 'right');
$table->AddField('Total', 'Total', 'right');
$table->AddField('Status', 'Status', 'right');
$table->AddField('Postage', 'Postage_Title', 'left');
$table->AddField('Backordered', 'Backordered', 'center');
$table->AddLink("order_package_details.php?orderid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Order Details\" border=\"0\">", "Order_ID");
$table->SetMaxRows(25);
$table->SetOrderBy("Ordered_On");
$table->Order = "DESC";
$table->Finalise();
$table->DisplayTable();
echo '<br />';
$table->DisplayNavigation();

$page->Display('footer');
require_once('lib/common/app_footer.php');