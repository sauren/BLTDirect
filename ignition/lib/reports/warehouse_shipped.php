<?php
$reportData = array();

$months = array(1, 2, 3, 6, 12);

foreach($months as $month) {
	$monthData['Start'] = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m') - $month, date('d'), date('Y')));
	$monthData['End'] = date('Y-m-d H:i:s'); 
	$monthData['Items'] = array();
	
	$warehouseBranches = array();
	
	$data = new DataQuery(sprintf("SELECT w.Warehouse_ID FROM branch AS b INNER JOIN warehouse AS w ON w.Type_Reference_ID=b.Branch_ID AND w.Type='B'"));
	while($data->Row) {
		$warehouseBranches[$data->Row['Warehouse_ID']] = $data->Row['Warehouse_ID'];
		
		$data->Next();
	}
	$data->Disconnect();
	
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_order SELECT o.Order_ID, ol.Product_ID, ol.Quantity, ol.Despatch_From_ID FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND o.Created_On>='%s' AND o.Created_On<'%s' WHERE o.Status LIKE 'Despatched'", $monthData['Start'], $monthData['End']));
	
	$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT Order_ID) AS Count FROM temp_order"));
	$monthData['TotalOrders'] = $data->Row['Count'];
	$data->Disconnect();
	
	$actualCounts = array(0, 500, 1000);
	$productCounts = array_merge($actualCounts);
	$products = array();
	$highestCount = 0;
	$lastCount = 0;
	
	foreach($productCounts as $productCount) {
		if($productCount > $highestCount) {
			$highestCount = $productCount;
		}
	}
	
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_product SELECT o.Order_ID, ol.Product_ID, ol.Quantity, ol.Despatch_From_ID FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND o.Created_On>='%s' AND o.Created_On<'%s' WHERE o.Status LIKE 'Despatched'", date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m') - 12, date('d'), date('Y'))), $monthData['End']));
	
	$data = new DataQuery(sprintf("SELECT o.Product_ID, SUM(o.Quantity) AS Quantity, IF(ws.Stock_ID IS NULL, 'N', 'Y') AS Is_Stocked FROM temp_product AS o LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=o.Product_ID AND (ws.Warehouse_ID=%d) GROUP BY o.Product_ID ORDER BY Quantity DESC LIMIT 0, %d", mysql_real_escape_string(implode(' OR ws.Warehouse_ID=', $warehouseBranches)), mysql_real_escape_string($highestCount)));
	while($data->Row) {
		$products[] = array('ProductID' => $data->Row['Product_ID'], 'IsStocked' => $data->Row['Is_Stocked']);
		
		$data->Next();
	}
	$data->Disconnect();
	
	new DataQuery(sprintf("DROP TABLE temp_product"));
	
	foreach($productCounts as $productCount) {
		$stockCount = 0;
		
		for($i=0; $i<$productCount; $i++) {
			if(isset($products[$i])) {
				if($products[$i]['IsStocked'] == 'Y') {
					$stockCount++;
				}
			}
		}
		
		if(!in_array($stockCount, $productCounts)) {
			$productCounts[] = $stockCount;
		}
	}
	
	sort($actualCounts);
	sort($productCounts);
	
	foreach($warehouseBranches as $warehouseId) {
		new DataQuery(sprintf("UPDATE temp_order SET Despatch_From_ID=0 WHERE Despatch_From_ID=%d", mysql_real_escape_string($warehouseId)));
	}
	
	foreach($productCounts as $productCount) {
		$monthData['Items'][$productCount] = array('OrdersShipped' => 0, 'ProductsStocked' => 0, 'Visible' => false);
		
		if(in_array($productCount, $actualCounts)) {
			$monthData['Items'][$productCount]['Visible'] = true;
		}	
		
		for($i=$lastCount; $i<$productCount; $i++) {
			if(isset($products[$i])) {
				new DataQuery(sprintf("UPDATE temp_order SET Despatch_From_ID=0 WHERE Product_ID=%d AND Despatch_From_ID>0", mysql_real_escape_string($products[$i]['ProductID'])));
			}
		}
		
		for($i=0; $i<$productCount; $i++) {
			if(isset($products[$i])) {
				if($products[$i]['IsStocked'] == 'Y') {
					$monthData['Items'][$productCount]['ProductsStocked']++;
				}
			}
		}
		
		$data = new DataQuery(sprintf("SELECT Order_ID, COUNT(DISTINCT Despatch_From_ID) AS Warehouse_Count, Despatch_From_ID FROM temp_order GROUP BY Order_ID ORDER BY Warehouse_Count DESC"));
		while($data->Row) {
			if($data->Row['Warehouse_Count'] == 1) {
				if($data->Row['Despatch_From_ID'] == 0) {
					$monthData['Items'][$productCount]['OrdersShipped']++;
				}
			}
		
			$data->Next();
		}
		$data->Disconnect();
		
		$lastCount = $productCount;
	}
	
	new DataQuery(sprintf("DROP TABLE temp_order"));
	
	$reportData[$month] = $monthData;
}

$reportCache = new ReportCache();
$reportCache->Report->GetByReference('warehouseshipped');
$reportCache->SetData($reportData);
$reportCache->Add();