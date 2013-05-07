<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$offset = (date('d') < 23) ? 1 : 0;

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('month', 'Month', 'select', date('m', mktime(0, 0, 0, date('m') - $offset, date('d'), date('Y'))), 'anything', 1, 11);
$form->AddOption('month', '', '');

for($i=1; $i<=12; $i++) {
	$form->AddOption('month', date('m', mktime(0, 0, 0, $i, date('d'), date('Y'))), date('F', mktime(0, 0, 0, $i, date('d'), date('Y'))));
}

$form->AddField('year', 'Year', 'select', date('Y', mktime(0, 0, 0, date('m') - $offset, date('d'), date('Y'))), 'anything', 1, 11);
$form->AddOption('year', '', '');

for($i=date('Y')-5; $i<=date('Y'); $i++) {
	$form->AddOption('year', $i, $i);
}

$start = date('Y-m-d H:i:s', mktime(0, 0, 0, $form->GetValue('month'), 23, $form->GetValue('year')));
$end = date('Y-m-d H:i:s', mktime(0, 0, 0, $form->GetValue('month') + 1, 23, $form->GetValue('year')));

$page = new Page('Sales Rep Month Report', '');
$page->Display('header');

$ranges = array(0 => '&pound;0 - &pound;50', 50 => '&pound;50 - &pound;100', 100 => '&pound;100 - &pound;250', 250 => '&pound;250 - &pound;500', 500 => '&pound;500 - &pound;1000', 1000 => '&pound;1000+');
$users = array();
$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User, DATE_FORMAT(o.Created_On, '%%Y-%%m') AS Created_Date, COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Price * ol.Quantity) AS SubTotal, SUM(ol.Cost * ol.Quantity) AS Cost, SUM(ol.Line_Discount) AS Discount, SUM(((ol.Price - ol.Cost) * ol.Quantity) - ol.Line_Discount) AS Profit FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN users AS u ON u.User_ID=o.Created_By INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID WHERE o.Created_By>0 AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Line_Status<>'Cancelled' AND u.User_ID=%d AND o.Created_On>='%s' AND o.Created_On<='%s' GROUP BY o.Created_By, Created_Date ORDER BY User ASC, Created_Date ASC", $GLOBALS['SESSION_USER_ID'], mysql_real_escape_string($start), mysql_real_escape_string($end)));
while($data->Row) {
	if(!isset($users[$data->Row['User_ID']])) {
		$users[$data->Row['User_ID']] = array('Name' => $data->Row['User'], 'Data' => array(), 'DataRange' => array(), 'DataRangeHighDiscount' => array());
	}

	$users[$data->Row['User_ID']]['Data'][] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$months = array();

$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User, DATE_FORMAT(o.Created_On, '%%Y-%%m') AS Created_Date, COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Price * ol.Quantity) AS SubTotal, SUM(ol.Cost * ol.Quantity) AS Cost, SUM(ol.Line_Discount) AS Discount, SUM(((ol.Price - ol.Cost) * ol.Quantity) - ol.Line_Discount) AS Profit FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN users AS u ON u.User_ID=o.Created_By INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID WHERE o.Created_By>0 AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Line_Status<>'Cancelled' AND u.User_ID=%d AND o.Created_On>='%s' AND o.Created_On<='%s' GROUP BY o.Order_ID ORDER BY User ASC, Created_Date ASC", $GLOBALS['SESSION_USER_ID'], mysql_real_escape_string($start), mysql_real_escape_string($end)));
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

$months = array();
$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User, DATE_FORMAT(o.Created_On, '%%Y-%%m') AS Created_Date, COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Price * ol.Quantity) AS SubTotal, SUM(ol.Cost * ol.Quantity) AS Cost, SUM(ol.Line_Discount) AS Discount, SUM(((ol.Price - ol.Cost) * ol.Quantity) - ol.Line_Discount) AS Profit FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN users AS u ON u.User_ID=o.Created_By INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID WHERE o.Created_By>0 AND o.Order_Prefix<>'R' AND o.Order_Prefix<>'B' AND o.Order_Prefix<>'N' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND ol.Line_Status<>'Cancelled' AND c.Is_High_Discount='Y' AND u.User_ID=%d AND o.Created_On>='%s' AND o.Created_On<='%s' GROUP BY o.Order_ID ORDER BY User ASC, Created_Date ASC", $GLOBALS['SESSION_USER_ID'], mysql_real_escape_string($start), mysql_real_escape_string($end)));
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

$window = new StandardWindow('Select Period');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');
echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow('Period', $form->GetHTML('month') . $form->GetHTML('year') . $form->GetIcon('year'));
echo $webForm->AddRow('', sprintf('<input type="submit" name="submit" value="submit" tabindex="%s" class="btn" />', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo '<br />';

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
				<td align="right"><?php
					if($totalSubTotal == 0){
						echo "0"; 
					}else{
						echo number_format(($totalDiscount/$totalSubTotal)*100, 2, '.', ',');} ?>%</strong></td>
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
		</tr>
	</table>
	<br />

	<?php
}

$page->Display('footer');
require_once('lib/common/app_footer.php');