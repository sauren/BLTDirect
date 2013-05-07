<?php
$reportData = array();

$data = new DataQuery(sprintf("SELECT MIN(Created_On) AS Start_Date, MAX(Created_On) AS End_Date FROM orders"));
if($data->TotalRows > 0) {
	$data2 = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, s.Supplier_ID, o.Org_Name AS Supplier, sp.Cost, SUM(ol.Quantity) AS Quantity_Sold, COUNT(DISTINCT o2.Order_ID) AS Orders, SUM(ol.Cost) AS Total_Cost FROM supplier_product AS sp INNER JOIN product AS p ON p.Product_ID=sp.Product_ID INNER JOIN supplier AS s ON s.Supplier_ID=sp.Supplier_ID LEFT JOIN warehouse AS w ON w.Type_Reference_ID=sp.Supplier_ID AND w.Type='S' INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c.Org_ID LEFT JOIN order_line AS ol ON ol.Product_ID=sp.Product_ID AND ol.Despatch_From_ID=w.Warehouse_ID LEFT JOIN orders AS o2 ON o2.Order_ID=ol.Order_ID AND o2.Created_On>=ADDDATE(NOW(), INTERVAL -12 MONTH) AND o2.Created_On<NOW() WHERE sp.Is_Stock_Held='Y' GROUP BY sp.Supplier_Product_ID ORDER BY p.Product_ID ASC"));
	while($data2->Row) {
		$reportData[] = $data2->Row;

		$data2->Next();
	}
	$data2->Disconnect();
	
	$reportCache = new ReportCache();
	$reportCache->Report->GetByReference('supplierstockheld');
	$reportCache->SetData($reportData);
	$reportCache->Add();
}
$data->Disconnect();