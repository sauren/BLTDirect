<?php
$reportData = array();
$reportData['AccountSales'] = array();

$data = new DataQuery(sprintf("SELECT MIN(o.Created_On) AS First_Order_On FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Status<>'Unauthenticated' AND o.Status<>'Cancelled' AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N'"));
if($data->TotalRows > 0) {
	$startYear = date('Y', strtotime($data->Row['First_Order_On']));
	$endYear = date('Y');

	for($i=$startYear; $i<=$endYear; $i++) {
		for($j=1; $j<=12; $j++) {
			$accountSales = array();

			$start = date('Y-m-d H:i:s', mktime(0, 0, 0, $j, 1, $i));
			$end = date('Y-m-d H:i:s', mktime(0, 0, 0, $j + 1, 1, $i));

			$orderSales = 0;
			$orderTurnover = 0;

			if(strtotime($start) < time()) {
				$data2 = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS OrderCount, SUM(o.Total-o.TotalTax) AS OrderTurnover FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N'", $start, $end));
				$orderSales += $data2->Row['OrderCount'];
				$orderTurnover += $data2->Row['OrderTurnover'];
				$data2->Disconnect();
			}

			$accountSales['Start'] = $start;
			$accountSales['End'] = $end;
			$accountSales['Data'] = array($orderSales, $orderTurnover);

			$reportData['AccountSales'][] = $accountSales;
		}
	}

	$reportCache = new ReportCache();
	$reportCache->Report->GetByReference('accountsales');
	$reportCache->SetData($reportData);
	$reportCache->Add();
}
$data->Disconnect();