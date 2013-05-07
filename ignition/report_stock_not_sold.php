<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');

ini_set('max_execution_time', 600);

if($action == 'export') {
	$session->Secure(2);
	export();
	exit();
} else if($action == 'writeoff') {
	$session->Secure(2);
	writeoff($_REQUEST['sid']);
	exit();
} else if($action == 'report'){
	$session->Secure(2);
	report($_REQUEST['warehouse'], $_REQUEST['lastOrderedMonths'], $_REQUEST['writtenOff']);
} else {
	$session->Secure(2);
	start();
	exit();
}


function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	$form->AddField('writtenOff', 'Show Me', 'select', $_REQUEST['writtenOff'], 'anything', 1, 1);
	$form->AddOption('writtenOff', 'N', 'Stock which has not been written off');
	$form->AddOption('writtenOff', 'Y', 'Stock which has been written off');

	$form->AddField('warehouse', 'from the Warehouse', 'select', '', 'anything', 1, 1);
	$warehouseData = new DataQuery("select Warehouse_ID, Warehouse_Name from warehouse where Type = 'B'");
	while($warehouseData->Row){
		$form->AddOption('warehouse', $warehouseData->Row['Warehouse_ID'], $warehouseData->Row['Warehouse_Name']);
		$warehouseData->Next();
	}

	$form->AddField('lastOrderedMonths', 'Not sold in the last \'X\' months', 'text', 6, 'numeric_unsigned', 1, 3);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate() && $form->GetValue('lastOrderedMonths') > 0) {
			report($_REQUEST['warehouse'], $_REQUEST['lastOrderedMonths'], $_REQUEST['writtenOff']);
			exit;
		}
	}

	$page = new Page('Stock not Sold Report', 'Please choose a warehouse and last ordered for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Stock not Sold.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select a Warehouse for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('writtenOff'), $form->GetHTML('writtenOff'));
	echo $webForm->AddRow($form->GetLabel('warehouse'), $form->GetHTML('warehouse'));
	echo $webForm->AddRow($form->GetLabel('lastOrderedMonths'), $form->GetHTML('lastOrderedMonths') . '&nbsp;&nbsp;<i>only applicable for stock which has not been written off</i>');
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($warehouse, $months, $writtenOff) {
	$fromLocation = '';
	$warehouseSql = new DataQuery(sprintf("select Warehouse_Name from warehouse where Warehouse_ID = %d", mysql_real_escape_string($warehouse)));
	if(isset($warehouseSql->Row['Warehouse_Name'])){
		$fromLocation = ' from ' . $warehouseSql->Row['Warehouse_Name'];
	}

	$having = sprintf('having OrderedOn < DATE_SUB(CURDATE(),INTERVAL %d MONTH)', mysql_real_escape_string($months));
	if($writtenOff == 'Y'){
		$having = '';
	}

	$writtenOffWhere = sprintf("and ws.Is_Writtenoff = '%s'", mysql_real_escape_string($writtenOff));

	$sqlBody = sprintf("from warehouse_stock as ws
join product as p on ws.Product_ID=p.Product_ID and p.Product_Type <> 'G'
join product_prices_current as ppc on p.Product_ID=ppc.Product_ID
left join (
	select ol.Product_ID, max(o.Ordered_On) as OrderedOn from order_line as ol
	join orders as o on ol.Order_ID=o.Order_ID
	group by ol.Product_ID
	%s
) as ol1 on ol1.Product_ID=ws.Product_ID
where ws.Warehouse_ID=%d and ol1.OrderedOn is not null and ws.Quantity_In_Stock > 0 %s", mysql_real_escape_string($having), mysql_real_escape_string($warehouse), $writtenOffWhere);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('warehouse','Warehouse','hidden', $warehouse, 'anything', 1, NULL, false);
	$form->AddField('lastOrderedMonths', 'Months', 'hidden', $months, 'anything', 1, NULL, false);
	$form->AddField('writtenOff','Written Off','hidden', $writtenOff, 'anything', 1, NULL, false);

	$form->AddField('date', 'Date Written-Off', 'text', $date, 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('users', 'Written-Off by', 'select', ($warehouseStock->WrittenOffBy > 0)?$warehouseStock->WrittenOffBy:$session->UserID, 'anything', 1, null);
	$data = new DataQuery(sprintf("SELECT c.Account_Manager_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Account_Manager FROM users AS u INNER JOIN contact AS c ON c.Account_Manager_ID=u.User_ID INNER JOIN person AS p ON p.Person_ID=u.Person_ID GROUP BY c.Account_Manager_ID"));
	while($data->Row) {
		$form->AddOption('users', $data->Row['Account_Manager_ID'], $data->Row['Account_Manager']);
		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['writeoffstock']) && strtolower($_REQUEST['writeoffstock']) == 'writeoff selected'){
		$form->Validate();
		if($form->Valid){
			$stockIds = array();
			$keyPrefixLength = strlen('stock_');
			foreach($_REQUEST as $key => $value){
				if(stristr($key, 'stock_') === false){
					continue;
				}

				$stockIds[] = mysql_real_escape_string(trim(substr($key, $keyPrefixLength)));
			}
			if(count($stockIds) > 0 && $form->GetValue('users') > 0){
				$date = (strlen($form->GetValue('date')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('date'), 6, 4), substr($form->GetValue('date'), 3, 2), substr($form->GetValue('date'), 0, 2)) : $warehouseStock->WrittenOffOn;
				new DataQuery(sprintf("update warehouse_stock
					set Is_Writtenoff = 'Y', Writtenoff_By = %d, Writtenoff_On = '%s'
					where %s", $form->GetValue('users'), $date, 'Stock_ID = ' . join(' or Stock_ID = ', $stockIds)));
			}
		}
	} elseif(isset($_REQUEST['writeoffstock']) && strtolower($_REQUEST['writeoffstock']) == 'writeoff all'){
		$form->Validate();
		if($form->Valid){
			$data = new DataQuery(sprintf("select ws.Stock_ID %s", $sqlBody));
			while($data->Row){
				new DataQuery(sprintf("update warehouse_stock
					set Is_Writtenoff = 'Y', Writtenoff_By = %d, Writtenoff_On = '%s'
					where Stock_ID = %d", $form->GetValue('users'), $date, $data->Row['Stock_ID']));
				$data->Next();
			}
			$data->Disconnect();
		}
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('warehouse');
	echo $form->GetHTML('lastOrderedMonths');
	echo $form->GetHTML('writtenOff');

	if($writtenOff == 'Y'){
		$page = new Page('Report on Stock which has been written off ' . $fromLocation, '');
	} elseif($writtenOff == 'N') {
		$page = new Page('Report on Stock not Sold in ' . $months . ' months' . $fromLocation, '');
	}

	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	$totalValue = 0;
	$data = new DataQuery(sprintf("select SUM(ws.Quantity_In_Stock*ws.Cost) as Total_Value, SUM(ws.Quantity_In_Stock*ppc.Price_Base_Our) as Total_Price %s", $sqlBody));
	if($data->TotalRows > 0){
		$totalValue = $data->Row['Total_Value'];
		$totalPrice = $data->Row['Total_Price'];
	}
	$data->Disconnect();

	echo '<strong>Total Cost : ' . $totalValue . '</strong><br />';
	echo '<strong>Total Price : ' . $totalPrice . '</strong><br /><br />';

	$window = new StandardWindow("Report on Stock not Sold.");
	$webForm = new StandardForm;

	echo $window->Open();
	echo $window->AddHeader('Warehouse: ' . $warehouseSql->Row['Warehouse_Name']);

	$table = new DataTable("stock");
	$table->ExtractVars = '';
	$table->SetSQL(sprintf("select ws.Product_ID, p.Product_Title, ws.Shelf_Location, ws.Quantity_In_Stock, ol1.OrderedOn, ws.Cost, (ws.Quantity_In_Stock*ws.Cost) as Value, (ws.Quantity_In_Stock*ppc.Price_Base_Our) as Price %s", $sqlBody));
	$table->AddField('Product ID','Product_ID');
	$table->AddField('Product Title','Product_Title');
	$table->AddField('Location','Shelf_Location');
	$table->AddField('Quantity','Quantity_In_Stock','right');
	$table->AddField('Last Ordered','OrderedOn');
	$table->AddField('Unit Cost','Cost','right');
	$table->AddField('Line Cost','Value','right');
	$table->AddField('Line Price','Price','right');
	if($writtenOff != 'Y'){
		$table->AddInput('', 'N', '', 'stock', 'Stock_ID', 'checkbox', 'checked="checked"');
		$table->AddLink('report_stock_not_sold.php?action=writeoff&sid=%s',"<img src=\"./images/icon_cross_4.gif\" alt=\"Update\" border=\"0\">",'Stock_ID');
	}
	$table->SetOrderBy('OrderedOn');
	$table->SetMaxRows(100);
	$table->Finalise();
	$table->DisplayTable();
	$table->DisplayNavigation();

	echo $window->OpenContent();
	echo '<input type="button" name="export" value="export" class="btn" onclick="window.self.location.href=\'?action=export&lastOrderedMonths=' . $months . '&warehouse=' . $warehouse . '&writtenOff=' . $writtenOff . '\';" />';
	echo $window->CloseContent();
	echo $window->Close();

	if($writtenOff != 'Y'){
		echo '<br />';

		echo $window->Open();
		echo $window->AddHeader('Writeoff Selected Stock');
		echo $window->OpenContent();
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('date'),$form->GetHTML('date').$form->GetIcon('date'));
		echo $webForm->AddRow($form->GetLabel('users'),$form->GetHTML('users').$form->GetIcon('users'));
		echo $webForm->AddRow('&nbsp;', '<input type="submit" name="writeoffstock" value="writeoff selected" class="btn" />&nbsp;<input type="submit" name="writeoffstock" value="writeoff all" class="btn" />');
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
	}
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function writeoff($stockId) {
	$fromLocation = '';
	$warehouseSql = new DataQuery(sprintf("select Warehouse_Name from warehouse where Warehouse_ID = %d", mysql_real_escape_string($_REQUEST['warehouse'])));
	if(isset($warehouseSql->Row['Warehouse_Name'])){
		$fromLocation = ' from ' . $warehouseSql->Row['Warehouse_Name'];
	}

	$warehouseStock = new WarehouseStock($stockId);

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
	$form->AddField('warehouse','Warehouse','hidden',$_REQUEST['warehouse'],'anything', 1, 4, false);
	$form->AddField('lastOrderedMonths', 'Months', 'hidden', $_REQUEST['lastOrderedMonths'], 'anything', 1, 4, false);
	$form->AddField('writtenOff','Written Off','hidden',$_REQUEST['writtenOff'],'anything', 1, 4, false);

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

			redirect(sprintf("Location: report_stock_not_sold.php?action=report&confirm=true&warehouse=%d&lastOrderedMonths=%d&writtenOff=%s", $form->GetValue('warehouse'), $form->GetValue('lastOrderedMonths'), $form->GetValue('writtenOff')));
		}
	}

	$data = new DataQuery(sprintf("SELECT * FROM product WHERE Product_ID=%d", mysql_real_escape_string($warehouseStock->Product->ID)));
	$page = new Page(sprintf('<a href="report_stock_not_sold.php?action=report&confirm=true&warehouse=%d&lastOrderedMonths=%d">Report on Stock not Sold in %d months %s</a> &gt; Update the warehouse stock', $form->GetValue('warehouse'), $form->GetValue('lastOrderedMonths'), $form->GetValue('lastOrderedMonths'), $fromLocation), 'Update the warehouse stock');
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
	echo $form->GetHTML('warehouse');
	echo $form->GetHTML('lastOrderedMonths');
	echo $form->GetHTML('writtenOff');
	echo $window->Open();
	echo $window->AddHeader('Please fill in the details below');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('Product', strip_tags($data->Row['Product_Title']);
	echo $webForm->AddRow('Stock Quantity', $warehouseStock->QuantityInStock);
	echo $webForm->AddRow('Value', $warehouseStock->Cost . ' e.a.');
	echo $webForm->AddRow('Total Value', $warehouseStock->QuantityInStock * $warehouseStock->Cost);

	echo $webForm->AddRow($form->GetLabel('date'),$form->GetHTML('date').$form->GetIcon('date'));
	echo $webForm->AddRow($form->GetLabel('users'),$form->GetHTML('users').$form->GetIcon('users'));


	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'report_stock_not_sold.php?action=report&confirm=true&warehouse=%d&lastOrderedMonths=%d&writtenOff=%s\';"> <input type="submit" name="add" value="update" class="btn" tabindex="%s">', $form->GetValue('warehouse'), $form->GetValue('lastOrderedMonths'), $form->GetValue('writtenOff'), $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function export() {
	$writtenOff = $_REQUEST['writtenOff'];
	$warehouseId = $_REQUEST['warehouse'];
	$warehouse = '';
	$warehouseSql = new DataQuery(sprintf("select Warehouse_Name from warehouse where Warehouse_ID = %d", mysql_real_escape_string($warehouseId)));
	if(isset($warehouseSql->Row['Warehouse_Name'])){
		$warehouse = str_replace(' ', '_', $warehouseSql->Row['Warehouse_Name']) . '_';
	}

	$months = $_REQUEST['lastOrderedMonths'];

	$having = sprintf('having OrderedOn < DATE_SUB(CURDATE(),INTERVAL %d MONTH)', mysql_real_escape_string($months));
	if($writtenOff == 'Y'){
		$having = '';
	}

	$writtenOffWhere = sprintf("and ws.Is_Writtenoff = '%s'", mysql_real_escape_string($writtenOff));

	$fileDate = getDatetime();
	$fileDate = substr($fileDate, 0, strpos($fileDate, ' '));

	if($writtenOff == 'Y'){
		$fileName = sprintf('%sstock_written_off_%s.csv', $warehouse, $fileDate);
	} else {
		$fileName = sprintf('%sstock_not_sold_in_%dmonths_%s.csv', $warehouse, $months, $fileDate);
	}

	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	header("Content-Disposition: attachment; filename=" . basename($fileName) . ";");
	header("Content-Transfer-Encoding: binary");

	$line = array();
	$line[] = 'Product ID';
	$line[] = 'Product Title';
	$line[] = 'Location';
	$line[] = 'Quantity';
	$line[] = 'Last Ordered';
	$line[] = 'Unit Cost';
	$line[] = 'Line Cost';
	$line[] = 'Line Price';

	echo getCsv($line);

	$stock = array();
	$data = new DataQuery(sprintf("select ws.Product_ID, p.Product_Title, ws.Shelf_Location, ws.Quantity_In_Stock, ol1.OrderedOn, ws.Cost, (ws.Quantity_In_Stock*ws.Cost) as Value, (ws.Quantity_In_Stock*ppc.Price_Base_Our) as Price
from warehouse_stock as ws
join product as p on ws.Product_ID=p.Product_ID and p.Product_Type <> 'G'
join product_prices_current as ppc on p.Product_ID=ppc.Product_ID
left join (
	select ol.Product_ID, max(o.Ordered_On) as OrderedOn from order_line as ol
	join orders as o on ol.Order_ID=o.Order_ID
	group by ol.Product_ID
	%s
) as ol1 on ol1.Product_ID=ws.Product_ID
where ws.Warehouse_ID=%d and ol1.OrderedOn is not null and ws.Quantity_In_Stock > 0 %s", mysql_real_escape_string($having), mysql_real_escape_string($warehouseId), $writtenOffWhere));
	while($data->Row){
		$stock[] = $data->Row;
		$data->Next();
	}
	$data->Disconnect();

	foreach($stock as $item) {
		$line = array();
		$line[] = $item['Product_ID'];
		$line[] = $item['Product_Title'];
		$line[] = $item['Shelf_Location'];
		$line[] = $item['Quantity_In_Stock'];
		$line[] = $item['OrderedOn'];
		$line[] = $item['Cost'];
		$line[] = $item['Value'];
		$line[] = $item['Price'];

		echo getCsv($line);
	}
}

function getCsv($row, $fd=',', $quot='"') {
	$str ='';

	foreach($row as $cell){
		$cell = str_replace($quot, $quot.$quot, $cell);

		if((strchr($cell, $fd) !== false) || (strchr($cell, $quot) !== false) || (strchr($cell, "\n") !== false)) {
			$str .= $quot.$cell.$quot.$fd;
		} else {
			$str .= $quot.$cell.$quot.$fd;
		}
	}

	return substr($str, 0, -1)."\n";
}