<?php
ini_set('max_execution_time', '7200');
ini_set('memory_limit', '1024M');

chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cron.php');

$cron = new Cron();
$cron->scriptName = 'Just Lamps Integration';
$cron->scriptFileName = 'integrate_justlamps.php';
$cron->mailLogLevel = Cron::LOG_LEVEL_WARNING;

## BEGIN SCRIPT
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/WarehouseStock.php');

$data = new DataQuery(sprintf("SELECT Type_Reference_ID FROM warehouse WHERE Warehouse_ID=%d", $GLOBALS['GL_WAREHOUSE']));
$targetSupplierId = ($data->TotalRows > 0) ? $data->Row['Type_Reference_ID'] : 0;
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT p.Product_ID, sp.Supplier_SKU FROM supplier AS s INNER JOIN supplier_product AS sp ON sp.Supplier_ID=s.Supplier_ID AND sp.Supplier_SKU<>'' INNER JOIN product AS p ON p.Product_ID=sp.Product_ID WHERE s.Supplier_ID=%d ORDER BY sp.Supplier_SKU ASC", mysql_real_escape_string($targetSupplierId)));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT Stock_ID FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d", mysql_real_escape_string($GLOBALS['GL_WAREHOUSE']), $data->Row['Product_ID']));
	$warehouseStockId = ($data2->TotalRows > 0) ? $data2->Row['Stock_ID'] : 0;
	$data2->Disconnect();

	$quantity = file_get_contents(sprintf('http://lampslookup.acuras.co.uk/?stockcode=%s', $data->Row['Supplier_SKU']));

	if($warehouseStockId > 0) {
		$stock = new WarehouseStock($warehouseStockId);
		$stock->QuantityInStock = $quantity;
		$stock->Update();
	} else {
		$stock = new WarehouseStock();
		$stock->Product->ID = $data->Row['Product_ID'];
		$stock->QuantityInStock = $quantity;
		$stock->Warehouse->ID = $GLOBALS['GL_WAREHOUSE'];
		$stock->Stocked = 'Y';
		$stock->Moniter = 'Y';
		$stock->Add();
	}

	$cron->log(sprintf('Updated Stock SKU: %s, Quantity: %d', $data->Row['Supplier_SKU'], $quantity), Cron::LOG_LEVEL_INFO);

	$data->Next();
}
$data->Disconnect();
## END SCRIPT

$cron->execute();
$cron->output();

$GLOBALS['DBCONNECTION']->Close();