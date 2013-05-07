<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('filter', 'Filter orders', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('filter', 'N', '-- All --');
	$form->AddOption('filter', 'W', 'Web Orders');
	$form->AddOption('filter', 'T', 'Telephone Orders');
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'Report End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', NULL, NULL, false);
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
	$form->AddField('value', 'Order Value', 'select', 0, 'alpha_numeric', 0, 32);
	$form->AddOption('value', 0, '-- All --');
	$form->AddOption('value', 10, '&pound;0 - 10');
	$form->AddOption('value', 20, '&pound;10 - 20');
	$form->AddOption('value', 40, '&pound;20 - 40');
	$form->AddOption('value', 60, '&pound;40 - 60');
	$form->AddOption('value', 80, '&pound;60 - 80');
	$form->AddOption('value', 100, '&pound;80 - 100');
	$form->AddOption('value', 250, '&pound;100 - 250');
	$form->AddOption('value', 500, '&pound;250 - 500');
	$form->AddOption('value', 750, '&pound;500 - 750');
	$form->AddOption('value', 1000, '&pound;750 - 1000');
	$form->AddOption('value', 2000, '&pound;1000 - 2000');
	$form->AddOption('value', 3000, '&pound;2000 - 3000');
	$form->AddOption('value', 5000, '&pound;3000 - 5000');
	$form->AddOption('value', 10000, '&pound;5000 - 10000');
	$form->AddOption('value', 1000000, '&pound;10000 - 1000000');

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

				case 'lastmonth': 	$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-1, 1, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1, date("Y")));
				break;
				case 'last3months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-3, 1, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1, date("Y")));
				break;
				case 'last6months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-6, 1, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1, date("Y")));
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

			report($start, $end, $form->GetValue('parent'), ($form->GetValue('subfolders') =='Y') ? true : false, $form->GetValue('filter'), $form->GetValue('value'));
			exit;
		} else {

			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))),$form->GetValue('parent'),($form->GetValue('subfolders') =='Y')?true:false, $form->GetValue('filter'), $form->GetValue('value'));
				exit;
			}
		}
	}

	$page = new Page('Product Category Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Products from a Category.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Filter out products sold for particular orders.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('filter'), $form->GetHTML('filter'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Report on orders between the given values only.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('value'), $form->GetHTML('value'));
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

	echo $window->AddHeader('Click on a the search icon to find a category to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->AddRow('&nbsp','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($start, $end, $cat, $sub, $filter = 'N', $orderValue){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Region.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$script = sprintf('<script language="javascript" type="text/javascript">
		var toggleOrders = function(obj, productId) {
			var element = document.getElementById(\'product_orders_\' + productId);

			if(element) {
				element.style.display = (obj.checked) ? \'\' : \'none\';
			}
		}
		</script>');

	$page = new Page('Product Category Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead($script);
	$page->Display('header');

	$sqlSelect = sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Orders, ol.Product_ID, ol.Product_Title ");
	$sqlFrom = sprintf("FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID ");
	$sqlWhere = sprintf("WHERE o.Created_On>'%s' AND o.Created_On<='%s' AND ol.Line_Status<>'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", mysql_real_escape_string($start), mysql_real_escape_string($end));

	$sqlMisc = sprintf("GROUP BY ol.Product_ID ORDER BY Orders DESC");

	if($cat != 0) {
		$sqlFrom .= sprintf("INNER JOIN product_in_categories AS c ON c.Product_ID=ol.Product_ID ");

		if($sub) {
			$sqlWhere .= sprintf("AND (c.Category_ID=%d %s) ", mysql_real_escape_string($cat), mysql_real_escape_string(getCategories($cat)));
		} else {
			$sqlWhere .= sprintf("AND c.Category_ID=%d ", mysql_real_escape_string($cat));
		}
	}

	if($filter != 'N') {
		$sqlWhere .= sprintf("AND o.Order_Prefix='%s' ", mysql_real_escape_string($filter));
	}

	if($orderValue > 0) {
		switch($orderValue) {
			case 10: $low = 0; break;
			case 20: $low = 10; break;
			case 40: $low = 20; break;
			case 60: $low = 40; break;
			case 80: $low = 60; break;
			case 100: $low = 80; break;
			case 250: $low = 100; break;
			case 500: $low = 250; break;
			case 750: $low = 500; break;
			case 1000: $low = 750; break;
			case 2000: $low = 1000; break;
			case 3000: $low = 2000; break;
			case 5000: $low = 3000; break;
			case 10000: $low = 5000; break;
			case 1000000: $low = 10000; break;
		}

		$sqlWhere .= sprintf("AND o.SubTotal>%f AND o.SubTotal<=%f ", mysql_real_escape_string($low), mysql_real_escape_string($orderValue));
	}
	?>

	<br />
	<h3>Products Sold</h3>
	<p>Listing products sold matching the criteria specified</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa" width="1%"><strong>&nbsp;</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Product Name</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quickfind</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quantity Sold</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Order Count</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Price</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Profit</strong></td>
		</tr>

		<?php
		$totalQuantity = 0;
		$totalOrders = 0;
		$totalPrice = 0;
		$totalCost = 0;
		$totalProfit = 0;

		$data = new DataQuery(sprintf("%s%s%s%s", $sqlSelect, $sqlFrom, $sqlWhere, $sqlMisc));
		while($data->Row) {
			$data2 = new DataQuery(sprintf("SELECT SUM(ol.Quantity) AS Quantity_Sold, SUM(ol.Line_Total + ol.Line_Tax - ol.Line_Discount) AS Total_Price, SUM(ol.Cost * ol.Quantity) AS Total_Cost FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE o.Created_On>'%s' AND o.Created_On<='%s' AND ol.Line_Status<>'Cancelled' AND o.Status<>'Unauthenticated' AND o.Status<>'Cancelled' AND ol.Product_ID=%d %s %s", mysql_real_escape_string($start), mysql_real_escape_string($end), $data->Row['Product_ID'], ($filter != 'N') ? sprintf("AND o.Order_Prefix='%s' ", $filter) : '', ($orderValue > 0) ? sprintf("AND o.SubTotal>%f AND o.SubTotal<=%f ", mysql_real_escape_string($low), mysql_real_escape_string($orderValue)) : ''));

			$totalQuantity += $data2->Row['Quantity_Sold'];
			$totalOrders += $data->Row['Orders'];
			$totalPrice += $data2->Row['Total_Price'];
			$totalCost += $data2->Row['Total_Cost'];
			$totalProfit += $data2->Row['Total_Price'] - $data2->Row['Total_Cost'];
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td align="center"><input type="checkbox" onclick="toggleOrders(this, <?php echo $data->Row['Product_ID']; ?>);" /></td>
				<td><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>"><?php echo strip_tags($data->Row['Product_Title']); ?></a></td>
				<td align="right"><?php echo $data->Row['Product_ID']; ?></td>
				<td align="right"><?php echo $data2->Row['Quantity_Sold']; ?></td>
				<td align="right"><?php echo $data->Row['Orders']; ?></td>
				<td align="right">&pound;<?php echo number_format($data2->Row['Total_Price'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($data2->Row['Total_Cost'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($data2->Row['Total_Price'] - $data2->Row['Total_Cost'], 2, '.', ','); ?></td>
			</tr>
			<?php
			$data2->Disconnect();
			?>
			<tr style="display: none;" id="product_orders_<?php echo $data->Row['Product_ID']; ?>">
				<td colspan="7">

				<?php
				$orders = array();
				$cols = 8;
				$col = 0;
				$count = 0;
				$columns = array();

				$data2 = new DataQuery(sprintf("SELECT o.Order_ID %s %s AND ol.Product_ID=%d GROUP BY o.Order_ID ORDER BY o.Order_ID ASC", $sqlFrom, $sqlWhere, $data->Row['Product_ID']));
				while($data2->Row) {
					$orders[] = $data2->Row['Order_ID'];

					$data2->Next();
				}
				$data2->Disconnect();

				for($i=0;$i < count($orders); $i++) {
					if($count >= (count($orders) / $cols)) {
						$col++;
						$count = 0;
					}

					$columns[$col][] = $orders[$i];
					$count++;
				}
				?>

				<table width="100%" border="0">

					<?php
					for($i=0;$i < count($columns[0]); $i++) {
						echo '<tr>';

						for($j=0;$j < $cols; $j++) {
							if(isset($columns[$j][$i])) {
								$link = sprintf('<a href="order_details.php?orderid=%d" target="_blank">%d</a>', $columns[$j][$i], $columns[$j][$i]);
							} else {
								$link = '&nbsp;';
							}

							echo sprintf('<td width="%s%%" style="text-align: right;">%s</td>', (100 / $cols), $link);
						}

						echo '</tr>';
					}
					?>
				</table>

				</td>
			</tr>

			<?php
			$data->Next();
		}
		$data->Disconnect();
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td align="right"><strong><?php echo $totalQuantity; ?></strong></td>
			<td align="right"><strong><?php echo $totalOrders; ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalPrice, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalProfit, 2, '.', ','); ?></strong></td>
		</tr>
	</table>

	<?php
	$page->Display('footer');
}

function getCategories($categoryId) {
	$string = '';

	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row){
		$string .= sprintf("OR c.Category_ID=%d %s ", $data->Row['Category_ID'], getCategories($data->Row['Category_ID']));

		$data->Next();
	}
	$data->Disconnect();

	return $string;
}
?>