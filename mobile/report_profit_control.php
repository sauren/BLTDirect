<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");

function calculateOverhead($overheads = array(), $startTime, $endTime) {
	$tempTime = $startTime;
	$value = 0;

	while($tempTime<$endTime) {
		foreach($overheads as $overhead) {
			$itemValue = 0;

			if((strtotime($overhead['Start_Date']) <= $tempTime) && ($tempTime < strtotime($overhead['End_Date']))) {
				switch($overhead['Is_Working_Days_Only']) {
					case 'N':
						switch($overhead['Period']) {
							case 'Y':
                                $itemValue += $overhead['Value'] / 365;
								break;
							case 'M':
                                $itemValue += $overhead['Value'] / date('t', $tempTime);
								break;
							case 'D':
								$itemValue += $overhead['Value'];
								break;
						}

						$value += $itemValue;

						break;

					case 'Y':
						if(date('N', $tempTime) < 6) {
	                        switch($overhead['Period']) {
								case 'Y':
                                	$itemValue += $overhead['Value'] / ((365 / 7) * 5);
									break;
								case 'M':
                                	$itemValue += ($overhead['Value'] * 12) / ((365 / 7) * 5);
									break;
								case 'D':
									$itemValue += $overhead['Value'];
									break;
							}

							$value += $itemValue;
						}

						break;
				}
			}
		}

		$tempTime = mktime(0, 0, 0, date('m', $tempTime), date('d', $tempTime) + 1, date('Y', $tempTime));
	}

	return $value;
}

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$connections = getSyncConnections();

    $overheads = array();

    $data2 = new DataQuery(sprintf("SELECT * FROM overhead"));
	while($data2->Row) {
		$overheads[] = $data2->Row;

        $data2->Next();
	}
	$data2->Disconnect();

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

	$content .= '<h1>Profit Control Report</h1>';

    $content .= '<br />';
	$content .= '<h2>Profit Summary</h2>';
	$content .= '<br />';

    $types = array();
	$types['T'] = 'Telesales';
	$types['W'] = 'Website (bltdirect.com)';
	$types['U'] = 'Website (bltdirect.co.uk)';

    $content .= '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-bottom:1px solid #dddddd;"><strong>Orders</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Today</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Last 7 Days</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>This Month</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>This Month Last Year</strong></td>';
	$content .= '</tr>';

	$totalTurnoverToday = 0;
	$totalProfitToday = 0;
    $totalTurnover7Days = 0;
	$totalProfit7Days = 0;
    $totalTurnoverMonth = 0;
	$totalProfitMonth = 0;
    $totalTurnoverMonthLastYear = 0;
	$totalProfitMonthLastYear = 0;

	foreach($types as $prefix=>$type) {
		$data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID AND o.Order_Prefix='%s' WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Cost>0", $prefix, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')+1, date('Y')))));
		$turnoverToday = $data2->Row['Turnover'];
		$profitToday = $data2->Row['Profit'];
		$data2->Disconnect();

	    $data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID AND o.Order_Prefix='%s' WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Cost>0", $prefix, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')-7, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y')))));
		$turnover7Days = $data2->Row['Turnover'];
		$profit7Days = $data2->Row['Profit'];
		$data2->Disconnect();

	    $data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID AND o.Order_Prefix='%s' WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Cost>0", $prefix, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m')+1, 1, date('Y')))));
		$turnoverMonth = $data2->Row['Turnover'];
		$profitMonth = $data2->Row['Profit'];
		$data2->Disconnect();

	    $data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID AND o.Order_Prefix='%s' WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Cost>0", $prefix, date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')-1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m')+1, 1, date('Y')-1))));
		$turnoverMonthLastYear = $data2->Row['Turnover'];
		$profitMonthLastYear = $data2->Row['Profit'];
		$data2->Disconnect();

		$content .= '<tr>';
		$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s</td>', $type);
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($turnoverToday, 2), 2, '.', ','), number_format(round($profitToday, 2), 2, '.', ','), number_format(round(($turnoverToday > 0) ? (($profitToday / $turnoverToday) * 100) : 0, 2), 2, '.', ','));
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($turnover7Days, 2), 2, '.', ','), number_format(round($profit7Days, 2), 2, '.', ','), number_format(round(($turnover7Days > 0) ? (($profit7Days / $turnover7Days) * 100) : 0, 2), 2, '.', ','));
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($turnoverMonth, 2), 2, '.', ','), number_format(round($profitMonth, 2), 2, '.', ','), number_format(round(($turnoverMonth > 0) ? (($profitMonth / $turnoverMonth) * 100) : 0, 2), 2, '.', ','));
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($turnoverMonthLastYear, 2), 2, '.', ','), number_format(round($profitMonthLastYear, 2), 2, '.', ','), number_format(round(($turnoverMonthLastYear > 0) ? (($profitMonthLastYear / $turnoverMonthLastYear) * 100) : 0, 2), 2, '.', ','));
		$content .= '</tr>';

        $totalTurnoverToday += $turnoverToday;
		$totalProfitToday += $profitToday;
	    $totalTurnover7Days += $turnover7Days;
		$totalProfit7Days += $profit7Days;
	    $totalTurnoverMonth += $turnoverMonth;
		$totalProfitMonth += $profitMonth;
	    $totalTurnoverMonthLastYear += $turnoverMonthLastYear;
		$totalProfitMonthLastYear += $profitMonthLastYear;
	}

	$data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID AND o.Order_Prefix='%s' WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Cost>0", 'L', date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')+1, date('Y')))), $connections[1]['Connection']);
	$turnoverToday = $data2->Row['Turnover'];
	$profitToday = $data2->Row['Profit'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID AND o.Order_Prefix='%s' WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Cost>0", 'L', date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')-7, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y')))), $connections[1]['Connection']);
	$turnover7Days = $data2->Row['Turnover'];
	$profit7Days = $data2->Row['Profit'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID AND o.Order_Prefix='%s' WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Cost>0", 'L', date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m')+1, 1, date('Y')))), $connections[1]['Connection']);
	$turnoverMonth = $data2->Row['Turnover'];
	$profitMonth = $data2->Row['Profit'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID AND o.Order_Prefix='%s' WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Cost>0", 'L', date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')-1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m')+1, 1, date('Y')-1))), $connections[1]['Connection']);
	$turnoverMonthLastYear = $data2->Row['Turnover'];
	$profitMonthLastYear = $data2->Row['Profit'];
	$data2->Disconnect();

	$content .= '<tr>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s</td>', 'Website (lightbulbsuk.co.uk)');
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($turnoverToday, 2), 2, '.', ','), number_format(round($profitToday, 2), 2, '.', ','), number_format(round(($turnoverToday > 0) ? (($profitToday / $turnoverToday) * 100) : 0, 2), 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($turnover7Days, 2), 2, '.', ','), number_format(round($profit7Days, 2), 2, '.', ','), number_format(round(($turnover7Days > 0) ? (($profit7Days / $turnover7Days) * 100) : 0, 2), 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($turnoverMonth, 2), 2, '.', ','), number_format(round($profitMonth, 2), 2, '.', ','), number_format(round(($turnoverMonth > 0) ? (($profitMonth / $turnoverMonth) * 100) : 0, 2), 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($turnoverMonthLastYear, 2), 2, '.', ','), number_format(round($profitMonthLastYear, 2), 2, '.', ','), number_format(round(($turnoverMonthLastYear > 0) ? (($profitMonthLastYear / $turnoverMonthLastYear) * 100) : 0, 2), 2, '.', ','));
	$content .= '</tr>';

    $totalTurnoverToday += $turnoverToday;
	$totalProfitToday += $profitToday;
	$totalTurnover7Days += $turnover7Days;
	$totalProfit7Days += $profit7Days;
	$totalTurnoverMonth += $turnoverMonth;
	$totalProfitMonth += $profitMonth;
	$totalTurnoverMonthLastYear += $turnoverMonthLastYear;
	$totalProfitMonthLastYear += $profitMonthLastYear;

    $overheadToday = calculateOverhead($overheads, mktime(0, 0, 0, date('m'), date('d'), date('Y')), mktime(0, 0, 0, date('m'), date('d')+1, date('Y')));
    $overhead7Days = calculateOverhead($overheads, mktime(0, 0, 0, date('m'), date('d')-7, date('Y')), mktime(0, 0, 0, date('m'), date('d'), date('Y')));
    $overheadMonth = calculateOverhead($overheads, mktime(0, 0, 0, date('m'), 1, date('Y')), mktime(0, 0, 0, date('m'), date('d')+1, date('Y')));
    $overheadMonthLastYear = calculateOverhead($overheads, mktime(0, 0, 0, date('m'), 1, date('Y')-1), mktime(0, 0, 0, date('m')+1, 1, date('Y')-1));

    $content .= '<tr>';
	$content .= '<td style="border-top:1px solid #dddddd;">&nbsp;</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($totalTurnoverToday, 2), 2, '.', ','), number_format(round($totalProfitToday, 2), 2, '.', ','), number_format(round(($totalTurnoverToday > 0) ? (($totalProfitToday / $totalTurnoverToday) * 100) : 0, 2), 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($totalTurnover7Days, 2), 2, '.', ','), number_format(round($totalProfit7Days, 2), 2, '.', ','), number_format(round(($totalTurnover7Days > 0) ? (($totalProfit7Days / $totalTurnover7Days) * 100) : 0, 2), 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($totalTurnoverMonth, 2), 2, '.', ','), number_format(round($totalProfitMonth, 2), 2, '.', ','), number_format(round(($totalTurnoverMonth > 0) ? (($totalProfitMonth / $totalTurnoverMonth) * 100) : 0, 2), 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($totalTurnoverMonthLastYear, 2), 2, '.', ','), number_format(round($totalProfitMonthLastYear, 2), 2, '.', ','), number_format(round(($totalTurnoverMonthLastYear > 0) ? (($totalProfitMonthLastYear / $totalTurnoverMonthLastYear) * 100) : 0, 2), 2, '.', ','));
	$content .= '</tr>';
	$content .= '<tr>';
	$content .= '<td style="border-top:1px solid #dddddd;">Overheads</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format(round($overheadToday, 2), 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format(round($overhead7Days, 2), 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format(round($overheadMonth, 2), 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format(round($overheadMonthLastYear, 2), 2, '.', ','));
	$content .= '</tr>';
	$content .= '<tr>';
	$content .= '<td style="border-top:1px solid #dddddd;">Gross Profit</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right"><strong>%s<br /><small>Percent: %s</small></strong></td>', number_format(round($totalProfitToday - $overheadToday, 2), 2, '.', ','), number_format(round((($totalProfitToday - $overheadToday) / $totalTurnoverToday) * 100, 2), 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right"><strong>%s<br /><small>Percent: %s</small></strong></td>', number_format(round($totalProfit7Days - $overhead7Days, 2), 2, '.', ','), number_format(round((($totalProfit7Days - $overhead7Days) / $totalTurnover7Days) * 100, 2), 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right"><strong>%s<br /><small>Percent: %s</small></strong></td>', number_format(round($totalProfitMonth - $overheadMonth, 2), 2, '.', ','), number_format(round((($totalProfitMonth - $overheadMonth) / $totalTurnoverMonth) * 100, 2), 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right"><strong>%s<br /><small>Percent: %s</small></strong></td>', number_format(round($totalProfitMonthLastYear - $overheadMonthLastYear, 2), 2, '.', ','), number_format(round((($totalProfitMonthLastYear - $overheadMonthLastYear) / $totalTurnoverMonthLastYear) * 100, 2), 2, '.', ','));
	$content .= '</tr>';
	$content .= '</table>';

    $content .= '<br />';
	$content .= '<h2>Products</h2>';
	$content .= '<br />';

	$content .= '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-bottom:1px solid #dddddd;"><strong>Product</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;"><strong>Product ID</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Today</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Last 7 Days</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>This Month</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>This Month Last Year</strong></td>';
	$content .= '</tr>';

	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title FROM product AS p WHERE p.Is_Profit_Control='Y' ORDER BY p.Product_ID ASC"));
	while($data->Row) {
		$data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", $data->Row['Product_ID'], date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')+1, date('Y')))));
		$turnoverToday = $data2->Row['Turnover'];
		$profitToday = $data2->Row['Profit'];
		$data2->Disconnect();

	    $data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", $data->Row['Product_ID'], date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d')-7, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y')))));
		$turnover7Days = $data2->Row['Turnover'];
		$profit7Days = $data2->Row['Profit'];
		$data2->Disconnect();

	    $data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", $data->Row['Product_ID'], date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y'))), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y')))));
		$turnoverMonth = $data2->Row['Turnover'];
		$profitMonth = $data2->Row['Profit'];
		$data2->Disconnect();

	    $data2 = new DataQuery(sprintf("SELECT SUM(ol.Line_Total-ol.Line_Discount) AS Turnover, SUM(ol.Line_Total-ol.Line_Discount-(ol.Cost*ol.Quantity)) AS Profit FROM order_line AS ol INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", $data->Row['Product_ID'], date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')-1)), date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('t'), date('Y')-1))));
		$turnoverMonthLastYear = $data2->Row['Turnover'];
		$profitMonthLastYear = $data2->Row['Profit'];
		$data2->Disconnect();

		$content .= '<tr>';
		$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s</td>', strip_tags($data->Row['Product_Title']));
		$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s</td>', $data->Row['Product_ID']);
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($turnoverToday, 2), 2, '.', ','), number_format(round($profitToday, 2), 2, '.', ','), number_format(round(($turnoverToday > 0) ? (($profitToday / $turnoverToday) * 100) : 0, 2), 2, '.', ','));
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($turnover7Days, 2), 2, '.', ','), number_format(round($profit7Days, 2), 2, '.', ','), number_format(round(($turnover7Days > 0) ? (($profit7Days / $turnover7Days) * 100) : 0, 2), 2, '.', ','));
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($turnoverMonth, 2), 2, '.', ','), number_format(round($profitMonth, 2), 2, '.', ','), number_format(round(($turnoverMonth > 0) ? (($profitMonth / $turnoverMonth) * 100) : 0, 2), 2, '.', ','));
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s<br /><small>Profit: %s</small><br /><small>Percent: %s</small></td>', number_format(round($turnoverMonthLastYear, 2), 2, '.', ','), number_format(round($profitMonthLastYear, 2), 2, '.', ','), number_format(round(($turnoverMonthLastYear > 0) ? (($profitMonthLastYear / $turnoverMonthLastYear) * 100) : 0, 2), 2, '.', ','));
		$content .= '</tr>';

		$data->Next();
	}
	$data->Disconnect();

	$content .= '</table>';

	$content .= '</body>';
	$content .= '</html>';

	echo $content;
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();