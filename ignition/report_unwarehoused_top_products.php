<?php
ini_set('max_execution_time', '120');

require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Unwarehoused Top Products Report', 'Please choose a start and end date for your report');
	$year = cDatetime(getDatetime(), 'y');
	$form = new Form($_SERVER['PHP_SELF'],'GET');
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
	$form->AddField('warehouse', 'Products not stocked by', 'select', 0, 'numeric_unsigned', 1, 11);
	$form->AddOption('warehouse', 0, '-- All --');

	$data = new DataQuery(sprintf("SELECT Warehouse_ID, Warehouse_Name FROM warehouse ORDER BY Warehouse_Name ASC"));
	while($data->Row) {
		$form->AddOption('warehouse', $data->Row['Warehouse_ID'], $data->Row['Warehouse_Name']);
		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('products', 'Number of products', 'select', 200, 'numeric_unsigned', 1, 11);

	for($i = 100; $i <= 1000; $i += 100) {
		$form->AddOption('products', $i, $i);
	}

	$form->AddField('parent', 'Category', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', NULL, NULL, false);

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

			report($start, $end, $form->GetValue('warehouse'), $form->GetValue('products'), $form->GetValue('parent'), ($form->GetValue('subfolders') == 'Y') ? true : false);
			exit;
		} else {
			
			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))),$form->GetValue('warehouse'), $form->GetValue('products'), $form->GetValue('parent'), ($form->GetValue('subfolders') == 'Y') ? true : false);
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
	$window = new StandardWindow("Report on Unwarehoused Top products.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

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
	echo $window->CloseContent();

	echo $window->AddHeader('Select a warehouse to report on and number of products to display.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('warehouse'), $form->GetHTML('warehouse'));
	echo $webForm->AddRow($form->GetLabel('products'), $form->GetHTML('products'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Click on a the search icon to find a category to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('parent') . $temp_1, '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
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

function report($start, $end, $warehouse = 0, $productCount = 200, $cat = 0, $sub = 'N'){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Region.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$clientString = "";

	if($cat != 0) {
		if($sub) {
			$clientString = sprintf("AND (cat.Category_ID=%d %s) ", $cat, GetChildIDS($cat));
		} else {
			$clientString = sprintf("AND cat.Category_ID=%d ", $cat);
		}
	} else {
		if(!$sub) {
			$clientString = sprintf("AND (cat.Category_ID IS NULL OR cat.Category_ID=%d) ", $cat);
		}
	}

	$page = new Page('Unwarehoused Top Products Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
	?>

	<h3><br />
	Top Notch Unwarehoused Products
	<?php
	if($warehouse > 0) {
		$data = new DataQuery(sprintf("SELECT Warehouse_Name FROM warehouse WHERE Warehouse_ID=%d", mysql_real_escape_string($warehouse)));

		echo ' for '.$data->Row['Warehouse_Name'];

		$data->Disconnect();
	}

	if($cat > 0) {
		$data = new DataQuery(sprintf("SELECT Category_Title FROM product_categories WHERE Category_ID=%d", mysql_real_escape_string($cat)));

		echo ' in '.$data->Row['Category_Title'];

		if($sub) {
			echo ' (including sub categories)';
		}

		$data->Disconnect();
	}
	?>
	</h3>
	<p>Top <?php print $productCount; ?> Products Sold</p>
	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Rank</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Product Name </strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quickfind</strong></td>
		<?php
		if($warehouse > 0) {
			?>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Supplier </strong></td>
			<?php
		}
		?>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quantity Ordered</strong> </td>
		<?php
		if($warehouse > 0) {
			?>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Cost Price</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Cost</strong></td>
			<?php
		}
		?>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Sell Price</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Sold</strong></td>
	  </tr>
	  <?php
	  	$products = array();

	  	if($warehouse > 0) {
	  		$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_warehoused_products
	  										SELECT p.Product_ID
	  										FROM product AS p
	  										LEFT JOIN product_in_categories AS cat ON p.Product_ID=cat.Product_ID
	  										INNER JOIN warehouse_stock AS ws ON p.Product_ID=ws.Product_ID
	  										INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID
	  										WHERE w.Warehouse_ID=%d
	  										GROUP BY p.Product_ID", mysql_real_escape_string($warehouse)));
			$data->Disconnect();

			$sql = sprintf("select sum(ol.Quantity) as OrderCount, ol.Product_ID, ol.Product_Title
	  										FROM order_line AS ol
	  										INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID
	  										INNER JOIN product AS p ON p.Product_ID=ol.Product_ID
	  										LEFT JOIN product_in_categories AS cat ON p.Product_ID=cat.Product_ID
	  										LEFT JOIN temp_warehoused_products s ON p.Product_ID = s.Product_ID
	  										where s.Product_ID IS NULL
	  										AND o.Created_On between '%s' and '%s'
	  										and ol.Line_Status != 'Cancelled'
	  										AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')
	  										%sgroup by ol.Product_ID
	  										order by OrderCount DESC
	  										limit %d", mysql_real_escape_string($start), mysql_real_escape_string($end), mysql_real_escape_string($clientString), mysql_real_escape_string($productCount));

	  		$data = new DataQuery($sql);
	  	} else {
	  		$sql = sprintf("select sum(ol.Quantity) as OrderCount, ol.Product_ID, ol.Product_Title
	  										FROM order_line AS ol
	  										INNER JOIN orders AS o ON ol.Order_ID=o.Order_ID
	  										INNER JOIN product AS p ON p.Product_ID=ol.Product_ID
	  										LEFT JOIN product_in_categories AS cat ON p.Product_ID=cat.Product_ID
	  										LEFT JOIN warehouse_stock s ON p.Product_ID = s.Product_ID
	  										where s.Stock_ID is NULL
	  										AND o.Created_On between '%s' and '%s'
	  										and ol.Line_Status != 'Cancelled'
	  										AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')
	  										%sgroup by ol.Product_ID
	  										order by OrderCount DESC
	  										limit %d", mysql_real_escape_string($start), mysql_real_escape_string($end), mysql_real_escape_string($clientString), mysql_real_escape_string($productCount));

	  		$data = new DataQuery($sql);
	  	}

	  	while($data->Row){
	  		if(!isset($products[$data->Row['Product_ID']])) {
	  			$data2 = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID=%d AND Price_Starts_On<=Now() ORDER BY Price_Starts_On DESC LIMIT 1", $data->Row['Product_ID']));

	  			$item = array();
	  			$item['Product_ID'] = $data->Row['Product_ID'];
	  			$item['Product_Title'] = strip_tags($data->Row['Product_Title']);
	  			$item['OrderCount'] = $data->Row['OrderCount'];
	  			$item['Price_Base_Our'] = $data2->Row['Price_Base_Our'];

	  			$data2->Disconnect();

	  			$products[$data->Row['Product_ID']] = $item;
	  		}

	  		$data->Next();
	  	}

	  	$data->Disconnect();

	  	if($warehouse > 0) {
			$data = new DataQuery("DROP TABLE temp_warehoused_products");
			$data->Disconnect();
		}

		$rank = 1;
		$totalCost = 0;
		$totalSold = 0;
		foreach($products as $key => $product) {
			if($warehouse > 0) {
				$cost = '-';
				$warehouseName = '-';
				$total = '-';

				$data2 = new DataQuery(sprintf("SELECT sp.Cost, w.Warehouse_Name FROM warehouse AS w INNER JOIN supplier AS s ON s.Supplier_ID=w.Type_Reference_ID INNER JOIN supplier_product AS sp ON sp.Supplier_ID=s.Supplier_ID WHERE w.Warehouse_ID<>%d AND w.Type='S' AND sp.Product_ID=%d AND sp.Preferred_Supplier='Y'", mysql_real_escape_string($warehouse), mysql_real_escape_string($product['Product_ID'])));
				if($data2->TotalRows > 0) {
					$cost = '&pound;'.number_format($data2->Row['Cost'], 2, '.', ',');
					$warehouseName = $data2->Row['Warehouse_Name'];
					if($data2->Row['Cost'] > 0) {
						$total = '&pound;'.number_format($data2->Row['Cost']*$product['OrderCount'], 2, '.', ',');+
						$totalCost += $data2->Row['Cost']*$product['OrderCount'];
					}
				}
				$data2->Disconnect();
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>#<?php print $rank; ?></td>
				<td><a href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><?php echo $product['Product_Title']; ?></a></td>
				<td align="right"><?php echo $product['Product_ID']; ?></td>
				<?php
				if($warehouse > 0) {
					?>
					<td><?php echo $warehouseName; ?></td>
					<?php
				}
				?>
				<td align="right"><?php echo $product['OrderCount']; ?></td>
				<?php
				if($warehouse > 0) {
					?>
					<td align="right"><?php echo $cost; ?></td>
					<td align="right"><?php echo $total; ?></td>
					<?php
				}
				?>
				<td align="right">&pound;<?php echo number_format($product['Price_Base_Our'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($product['Price_Base_Our']*$product['OrderCount'], 2, '.', ','); ?></td>
			</tr>
			<?php

			$totalSold += $product['Price_Base_Our']*$product['OrderCount'];
			$rank++;
		}
		?>

		</table>

		<br />

		<table width="100%" border="0">
			<?php
			if($warehouse > 0) {
				?>
				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><strong>Total Cost</strong></td>
					<td align="right"><strong>&pound;<?php print number_format($totalCost, 2, '.', ','); ?></strong></td>
				</tr>
				<?php
			}
			?>
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><strong>Total Sold</strong></td>
				<td align="right"><strong>&pound;<?php print number_format($totalSold, 2, '.', ','); ?></strong></td>
			</tr>
		</table>

		<?php
	  $page->Display('footer');
}

function GetChildIDS($cat) {
	$string = "";
	$children = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($cat)));
	while($children->Row) {
		$string .= "OR cat.Category_ID=".$children->Row['Category_ID']." ";
		$string .= GetChildIDS($children->Row['Category_ID']);
		$children->Next();
	}
	$children->Disconnect();
	return $string;
}
?>