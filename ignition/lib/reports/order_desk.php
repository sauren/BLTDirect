<?php
$reportData = array();
$reportData['PendingOrders'] = 0;

$connections = getSyncConnections();

for($k=0; $k<count($connections); $k++) {
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM orders WHERE Status LIKE 'Unread' OR Status LIKE 'Pending' OR Status LIKE 'Packing' OR Status LIKE 'Partially Despatched'"));
	$reportData['PendingOrders'] += $data->Row['Count'];
	$data->Disconnect();
}

$reportCache = new ReportCache();
$reportCache->Report->GetByReference('orderdesk');
$reportCache->SetData($reportData);
$reportCache->Add();