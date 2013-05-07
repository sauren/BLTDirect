<?php
// Required Indicies:
//
// supplier_product.Product_ID
// product_in_categories.Product_ID

require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Discount Schema Back Test Report', 'Please choose a start and end date for your report');
	$year = cDatetime(getDatetime(), 'y');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', NULL, NULL, false);
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'Report End Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('schema', 'Discount Schema', 'select', '', 'anything', 0, 128);
	$form->AddOption('schema', '', '-- Select Schema --');
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
	$form->AddField('percent', 'Percentage Sales', 'select', 0, 'numeric_unsigned', 0, 11);
	$form->AddOption('percent', 0, '-- Select Percentage --');

	for($i=5;$i<=100;$i=$i+5) {
		$form->AddOption('percent', $i, $i.'%');
	}

	$schemas = array();

	$getSchema = new DataQuery("SELECT * FROM discount_schema");
	while($getSchema->Row){
		if(stristr($getSchema->Row['Discount_Ref'], 'DIS-BRO')) {
			$schemas['Bronze'] = 'DIS-BRO';
		} elseif(stristr($getSchema->Row['Discount_Ref'], 'DIS-SIL')) {
			$schemas['Silver'] = 'DIS-SIL';
		} elseif(stristr($getSchema->Row['Discount_Ref'], 'DIS-GOL')) {
			$schemas['Gold'] = 'DIS-GOL';
		}

		$getSchema->Next();
	}
	$getSchema->Disconnect();

	foreach($schemas as $key => $value) {
		$form->AddOption('schema', $value, $key);
	}

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if(strlen($form->GetValue('schema')) == 0) {
			$form->AddError('Please select a schema to report on.', 'schema');
		}

		if($form->GetValue('percent') == 0) {
			$form->AddError('Please select a percentage to report on.', 'percent');
		}

		if($form->Validate()){
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

				report($form->GetValue('schema'), $form->GetValue('parent'), ($form->GetValue('subfolders') =='Y') ? true : false, $start, $end, $form->GetValue('percent'));
				exit;
			} else {

				if($form->Validate()){
					report($form->GetValue('schema'), $form->GetValue('parent'), ($form->GetValue('subfolders') =='Y') ? true : false, $form->GetValue('start'), $form->GetValue('end'), $form->GetValue('percent'));
					exit;
				}
			}
		}
	}

	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');
	
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Discount Schema Markup.");
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

	echo $window->AddHeader('Select one of the discount schemas to report on and a percentage sales.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('schema'), $form->GetHTML('schema'));
	echo $webForm->AddRow($form->GetLabel('percent'), $form->GetHTML('percent'));
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

function report($schema, $cat, $sub, $start, $end, $percent){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductBand.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountSchema.php');

	$totalCost = 0;
	$totalTurnover = 0;
	$totalProfit = 0;

	$useAllProductsInitialised = false;
	$reportedAnything = false;
	$schemas = array();

	$data = new DataQuery(sprintf("SELECT Discount_Schema_ID FROM discount_schema WHERE Discount_Ref LIKE '%%%s%%' ORDER BY Discount_Title", mysql_real_escape_string($schema)));
	while($data->Row) {
		$schemas[] = new DiscountSchema($data->Row['Discount_Schema_ID']);
		$data->Next();
	}
	$data->Disconnect();

	$page = new Page('Discount Schema Back Test Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');

	$sqlCategories = '';

	if($cat != 0) {
		if($sub) {
			$sqlCategories = sprintf("WHERE (pc.Category_ID=%d %s) ", mysql_real_escape_string($cat), mysql_real_escape_string(GetChildIDS($cat)));
		} else {
			$sqlCategories = sprintf("WHERE pc.Category_ID=%d ", mysql_real_escape_string($cat));
		}
	} else {
		if(!$sub) {
			$sqlCategories = sprintf("WHERE (pc.Category_ID IS NULL OR pc.Category_ID=%d) ", mysql_real_escape_string($cat));
		}
	}

	$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_orders
									SELECT ol.Product_ID, SUM(ol.Quantity) AS Quantity
									FROM orders AS o
									INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID
									WHERE ol.Line_Status NOT LIKE 'Cancelled'
									AND o.Created_On BETWEEN '%s' AND '%s'
									AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')
									GROUP BY ol.Product_ID", mysql_real_escape_string($start), mysql_real_escape_string($end)));
	$data->Disconnect();

	$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_products
									SELECT o.Product_ID, o.Quantity, sp.Cost
									FROM temp_orders AS o
									INNER JOIN supplier_product AS sp ON sp.Product_ID=o.Product_ID
									WHERE sp.Preferred_Supplier='Y'"));
	$data->Disconnect();

	foreach($schemas as $schema) {
		$sqlProducts = '';
		$used = array();
		$productsArr = array();
		$products = array();
		$useAllProducts = true;

		if($schema->IsAllProducts == 'N') {
			$productsArr = array();

			$data = new DataQuery(sprintf("SELECT Product_ID FROM discount_product WHERE Discount_Schema_ID=%d", mysql_real_escape_string($schema->ID)));
			while($data->Row) {
				$productsArr[] = $data->Row['Product_ID'];
				$data->Next();
			}
			$data->Disconnect();

			if(count($productsArr) > 0) {
				$useAllProducts = false;

				$glue = 'p.Product_ID=';
				$sqlProducts .= $glue.implode(' OR '.mysql_real_escape_string($glue), mysql_real_escape_string($productsArr));
				$sqlProducts = " AND (".$sqlProducts.")";
			}
		} elseif($schema->UseBand > 0) {
			$useAllProducts = false;

			$sqlProducts .= sprintf(" AND p.Product_Band_ID=%d", mysql_real_escape_string($schema->UseBand));
		}

		if($useAllProducts) {
			if(!$useAllProductsInitialised) {
				$useAllProductsInitialised = true;

				$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_prices
											SELECT p.Product_ID, p.Product_Title, pp.Price_Base_Our, pp.Price_Starts_On
											FROM product AS p
											INNER JOIN product_prices AS pp ON pp.Product_ID=p.Product_ID
											WHERE pp.Price_Starts_On<=Now()
											ORDER BY pp.Price_Starts_On DESC"));
				$data->Disconnect();

				$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_data
										SELECT tp.Product_ID, p.Product_Title, p.Price_Base_Our, tp.Cost, tp.Quantity
										FROM temp_products AS tp
										INNER JOIN temp_prices AS p ON p.Product_ID=tp.Product_ID
										INNER JOIN product_in_categories AS pc ON pc.Product_ID=tp.product_ID
										%s
										ORDER BY p.Price_Starts_On DESC", $sqlCategories));
			}

			$data = new DataQuery(sprintf("SELECT * FROM temp_data"));

		} else {
			$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_prices_for_schema
											SELECT p.Product_ID, p.Product_Title, pp.Price_Base_Our, pp.Price_Starts_On
											FROM product AS p
											INNER JOIN product_prices AS pp ON pp.Product_ID=p.Product_ID
											WHERE pp.Price_Starts_On<=Now() %s
											ORDER BY pp.Price_Starts_On DESC", $sqlProducts));
			$data->Disconnect();

			$data = new DataQuery(sprintf("SELECT tp.Product_ID, p.Product_Title, p.Price_Base_Our, tp.Cost, tp.Quantity
										FROM temp_products AS tp
										INNER JOIN temp_prices_for_schema AS p ON p.Product_ID=tp.Product_ID
										INNER JOIN product_in_categories AS pc ON pc.Product_ID=tp.product_ID
										%s
										ORDER BY p.Price_Starts_On DESC", $sqlCategories));
		}

		while($data->Row) {
			if(!isset($used[$data->Row['Product_ID']])) {
				$used[$data->Row['Product_ID']] = true;

				$item = array();

				$item['Quantity'] = $data->Row['Quantity'];
				$item['Cost'] = $data->Row['Cost'];
				$item['Product_ID'] = $data->Row['Product_ID'];
				$item['Product_Title'] = strip_tags($data->Row['Product_Title']);
				$item['Price_Base_Our'] = $data->Row['Price_Base_Our'];
				$item['Discounted_Price'] = $data->Row['Price_Base_Our'] * ((100 - $schema->Discount) / 100);
				$item['Total_Cost'] = ($item['Cost'] * $item['Quantity']) * ($percent / 100);
				$item['Turnover'] = ($item['Discounted_Price'] * $item['Quantity']) * ($percent / 100);
				$item['Profit'] = $item['Turnover'] - $item['Total_Cost'];

				$item['SortBy'] = $item['Profit'];

				$totalCost += $item['Total_Cost'];
				$totalTurnover += $item['Turnover'];
				$totalProfit += $item['Profit'];

				$products[$data->Row['Product_ID']] = $item;
			}

			$data->Next();
		}
		$data->Disconnect();

		if(count($products) > 0) {
			$reportedAnything = true;
			$sortedProducts = array();

			foreach($products as $key => $product) {
				$sortedProducts[$product['SortBy']][] = $product;
			}

			krsort($sortedProducts);

			$band = new ProductBand($schema->UseBand);
			?>

			<h3><br />Discount Schema Back Test for <?php print $schema->Name; ?> - <?php print $band->Name; ?> (<?php print $schema->Discount; ?>% off)</h3>
			<p>The cost and price details of products once this discount schema has been applied based on a sales percentage of the given period.</p>

			<table width="100%" border="0">
			  <tr>
				<td nowrap="nowrap" valign="top" style="border-bottom:1px solid #aaaaaa; white-space: no-wrap;"><strong>Product Name</strong></td>
				<td valign="top" style="border-bottom:1px solid #aaaaaa"><strong>Quantity Sold</strong><br />Total quantities sold within this period.</td>
				<td valign="top" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Cost</strong><br />Total cost of quantities based on current cost price.</td>
				<td valign="top" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Turnover</strong><br />Based on <?php print $percent; ?>% sales with this schema during this period.</td>
				<td valign="top" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Profit</strong><br />Based on <?php print $percent; ?>% sales with this schema during this period.</td>
			  </tr>

			  <?php
			  foreach($sortedProducts as $key => $products) {
			  	foreach($products as $product) {
			  	?>

			  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				  	<td><a target="_blank" href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><?php echo $product['Product_Title']; ?></a></td>
				  	<td><?php echo $product['Quantity']; ?></td>
				  	<td align="right">&pound;<?php echo number_format($product['Total_Cost'], 2, '.', ','); ?></td>
				  	<td align="right">&pound;<?php echo number_format($product['Turnover'], 2, '.', ','); ?></td>
				  	<td align="right">&pound;<?php echo number_format($product['Profit'], 2, '.', ','); ?></td>
				 </tr>

			  	<?php
			  	}
			  }
			?>

			 </table>

		  <?php
		}

		if(!$useAllProducts) {
			$data = new DataQuery(sprintf("DROP TABLE temp_prices_for_schema"));
			$data->Disconnect();
		}
	}

	if($useAllProductsInitialised) {
		$data = new DataQuery(sprintf("DROP TABLE temp_prices"));
		$data->Disconnect();

		$data = new DataQuery(sprintf("DROP TABLE temp_data"));
		$data->Disconnect();
	}

	$data = new DataQuery(sprintf("DROP TABLE temp_orders"));
	$data->Disconnect();

	$data = new DataQuery(sprintf("DROP TABLE temp_products"));
	$data->Disconnect();

	if(!$reportedAnything) {
		echo '<p>There is no data to report on for the given criteria.</p>';
	} else {
		?>

		<h3><br />Total Values</h3>
		<p>Based on achieving <?php print $percent; ?>% of sales for the given period.</p>

		<table width="100%" border="0">
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>Sales Percentage:</td>
				<td align="right"><?php print $percent; ?>%</td>
			</tr>
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>Total Cost:</td>
				<td align="right">&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></td>
			</tr>
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>Total Turnover:</td>
				<td align="right">&pound;<?php echo number_format($totalTurnover, 2, '.', ','); ?></td>
			</tr>
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>Total Profit:</td>
				<td align="right">&pound;<?php echo number_format($totalProfit, 2, '.', ','); ?></td>
			</tr>
		</table>

		<?php
	}

	$page->Display('footer');
}

function GetChildIDS($cat) {
	$string = "";

	$children = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($cat)));
	while($children->Row) {
		$string .= sprintf(' OR pc.Category_ID=%d', mysql_real_escape_string($children->Row['Category_ID']));
		$string .= GetChildIDS($children->Row['Category_ID']);

		$children->Next();
	}
	$children->Disconnect();

	return $string;
}
?>