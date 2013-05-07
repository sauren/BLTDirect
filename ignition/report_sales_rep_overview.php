<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

if($action == 'report') {
	$session->Secure(2);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start(){
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'Report End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('range', 'Date Range', 'select', 'none', 'alpha_numeric', 0, 32);
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
	$form->AddField('user', 'Sales Rep', 'select', '', 'anything', 1, 11);
	$form->AddOption('user', '', '');

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY User ASC"));
	while($data->Row) {
		$form->AddOption('user', $data->Row['User_ID'], $data->Row['User']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('splithighdiscount', 'Split High Discounts', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm'])) {
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

			redirect(sprintf('Location: %s?action=report&start=%s&end=%s&user=%d&splithighdiscount=%s', $_SERVER['PHP_SELF'], $start, $end, $form->GetValue('user'), $form->GetValue('splithighdiscount')));
		} else {
			if($form->Validate()) {
				redirect(sprintf('Location: %s?action=report&start=%s&end=%s&user=%d&splithighdiscount=%s', $_SERVER['PHP_SELF'], sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('user'), $form->GetValue('splithighdiscount')));
			}
		}
	}

	$page = new Page('Sales Rep Overview Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Sales Reps.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
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

    echo $window->AddHeader('Select the sales rep for this report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('user'), $form->GetHTML('user'));
	echo $webForm->AddRow($form->GetLabel('splithighdiscount'), $form->GetHTML('splithighdiscount'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->CloseContent();

	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
    $form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'reprot', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start Date', 'hidden', '0000-00-00 00:00:00', 'anything', 1, 19);
	$form->AddField('end', 'End Date', 'hidden', '0000-00-00 00:00:00', 'anything', 1, 19);
	$form->AddField('user', 'Sales Rep', 'hidden', '0', 'numeric_unsigned', 1, 11);
    $form->AddField('splithighdiscount', 'Split High Discounts', 'hidden', 'N', 'boolean', 1, 1, false);

	$page = new Page('Sales Rep Overview Report', '');
	$page->Display('header');

	$ranges = array(0 => '&pound;0 - &pound;50', 50 => '&pound;50 - &pound;100', 100 => '&pound;100 - &pound;250', 250 => '&pound;250 - &pound;500', 500 => '&pound;500 - &pound;1000', 1000 => '&pound;1000+');
	$users = array();
	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User, DATE_FORMAT(o.Created_On, '%%Y-%%m') AS Created_Date, COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Price * ol.Quantity) AS SubTotal, SUM(ol.Cost * ol.Quantity) AS Cost, SUM(ol.Line_Discount) AS Discount, SUM(((ol.Price - ol.Cost) * ol.Quantity) - ol.Line_Discount) AS Profit FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN users AS u ON u.User_ID=o.Created_By INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID WHERE o.Created_By>0 AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Line_Status<>'Cancelled'%s%s%s GROUP BY o.Created_By, Created_Date ORDER BY User ASC, Created_Date ASC", ($form->GetValue('user') > 0) ? sprintf(' AND u.User_ID=%d', mysql_real_escape_string($form->GetValue('user'))) : '', ($form->GetValue('start') != '0000-00-00 00:00:00') ? sprintf(' AND o.Created_On>=\'%s\'', mysql_real_escape_string($form->GetValue('start'))) : '', ($form->GetValue('end') != '0000-00-00 00:00:00') ? sprintf(' AND o.Created_On<=\'%s\'', mysql_real_escape_string($form->GetValue('end'))) : ''));
	while($data->Row) {
		if(!isset($users[$data->Row['User_ID']])) {
			$users[$data->Row['User_ID']] = array('Name' => $data->Row['User'], 'Data' => array(), 'DataRange' => array(), 'DataRangeHighDiscount' => array());
		}

		$users[$data->Row['User_ID']]['Data'][] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

	$months = array();
    $data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User, DATE_FORMAT(o.Created_On, '%%Y-%%m') AS Created_Date, COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Price * ol.Quantity) AS SubTotal, SUM(ol.Cost * ol.Quantity) AS Cost, SUM(ol.Line_Discount) AS Discount, SUM(((ol.Price - ol.Cost) * ol.Quantity) - ol.Line_Discount) AS Profit FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN users AS u ON u.User_ID=o.Created_By INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID WHERE o.Created_By>0 AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Line_Status<>'Cancelled'%s%s%s%s GROUP BY o.Order_ID ORDER BY User ASC, Created_Date ASC", ($form->GetValue('user') > 0) ? sprintf(' AND u.User_ID=%d', mysql_real_escape_string($form->GetValue('user'))) : '', ($form->GetValue('start') != '0000-00-00 00:00:00') ? sprintf(' AND o.Created_On>=\'%s\'', mysql_real_escape_string($form->GetValue('start'))) : '', ($form->GetValue('end') != '0000-00-00 00:00:00') ? sprintf(' AND o.Created_On<=\'%s\'', mysql_real_escape_string($form->GetValue('end'))) : '', ($form->GetValue('splithighdiscount') == 'Y') ? ' AND c.Is_High_Discount=\'N\'' : ''));
	while($data->Row) {
		$orderRange = 0;

		foreach($ranges as $range=>$rangeText) {
			if($data->Row['SubTotal'] > $range) {
				$orderRange = $range;
			}
		}

        if(!isset($months[$data->Row['User_ID']])) {
			$months[$data->Row['User_ID']] = array();
		}

		if(!isset($months[$data->Row['User_ID']][$data->Row['Created_Date']])) {
			$months[$data->Row['User_ID']][$data->Row['Created_Date']] = array();
		}

        if(!isset($months[$data->Row['User_ID']][$data->Row['Created_Date']][$orderRange])) {
			$months[$data->Row['User_ID']][$data->Row['Created_Date']][$orderRange] = array();
		}

		$months[$data->Row['User_ID']][$data->Row['Created_Date']][$orderRange][] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

	foreach($months as $user=>$monthData) {
		foreach($monthData as $month=>$rangeData) {
			ksort($rangeData);

			foreach($rangeData as $range=>$items) {
				$item = array('Orders' => 0, 'SubTotal' => 0, 'Discount' => 0, 'Profit' => 0, 'Created_Date' => $month, 'Range' => $ranges[$range]);

				foreach($items as $data) {
					$item['Orders'] += $data['Orders'];
					$item['SubTotal'] += $data['SubTotal'];
					$item['Discount'] += $data['Discount'];
					$item['Profit'] += $data['Profit'];
				}

				$users[$user]['DataRange'][] = $item;
			}
		}
	}

	if($form->GetValue('splithighdiscount') == 'Y') {
		$months = array();

		$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User, DATE_FORMAT(o.Created_On, '%%Y-%%m') AS Created_Date, COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Price * ol.Quantity) AS SubTotal, SUM(ol.Cost * ol.Quantity) AS Cost, SUM(ol.Line_Discount) AS Discount, SUM(((ol.Price - ol.Cost) * ol.Quantity) - ol.Line_Discount) AS Profit FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN users AS u ON u.User_ID=o.Created_By INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID WHERE o.Created_By>0 AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Line_Status<>'Cancelled' AND c.Is_High_Discount='Y' %s%s%s GROUP BY o.Order_ID ORDER BY User ASC, Created_Date ASC", ($form->GetValue('user') > 0) ? sprintf(' AND u.User_ID=%d', $form->GetValue('user')) : '', ($form->GetValue('start') != '0000-00-00 00:00:00') ? sprintf(' AND o.Created_On>=\'%s\'', mysql_real_escape_string($form->GetValue('start'))) : '', ($form->GetValue('end') != '0000-00-00 00:00:00') ? sprintf(' AND o.Created_On<=\'%s\'', mysql_real_escape_string($form->GetValue('end'))) : ''));
		while($data->Row) {
			$orderRange = 0;

			foreach($ranges as $range=>$rangeText) {
				if($data->Row['SubTotal'] > $range) {
					$orderRange = $range;
				}
			}

		    if(!isset($months[$data->Row['User_ID']])) {
				$months[$data->Row['User_ID']] = array();
			}

			if(!isset($months[$data->Row['User_ID']][$data->Row['Created_Date']])) {
				$months[$data->Row['User_ID']][$data->Row['Created_Date']] = array();
			}

		    if(!isset($months[$data->Row['User_ID']][$data->Row['Created_Date']][$orderRange])) {
				$months[$data->Row['User_ID']][$data->Row['Created_Date']][$orderRange] = array();
			}

			$months[$data->Row['User_ID']][$data->Row['Created_Date']][$orderRange][] = $data->Row;

			$data->Next();
		}
		$data->Disconnect();

		foreach($months as $user=>$monthData) {
			foreach($monthData as $month=>$rangeData) {
				ksort($rangeData);

				foreach($rangeData as $range=>$items) {
					$item = array('Orders' => 0, 'SubTotal' => 0, 'Discount' => 0, 'Profit' => 0, 'Created_Date' => $month, 'Range' => $ranges[$range]);

					foreach($items as $data) {
						$item['Orders'] += $data['Orders'];
						$item['SubTotal'] += $data['SubTotal'];
						$item['Discount'] += $data['Discount'];
						$item['Profit'] += $data['Profit'];
					}

					$users[$user]['DataRangeHighDiscount'][] = $item;
				}
			}
		}
	}

	foreach($users as $user) {
		?>

		<br />
		<h3><?php echo $user['Name']; ?></h3>
		<p>Summary of sales statistics made by this sales rep.</p>

		<table width="100%" border="0">
			<tr>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Month</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Orders</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Sub Total</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Discount</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Discount %</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Profit</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Profit %</strong></td>
			</tr>

			<?php
			$totalOrders = 0;
			$totalSubTotal = 0;
			$totalDiscount = 0;
			$totalProfit = 0;

			foreach($user['Data'] as $month) {
				$totalOrders += $month['Orders'];
				$totalSubTotal += $month['SubTotal'];
	            $totalDiscount += $month['Discount'];
				$totalProfit += $month['Profit'];
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $month['Created_Date']; ?></td>
					<td><?php echo $month['Orders']; ?></td>
					<td align="right">&pound;<?php echo number_format($month['SubTotal'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($month['Discount'], 2, '.', ','); ?></td>
					<td align="right"><?php echo number_format(($month['Discount']/$month['SubTotal'])*100, 2, '.', ','); ?>%</td>
					<td align="right">&pound;<?php echo number_format($month['Profit'], 2, '.', ','); ?></td>
					<td align="right"><?php echo number_format(($month['Profit']/$month['SubTotal'])*100, 2, '.', ','); ?>%</td>
				</tr>

				<?php
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td><strong><?php echo $totalOrders; ?></strong></td>
				<td align="right"><strong>&pound;<?php echo number_format($totalSubTotal, 2, '.', ','); ?></strong></td>
				<td align="right"><strong>&pound;<?php echo number_format($totalDiscount, 2, '.', ','); ?></strong></td>
				<td align="right"><strong><?php echo number_format(($totalDiscount/$totalSubTotal)*100, 2, '.', ','); ?>%</strong></td>
				<td align="right"><strong>&pound;<?php echo number_format($totalProfit, 2, '.', ','); ?></strong></td>
				<td align="right"><strong><?php echo number_format(($totalProfit/$totalSubTotal)*100, 2, '.', ','); ?>%</strong></td>
			</tr>
		</table>
		<br />

        <table width="100%" border="0">
			<tr>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Month</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Range</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Orders</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Sub Total</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Discount</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Discount %</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Profit</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Profit %</strong></td>
			</tr>

			<?php
			$totalOrders = 0;
			$totalSubTotal = 0;
			$totalDiscount = 0;
			$totalProfit = 0;

			foreach($user['DataRange'] as $month) {
				$totalOrders += $month['Orders'];
				$totalSubTotal += $month['SubTotal'];
	            $totalDiscount += $month['Discount'];
				$totalProfit += $month['Profit'];
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $month['Created_Date']; ?></td>
					<td><?php echo $month['Range']; ?></td>
					<td><?php echo $month['Orders']; ?></td>
					<td align="right">&pound;<?php echo number_format($month['SubTotal'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($month['Discount'], 2, '.', ','); ?></td>
					<td align="right"><?php echo number_format(($month['Discount']/$month['SubTotal'])*100, 2, '.', ','); ?>%</td>
					<td align="right">&pound;<?php echo number_format($month['Profit'], 2, '.', ','); ?></td>
					<td align="right"><?php echo number_format(($month['Profit']/$month['SubTotal'])*100, 2, '.', ','); ?>%</td>
				</tr>

				<?php
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td><strong><?php echo $totalOrders; ?></strong></td>
				<td align="right"><strong>&pound;<?php echo number_format($totalSubTotal, 2, '.', ','); ?></strong></td>
				<td align="right"><strong>&pound;<?php echo number_format($totalDiscount, 2, '.', ','); ?></strong></td>
				<td align="right"><strong><?php echo number_format(($totalDiscount/$totalSubTotal)*100, 2, '.', ','); ?>%</strong></td>
				<td align="right"><strong>&pound;<?php echo number_format($totalProfit, 2, '.', ','); ?></strong></td>
				<td align="right"><strong><?php echo number_format(($totalProfit/$totalSubTotal)*100, 2, '.', ','); ?>%</strong></td>
			</tr>
		</table>
		<br />

		<?php
		if($form->GetValue('splithighdiscount') == 'Y') {
			?>

            <table width="100%" border="0">
				<tr>
					<td style="border-bottom:1px solid #aaaaaa;"><strong>Month</strong></td>
					<td style="border-bottom:1px solid #aaaaaa;"><strong>Range</strong></td>
					<td style="border-bottom:1px solid #aaaaaa;"><strong>Orders</strong></td>
					<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Sub Total</strong></td>
					<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Discount</strong></td>
					<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Discount %</strong></td>
					<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Profit</strong></td>
					<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Profit %</strong></td>
				</tr>

				<?php
				$totalOrders = 0;
				$totalSubTotal = 0;
				$totalDiscount = 0;
				$totalProfit = 0;

				foreach($user['DataRangeHighDiscount'] as $month) {
					$totalOrders += $month['Orders'];
					$totalSubTotal += $month['SubTotal'];
		            $totalDiscount += $month['Discount'];
					$totalProfit += $month['Profit'];
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td><?php echo $month['Created_Date']; ?></td>
						<td><?php echo $month['Range']; ?></td>
						<td><?php echo $month['Orders']; ?></td>
						<td align="right">&pound;<?php echo number_format($month['SubTotal'], 2, '.', ','); ?></td>
						<td align="right">&pound;<?php echo number_format($month['Discount'], 2, '.', ','); ?></td>
						<td align="right"><?php echo number_format(($month['Discount']/$month['SubTotal'])*100, 2, '.', ','); ?>%</td>
						<td align="right">&pound;<?php echo number_format($month['Profit'], 2, '.', ','); ?></td>
						<td align="right"><?php echo number_format(($month['Profit']/$month['SubTotal'])*100, 2, '.', ','); ?>%</td>
					</tr>

					<?php
				}
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td><strong><?php echo $totalOrders; ?></strong></td>
					<td align="right"><strong>&pound;<?php echo number_format($totalSubTotal, 2, '.', ','); ?></strong></td>
					<td align="right"><strong>&pound;<?php echo number_format($totalDiscount, 2, '.', ','); ?></strong></td>
					<td align="right"><strong><?php echo number_format(($totalDiscount/$totalSubTotal)*100, 2, '.', ','); ?>%</strong></td>
					<td align="right"><strong>&pound;<?php echo number_format($totalProfit, 2, '.', ','); ?></strong></td>
					<td align="right"><strong><?php echo number_format(($totalProfit/$totalSubTotal)*100, 2, '.', ','); ?>%</strong></td>
				</tr>
			</table>
			<br />

			<?php
		}
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}