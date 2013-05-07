<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$start = date('Y-m-d 00:00:00');
	$end = date('Y-m-d H:i:s');
	$content = '';

	$invoices = array();
	$total = array('Orders' => 0, 'Net' => 0, 'Tax' => 0, 'Total' => 0);

	$data = new DataQuery(sprintf("SELECT * FROM payment_method ORDER BY Method ASC"));
	while($data->Row) {
        $data2 = new DataQuery(sprintf("SELECT COUNT(i.Invoice_ID) AS Invoices, COUNT(DISTINCT i.Order_ID) AS Orders, SUM(i.Invoice_Net - i.Invoice_Discount) AS Invoice_Net, SUM(i.Invoice_Tax) AS Invoice_Tax, SUM(i.Invoice_Total) AS Invoice_Total FROM invoice AS i WHERE i.Payment_Method_ID=%d AND i.Created_On BETWEEN '%s' AND '%s' ORDER BY i.Invoice_ID ASC", $data->Row['Payment_Method_ID'], $start, $end));
		if($data2->Row['Invoices'] > 0) {
	        $item = array();
			$item['Orders'] = $data2->Row['Orders'];
			$item['Net'] = $data2->Row['Invoice_Net'];
			$item['Tax'] = $data2->Row['Invoice_Tax'];
			$item['Total'] = $data2->Row['Invoice_Total'];

			$total['Orders'] += $item['Orders'];
			$total['Net'] += $item['Net'];
			$total['Tax'] += $item['Tax'];
			$total['Total'] += $item['Total'];

			$invoices[] = array('PaymentMethod' => $data->Row, 'Value' => $item);
		}
		$data2->Disconnect();

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT n.Order_ID) AS Orders, SUM(n.TotalNet) AS Credit_Net, SUM(n.TotalTax) AS Credit_Tax, SUM(n.Total) AS Credit_Total FROM credit_note AS n INNER JOIN orders AS o ON o.Order_ID=n.Order_ID WHERE n.Credited_On BETWEEN '%s' AND '%s' ORDER BY n.Credit_Note_ID ASC", $start, $end));
	$noteOrders = $data->Row['Orders'];
	$noteNetTotal = $data->Row['Credit_Net'];
	$noteTaxTotal = $data->Row['Credit_Tax'];
	$noteTotal = $data->Row['Credit_Total'];
	$data->Disconnect();

    $total['Net'] -= $noteNetTotal;
	$total['Tax'] -= $noteTaxTotal;
	$total['Total'] -= $noteTotal;

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

	$content .= '<h1>Invoices Report</h1>';
	$content .= sprintf('<p style="margin-bottom: 0;">%s - %s</p>', cDatetime($start, 'longdatetime'), cDatetime($end, 'longdatetime'));

	$content .= '<br />';
	$content .= '<h2>Invoices Summary</h2>';
	$content .= '<p>Summary of all invoice methods for the date range.</p>';

	$content .= '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-bottom:1px solid #dddddd;"><strong>Invoice Type</strong></td>';
	$content .= '<td align="right" style="border-bottom:1px solid #dddddd;"><strong>Orders</strong></td>';
	$content .= '<td align="right" style="border-bottom:1px solid #dddddd;"><strong>Net</strong></td>';
	$content .= '<td align="right" style="border-bottom:1px solid #dddddd;"><strong>Tax</strong></td>';
	$content .= '<td align="right" style="border-bottom:1px solid #dddddd;"><strong>Total</strong></td>';
	$content .= '</tr>';

	foreach($invoices as $invoice) {
        $content .= '<tr class="dataRow" onMouseOver="setClassName(this, \'dataRowOver\');" onMouseOut="setClassName(this, \'dataRow\');">';
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" valign="top">%s</td>', $invoice['PaymentMethod']['Method']);
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" valign="top" align="right">%d</td>', $invoice['Value']['Orders']);
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" valign="top" align="right">&pound;%s</td>', number_format($invoice['Value']['Net'], 2, '.', ','));
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" valign="top" align="right">&pound;%s</td>', number_format($invoice['Value']['Tax'], 2, '.', ','));
		$content .= sprintf('<td style="border-top:1px solid #dddddd;" valign="top" align="right">&pound;%s</td>', number_format($invoice['Value']['Total'], 2, '.', ','));
		$content .= '</tr>';
	}

	$content .= '<tr class="dataRow" onMouseOver="setClassName(this, \'dataRowOver\');" onMouseOut="setClassName(this, \'dataRow\');">';
	$content .= '<td style="border-top:1px solid #dddddd;" valign="top">Credit Notes</td>';	
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" valign="top" align="right">%d</td>', $noteOrders);
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" valign="top" align="right">&pound;%s</td>', number_format($noteNetTotal, 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" valign="top" align="right">&pound;%s</td>', number_format($noteTaxTotal, 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" valign="top" align="right">&pound;%s</td>', number_format($noteTotal, 2, '.', ','));
	$content .= '</tr>';
	$content .= '<tr style="background-color:#eeeeee;">';
	$content .= '<td style="border-top:1px solid #dddddd;" valign="top"><strong>Totals</td>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" valign="top" align="right"><strong>%d</strong></td>', $total['Orders']);
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" valign="top" align="right"><strong>&pound;%s</strong></td>', number_format($total['Net'], 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" valign="top" align="right"><strong>&pound;%s</strong></td>', number_format($total['Tax'], 2, '.', ','));
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" valign="top" align="right"><strong>&pound;%s</strong></td>', number_format($total['Total'], 2, '.', ','));
	$content .= '</tr>';
	$content .= '</table>';

	$content .= '</body>';
	$content .= '</html>';

	echo $content;
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();