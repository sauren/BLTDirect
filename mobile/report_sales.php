<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$connections = getSyncConnections();

	$start = date('Y-m-d 00:00:00');
	$end = date('Y-m-d H:i:s');

	$content = '';

	$orderTypes = array();
	$orderTypes['W'] = "Website (bltdirect.com)";
	$orderTypes['U'] = "Website (bltdirect.co.uk)";
	$orderTypes['L'] = "Website (lightbulbsuk.co.uk)";
	$orderTypes['M'] = "Mobile";
	$orderTypes['T'] = "Telesales";
	$orderTypes['F'] = "Fax";
	$orderTypes['E'] = "Email";
	$orderTypes['N'] = "Not Received";
	$orderTypes['R'] = "Return";
	$orderTypes['B'] = "Broken";

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

	$content .= '<h1>Sales Order Report</h1>';
	$content .= sprintf('<p style="margin-bottom: 0;">%s - %s</p>', cDatetime($start, 'longdatetime'), cDatetime($end, 'longdatetime'));

	$content .= '<br />';
	$content .= '<h2>Sale Methods</h2>';
	$content .= '<br />';

	$content .= '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-bottom:1px solid #dddddd;" width="50%"><strong>Order Type </strong></td>';
	$content .= '<td align="right" style="border-bottom:1px solid #dddddd;" width="25%"><strong>Number</strong></td>';
	$content .= '<td align="right" style="border-bottom:1px solid #dddddd;" width="25%"><strong>Net</strong></td>';
	$content .= '</tr>';

	$totalOrders = 0;
	$totalTotal = 0;

	for($i=0; $i<count($connections); $i++) {
		$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Count, o.Order_Prefix, SUM(o.Total) AS Total, SUM(o.TotalTax) AS TotalTax FROM orders AS o WHERE o.Created_On BETWEEN '%s' AND '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N' GROUP BY Order_Prefix", $start, $end), $connections[$i]['Connection']);
		while($data->Row) {
			$content .= '<tr>';
			$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s</td>', $orderTypes[$data->Row['Order_Prefix']]);
			$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', $data->Row['Count']);
			$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($data->Row['Total'] - $data->Row['TotalTax'], 2, '.', ','));
			$content .= '</tr>';

			$totalOrders += $data->Row['Count'];
			$totalTotal += $data->Row['Total'] - $data->Row['TotalTax'];

			$data->Next();
		}
		$data->Disconnect();
	}

	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-top:1px solid #dddddd;"><strong>Totals</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right"><strong>%s</strong></td>', $totalOrders);
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right"><strong>%s</strong></td>', number_format($totalTotal, 2, '.', ','));
	$content .= '</tr>';
	
	$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Count, SUM(o.Total) AS Total, SUM(o.TotalTax) AS TotalTax FROM (SELECT o.* FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID LEFT JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.Is_Stocked='N' WHERE o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix NOT IN ('R', 'B', 'N') GROUP BY o.Order_ID HAVING COUNT(DISTINCT p.Product_ID)=0) AS o", $start, $end));
	while($data->Row) {
		$content .= '<tr style="background-color:#eeeeee;">';
		$content .= '<td style="border-top:1px solid #dddddd;"><strong>Automatic</td>';
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right"><strong>%s</strong></td>', $data->Row['Count']);
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right"><strong>%s</strong></td>', number_format($data->Row['Total']-$data->Row['TotalTax'], 2, '.', ','));
		$content .= '</tr>';
		
		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Count, SUM(o.Total) AS Total, SUM(o.TotalTax) AS TotalTax FROM orders AS o WHERE o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix NOT IN ('R', 'B', 'N') AND o.Is_Auto_Ship='Y'", $start, $end));
	while($data->Row) {
		$content .= '<tr style="background-color:#eeeeee;">';
		$content .= '<td style="border-top:1px solid #dddddd;"><strong>Auto-Ship</td>';
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right"><strong>%s</strong></td>', $data->Row['Count']);
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right"><strong>%s</strong></td>', number_format($data->Row['Total']-$data->Row['TotalTax'], 2, '.', ','));
		$content .= '</tr>';
		
		$data->Next();
	}
	$data->Disconnect();
		
	$content .= '</table>';

	$content .= '<br />';
	$content .= '<h2>Device Types</h2>';
	$content .= '<br />';

	$content .= '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-bottom:1px solid #dddddd;"><strong>Device Platform</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right" width="25%"><strong>Number</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right" width="25%"><strong>Net</strong></td>';
	$content .= '</tr>';

	$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Count, o.DevicePlatform, SUM(o.Total) AS Total, SUM(o.TotalTax) AS TotalTax FROM orders AS o WHERE o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.DevicePlatform<>'' AND o.DeviceBrowser<>'' GROUP BY o.DevicePlatform ORDER BY o.DevicePlatform ASC, o.DeviceBrowser ASC, o.DeviceVersion ASC", $start, $end));
	while($data->Row) {
		$content .= '<tr>';
		$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s</td>', $data->Row['DevicePlatform']);
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', $data->Row['Count']);
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($data->Row['Total'] - $data->Row['TotalTax'], 2, '.', ','));
		$content .= '</tr>';

		$data->Next();
	}
	$data->Disconnect();

	$content .= '</table>';

	$content .= '<br />';
	$content .= '<h2>Credit Methods</h2>';
	$content .= '<br />';

	$content .= '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-bottom:1px solid #dddddd;" width="50%"><strong>Order Type </strong></td>';
	$content .= '<td align="right" style="border-bottom:1px solid #dddddd;" width="25%"><strong>Number</strong></td>';
	$content .= '<td align="right" style="border-bottom:1px solid #dddddd;" width="25%"><strong>Net</strong></td>';
	$content .= '</tr>';

	$totalCredits = 0;
	$totalTotal = 0;

	for($i=0; $i<count($connections); $i++) {
		$data = new DataQuery(sprintf("SELECT o.Order_Prefix, COUNT(c.Credit_Note_ID) AS Count, SUM(c.TotalTax) AS TotalTax, SUM(c.Total) AS Total FROM credit_note AS c INNER JOIN orders AS o ON c.Order_ID=o.Order_ID WHERE c.Created_On BETWEEN '%s' AND '%s' GROUP BY o.Order_Prefix", $start, $end), $connections[$i]['Connection']);
		while ($data->Row) {
			$content .= '<tr>';
			$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s</td>', $orderTypes[$data->Row['Order_Prefix']]);
			$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', $data->Row['Count']);
			$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($data->Row['Total'] - $data->Row['TotalTax'], 2, '.', ','));
			$content .= '</tr>';

			$totalCredits += $data->Row['Count'];
			$totalTotal += $data->Row['Total'] - $data->Row['TotalTax'];

			$data->Next();
		}
		$data->Disconnect();
	}

	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-top:1px solid #dddddd;"><strong>Totals</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right"><strong>%s</strong></td>', $totalCredits);
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right"><strong>%s</strong></td>', number_format($totalTotal, 2, '.', ','));
	$content .= '</tr>';
	$content .= '</table>';

	$content .= '<br />';
	$content .= '<h2>Sales Representatives</h2>';
	$content .= '<br />';

	$content .= '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-bottom:1px solid #dddddd;" width="50%"><strong>Name</strong></td>';
	$content .= '<td align="right" style="border-bottom:1px solid #dddddd;" width="25%"><strong>Number</strong></td>';
	$content .= '<td align="right" style="border-bottom:1px solid #dddddd;" width="25%"><strong>Net</strong></td>';
	$content .= '</tr>';

	$data = new DataQuery(sprintf("SELECT COUNT(Order_ID) AS Count, Owned_By, SUM(Total) AS Total, SUM(TotalTax) AS TotalTax FROM orders WHERE Owned_By>0 AND Created_On BETWEEN '%s' AND '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N' GROUP BY Owned_By", $start, $end));
	if($data->TotalRows > 0) {
		while ($data->Row) {
			$user = new User($data->Row['Owned_By']);

			$content .= '<tr>';
			$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s&nbsp;</td>', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)));
			$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', $data->Row['Count']);
			$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($data->Row['Total'] - $data->Row['TotalTax'], 2, '.', ','));
			$content .= '</tr>';

			$data->Next();
		}
	} else {
		$content .= '<tr><td align="center" colspan="3">No Statistics Available</td></tr>';
	}
	$data->Disconnect();

	$content .= '</table>';

    $content .= '<br />';
	$content .= '<h2>Watch Lists</h2>';
	$content .= '<br />';

	$content .= '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-bottom: 1px solid #dddddd;" width="50%"><strong>Name</strong></td>';
	$content .= '<td style="border-bottom: 1px solid #dddddd;" width="25%" align="right"><strong>Number</strong></td>';
	$content .= '<td style="border-bottom: 1px solid #dddddd;" width="25%" align="right"><strong>Net</strong></td>';
	$content .= '</tr>';

	$data = new DataQuery(sprintf("SELECT pw.Name, SUM(ol.Line_Total-ol.Line_Discount) AS Total, SUM(ol.Quantity) AS Quantity FROM product_watch AS pw INNER JOIN product_watch_item AS pwi ON pwi.ProductWatchID=pw.ProductWatchID INNER JOIN order_line AS ol ON ol.Product_ID=pwi.ProductID INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID WHERE o.Created_On BETWEEN '%s' AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' GROUP BY pw.ProductWatchID ORDER BY pw.Name ASC", $start, $end));
	if($data->TotalRows > 0) {
		while ($data->Row) {
			$content .= '<tr>';
			$content .= sprintf('<td style="border-top: 1px solid #dddddd;">%s</td>', $data->Row['Name']);
			$content .= sprintf('<td style="border-top: 1px solid #dddddd;" align="right">%s</td>', $data->Row['Quantity']);
			$content .= sprintf('<td style="border-top: 1px solid #dddddd;" align="right">%s</td>', number_format($data->Row['Total'], 2, '.', ','));
			$content .= '</tr>';

			$data->Next();
		}
	} else {
		$content .= '<tr><td align="center" colspan="3">No Statistics Available</td></tr>';
	}
	$data->Disconnect();

	$content .= '</table>';

	$content .= '<br />';
	$content .= '<h2>Accumulated Totals</h2>';
	$content .= '<br />';

	$totalOrdersLastDay = 0;
	$totalTotalLastDay = 0;
	$totalOrdersThisMonth = 0;
	$totalTotalThisMonth = 0;
	$totalOrdersThisMonthCompleteDays = 0;
	$totalTotalThisMonthCompleteDays = 0;
	$totalOrdersLastYear = 0;
	$totalTotalLastYear = 0;
	$totalOrdersFinancialYear = 0;
	$totalTotalFinancialYear = 0;
	$totalOrdersFinancialYearLastYear = 0;
	$totalTotalFinancialYearLastYear = 0;
	$totalOrdersCount = 0;

	for($i=0; $i<count($connections); $i++) {
		$data = new DataQuery(sprintf("SELECT COUNT(Order_ID) AS Count, SUM(Total) AS Total, SUM(TotalTax) AS TotalTax FROM orders WHERE Created_On>='%s' AND Created_On<'%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N'", date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y')))), $connections[$i]['Connection']);
		$totalOrdersLastDay += $data->Row['Count'];
		$totalTotalLastDay += $data->Row['Total'] - $data->Row['TotalTax'];
		$data->Disconnect();
	}

	for($i=0; $i<count($connections); $i++) {
		$data = new DataQuery(sprintf("SELECT COUNT(Order_ID) AS Count, SUM(Total) AS Total, SUM(TotalTax) AS TotalTax FROM orders WHERE Created_On>='%s' AND Created_On<'%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N'", date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($start)), 1, date('Y', strtotime($start)))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($start)) + 1, 1, date('Y', strtotime($start))))), $connections[$i]['Connection']);
		$totalOrdersThisMonth += $data->Row['Count'];
		$totalTotalThisMonth += $data->Row['Total'] - $data->Row['TotalTax'];
		$data->Disconnect();
	}

	for($i=0; $i<count($connections); $i++) {
		$data = new DataQuery(sprintf("SELECT COUNT(Order_ID) AS Count, SUM(Total) AS Total, SUM(TotalTax) AS TotalTax FROM orders WHERE Created_On>='%s' AND Created_On<'%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N'", date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($start)), 1, date('Y', strtotime($start)))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($start)), date('d', strtotime($start)), date('Y', strtotime($start))))), $connections[$i]['Connection']);
		$totalOrdersThisMonthCompleteDays += $data->Row['Count'];
		$totalTotalThisMonthCompleteDays += $data->Row['Total'] - $data->Row['TotalTax'];
		$data->Disconnect();
	}

	for($i=0; $i<count($connections); $i++) {
		$data = new DataQuery(sprintf("SELECT COUNT(Order_ID) AS Count, SUM(Total) AS Total, SUM(TotalTax) AS TotalTax FROM orders WHERE Created_On>='%s' AND Created_On<'%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N'", date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($start)), 1, date('Y') - 1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($start)) + 1, 1, date('Y') - 1))), $connections[$i]['Connection']);
		$totalOrdersLastYear += $data->Row['Count'];
		$totalTotalLastYear += $data->Row['Total'] - $data->Row['TotalTax'];
		$data->Disconnect();
	}

	for($i=0; $i<count($connections); $i++) {
		$data = new DataQuery(sprintf("SELECT COUNT(Order_ID) AS Count, SUM(Total) AS Total, SUM(TotalTax) AS TotalTax FROM orders WHERE Created_On>='%s' AND Created_On<'%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N'", date('Y-m-d H:i:s', mktime(0, 0, 0, 5, 1, date('Y') - ((date('m') < 5) ? 1 : 0))), date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y') + ((date('m') < 5) ? 0 : 1)))), $connections[$i]['Connection']);
		$totalOrdersFinancialYear += $data->Row['Count'];
		$totalTotalFinancialYear += $data->Row['Total'] - $data->Row['TotalTax'];
		$data->Disconnect();
	}

	for($i=0; $i<count($connections); $i++) {
		$data = new DataQuery(sprintf("SELECT COUNT(Order_ID) AS Count, SUM(Total) AS Total, SUM(TotalTax) AS TotalTax FROM orders WHERE Created_On>='%s' AND Created_On<'%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N'", date('Y-m-d H:i:s', mktime(0, 0, 0, 5, 1, date('Y') - ((date('m') < 5) ? 2 : 1))), date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y') - ((date('m') < 5) ? 2 : 1)))), $connections[$i]['Connection']);
		$totalOrdersFinancialYearLastYear += $data->Row['Count'];
		$totalTotalFinancialYearLastYear += $data->Row['Total'] - $data->Row['TotalTax'];
		$data->Disconnect();
	}

	for($i=0; $i<count($connections); $i++) {
		$data = new DataQuery(sprintf("SELECT COUNT(Order_ID) AS Count, SUM(Total) AS Total, SUM(TotalTax) AS TotalTax FROM orders WHERE Created_On>='%s' AND Created_On<'%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND Order_Prefix<>'R' AND Order_Prefix<>'B' AND Order_Prefix<>'N'", date('Y-m-d H:i:s', mktime(0, 0, 0, 5, 1, date('Y') - ((date('m') < 5) ? 2 : 1))), date('Y-m-d H:i:s', mktime(0, 0, 0, 5, 1, date('Y') - ((date('m') < 5) ? 1 : 0)))), $connections[$i]['Connection']);
		$totalOrdersFinancialYearLastYearWhole += $data->Row['Count'];
		$totalTotalFinancialYearLastYearWhole += $data->Row['Total'] - $data->Row['TotalTax'];
		$data->Disconnect();
	}

	for($i=0; $i<count($connections); $i++) {
		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM orders WHERE Order_Prefix<>'N' AND Order_Prefix<>'B' AND Order_Prefix<>'R'"), $connections[$i]['Connection']);
		$totalOrdersCount += $data->Row['Count'];
		$data->Disconnect();
	}

	$content .= '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-bottom:1px solid #dddddd;" width="50%"><strong>Period</strong></td>';
	$content .= '<td align="right" style="border-bottom:1px solid #dddddd;" width="25%"><strong>Number</strong></td>';
	$content .= '<td align="right" style="border-bottom:1px solid #dddddd;" width="25%"><strong>Net</strong></td>';
	$content .= '</tr>';
	$content .= '<tr>';
	$content .= '<td style="border-top:1px solid #dddddd;">Yesterday</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($totalOrdersLastDay, 0, '.', ''));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($totalTotalLastDay, 2, '.', ','));
	$content .= '</tr>';
	$content .= '<tr>';
	$content .= '<td style="border-top:1px solid #dddddd;">Current Month</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($totalOrdersThisMonth, 0, '.', ''));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($totalTotalThisMonth, 2, '.', ','));
	$content .= '</tr>';
	$content .= '<tr>';
	$content .= '<td style="border-top:1px solid #dddddd;">Estimated Month</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', (date('d') > 1) ? number_format(($totalOrdersThisMonthCompleteDays / (date('d') - 1)) * date('t'), 0, '.', '') : 0);
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', (date('d') > 1) ? number_format(($totalTotalThisMonthCompleteDays / (date('d') - 1)) * date('t'), 2, '.', ',') : 0.00);
	$content .= '</tr>';
	$content .= '<tr>';
	$content .= '<td style="border-top:1px solid #dddddd;">Current Month Last Year</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($totalOrdersLastYear, 0, '.', ''));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($totalTotalLastYear, 2, '.', ','));
	$content .= '</tr>';
	$content .= '<tr>';
	$content .= '<td style="border-top:1px solid #dddddd;">Financial Year</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($totalOrdersFinancialYear, 0, '.', ''));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br />%s%%</td>', number_format($totalTotalFinancialYear, 2, '.', ','), number_format(($totalTotalFinancialYearLastYear > 0) ? (($totalTotalFinancialYear / $totalTotalFinancialYearLastYear) * 100) - 100 : 0, 2, '.', ','));
	$content .= '</tr>';
	$content .= '<tr>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;">FY Last Year (Up to %s)</td>', date("jS M"));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($totalOrdersFinancialYearLastYear, 0, '.', ''));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($totalTotalFinancialYearLastYear, 2, '.', ','));
	$content .= '</tr>';
	$content .= '<tr>';
	$content .= '<td style="border-top:1px solid #dddddd;">FY Last Year (Whole Year)</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($totalOrdersFinancialYearLastYearWhole, 0, '.', ''));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($totalTotalFinancialYearLastYearWhole, 2, '.', ','));
	$content .= '</tr>';
	$content .= '<tr>';
	$content .= '<td style="border-top:1px solid #dddddd;">Total Orders</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', $totalOrdersCount);
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">&nbsp;</td>');
	$content .= '</tr>';
	$content .= '</table>';

	$content .= '<br />';
	$content .= '<h2>Warehouse Stats</h2>';
	$content .= '<br />';

	$warehouses = array(1, 2, 3, 4, 6, 9, 10, 18);

	$content .= '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-bottom:1px solid #dddddd;" width="75%"><strong>All Warehouses</strong></td>';
	$content .= '<td align="right" style="border-bottom:1px solid #dddddd;" width="25%">&nbsp;</td>';
	$content .= '</tr>';

	$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o WHERE o.Status LIKE 'Unread'"));

	$content .= '<tr>';
	$content .= '<td style="border-top:1px solid #dddddd;">New Orders</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', $data->Row['Count']);
	$content .= '</tr>';

	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o WHERE (o.Status LIKE 'Unread' OR o.Status LIKE 'Pending' OR o.Status LIKE 'Packing' OR o.Status LIKE 'Partially Despatched') AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N'"));

	$content .= '<tr>';
	$content .= '<td style="border-top:1px solid #dddddd;">Total Orders</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', $data->Row['Count']);
	$content .= '</tr>';

	$data->Disconnect();

	if(count($warehouses) > 0) {
		$data = new DataQuery(sprintf("SELECT Warehouse_ID, Warehouse_Name FROM warehouse WHERE Warehouse_ID=%s ORDER BY Type DESC, Warehouse_Name ASC", implode(' OR Warehouse_ID=', $warehouses)));
		while($data->Row) {
			$stats = array();

			$data2 = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND ol.Despatch_From_ID=%d WHERE ol.Line_Status LIKE '' AND o.Status LIKE 'Pending' AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N'", $data->Row['Warehouse_ID']));
			$stats['Pending'] = $data2->Row['Count'];
			$data2->Disconnect();

			$data2 = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND ol.Despatch_From_ID=%d AND ol.Despatch_ID=0 LEFT JOIN order_line AS ol2 ON ol2.Order_ID=o.Order_ID AND ol2.Despatch_From_ID=%d AND ol2.Despatch_ID=0 AND ol2.Line_Status LIKE 'Backordered' WHERE (o.Status LIKE 'Packing' OR o.Status LIKE 'Partially Despatched') AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N' AND ol2.Order_Line_ID IS NULL", $data->Row['Warehouse_ID'], $data->Row['Warehouse_ID']));
			$stats['Packing'] = $data2->Row['Count'];
			$data2->Disconnect();

			$data2 = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND ol.Despatch_From_ID=%d AND ol.Despatch_ID=0 LEFT JOIN order_line AS ol2 ON ol2.Order_ID=o.Order_ID AND ol2.Despatch_From_ID=%d AND ol2.Despatch_ID=0 AND ol2.Line_Status LIKE 'Backordered' WHERE (o.Status LIKE 'Packing' OR o.Status LIKE 'Partially Despatched') AND o.Is_Declined='N' AND o.Is_Warehouse_Declined='N' AND ol2.Order_Line_ID IS NOT NULL", $data->Row['Warehouse_ID'], $data->Row['Warehouse_ID']));
			$stats['Backorder'] = $data2->Row['Count'];
			$data2->Disconnect();

			$content .= '<tr style="background-color:#eeeeee;">';
			$content .= sprintf('<td style="border-top:1px solid #dddddd;"><strong>%s</strong></td>', $data->Row['Warehouse_Name']);
			$content .= '<td style="border-top:1px solid #dddddd;" align="right">&nbsp;</td>';
			$content .= '</tr>';

			foreach($stats as $identifier=>$value) {
				$content .= '<tr>';
				$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s</td>', $identifier);
				$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', $value);
				$content .= '</tr>';
			}

			$data->Next();
		}
		$data->Disconnect();
	}

	$content .= '</table>';

	$content .= '</body>';
	$content .= '</html>';

	echo $content;
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();