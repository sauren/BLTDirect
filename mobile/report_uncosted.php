<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
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

	$content .= '<h1>Uncosted Report</h1>';

	$content .= '<br />';
	$content .= '<h2>Products</h2>';
	$content .= '<p>Created products over the last 7 days.</p>';

	$content .= '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
	$content .= '<tr style="background-color: #eeeeee;">';
	$content .= '<td style="border-bottom: 1px solid #dddddd;"><strong>Product</strong></td>';
	$content .= '<td style="border-bottom: 1px solid #dddddd;"><strong>Product ID</strong></td>';
	$content .= '<td style="border-bottom: 1px solid #dddddd;"><strong>Despatched</strong></td>';
	$content .= '</tr>';

    $data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, d.Created_On FROM order_line AS ol INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID INNER JOIN product AS p ON p.Product_ID=ol.Product_ID INNER JOIN despatch AS d ON d.Despatch_ID=ol.Despatch_ID AND d.Created_On>ADDDATE(NOW(), INTERVAL -1 MONTH) WHERE ol.Cost=0 GROUP BY p.Product_ID ORDER BY d.Created_On DESC"));
	while($data->Row) {
		$content .= '<tr>';
		$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s</td>', strip_tags($data->Row['Product_Title']));
		$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s</td>', $data->Row['Product_ID']);
		$content .= sprintf('<td style="border-top:1px solid #dddddd;">%s</td>', $data->Row['Created_On']);
		$content .= '</tr>';

		$data->Next();
	}
	$data->Disconnect();

	$content .= '</body>';
	$content .= '</html>';

	echo $content;
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();