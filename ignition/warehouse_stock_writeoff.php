<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');

$session->Secure(3);

$warehouseStock = new WarehouseStock($_REQUEST['sid']);

$date = '';

if($warehouseStock->WrittenOffOn > '0000-00-00 00:00:00') {
	$date = date('d/m/Y', strtotime($warehouseStock->WrittenOffOn));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('sid','sid','hidden',$_REQUEST['sid'],'numeric_unsigned',0,11);
$form->AddField('direct','Direct','hidden',$direct,'anything', 1, 32, false);
$form->AddField('redirect', 'Redirect', 'hidden', urlencode($redirect), 'anything', 1, 1024, false);

$form->AddField('date', 'Date Written-Off', 'text', $date, 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('users', 'Written-Off by', 'select', ($warehouseStock->WrittenOffBy > 0)?$warehouseStock->WrittenOffBy:$session->UserID, 'anything', 1, null);
$data = new DataQuery(sprintf("SELECT c.Account_Manager_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Account_Manager FROM users AS u INNER JOIN contact AS c ON c.Account_Manager_ID=u.User_ID INNER JOIN person AS p ON p.Person_ID=u.Person_ID GROUP BY c.Account_Manager_ID"));
while($data->Row) {
	$form->AddOption('users', $data->Row['Account_Manager_ID'], $data->Row['Account_Manager']);
	$data->Next();
}
$data->Disconnect();

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	$form->Validate();

	$warehouseStock->IsWrittenOff = 'Y';
	$warehouseStock->WrittenOffBy = $form->GetValue('users');
	$warehouseStock->WrittenOffOn = (strlen($form->GetValue('date')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('date'), 6, 4), substr($form->GetValue('date'), 3, 2), substr($form->GetValue('date'), 0, 2)) : $warehouseStock->WrittenOffOn;

	if($form->Valid){
		$warehouseStock->Update();

		redirect(sprintf("Location: warehouse_stock_view.php?pid=%d", $warehouseStock->Product->ID));
	}
}

$data = new DataQuery(sprintf("SELECT * FROM product WHERE Product_ID=%d", mysql_real_escape_string($warehouseStock->Product->ID)));
$page = new Page(sprintf("<a href='product_profile.php?pid=%d'> %s </a> &gt; <a href='warehouse_stock_view.php?pid=%s'>Warehouse Stock</a> &gt; Write-Off stock", $warehouseStock->Product->ID, strip_tags($data->Row['Product_Title']), $warehouseStock->Product->ID),"Update the warehouse stock");
$data->Disconnect();

$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}
$window = new StandardWindow("Write-Off an item of stock.");
$webForm = new StandardForm;
echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('action');
echo $form->GetHTML('sid');
echo $window->Open();
echo $window->AddHeader('Please fill in the details below');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow('Product', strip_tags($data->Row['Product_Title']));
echo $webForm->AddRow('Stock Quantity', $warehouseStock->QuantityInStock);
echo $webForm->AddRow('Value', $warehouseStock->Cost . ' e.a.');
echo $webForm->AddRow('Total Value', $warehouseStock->QuantityInStock * $warehouseStock->Cost);

echo $webForm->AddRow($form->GetLabel('date'),$form->GetHTML('date').$form->GetIcon('date'));
echo $webForm->AddRow($form->GetLabel('users'),$form->GetHTML('users').$form->GetIcon('users'));


echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'warehouse_stock_view.php?pid=%s\';"> <input type="submit" name="add" value="update" class="btn" tabindex="%s">', $form->GetValue('product'), $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$page->Display('footer');