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
		$item['Raw'] = array('Prefix' => array(), 'User' => array());
		$item['Data'] = array('Web' => array('Prefix' => array('W'), 'Orders' => 0, 'Value' => 0), 'Telesales' => array('Prefix' => array('T', 'U'), 'Orders' => 0, 'Value' => 0));
		$item['Compiled'] = array('All' => 0, 'Web' => 0, 'Telesales' => 0, 'User' => array());

		$data2 = new DataQuery(sprintf("SELECT Order_Prefix, COUNT(Order_ID) AS Orders, SUM(Total-TotalTax) AS Value FROM orders WHERE Created_On>='%s' AND Created_On<'%s' AND Order_Prefix IN ('W', 'U', 'L', 'M', 'T') GROUP BY Order_Prefix", $start, $end));
		while($data2->Row) {
			$item['Raw']['Prefix'][$data2->Row['Order_Prefix']] = array('Orders' => $data2->Row['Orders'], 'Value' => $data2->Row['Value']);

			$data2->Next();
		}
		$data2->Disconnect();
		
		$data2 = new DataQuery(sprintf("SELECT Created_By, COUNT(Order_ID) AS Orders, SUM(Total-TotalTax) AS Value FROM orders WHERE Created_On>='%s' AND Created_On<'%s' AND Created_By>0 GROUP BY Created_By", $start, $end));
		while($data2->Row) {
			$item['Raw']['User'][$data2->Row['Created_By']] = array('Orders' => $data2->Row['Orders'], 'Value' => $data2->Row['Value']);
			
			$reportData['Users'][$data2->Row['Created_By']] = '';
			
			$data2->Next();
		}
		$data2->Disconnect();
		
		foreach($item['Data'] as $type=>$dataItem) {
			foreach($dataItem['Prefix'] as $prefixItem) {
				$prefixItem = strtoupper($prefixItem);
			
				if(isset($item['Raw']['Prefix'][$prefixItem])) {
					$item['Data'][$type]['Orders'] += $item['Raw']['Prefix'][$prefixItem]['Orders'];
					$item['Data'][$type]['Value'] += $item['Raw']['Prefix'][$prefixItem]['Value'];
				}
			}
		}
		
		$item['Compiled']['Web'] += ($item['Data']['Web']['Orders'] > 0) ? $item['Data']['Web']['Value'] / $item['Data']['Web']['Orders'] : 0;
		$item['Compiled']['Telesales'] += ($item['Data']['Telesales']['Orders'] > 0) ? $item['Data']['Telesales']['Value'] / $item['Data']['Telesales']['Orders'] : 0;
		$item['Compiled']['All'] += (($item['Data']['Web']['Orders'] + $item['Data']['Telesales']['Orders']) > 0) ? ($item['Data']['Web']['Value'] + $item['Data']['Telesales']['Value']) / ($item['Data']['Web']['Orders'] + $item['Data']['Telesales']['Orders']) : 0;

		$reverseData[] = $item;

		$index--;
	}
	
	foreach($reportData['Users'] as $userId=>$value) {
		$data2 = new DataQuery(sprintf("SELECT CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID WHERE u.User_ID=%d", mysql_real_escape_string($userId)));
		if($data2->TotalRows > 0) {
			$reportData['Users'][$userId] = $data2->Row['Name'];
		} else {
			unset($reportData['Users'][$userId]);
		}
		$data2->Disconnect();
	}
	
	for($i=count($reverseData) - 1; $i>=0; $i--) {
		$reportData['Items'][] = $reverseData[$i];
	}
	
	for($i=0; $i<count($reportData['Items']); $i++) {
		foreach($reportData['Users'] as $userId=>$value) {
			$found = false;
			
			foreach($reportData['Items'][$i]['Raw']['User'] as $rawUserId=>$dataItem) {
				if($userId == $rawUserId) {
					$reportData['Items'][$i]['Compiled']['User'][$userId] = $dataItem['Value'] / $dataItem['Orders'];

					$found = true;
					break;
				}
			}
			
			if(!$found) {
				$reportData['Items'][$i]['Compiled']['User'][$userId] = 0;
			}
		}
	}
	
	$reportCache = new ReportCache();
	$reportCache->Report->GetByReference('orderaveragevalue');
	$reportCache->SetData($reportData);
	$reportCache->Add();
}
$data->Disconnect();