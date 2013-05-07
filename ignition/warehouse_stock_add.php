<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');

$session->Secure(3);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('pid','pid','hidden',$_REQUEST['pid'],'numeric_unsigned',0,11);
$form->AddField('manufacturer', 'Manufacturer', 'select', '0', 'numeric_unsigned', 1, 11);
$form->AddOption('manufacturer', '0', '');

$data = new DataQuery(sprintf("SELECT Manufacturer_ID, Manufacturer_Name FROM manufacturer ORDER BY Manufacturer_Name ASC"));
while($data->Row) {
	$form->AddOption('manufacturer', $data->Row['Manufacturer_ID'], $data->Row['Manufacturer_Name']);

	$data->Next();	
}
$data->Disconnect();
	
$form->AddField('location','Shelf Location','text','','alpha_numeric',1,45);
$form->AddField('wid','Warehouse','select','','alpha_numeric',1,60);
$form->AddOption('wid','','');
$form->AddGroup('wid','B','Branches');
$form->AddGroup('wid','S','Suppliers');

$data = new DataQuery("SELECT * FROM warehouse ORDER BY Warehouse_Name ASC");
while ($data->Row) {
	$form->AddOption('wid',$data->Row['Warehouse_ID'],$data->Row['Warehouse_Name'],$data->Row['Type']);
	$data->Next();
}
$data->Disconnect();

$form->AddField('stock', 'Quantity', 'text', '', 'numeric_unsigned',1,11);
$form->AddField('cost', 'Cost', 'text', '', 'float', 1, 11);
$form->AddField('isarchived', 'Is Archived', 'checkbox', 'N', 'boolean', 1, 1, false);
$form->AddField('stocked','Is Stocked','select','N','alpha',0,2);
$form->AddOption('stocked','N','No');
$form->AddOption('stocked','Y','Yes');
$form->AddField('imported','Is Stock Imported','select','N','alpha',0,2);
$form->AddOption('imported','N','No');
$form->AddOption('imported','Y','Yes');
$form->AddField('moniter','Monitor Stock','select','N','alpha',0,2);
$form->AddOption('moniter','N','No');
$form->AddOption('moniter','Y','Yes');
$form->AddField('isbackordered', 'Is Backordered', 'checkbox', 'N', 'boolean', 1, 1, false);
$form->AddField('backorderexpectedon', 'Backorder Expected On', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$warehouseStock = new WarehouseStock();
		$warehouseStock->Manufacturer->ID = $form->GetValue('manufacturer');
		$warehouseStock->Product->ID = $form->GetValue('pid');
		$warehouseStock->Location = $form->GetValue('location');
		$warehouseStock->QuantityInStock = $form->GetValue('stock');
		$warehouseStock->Cost = $form->GetValue('cost');
		$warehouseStock->IsArchived = $form->GetValue('isarchived');
		$warehouseStock->Warehouse->ID = $form->GetValue('wid');
		$warehouseStock->Stocked = $form->GetValue('stocked');
		$warehouseStock->Imported = $form->GetValue('imported');
		$warehouseStock->Moniter = $form->GetValue('moniter');
		$warehouseStock->IsBackordered = $form->GetValue('isbackordered');
		$warehouseStock->BackorderExpectedOn = (strlen($form->GetValue('backorderexpectedon')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('backorderexpectedon'), 6, 4), substr($form->GetValue('backorderexpectedon'), 3, 2), substr($form->GetValue('backorderexpectedon'), 0, 2)) : $warehouseStock->BackorderExpectedOn;
		$warehouseStock->Add();

		$data = new DataQuery(sprintf("SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Product_ID=%d AND ol.Despatch_From_ID=%d AND ol.Line_Status NOT LIKE 'Invoiced' AND ol.Line_Status NOT LIKE 'Cancelled' AND ol.Line_Status NOT LIKE 'Despatched' GROUP BY o.Order_ID", mysql_real_escape_string($warehouseStock->Product->ID), mysql_real_escape_string($warehouseStock->Warehouse->ID)));
		while($data->Row) {
			$order = new Order($data->Row['Order_ID']);
			$order->IsWarehouseBackordered = $warehouseStock->IsBackordered;
			$order->Update();

			$data->Next();
		}
		$data->Disconnect();

		redirect(sprintf("Location: warehouse_stock_view.php?pid=%d",$_REQUEST['pid']));
	}
}

$pageTitle = new DataQuery(sprintf("SELECT * FROM product WHERE Product_ID = %d",mysql_real_escape_string($_REQUEST['pid'])));
$page = new Page(sprintf("<a href='product_profile.php?pid=%d'> %s </a> &gt; <a href='warehouse_stock_view.php?pid=%s'>Warehouse Stock</a> &gt; Add new stock",$_REQUEST['pid'],strip_tags($pageTitle->Row['Product_Title']),$_REQUEST['pid']),"Add this product to a warehouses stock");
$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$pageTitle->Disconnect();
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

$window = new StandardWindow("Add an item of stock.");
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('action');
echo $form->GetHTML('pid');
echo $window->Open();
echo $window->AddHeader('Please select a warehouse');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('wid'), $form->GetHTML('wid').$form->GetIcon('wid'));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->AddHeader('Please fill in the rest of the stock details');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('manufacturer'),$form->GetHTML('manufacturer').$form->GetIcon('manufacturer'));
echo $webForm->AddRow($form->GetLabel('location'),$form->GetHTML('location').$form->GetIcon('location'));
echo $webForm->AddRow($form->GetLabel('stock'),$form->GetHTML('stock').$form->GetIcon('stock'));
echo $webForm->AddRow($form->GetLabel('cost'),$form->GetHTML('cost').$form->GetIcon('cost'));
echo $webForm->AddRow($form->GetLabel('isarchived'),$form->GetHTML('isarchived').$form->GetIcon('isarchived'));
echo $webForm->AddRow($form->GetLabel('stocked'),$form->GetHTML('stocked').$form->GetIcon('stocked'));
echo $webForm->AddRow($form->GetLabel('imported'),$form->GetHTML('imported').$form->GetIcon('imported'));
echo $webForm->AddRow($form->GetLabel('moniter'),$form->GetHTML('moniter').$form->GetIcon('moniter'));
echo $webForm->AddRow($form->GetLabel('isbackordered'),$form->GetHTML('isbackordered').$form->GetIcon('isbackordered'));
echo $webForm->AddRow($form->GetLabel('backorderexpectedon'),$form->GetHTML('backorderexpectedon').$form->GetIcon('backorderexpectedon'));
echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'warehouse_stock_view.php?pid=%s\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">',$_REQUEST['pid'], $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$page->Display('footer');