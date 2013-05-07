<?php
$reportData = array();
$reportData['WebSales'] = array();

new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_orders SELECT MIN(o.Order_ID) AS Order_ID FROM orders AS o WHERE o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix IN ('W', 'U', 'L', 'M') GROUP BY Customer_ID"));
new DataQuery(sprintf("CREATE INDEX Order_ID ON temp_orders (Order_ID)"));

$data = new DataQuery(sprintf("SELECT MIN(o.Created_On) AS First_Order_On FROM orders AS o WHERE o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix IN ('W', 'U', 'L', 'M')"));
if($data->TotalRows > 0) {
	$startYear = date('Y', strtotime($data->Row['First_Order_On']));
	$endYear = date('Y');

	for($i=$startYear; $i<=$endYear; $i++) {
		$items = array();
		
		for($j=1; $j<=12; $j++) {
			$start = date('Y-m-d H:i:s', mktime(0, 0, 0, $j, 1, $i));
			$end = date('Y-m-d H:i:s', mktime(0, 0, 0, $j + 1, 1, $i));

			$orderSales = 0;
			$orderTurnover = 0;
			$reorderSales = 0;
			$reorderTurnover = 0;
			$firstSales = 0;
			$firstTurnover = 0;

			if(strtotime($start) < time()) {
				$data2 = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS OrderCount, SUM(o.Total-o.TotalTax) AS OrderTurnover FROM orders AS o WHERE o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix IN ('W', 'U', 'L', 'M')", $start, $end));
				$orderSales += $data2->Row['OrderCount'];
				$orderTurnover += $data2->Row['OrderTurnover'];
				$data2->Disconnect();				

				$data2 = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS OrderCount, SUM(o.Total-o.TotalTax) AS OrderTurnover FROM orders AS o LEFT JOIN temp_orders AS tto ON o.Order_ID=tto.Order_ID WHERE tto.Order_ID IS NULL AND o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix IN ('W', 'U', 'L', 'M')", $start, $end));
				$reorderSales += $data2->Row['OrderCount'];
				$reorderTurnover += $data2->Row['OrderTurnover'];
				$data2->Disconnect();
				
				$data2 = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS OrderCount, SUM(o.Total-o.TotalTax) AS OrderTurnover FROM orders AS o INNER JOIN temp_orders AS tto ON o.Order_ID=tto.Order_ID WHERE tto.Order_ID=o.Order_ID AND o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix IN ('W', 'U', 'L', 'M')", $start, $end));
				$firstSales += $data2->Row['OrderCount'];
				$firstTurnover += $data2->Row['OrderTurnover'];
				$data2->Disconnect();
			}

			$item = array();
			$item['Start'] = $start;
			$item['End'] = $end;
			$item['Data'] = array($orderSales, $orderTurnover, $reorderSales, $reorderTurnover, $firstSales, $firstTurnover);

			$items[$j] = $item;
		}
		
		$reportData['WebSales'][$i] = $items;
	}
	
	$reportCache = new ReportCache();
	$reportCache->Report->GetByReference('orderturnovermonthly');
	$reportCache->SetData($reportData);
	$reportCache->Add();
}
$data->Disconnect();

new DataQuery(sprintf("DROP TABLE temp_orders"));