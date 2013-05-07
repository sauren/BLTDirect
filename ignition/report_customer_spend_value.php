<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Customer Spend Value Report', 'Please choose a start and end date for your report');
	$year = cDatetime(getDatetime(), 'y');
	$form = new Form($_SERVER['PHP_SELF'], 'get');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'Report End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('range', 'Date range', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('range', 'none', '-- None --');
	$form->AddOption('range', 'all', '-- All --');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'thisminute', 'This Minute');
	$form->AddOption('range', 'thishour', 'This Hour');
	$form->AddOption('range', 'thisday', 'This Day');
	$form->AddOption('range', 'thismonth', 'This Month');
	$form->AddOption('range', 'thisyear', 'This Year');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lasthour', 'Last Hour');
	$form->AddOption('range', 'last3hours', 'Last 3 Hours');
	$form->AddOption('range', 'last6hours', 'Last 6 Hours');
	$form->AddOption('range', 'last12hours', 'Last 12 Hours');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastday', 'Last Day');
	$form->AddOption('range', 'last2days', 'Last 2 Days');
	$form->AddOption('range', 'last3days', 'Last 3 Days');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastmonth', 'Last Month');
	$form->AddOption('range', 'last3months', 'Last 3 Months');
	$form->AddOption('range', 'last6months', 'Last 6 Months');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastyear', 'Last Year');
	$form->AddOption('range', 'last2years', 'Last 2 Years');
	$form->AddOption('range', 'last3years', 'Last 3 Years');
	$form->AddField('prefix', 'Order Type', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('prefix', '', '-- All --');
	$form->AddOption('prefix', 'W', 'Website');
	$form->AddOption('prefix', 'T', 'Telesales');
	$form->AddOption('prefix', 'E', 'Email');
	$form->AddOption('prefix', 'F', 'Fax');
	$form->AddOption('prefix', 'M', 'Mobile');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 1)) {
			switch($form->GetValue('range')) {
				case 'all': 		$start = date('Y-m-d H:i:s', 0);
				$end = date('Y-m-d H:i:s');
				break;

				case 'thisminute': 	$start = date('Y-m-d H:i:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thishour': 	$start = date('Y-m-d H:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thisday': 	$start = date('Y-m-d 00:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thismonth': 	$start = date('Y-m-01 00:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thisyear': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")));
				$end = date('Y-m-d H:i:s');
				break;

				case 'lasthour': 	$start = date('Y-m-d H:00:00', mktime(date("H")-1, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last3hours': 	$start = date('Y-m-d H:00:00', mktime(date("H")-3, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last6hours': 	$start = date('Y-m-d H:00:00', mktime(date("H")-6, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last12hours': $start = date('Y-m-d H:00:00', mktime(date("H")-12, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;

				case 'lastday': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last2days': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-2, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last3days': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;

				case 'lastmonth': 	$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-1, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;
				case 'last3months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-3, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;
				case 'last6months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-6, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;

				case 'lastyear': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-1));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
				case 'last2years': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-2));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
				case 'last3years': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-3));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
			}

			report($start, $end, $form->GetValue('prefix'));
			exit;
		} else {
			
			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('prefix'));
				exit;
			}
		}
	}

	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Customer Spend Values.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select the type of orders to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('prefix'), $form->GetHTML('prefix'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Select one of the predefined date ranges for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('range'), $form->GetHTML('range'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Or select the date range from below for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start'));
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function report($start, $end, $prefix){
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/chart/libchart.php');

	$totalFrequency = 0;
	$lastRange = 0;

	$ranges = array();
	$ranges[10] = 0;
	$ranges[20] = 0;
	$ranges[40] = 0;
	$ranges[60] = 0;
	$ranges[80] = 0;
	$ranges[100] = 0;
	$ranges[250] = 0;
	$ranges[500] = 0;
	$ranges[750] = 0;
	$ranges[1000] = 0;
	$ranges[2000] = 0;
	$ranges[3000] = 0;
	$ranges[5000] = 0;
	$ranges[10000] = 0;
	$ranges[1000000] = 0;

	$page = new Page('Customer Spend Value Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
	?>

	<h3>Sale Value Count</h3>
	<p>The number of customers whose total sales value for the period lies between the below predefined ranges.</p>

	<?php
	$sqlPrefix = '';

	if(strlen($prefix) > 0) {
		$sqlPrefix = sprintf(" WHERE o.Status<>'Unauthenticated' AND o.Status<>'Cancelled' AND o.Order_Prefix='%s' AND o.Created_On BETWEEN '%s' AND '%s'", mysql_real_escape_string($prefix), $start, $end);
	} else {
		$sqlPrefix = sprintf(" WHERE o.Status<>'Unauthenticated' AND o.Status<>'Cancelled' AND o.Created_On BETWEEN '%s' AND '%s'", $start, $end);
	}

	$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_average SELECT o.Order_ID, ol.Order_Line_ID, o.Customer_ID, sp.Product_ID, (sp.Cost*ol.Quantity) AS Cost, ((pp.Price_Base_Our-(ol.Line_Discount/ol.Quantity))*ol.Quantity) AS Price
										FROM orders AS o
										INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID
										INNER JOIN supplier_product AS sp ON sp.Product_ID=ol.Product_ID
										INNER JOIN product_prices AS pp ON pp.Product_ID=ol.Product_ID AND pp.Price_Starts_On<=NOW()
										%s AND sp.Preferred_Supplier='Y'
										ORDER BY pp.Price_Starts_On DESC", $sqlPrefix));
	$data->Disconnect();

	$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_orders SELECT SUM(o.Total) AS Total, o.Customer_ID FROM orders AS o%s GROUP BY o.Customer_ID", $sqlPrefix));
	$data->Disconnect();

	$used = array();
	$profit = array();

	foreach($ranges as $key => $range) {
		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM temp_orders WHERE Total>%d AND Total<=%d", $lastRange, $key));
		$ranges[$key] = $data->Row['Count'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_customers SELECT DISTINCT Customer_ID FROM temp_orders WHERE Total>%d AND Total<=%d", $lastRange, $key));
		$data->Disconnect();

		$profit[$key]['Profit'] = 0;
		$profit[$key]['Count'] = 0;

		$data = new DataQuery(sprintf("SELECT ta.* FROM temp_average AS ta INNER JOIN temp_customers AS tc ON tc.Customer_ID=ta.Customer_ID"));
		while($data->Row) {
			$uniqueName = $data->Row['Order_ID'].'-'.$data->Row['Order_Line_ID'];
			if(!isset($used[$uniqueName])) {
				$used[$uniqueName] = true;

				$profit[$key]['Profit'] += $data->Row['Price'] - $data->Row['Cost'];
				$profit[$key]['Count']++;
			}

			$data->Next();
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("DROP TABLE temp_customers"));
		$data->Disconnect();

		$lastRange = $key;
		$totalFrequency += $ranges[$key];
	}

	$lastRange = 0;
	?>

	<table width="100%" border="0" >
		<tr>
			<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Start Sale Value Range</strong></td>
			<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>End Sale Value Range</strong></td>
			<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer Sale Value Frequency</strong></td>
			<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer Sale Value Percentage</strong></td>
			<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Average Profit</strong></td>
		</tr>

		<?php
		foreach($ranges as $key => $range) {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td align="left">&pound;<?php print number_format($lastRange + 0.01, 2, '.', ','); ?></td>
				<td align="left">&pound;<?php print number_format($key, 2, '.', ','); ?></td>
				<td align="left"><?php print $range; ?></td>
				<td align="left"><?php print number_format(($range/$totalFrequency)*100, 2, '.', ','); ?>%</td>
				<td align="left">&pound;<?php print number_format(($profit[$key]['Profit']/$profit[$key]['Count']), 2, '.', ','); ?></td>
			</tr>

			<?php
			$lastRange = $key;
		}
		?>

	</table>

	<?php
	$lastRange = 0;

	$chartFileName = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
	$chartWidth = 900;
	$chartHeight = 600;
	$chartTitle = 'Sale Value Count';
	$chartReference = sprintf('temp/charts/chart_%s.png', $chartFileName);

	$chart = new LineChart(900,600,null);

	foreach($ranges as $key => $range) {
		$chart->addPoint(new Point(sprintf("%s", number_format($key, 0, '.', ',')),array($range)));

		$lastRange = $key;
	}

	$chart->SetTitle($chartTitle);
	$chart->SetLabelY('Customer Sale Value Frequency');
	$chart->render($chartReference);
	?>

	<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
		<img src="<?php echo $chartReference; ?>" width="<?php print $chartWidth; ?>" height="<?php print $chartHeight; ?>" alt="<?php print $chartTitle; ?>" />
	</div>

	<script language="javascript">
		window.onload = function() {
			var httpRequest = new HttpRequest();
			httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $chartReference; ?>');
		}
	</script>

	<?php
	$data = new DataQuery("DROP TEMPORARY TABLE temp_orders");
	$data->Disconnect();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
?>