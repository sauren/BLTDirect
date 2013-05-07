<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$connections = getSyncConnections();

	$start = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')));
	$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m') + 1, 1, date('Y')));

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

	$content .= '<h1>Product Report</h1>';
	$content .= sprintf('<p style="margin-bottom: 0;">%s - %s</p>', cDatetime($start, 'longdatetime'), cDatetime($end, 'longdatetime'));

	$content .= '<br />';
	$content .= '<h2>Popular Products</h2>';
	$content .= '<br />';

	$content .= '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
	$content .= '<tr style="background-color: #eeeeee;">';
	$content .= '<td style="border-bottom:1px solid #dddddd;"><strong>Rank</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;"><strong>Product Name</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Quickfind</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Quantity Ordered</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Orders</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Average Sold Price</strong></td>';
	$content .= '<td style="border-bottom:1px solid #dddddd;" align="right"><strong>Total</strong></td>';
	$content .= '</tr>';

	$rank = 1;
	$totalPrice = 0;

	$data = new DataQuery(sprintf("SELECT SUM(ol.Quantity) AS Quantity, ol.Product_ID, ol.Product_Title, SUM(ol.Line_Total) AS Total, COUNT(DISTINCT o.Order_ID) AS Orders, COUNT(DISTINCT ws.Stock_ID) AS Stocked_Items FROM order_line AS ol LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=ol.Product_ID LEFT JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID WHERE o.Created_On BETWEEN '%s' AND '%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY ol.Product_ID ORDER BY Orders DESC", $start, $end));
	while($data->Row) {
		$totalPrice += $data->Row['Total'];

		$content .= sprintf('<tr%s>', ($data->Row['Stocked_Items'] == 0) ? ' style="background-color: #ffc;"' : '');
		$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s</td>', $rank);
		$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s</td>', strip_tags($data->Row['Product_Title']));
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', $data->Row['Product_ID']);
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', $data->Row['Quantity']);
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', $data->Row['Orders']);
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($data->Row['Total'] / $data->Row['Quantity'], 2, '.', ','));
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%s</td>', number_format($data->Row['Total'], 2, '.', ','));
		$content .= '</tr>';

		$rank++;

		$data->Next();
	}
	$data->Disconnect();

	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-top:1px solid #dddddd;"><strong>Totals</strong></td>';
	$content .= '<td style="border-top:1px solid #dddddd;"><strong>&nbsp;</strong></td>';
	$content .= '<td style="border-top:1px solid #dddddd;"><strong>&nbsp;</strong></td>';
	$content .= '<td style="border-top:1px solid #dddddd;"><strong>&nbsp;</strong></td>';
	$content .= '<td style="border-top:1px solid #dddddd;"><strong>&nbsp;</strong></td>';
	$content .= '<td style="border-top:1px solid #dddddd;"><strong>&nbsp;</strong></td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right"><strong>%s</strong></td>', number_format($totalPrice, 2, '.', ','));
	$content .= '</tr>';
	$content .= '</table>';

	$content .= '</body>';
	$content .= '</html>';

	echo $content;
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();
?>