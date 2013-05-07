<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$connections = getSyncConnections();

	$content = '';
	$content .= '<html>';
	$content .= '<head>';
	$content .= '<style>';
	$content .= 'body, th, td { font-family: arial, sans-serif; font-size: 0.8em; }';
	$content .= 'h1, h2, h3, h4, h5, h6 { margin-bottom: 0; padding-bottom: 0; }';
	$content .= 'h1 { font-size: 1.6em; }';
	$content .= 'h2 { font-size: 1.2em;}';
	$content .= 'p { margin-top: 0;}';
	$content .= '</style>';
	$content .= '</head>';
	$content .= '<body>';

	$content .= '<h1>Shipping Report</h1>';

    $content .= '<br />';
	$content .= '<h2>Shipping Summary</h2>';
	$content .= '<br />';
	
	$postageStandard = array($GLOBALS['DEFAULT_POSTAGE']);
	$postageNext = array();
	
	$data = new DataQuery(sprintf("SELECT Postage_ID FROM postage WHERE Postage_Days=1"));
	while($data->Row) {
		$postageNext[] = $data->Row['Postage_ID'];
		
		$data->Next();
	}
	$data->Disconnect();
	
	$shippingItems = array();
	$totalIndex = 0;
	
	$sql = "SELECT SUM(o.TotalShipping) AS Shipping FROM orders AS o INNER JOIN (SELECT Order_ID, MAX(Created_On) FROM despatch WHERE Created_On>='%s' AND Created_On<'%s' GROUP BY Order_ID) AS d ON d.Order_ID=o.Order_ID WHERE o.Status LIKE 'Despatched'%s";
	
	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')+1, date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND (o.Postage_ID=%d)', implode(' OR o.Postage_ID=', mysql_real_escape_string($postageStandard))) : ''));
	$shippingToday = $data2->Row['Shipping'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')-7, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND (o.Postage_ID=%d)', implode(' OR o.Postage_ID=', mysql_real_escape_string($postageStandard))) : ''));
	$shipping7Days = $data2->Row['Shipping'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND (o.Postage_ID=%d)', implode(' OR o.Postage_ID=', mysql_real_escape_string($postageStandard))) : ''));
	$shippingMonth = $data2->Row['Shipping'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')-1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y')-1)), (count($postageStandard) > 0) ? sprintf(' AND (o.Postage_ID=%d)', implode(' OR o.Postage_ID=', mysql_real_escape_string($postageStandard))) : ''));
	$shippingMonthLastYear = $data2->Row['Shipping'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingToday, $shipping7Days, $shippingMonth, $shippingMonthLastYear), 'Name' => 'Shipping Charged (Standard Service)');
	
	$sql = "SELECT SUM(o.TotalShipping) AS Shipping FROM orders AS o INNER JOIN (SELECT Order_ID, MAX(Created_On) FROM despatch WHERE Created_On>='%s' AND Created_On<'%s' GROUP BY Order_ID) AS d ON d.Order_ID=o.Order_ID WHERE o.Status LIKE 'Despatched'%s";
	
	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')+1, date('Y'))), (count($postageNext) > 0) ? sprintf(' AND (o.Postage_ID=%d)', implode(' OR o.Postage_ID=', mysql_real_escape_string($postageNext))) : ''));
	$shippingToday = $data2->Row['Shipping'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')-7, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), (count($postageNext) > 0) ? sprintf(' AND (o.Postage_ID=%d)', implode(' OR o.Postage_ID=', mysql_real_escape_string($postageNext))) : ''));
	$shipping7Days = $data2->Row['Shipping'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y'))), (count($postageNext) > 0) ? sprintf(' AND (o.Postage_ID=%d)', implode(' OR o.Postage_ID=', mysql_real_escape_string($postageNext))) : ''));
	$shippingMonth = $data2->Row['Shipping'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')-1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y')-1)), (count($postageNext) > 0) ? sprintf(' AND (o.Postage_ID=%d)', implode(' OR o.Postage_ID=', mysql_real_escape_string($postageNext))) : ''));
	$shippingMonthLastYear = $data2->Row['Shipping'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingToday, $shipping7Days, $shippingMonth, $shippingMonthLastYear), 'Name' => 'Shipping Charged (Next Day Delivery)');
	
	$sql = "SELECT SUM(o.TotalShipping) AS Shipping FROM orders AS o INNER JOIN (SELECT Order_ID, MAX(Created_On) FROM despatch WHERE Created_On>='%s' AND Created_On<'%s' GROUP BY Order_ID) AS d ON d.Order_ID=o.Order_ID WHERE o.Status LIKE 'Despatched'%s%s";
	
	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')+1, date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND o.Postage_ID<>%d', implode(' AND o.Postage_ID<>', mysql_real_escape_string($postageStandard))) : '', (count($postageNext) > 0) ? sprintf(' AND o.Postage_ID<>%d', implode(' AND o.Postage_ID<>', mysql_real_escape_string($postageNext))) : ''));
	$shippingToday = $data2->Row['Shipping'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')-7, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND o.Postage_ID<>%d', implode(' AND o.Postage_ID<>', mysql_real_escape_string($postageStandard))) : '', (count($postageNext) > 0) ? sprintf(' AND o.Postage_ID<>%d', implode(' AND o.Postage_ID<>', mysql_real_escape_string($postageNext))) : ''));
	$shipping7Days = $data2->Row['Shipping'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND o.Postage_ID<>%d', implode(' AND o.Postage_ID<>', mysql_real_escape_string($postageStandard))) : '', (count($postageNext) > 0) ? sprintf(' AND o.Postage_ID<>%d', implode(' AND o.Postage_ID<>', mysql_real_escape_string($postageNext))) : ''));
	$shippingMonth = $data2->Row['Shipping'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')-1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y')-1)), (count($postageStandard) > 0) ? sprintf(' AND o.Postage_ID<>%d', implode(' AND o.Postage_ID<>', mysql_real_escape_string($postageStandard))) : '', (count($postageNext) > 0) ? sprintf(' AND o.Postage_ID<>%d', implode(' AND o.Postage_ID<>', mysql_real_escape_string($postageNext))) : ''));
	$shippingMonthLastYear = $data2->Row['Shipping'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingToday, $shipping7Days, $shippingMonth, $shippingMonthLastYear), 'Name' => 'Shipping Charged (Other Postage)');
	
	$totals = array();

	for($j=$totalIndex; $j<count($shippingItems); $j++) {
		for($i=0; $i<count($shippingItems[$j]['Columns']); $i++) {
			if(!isset($totals[$i])) {
				$totals[$i] = 0;
			}
			
			$totals[$i] += $shippingItems[$j]['Columns'][$i];
		}
	}
	
	$shippingItems[] = array('Columns' => $totals, 'Name' => 'Total Charged', 'Bold' => true);
	$totalIndex = 4;
	
	$sql = "SELECT SUM(d.Postage_Cost) AS Cost FROM despatch AS d INNER JOIN order_line AS ol ON d.Despatch_ID=ol.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='B' WHERE d.Created_On>='%s' AND d.Created_On<'%s'%s";
	
	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')+1, date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageStandard)) : ''));
	$shippingToday = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')-7, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageStandard)) : ''));
	$shipping7Days = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageStandard)) : ''));
	$shippingMonth = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')-1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y')-1)), (count($postageStandard) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageStandard)) : ''));
	$shippingMonthLastYear = $data2->Row['Cost'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingToday, $shipping7Days, $shippingMonth, $shippingMonthLastYear), 'Name' => 'Shipping Branch Cost (Standard Service)');
	
	$sql = "SELECT SUM(d.Postage_Cost) AS Cost FROM despatch AS d INNER JOIN order_line AS ol ON d.Despatch_ID=ol.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='B' WHERE d.Created_On>='%s' AND d.Created_On<'%s'%s";
	
	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')+1, date('Y'))), (count($postageNext) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageNext)) : ''));
	$shippingToday = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')-7, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), (count($postageNext) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageNext)) : ''));
	$shipping7Days = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y'))), (count($postageNext) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageNext)) : ''));
	$shippingMonth = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')-1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y')-1)), (count($postageNext) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageNext)) : ''));
	$shippingMonthLastYear = $data2->Row['Cost'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingToday, $shipping7Days, $shippingMonth, $shippingMonthLastYear), 'Name' => 'Shipping Branch Cost (Next Day Delivery)');
	
	$sql = "SELECT SUM(d.Postage_Cost) AS Cost FROM despatch AS d INNER JOIN order_line AS ol ON d.Despatch_ID=ol.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='B' WHERE d.Created_On>='%s' AND d.Created_On<'%s'%s%s";
	
	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')+1, date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageStandard)) : '', (count($postageNext) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageNext)) : ''));
	$shippingToday = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')-7, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageStandard)) : '', (count($postageNext) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageNext)) : ''));
	$shipping7Days = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageStandard)) : '', (count($postageNext) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageNext)) : ''));
	$shippingMonth = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')-1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y')-1)), (count($postageStandard) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageStandard)) : '', (count($postageNext) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageNext)) : ''));
	$shippingMonthLastYear = $data2->Row['Cost'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingToday, $shipping7Days, $shippingMonth, $shippingMonthLastYear), 'Name' => 'Shipping Branch Cost (Other Postage)');
	
	$totals = array();
	
	for($j=$totalIndex; $j<count($shippingItems); $j++) {
		for($i=0; $i<count($shippingItems[$j]['Columns']); $i++) {
			if(!isset($totals[$i])) {
				$totals[$i] = 0;
			}
			
			$totals[$i] += $shippingItems[$j]['Columns'][$i];
		}
	}
	
	$shippingItems[] = array('Columns' => $totals, 'Name' => 'Total Branch Cost', 'Bold' => true);
	$totalIndex = 8;
	
	$sql = "SELECT SUM(d.Postage_Cost) AS Cost FROM despatch AS d INNER JOIN order_line AS ol ON d.Despatch_ID=ol.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' WHERE d.Created_On>='%s' AND d.Created_On<'%s'%s";

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')+1, date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageStandard)) : ''));
	$shippingToday = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')-7, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageStandard)) : ''));
	$shipping7Days = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageStandard)) : ''));
	$shippingMonth = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')-1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y')-1)), (count($postageStandard) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageStandard)) : ''));
	$shippingMonthLastYear = $data2->Row['Cost'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingToday, $shipping7Days, $shippingMonth, $shippingMonthLastYear), 'Name' => 'Shipping Supplier Cost (Standard Service)');
	
	$sql = "SELECT SUM(d.Postage_Cost) AS Cost FROM despatch AS d INNER JOIN order_line AS ol ON d.Despatch_ID=ol.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' WHERE d.Created_On>='%s' AND d.Created_On<'%s'%s";
	
	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')+1, date('Y'))), (count($postageNext) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageNext)) : ''));
	$shippingToday = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')-7, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), (count($postageNext) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageNext)) : ''));
	$shipping7Days = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y'))), (count($postageNext) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageNext)) : ''));
	$shippingMonth = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')-1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y')-1)), (count($postageNext) > 0) ? sprintf(' AND (d.Postage_ID=%d)', implode(' OR d.Postage_ID=', $postageNext)) : ''));
	$shippingMonthLastYear = $data2->Row['Cost'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingToday, $shipping7Days, $shippingMonth, $shippingMonthLastYear), 'Name' => 'Shipping Supplier Cost (Next Day Delivery)');
	
	$sql = "SELECT SUM(d.Postage_Cost) AS Cost FROM despatch AS d INNER JOIN order_line AS ol ON d.Despatch_ID=ol.Despatch_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' WHERE d.Created_On>='%s' AND d.Created_On<'%s'%s%s";
	
	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')+1, date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageStandard)) : '', (count($postageNext) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageNext)) : ''));
	$shippingToday = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')-7, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageStandard)) : '', (count($postageNext) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageNext)) : ''));
	$shipping7Days = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y'))), (count($postageStandard) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageStandard)) : '', (count($postageNext) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageNext)) : ''));
	$shippingMonth = $data2->Row['Cost'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf($sql, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')-1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y')-1)), (count($postageStandard) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageStandard)) : '', (count($postageNext) > 0) ? sprintf(' AND d.Postage_ID<>%d', implode(' AND d.Postage_ID<>', $postageNext)) : ''));
	$shippingMonthLastYear = $data2->Row['Cost'];
	$data2->Disconnect();
	
	$shippingItems[] = array('Columns' => array($shippingToday, $shipping7Days, $shippingMonth, $shippingMonthLastYear), 'Name' => 'Shipping Supplier Cost (Other Postage)');
	
	$totals = array();

	for($j=$totalIndex; $j<count($shippingItems); $j++) {
		for($i=0; $i<count($shippingItems[$j]['Columns']); $i++) {
			if(!isset($totals[$i])) {
				$totals[$i] = 0;
			}
			
			$totals[$i] += $shippingItems[$j]['Columns'][$i];
		}
	}
	
	$shippingItems[] = array('Columns' => $totals, 'Name' => 'Total Supplier Cost', 'Bold' => true);
	
    $content .= '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-bottom:1px solid #dddddd;"><strong>Orders</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Today</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Last 7 Days</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>This Month</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>This Month Last Year</strong></td>';
	$content .= '</tr>';

	foreach($shippingItems as $shippingItem) {
		$content .= '<tr>';
		$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s</td>', $shippingItem['Name']);
		
		foreach($shippingItem['Columns'] as $item) {
			$content .= sprintf('<td style="border-top: 1px solid #dddddd;%s" align="right">%s</td>', (isset($shippingItem['Bold']) && $shippingItem['Bold']) ? ' font-weight: bold;' : '', number_format(round($item, 2), 2, '.', ','));
		}
		
		$content .= '</tr>';
	}
	
	$content .= '</table>';

	$content .= '</body>';
	$content .= '</html>';

	echo $content;
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();