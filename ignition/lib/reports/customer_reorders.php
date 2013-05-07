<?php
$reportData = array();

new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_customer SELECT c.Customer_ID, COUNT(o.Order_ID) AS Count FROM customer AS c INNER JOIN orders AS o ON o.Customer_ID=c.Customer_ID GROUP BY c.Customer_ID"));
new DataQuery(sprintf("DELETE FROM temp_customer WHERE Count<=1"));

$data = new DataQuery(sprintf("SELECT tc.Count, UNIX_TIMESTAMP(MIN(o.Created_On)) AS StartTimestamp, UNIX_TIMESTAMP(MAX(o.Created_On)) AS EndTimestamp FROM temp_customer AS tc INNER JOIN orders AS o ON o.Customer_ID=tc.Customer_ID GROUP BY tc.Customer_ID"));
while($data->Row) {
	$item = array();
	$item['OrderCount'] = $data->Row['Count'];
	$item['StartTimestamp'] = $data->Row['StartTimestamp'];
	$item['EndTimestamp'] = $data->Row['EndTimestamp'];
	$item['TimestampDifference'] = $data->Row['EndTimestamp'] - $data->Row['StartTimestamp'];
	$item['AverageTimestamp'] = ($data->Row['EndTimestamp'] - $data->Row['StartTimestamp']) / ($data->Row['Count'] - 1);

	$reportData[] = $item;
	
	$data->Next();
}
$data->Disconnect();

$reportCache = new ReportCache();
$reportCache->Report->GetByReference('customerreorders');
$reportCache->SetData($reportData);
$reportCache->Add();