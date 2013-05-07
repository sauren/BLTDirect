<?php
ini_set('max_execution_time', '1800');
ini_set('memory_limit', '512M');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cron.php');

$cron = new Cron();
$cron->scriptName = 'Cache Product';
$cron->scriptFileName = 'cache_product.php';
$cron->mailLogLevel = Cron::LOG_LEVEL_WARNING;

## BEGIN SCRIPT

# Positions General
new DataQuery(sprintf("UPDATE product SET Position_Quantities=0, Position_Quantities_Recent=0, Position_Orders=0, Position_Orders_Recent=0, Total_Quantities=0, Total_Orders=0"));

$positionQuantities = array();
$positionOrders = array();

$products = array();

$data = new DataQuery(sprintf("SELECT ol.Product_ID, SUM(ol.Quantity) AS Quantities, COUNT(DISTINCT o.Order_ID) AS Orders FROM order_line AS ol INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -3 MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 GROUP BY ol.Product_ID"));
while($data->Row) {
	if(!isset($products[$data->Row['Product_ID']])) {
		$products[$data->Row['Product_ID']] = array('Orders' => 0, 'Quantities' => 0);
	}
	
	$products[$data->Row['Product_ID']]['Orders'] += $data->Row['Orders'];
	$products[$data->Row['Product_ID']]['Quantities'] += $data->Row['Quantities'];

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT pc.Product_ID, SUM(ol.Quantity*pc.Component_Quantity) AS Quantities, COUNT(DISTINCT o.Order_ID) AS Orders FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -3 MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 GROUP BY pc.Product_ID"));
while($data->Row) {
	if(!isset($products[$data->Row['Product_ID']])) {
		$products[$data->Row['Product_ID']] = array('Orders' => 0, 'Quantities' => 0);
	}
	
	$products[$data->Row['Product_ID']]['Orders'] += $data->Row['Orders'];
	$products[$data->Row['Product_ID']]['Quantities'] += $data->Row['Quantities'];

	$data->Next();
}
$data->Disconnect();

foreach($products as $productId=>$product) {
	if(!isset($positionQuantities[$product['Quantities']])) {
		$positionQuantities[$product['Quantities']] = array();
	}

	if(!isset($positionOrders[$product['Orders']])) {
		$positionOrders[$product['Orders']] = array();
	}

	$positionQuantities[$product['Quantities']][] = $productId;
	$positionOrders[$product['Orders']][] = $productId;
}
	
krsort($positionQuantities);
krsort($positionOrders);

$index = 1;

foreach($positionQuantities as $position) {
	$jointPosition = $index;

	foreach($position as $productId) {
		new DataQuery(sprintf("UPDATE product SET Position_Quantities_Recent=%d WHERE Product_ID=%d", mysql_real_escape_string($jointPosition), mysql_real_escape_string($productId)));

		$index++;
	}
}

$cron->log(sprintf('Cached: %d Recent Quantity Positions', $index - 1), Cron::LOG_LEVEL_INFO);

$index = 1;

foreach($positionOrders as $position) {
	$jointPosition = $index;

	foreach($position as $productId) {
		new DataQuery(sprintf("UPDATE product SET Position_Orders_Recent=%d WHERE Product_ID=%d", mysql_real_escape_string($jointPosition), mysql_real_escape_string($productId)));

		$index++;
	}
}

$cron->log(sprintf('Cached: %d Recent Order Positions', $index - 1), Cron::LOG_LEVEL_INFO);

$positionQuantities = array();
$positionOrders = array();

$products = array();

$data = new DataQuery(sprintf("SELECT ol.Product_ID, SUM(ol.Quantity) AS Quantities, COUNT(DISTINCT o.Order_ID) AS Orders FROM order_line AS ol INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 GROUP BY ol.Product_ID"));
while($data->Row) {
	if(!isset($products[$data->Row['Product_ID']])) {
		$products[$data->Row['Product_ID']] = array('Orders' => 0, 'Quantities' => 0);
	}
	
	$products[$data->Row['Product_ID']]['Orders'] += $data->Row['Orders'];
	$products[$data->Row['Product_ID']]['Quantities'] += $data->Row['Quantities'];

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT pc.Product_ID, SUM(ol.Quantity*pc.Component_Quantity) AS Quantities, COUNT(DISTINCT o.Order_ID) AS Orders FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 GROUP BY pc.Product_ID"));
while($data->Row) {
	if(!isset($products[$data->Row['Product_ID']])) {
		$products[$data->Row['Product_ID']] = array('Orders' => 0, 'Quantities' => 0);
	}
	
	$products[$data->Row['Product_ID']]['Orders'] += $data->Row['Orders'];
	$products[$data->Row['Product_ID']]['Quantities'] += $data->Row['Quantities'];

	$data->Next();
}
$data->Disconnect();

foreach($products as $productId=>$product) {
	if(!isset($positionQuantities[$product['Quantities']])) {
		$positionQuantities[$product['Quantities']] = array();
	}

	if(!isset($positionOrders[$product['Orders']])) {
		$positionOrders[$product['Orders']] = array();
	}

	$positionQuantities[$product['Quantities']][] = $productId;
	$positionOrders[$product['Orders']][] = $productId;
	
	new DataQuery(sprintf("UPDATE product SET Total_Quantities=%d, Total_Orders=%d WHERE Product_ID=%d", mysql_real_escape_string($product['Quantities']), mysql_real_escape_string($product['Orders']), mysql_real_escape_string($productId)));
}

krsort($positionQuantities);
krsort($positionOrders);

$index = 1;

foreach($positionQuantities as $position) {
	$jointPosition = $index;

	foreach($position as $productId) {
		new DataQuery(sprintf("UPDATE product SET Position_Quantities=%d WHERE Product_ID=%d", mysql_real_escape_string($jointPosition), mysql_real_escape_string($productId)));

		$index++;
	}
}

$cron->log(sprintf('Cached: %d Quantity Positions', $index - 1), Cron::LOG_LEVEL_INFO);

$index = 1;

foreach($positionOrders as $position) {
	$jointPosition = $index;

	foreach($position as $productId) {
		new DataQuery(sprintf("UPDATE product SET Position_Orders=%d WHERE Product_ID=%d", mysql_real_escape_string($jointPosition), mysql_real_escape_string($productId)));

		$index++;
	}
}

$cron->log(sprintf('Cached: %d Order Positions', $index - 1), Cron::LOG_LEVEL_INFO);

# Positions Months
$months = array(3, 12);

$updates = array();

foreach($months as $month) {
	$updates[] = sprintf('Position_Quantities_%d_Month=0', $month);
	$updates[] = sprintf('Position_Orders_%d_Month=0', $month);
	$updates[] = sprintf('Total_Quantities_%d_Month=0', $month);
	$updates[] = sprintf('Total_Orders_%d_Month=0', $month);
}

new DataQuery(sprintf("UPDATE product SET %s", implode(', ', $updates)));

foreach($months as $month) {
	$positionQuantities = array();
	$positionOrders = array();
	
	$products = array();
	
	$data = new DataQuery(sprintf("SELECT ol.Product_ID, SUM(ol.Quantity) AS Quantities, COUNT(DISTINCT o.Order_ID) AS Orders FROM order_line AS ol INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 GROUP BY ol.Product_ID", mysql_real_escape_string($month)));
	while($data->Row) {
		if(!isset($products[$data->Row['Product_ID']])) {
			$products[$data->Row['Product_ID']] = array('Orders' => 0, 'Quantities' => 0);
		}
		
		$products[$data->Row['Product_ID']]['Orders'] += $data->Row['Orders'];
		$products[$data->Row['Product_ID']]['Quantities'] += $data->Row['Quantities'];
	
		$data->Next();
	}
	$data->Disconnect();
	
	$data = new DataQuery(sprintf("SELECT pc.Product_ID, SUM(ol.Quantity*pc.Component_Quantity) AS Quantities, COUNT(DISTINCT o.Order_ID) AS Orders FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 GROUP BY pc.Product_ID", mysql_real_escape_string($month)));
	while($data->Row) {
		if(!isset($products[$data->Row['Product_ID']])) {
			$products[$data->Row['Product_ID']] = array('Orders' => 0, 'Quantities' => 0);
		}
		
		$products[$data->Row['Product_ID']]['Orders'] += $data->Row['Orders'];
		$products[$data->Row['Product_ID']]['Quantities'] += $data->Row['Quantities'];
	
		$data->Next();
	}
	$data->Disconnect();

	foreach($products as $productId=>$product) {
		if(!isset($positionQuantities[$product['Quantities']])) {
			$positionQuantities[$product['Quantities']] = array();
		}

	    if(!isset($positionOrders[$product['Orders']])) {
			$positionOrders[$product['Orders']] = array();
		}

		$positionQuantities[$product['Quantities']][] = $productId;
		$positionOrders[$product['Orders']][] = $productId;
		
		new DataQuery(sprintf("UPDATE product SET Total_Quantities_%d_Month=%d, Total_Orders_%d_Month=%d WHERE Product_ID=%d", mysql_real_escape_string($month), mysql_real_escape_string($product['Quantities']), mysql_real_escape_string($month), mysql_real_escape_string($product['Orders']), mysql_real_escape_string($productId)));
	}

	krsort($positionQuantities);
	krsort($positionOrders);

	$index = 1;

	foreach($positionQuantities as $position) {
		$jointPosition = $index;

		foreach($position as $productId) {
			new DataQuery(sprintf("UPDATE product SET Position_Quantities_%d_Month=%d WHERE Product_ID=%d", mysql_real_escape_string($month), mysql_real_escape_string($jointPosition), mysql_real_escape_string($productId)));

			$index++;
		}
	}

	$cron->log(sprintf('Cached: %d %d Month Quantity Positions', $index - 1, $month), Cron::LOG_LEVEL_INFO);

	$index = 1;

	foreach($positionOrders as $position) {
		$jointPosition = $index;

		foreach($position as $productId) {
			new DataQuery(sprintf("UPDATE product SET Position_Orders_%d_Month=%d WHERE Product_ID=%d", mysql_real_escape_string($month), mysql_real_escape_string($jointPosition), mysql_real_escape_string($productId)));

			$index++;
		}
	}

	$cron->log(sprintf('Cached: %d %d Order Positions', $index - 1, $month), Cron::LOG_LEVEL_INFO);
}

# Costs
new DataQuery(sprintf("UPDATE product SET CacheBestCost=0, CacheBestSupplierID=0, CacheRecentCost=0"));

$products = array();

$data = new DataQuery(sprintf("SELECT sp.Product_ID, sp.Supplier_ID, sp.Cost FROM product AS p INNER JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID WHERE p.Product_Type<>'G' AND sp.Cost>0 ORDER BY Cost DESC"));
while($data->Row) {
	$products[$data->Row['Product_ID']] = $data->Row;

	$data->Next();
}
$data->Disconnect();

foreach($products as $productId=>$productData) {
	new DataQuery(sprintf("UPDATE product SET CacheBestCost=%f, CacheBestSupplierID=%d WHERE Product_ID=%d", mysql_real_escape_string($productData['Cost']), mysql_real_escape_string($productData['Supplier_ID']), mysql_real_escape_string($productData['Product_ID'])));
}

$cron->log(sprintf('Cached: %d Product Best Costs', $data->TotalRows), Cron::LOG_LEVEL_INFO);

$products = array();

$data = new DataQuery(sprintf("SELECT p.Product_ID, sp.Supplier_ID, sp.Cost*pc.Component_Quantity AS Cost FROM product AS p INNER JOIN product_components AS pc ON p.Product_ID=pc.Component_Of_Product_ID INNER JOIN supplier_product AS sp ON sp.Product_ID=pc.Product_ID WHERE p.Product_Type='G' AND sp.Cost>0 ORDER BY Cost DESC"));
while($data->Row) {
	$products[$data->Row['Product_ID']] = $data->Row;

	$data->Next();
}
$data->Disconnect();

foreach($products as $productId=>$productData) {
	new DataQuery(sprintf("UPDATE product SET CacheBestCost=%f, CacheBestSupplierID=%d WHERE Product_ID=%d", mysql_real_escape_string($productData['Cost']), mysql_real_escape_string($productData['Supplier_ID']), mysql_real_escape_string($productData['Product_ID'])));
}

$cron->log(sprintf('Cached: %d Product Best Costs', $data->TotalRows), Cron::LOG_LEVEL_INFO);

$products = array();

$data = new DataQuery(sprintf("SELECT pl.Product_ID, pl.Cost FROM product AS p INNER JOIN purchase_line AS pl ON pl.Product_ID=p.Product_ID AND pl.Quantity_Decremental=0 INNER JOIN purchase AS pu ON pu.Purchase_ID=pl.Purchase_ID AND pu.For_Branch>0 WHERE p.Product_Type<>'G' ORDER BY pu.Purchase_ID ASC"));
while($data->Row) {
	$products[$data->Row['Product_ID']] = $data->Row['Cost'];

	$data->Next();
}
$data->Disconnect();

foreach($products as $productId=>$cost) {
	new DataQuery(sprintf("UPDATE product SET CacheRecentCost=%f WHERE Product_ID=%d", mysql_real_escape_string($cost), mysql_real_escape_string($productId)));
}

$cron->log(sprintf('Cached: %d Product Recent Costs', count($products)), Cron::LOG_LEVEL_INFO);

$products = array();

$data = new DataQuery(sprintf("SELECT p.Product_ID, pl.Cost*pc.Component_Quantity AS Cost FROM product AS p INNER JOIN product_components AS pc ON pc.Component_Of_Product_ID=p.Product_ID INNER JOIN purchase_line AS pl ON pl.Product_ID=pc.Product_ID AND pl.Quantity_Decremental=0 INNER JOIN purchase AS pu ON pu.Purchase_ID=pl.Purchase_ID AND pu.For_Branch>0 WHERE p.Product_Type='G' ORDER BY pu.Purchase_ID ASC"));
while($data->Row) {
	$products[$data->Row['Product_ID']] = $data->Row['Cost'];

	$data->Next();
}
$data->Disconnect();

foreach($products as $productId=>$cost) {
	new DataQuery(sprintf("UPDATE product SET CacheRecentCost=%f WHERE Product_ID=%d", mysql_real_escape_string($cost), mysql_real_escape_string($productId)));
}

$cron->log(sprintf('Cached: %d Product Recent Costs', count($products)), Cron::LOG_LEVEL_INFO);

# Average Despatch
new DataQuery(sprintf("UPDATE product SET Average_Despatch=-1"));

$data = new DataQuery(sprintf("SELECT ol.Product_ID, CEIL(AVG(DATEDIFF(d.Created_On, o.Created_On)) * 0.8) AS Average_Days FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN product AS p ON p.Product_ID=ol.Product_ID INNER JOIN despatch AS d ON d.Despatch_ID=ol.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' GROUP BY ol.Product_ID"));
while($data->Row) {
	new DataQuery(sprintf("UPDATE product SET Average_Despatch=%d WHERE Product_ID=%d", mysql_real_escape_string($data->Row['Average_Days']), mysql_real_escape_string($data->Row['Product_ID'])));

	$data->Next();
}
$data->Disconnect();

$cron->log(sprintf('Cached: %d Average Despatch Days', $data->TotalRows), Cron::LOG_LEVEL_INFO);

## END SCRIPT

$cron->execute();
$cron->output();

$GLOBALS['DBCONNECTION']->Close();