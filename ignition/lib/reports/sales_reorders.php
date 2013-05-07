<?php
$reportData = array();
$reportData['OrderFrequency'] = array();
$reportData['OrderTurnover'] = array();
$reportData['PercentageRatio'] = array();

$connections = getSyncConnections();

$data = new DataQuery(sprintf("SELECT MIN(Created_On) AS First_Order_On FROM orders"));
if($data->TotalRows > 0) {
	$startYear = date('Y', strtotime($data->Row['First_Order_On']));
	$endYear = date('Y');

	for($i=$startYear; $i<=$endYear; $i++) {
		for($j=1; $j<=12; $j++) {
			$orderFrequency = array();
			$orderTurnover = array();
			$percentageRatio = array();

			$start = date('Y-m-d H:i:s', mktime(0, 0, 0, $j, 1, $i));
			$end = date('Y-m-d H:i:s', mktime(0, 0, 0, $j + 1, 1, $i));

			$orderSalesFigure = 0;
			$orderTurnoverFigure = 0;
			$reorderSalesFigure = 0;
			$reorderTurnoverFigure = 0;
			
			$webOrderSalesFigure = 0;
			$webOrderTurnoverFigure = 0;
			$webReorderSalesFigure = 0;
			$webReorderTurnoverFigure = 0;
			
			$telesalesOrderSalesFigure = 0;
			$telesalesOrderTurnoverFigure = 0;
			$telesalesReorderSalesFigure = 0;
			$telesalesReorderTurnoverFigure = 0;

			for($k=0; $k<count($connections); $k++) {
				if(strtotime($start) < time()) {
					$data2 = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS OrderCount, SUM(o.Total-o.TotalTax) AS OrderTurnover FROM orders AS o WHERE o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N'", $start, $end), $connections[$k]['Connection']);
					$orderSalesFigure += $data2->Row['OrderCount'];
					$orderTurnoverFigure += $data2->Row['OrderTurnover'];
					$data2->Disconnect();
					
					$data2 = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS OrderCount, SUM(o.Total-o.TotalTax) AS OrderTurnover FROM orders AS o WHERE o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix IN ('W', 'U', 'L', 'M')", $start, $end), $connections[$k]['Connection']);
					$webOrderSalesFigure += $data2->Row['OrderCount'];
					$webOrderTurnoverFigure += $data2->Row['OrderTurnover'];
					$data2->Disconnect();
					
					$data2 = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS OrderCount, SUM(o.Total-o.TotalTax) AS OrderTurnover FROM orders AS o WHERE o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix='T'", $start, $end), $connections[$k]['Connection']);
					$telesalesOrderSalesFigure += $data2->Row['OrderCount'];
					$telesalesOrderTurnoverFigure += $data2->Row['OrderTurnover'];
					$data2->Disconnect();

					new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_orders SELECT MIN(Order_ID) AS Order_ID FROM orders WHERE Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N' GROUP BY Customer_ID ORDER BY Order_ID"), $connections[$k]['Connection']);
					new DataQuery(sprintf("CREATE INDEX Order_ID ON temp_orders (Order_ID)"), $connections[$k]['Connection']);

					$data2 = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS OrderCount, SUM(o.Total-o.TotalTax) AS OrderTurnover FROM orders AS o LEFT JOIN temp_orders AS tto ON o.Order_ID=tto.Order_ID WHERE tto.Order_ID IS NULL AND o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N'", $start, $end), $connections[$k]['Connection']);
					$reorderSalesFigure += $data2->Row['OrderCount'];
					$reorderTurnoverFigure += $data2->Row['OrderTurnover'];
					$data2->Disconnect();
					
					new DataQuery(sprintf("DROP TABLE temp_orders"), $connections[$k]['Connection']);
					
					new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_orders SELECT MIN(Order_ID) AS Order_ID FROM orders WHERE Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix IN ('W', 'U', 'L', 'M') GROUP BY Customer_ID ORDER BY Order_ID"), $connections[$k]['Connection']);
					new DataQuery(sprintf("CREATE INDEX Order_ID ON temp_orders (Order_ID)"), $connections[$k]['Connection']);

					$data2 = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS OrderCount, SUM(o.Total-o.TotalTax) AS OrderTurnover FROM orders AS o LEFT JOIN temp_orders AS tto ON o.Order_ID=tto.Order_ID WHERE tto.Order_ID IS NULL AND o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix IN ('W', 'U', 'L', 'M')", $start, $end), $connections[$k]['Connection']);
					$webReorderSalesFigure += $data2->Row['OrderCount'];
					$webReorderTurnoverFigure += $data2->Row['OrderTurnover'];
					$data2->Disconnect();

					new DataQuery(sprintf("DROP TABLE temp_orders"), $connections[$k]['Connection']);
					
					new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_orders SELECT MIN(Order_ID) AS Order_ID FROM orders WHERE Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix='T' GROUP BY Customer_ID ORDER BY Order_ID"), $connections[$k]['Connection']);
					new DataQuery(sprintf("CREATE INDEX Order_ID ON temp_orders (Order_ID)"), $connections[$k]['Connection']);

					$data2 = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS OrderCount, SUM(o.Total-o.TotalTax) AS OrderTurnover FROM orders AS o LEFT JOIN temp_orders AS tto ON o.Order_ID=tto.Order_ID WHERE tto.Order_ID IS NULL AND o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix='T'", $start, $end), $connections[$k]['Connection']);
					$telesalesReorderSalesFigure += $data2->Row['OrderCount'];
					$telesalesReorderTurnoverFigure += $data2->Row['OrderTurnover'];
					$data2->Disconnect();

					new DataQuery(sprintf("DROP TABLE temp_orders"), $connections[$k]['Connection']);
				}
			}

			$orderFrequency['Start'] = $start;
			$orderFrequency['End'] = $end;
			$orderFrequency['Data'] = array($orderSalesFigure, $reorderSalesFigure, $webOrderSalesFigure, $webReorderSalesFigure, $telesalesOrderSalesFigure, $telesalesReorderSalesFigure);

			$orderTurnover['Start'] = $start;
			$orderTurnover['End'] = $end;
			$orderTurnover['Data'] = array($orderTurnoverFigure, $reorderTurnoverFigure, $webOrderTurnoverFigure, $webReorderTurnoverFigure, $telesalesOrderTurnoverFigure, $telesalesReorderTurnoverFigure);

			$percentageRatio['Start'] = $start;
			$percentageRatio['End'] = $end;
			$percentageRatio['Data'] = array(($orderSalesFigure > 0) ? ($reorderSalesFigure / $orderSalesFigure) * 100 : 0, ($orderTurnoverFigure > 0) ? ($reorderTurnoverFigure / $orderTurnoverFigure) * 100 : 0);

			$reportData['OrderFrequency'][] = $orderFrequency;
			$reportData['OrderTurnover'][] = $orderTurnover;
			$reportData['PercentageRatio'][] = $percentageRatio;
		}
	}

	$reportCache = new ReportCache();
	$reportCache->Report->GetByReference('salesreorders');
	$reportCache->SetData($reportData);
	$reportCache->Add();
}
$data->Disconnect();