<?php
$reportData = array();
$reverseData = array();

$data = new DataQuery(sprintf("SELECT MIN(Created_On) AS Start_Date, MAX(Created_On) AS End_Date FROM orders"));
if($data->TotalRows > 0) {
	$startDate = date('Y-m-01 00:00:00', strtotime($data->Row['Start_Date']));
	$endDate = date('Y-m-01 00:00:00', strtotime($data->Row['End_Date']));

	$index = 0;

	while(true) {
		$start = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m') + $index - 1, 1, date('Y')));
		$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m') + $index, 1, date('Y')));

		if($start < $startDate) {
			break;
		}

		$item = array();
		$item['Start'] = $start;
		$item['End'] = $end;
		$item['Customers'] = array();

		$data2 = new DataQuery(sprintf("SELECT Customer_ID, COUNT(Order_ID) AS OrderCount FROM orders WHERE Created_On>='%s' AND Created_On<'%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'N' AND Order_Prefix<>'B' AND Order_Prefix<>'R' AND Customer_ID>0 GROUP BY Customer_ID", $start, $end));
		while($data2->Row) {
			$item['Customers'][$data2->Row['Customer_ID']] = $data2->Row['OrderCount'];

			$data2->Next();
		}
		$data2->Disconnect();

		$reverseData[] = $item;

		$index--;
	}

	for($i=count($reverseData) - 1; $i>=0; $i--) {
		$reportData[] = $reverseData[$i];
	}

	for($i=0; $i<count($reportData); $i++) {
		$reportData[$i]['RepeatCustomers'] = 0;

		if(isset($reportData[$i - 1])) {
			foreach($reportData[$i - 1]['Customers'] as $customerId=>$orderCount) {
				if(isset($reportData[$i]['Customers'][$customerId])) {
					$reportData[$i]['Customers'][$customerId] += $orderCount;
				} else {
					$reportData[$i]['Customers'][$customerId] = $orderCount;
				}
			}
		}

		foreach($reportData[$i]['Customers'] as $orderCount) {
			if($orderCount > 1) {
				$reportData[$i]['RepeatCustomers']++;
			}
		}

		$reportData[$i]['ReorderPercentage'] = ($reportData[$i]['RepeatCustomers'] / count($reportData[$i]['Customers'])) * 100;
	}

	for($i=0; $i<count($reportData); $i++) {
		$reportData[$i]['Customers'] = count($reportData[$i]['Customers']);
	}

	$reportCache = new ReportCache();
	$reportCache->Report->GetByReference('customerpercentages');
	$reportCache->SetData($reportData);
	$reportCache->Add();
}
$data->Disconnect();