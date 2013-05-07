<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$counts = array(10, 5, 3, 1, 0);
	
	$chart1Legend = array();
	
	$lastCount = 0;
	
	foreach($counts as $count) {
		$chart1Legend[] = ($lastCount > 0) ? sprintf('%d-%d', $count, $lastCount-1) : sprintf('%d+', $count);
		
		$lastCount = $count;
	}
	
	$chart1FileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
	$chart1Width = 900;
	$chart1Height = 600;
	$chart1Title = 'Monthly account customer order frequencies.';
	$chart1Reference = sprintf('../ignition/temp/charts/chart_%s.png', $chart1FileName);
	
	$chart1 = new LineChart($chart1Width, $chart1Height, $chart1Legend);

	$startDate = '2010-01-01 00:00:00';
	$endDate = date('Y-m-01 00:00:00');
	$tempDate = $startDate;
	
	while(strtotime($tempDate) < strtotime($endDate)) {
		new DataQuery(sprintf('CREATE TEMPORARY TABLE temp_orders SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM customer AS c INNER JOIN contact_credit_account AS cca ON cca.contactId=c.Contact_ID AND cca.startedOn<=\'%1$s\' AND (cca.endedOn>\'%1$s\' OR cca.endedOn=\'0000-00-00 00:00:00\') LEFT JOIN orders AS o ON o.Customer_ID=c.Customer_ID AND o.Created_On<=\'%1$s\' GROUP BY c.Customer_ID', mysql_real_escape_string($tempDate)));
		
		$points = array();
		
		$lastCount = 0;
		
		foreach($counts as $count) {
			$data = new DataQuery(sprintf("SELECT COUNT(Count) AS Frequency FROM temp_orders WHERE Count>=%d%s", $count, ($lastCount > 0) ? sprintf(' AND Count<%d', $lastCount) : ''));
			$points[] = $data->Row['Frequency'];
			$data->Disconnect();
		
			$lastCount = $count;
		}
		
		$chart1->addPoint(new Point(date('M Y', strtotime($tempDate)), $points));
		
		new DataQuery(sprintf('DROP TABLE temp_orders'));
		
		$tempDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($tempDate)) + 1, 1, date('Y', strtotime($tempDate))));
	}
	
	$chart1->SetTitle($chart1Title);
	$chart1->SetLabelY('Customers');
	$chart1->ShowText = false;
	$chart1->render($chart1Reference);
		
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

	$content .= '<h1>Accounts Report</h1>';

	$content .= '<h2>Frequency</h2>';
	$content .= '<p>Account customer order frequencies.</p>';

	$content .= '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
	$content .= '<tr style="background-color: #eeeeee;">';
	$content .= '<td style="border-bottom: 1px solid #dddddd;"><strong>Orders</strong></td>';
	$content .= '<td style="border-bottom: 1px solid #dddddd;" align="right"><strong>Customers</strong></td>';
	$content .= '</tr>';
	
	$data = new DataQuery(sprintf("SELECT COUNT(o.Count) AS Frequency FROM (SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM customer AS c LEFT JOIN orders AS o ON o.Customer_ID=c.Customer_ID WHERE c.Is_Credit_Active='Y' AND c.Credit_Period>0 GROUP BY c.Customer_ID) AS o WHERE o.Count>=10"));
	
	$content .= '<tr>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;">10+</td>');
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%d</td>', $data->Row['Frequency']);
	$content .= '</tr>';
	
	$data->Disconnect();
	
	$data = new DataQuery(sprintf("SELECT COUNT(o.Count) AS Frequency FROM (SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM customer AS c LEFT JOIN orders AS o ON o.Customer_ID=c.Customer_ID WHERE c.Is_Credit_Active='Y' AND c.Credit_Period>0 GROUP BY c.Customer_ID) AS o WHERE o.Count>=5 AND o.Count<=9"));
	
	$content .= '<tr>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;">5-9</td>');
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%d</td>', $data->Row['Frequency']);
	$content .= '</tr>';
	
	$data->Disconnect();
	
	$data = new DataQuery(sprintf("SELECT COUNT(o.Count) AS Frequency FROM (SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM customer AS c LEFT JOIN orders AS o ON o.Customer_ID=c.Customer_ID WHERE c.Is_Credit_Active='Y' AND c.Credit_Period>0 GROUP BY c.Customer_ID) AS o WHERE o.Count>=3 AND o.Count<=4"));
	
	$content .= '<tr>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;">3-4</td>');
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%d</td>', $data->Row['Frequency']);
	$content .= '</tr>';
	
	$data->Disconnect();
	
	$data = new DataQuery(sprintf("SELECT COUNT(o.Count) AS Frequency FROM (SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM customer AS c LEFT JOIN orders AS o ON o.Customer_ID=c.Customer_ID WHERE c.Is_Credit_Active='Y' AND c.Credit_Period>0 GROUP BY c.Customer_ID) AS o WHERE o.Count>=1 AND o.Count<=2"));
	
	$content .= '<tr>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;">1-2</td>');
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%d</td>', $data->Row['Frequency']);
	$content .= '</tr>';
	
	$data->Disconnect();
	
	$data = new DataQuery(sprintf("SELECT COUNT(o.Count) AS Frequency FROM (SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM customer AS c LEFT JOIN orders AS o ON o.Customer_ID=c.Customer_ID WHERE c.Is_Credit_Active='Y' AND c.Credit_Period>0 GROUP BY c.Customer_ID) AS o WHERE o.Count=0"));
	
	$content .= '<tr>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;">0</td>');
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right">%d</td>', $data->Row['Frequency']);
	$content .= '</tr>';
	
	$data->Disconnect();
	
	$data = new DataQuery(sprintf("SELECT COUNT(o.Count) AS Frequency FROM (SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM customer AS c LEFT JOIN orders AS o ON o.Customer_ID=c.Customer_ID WHERE c.Is_Credit_Active='Y' AND c.Credit_Period>0 GROUP BY c.Customer_ID) AS o"));
	
	$content .= '<tr>';
	$content .= sprintf('<td style="border-top:1px solid #dddddd;"></td>');
	$content .= sprintf('<td style="border-top:1px solid #dddddd;" align="right"><strong>%d</strong></td>', $data->Row['Frequency']);
	$content .= '</tr>';
	$content .= '</table>';
	$content .= '<br />';
	
	$data->Disconnect();
	
	$content .= '<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">';
	$content .= sprintf('<img src="%s" width="%d" height="%d" alt="%s" />', $chart1Reference, $chart1Width, $chart1Height, $chart1Title);
	$content .= '</div>';

	$content .= '</body>';
	$content .= '</html>';

	echo $content;
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();