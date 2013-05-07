<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

if($action == 'reband') {
	$session->Secure(3);
	reband();
	exit;
} elseif($action == 'export') {
	$session->Secure(3);
	export();
	exit;
} elseif($action == 'report') {
	$session->Secure(2);
	report();
	exit;
} else {
	$session->Secure(2);
	start();
	exit;
}

function breakRow($row) {
	$str = '';

	foreach($row as $cell) {
		$str .= $cell;
	}

	return substr($str, 0, -1)."\n";
}

function export() {
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_prices SELECT p.Product_ID, p.Product_Title, pp.Price_Base_Our, pp.Price_Starts_On FROM product AS p INNER JOIN product_prices AS pp ON pp.Product_ID=p.Product_ID WHERE pp.Price_Starts_On<=Now() ORDER BY pp.Price_Starts_On DESC"));
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_markup SELECT sp.Product_ID, sp.Cost FROM supplier_product AS sp WHERE sp.Preferred_Supplier='Y'"));

	$used = array();
	$products = array();

	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.Price_Base_Our, (((p.Price_Base_Our/m.Cost)*100)-100) AS Markup, m.Cost
									FROM temp_markup AS m
									INNER JOIN temp_prices AS p ON p.Product_ID=m.Product_ID
									ORDER BY p.Price_Starts_On DESC, Markup DESC"));

	while($data->Row) {
		if(!isset($used[$data->Row['Product_ID']])) {
			$used[$data->Row['Product_ID']] = true;

			$item = array();

			$item['Cost'] = $data->Row['Cost'];
			$item['Product_ID'] = $data->Row['Product_ID'];
			$item['Product_Title'] = strip_tags($data->Row['Product_Title']);
			$item['Price_Base_Our'] = $data->Row['Price_Base_Our'];

			if(($item['Cost'] > 0) && ($item['Price_Base_Our'] > 0)) {
				$item['Markup'] = number_format((($item['Price_Base_Our']/$item['Cost'])*100)-100, 2, '.', '');
			} else {
				$item['Markup'] = 0;
			}

			if(($item['Cost'] > 0) && ($item['Price_Base_Our'] > 0)) {
				$item['SortBy'] = (($item['Price_Base_Our']/$item['Cost'])*100)-100;
			} else {
				$item['SortBy'] = 0;
			}

			$products[$data->Row['Product_ID']] = $item;
		}

		$data->Next();
	}
	$data->Disconnect();

	$sortedProducts = array();

	foreach($products as $key => $product) {
		$sortedProducts[$product['SortBy']][] = $product;
	}

	krsort($sortedProducts);

	$contents = '';

	$line = array();
	$line[] = sprintf('"Rank",');
	$line[] = sprintf('"Product Name",');
	$line[] = sprintf('"Quickfind",');
	$line[] = sprintf('"Preferred Supplier Cost",');
	$line[] = sprintf('"Current Price",');
	$line[] = sprintf('"Markup",');

	$contents .= breakRow($line);

	$count = 1;

	foreach($sortedProducts as $key => $products) {
	 	foreach($products as $product) {
	 		$line = array();
			$line[] = sprintf('"%s",', $count);
			$line[] = sprintf('"%s",', $product['Product_Title']);
			$line[] = sprintf('"%s",', $product['Product_ID']);
			$line[] = sprintf('"%s",', number_format($product['Cost'], 2, '.', ','));
			$line[] = sprintf('"%s",', number_format($product['Price_Base_Our'], 2, '.', ','));
			$line[] = sprintf('"%s",', $product['Markup']);

		  	$contents .= breakRow($line);

	  		$count++;
		}
	}

	$fileName = sprintf('temp/reports/markup_%s.csv', date('ymdHis'));

	$fh = fopen($fileName, 'w') or die("Can't open file");
	fwrite($fh, $contents);
	fclose($fh);

	redirect(sprintf("Location: %s", $fileName));
}

function reband() {
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_prices SELECT p.Product_ID, p.Product_Title, pp.Price_Base_Our, pp.Price_Starts_On FROM product AS p INNER JOIN product_prices AS pp ON pp.Product_ID=p.Product_ID WHERE pp.Price_Starts_On<=Now() ORDER BY pp.Price_Starts_On DESC"));
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_markup SELECT sp.Product_ID, sp.Cost FROM supplier_product AS sp WHERE sp.Preferred_Supplier='Y'"));

	$used = array();
	$products = array();

	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Price_Base_Our, (((p.Price_Base_Our/m.Cost)*100)-100) AS Markup, m.Cost
									FROM temp_markup AS m
									INNER JOIN temp_prices AS p ON p.Product_ID=m.Product_ID
									ORDER BY p.Price_Starts_On DESC, Markup DESC"));

	while($data->Row) {
		if(!isset($used[$data->Row['Product_ID']])) {
			$used[$data->Row['Product_ID']] = true;

			$item = array();
			$item['Product_ID'] = $data->Row['Product_ID'];

			if(($data->Row['Cost'] > 0) && ($data->Row['Price_Base_Our'] > 0)) {
				$item['Markup'] = (($data->Row['Price_Base_Our']/$data->Row['Cost'])*100)-100;
			} else {
				$item['Markup'] = 0;
			}

			$products[$data->Row['Product_ID']] = $item;
		}

		$data->Next();
	}
	$data->Disconnect();

	$bands = array();

	$data = new DataQuery("SELECT * FROM product_band");
	while($data->Row) {
		$bands[$data->Row['Band_Ref']] = $data->Row['Product_Band_ID'];

		$data->Next();
	}
	$data->Disconnect();

	foreach($products as $key => $product) {
		if($product['Markup'] >= 700) {
			$band = 'A';
		} elseif($product['Markup'] >= 600) {
			$band = 'B';
		} elseif($product['Markup'] >= 500) {
			$band = 'C';
		} elseif($product['Markup'] >= 400) {
			$band = 'D';
		} elseif($product['Markup'] >= 300) {
			$band = 'E';
		} elseif($product['Markup'] >= 250) {
			$band = 'F';
		} elseif($product['Markup'] >= 200) {
			$band = 'G';
		} elseif($product['Markup'] >= 150) {
			$band = 'H';
		} elseif($product['Markup'] >= 100) {
			$band = 'I';
		} elseif($product['Markup'] >= 80) {
			$band = 'J';
		} elseif($product['Markup'] >= 60) {
			$band = 'K';
		} elseif($product['Markup'] >= 40) {
			$band = 'L';
		} elseif($product['Markup'] >= 30) {
			$band = 'M';
		} elseif($product['Markup'] >= 20) {
			$band = 'N';
		} else {
			$band = 'O';
		}

		if(isset($bands[$band])) {
			$data = new DataQuery(sprintf("UPDATE product SET Product_Band_ID=%d WHERE Product_ID=%d", mysql_real_escape_string($bands[$band]), mysql_real_escape_string($product['Product_ID'])));
		}
	}

	$page = new Page('Markup Report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	print "<p>Banding recalculated. <a href=\"".$_SERVER['PHP_SELF']."\">Click here</a> to view product mark up.</p>";

	$page->Display('footer');
	include('lib/common/app_footer.php');
}

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', NULL, NULL, false);
	$form->AddField('period', 'Report Period', 'select', 1, 'numeric_unsigned', 1, 11);
	$form->AddOption('period', '1', '1 Month');
	$form->AddOption('period', '2', '2 Month');
	$form->AddOption('period', '3', '3 Month');

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			redirect(sprintf("Location: %s?action=report&cat=%d&sub=%s&period=%d", $_SERVER['PHP_SELF'], $form->GetValue('parent'), $form->GetValue('subfolders'), $form->GetValue('period')));
		}
	}

	$page = new Page('Markup Report', 'Please select a product category to report on.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on markup.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Select the period for this report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('period'), $form->GetHTML('period'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Click on a the search icon to find a category to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('parent') . $temp_1, '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->AddRow('&nbsp','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductPrice.php');
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cat', 'Category ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('sub', 'Sub Folders', 'hidden', '', 'alpha', 1, 1);
	$form->AddField('period', 'Period', 'hidden', '', 'numeric_unsigned', 1, 11);
	
	$cat = $_REQUEST['cat'];
	$sub = ($_REQUEST['sub'] == 'Y') ? true : false;
	$period = $_REQUEST['period'];

	$clientString = '';

	if($cat != 0) {
		if($sub) {
			$clientString .= sprintf("AND (cat.Category_ID=%d %s) ", $cat, GetChildIDS($cat));
		} else {
			$clientString .= sprintf("AND (cat.Category_ID=%d) ", $cat);
		}
	} else {
		if(!$sub) {
			$clientString .= sprintf("AND (cat.Category_ID=%d) ", $cat);
		}
	}

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_prices SELECT p.Product_ID, p.Product_Title, pp.Price_Base_Our, pp.Price_Base_RRP, pp.Price_Starts_On FROM product AS p LEFT JOIN product_in_categories AS cat ON cat.Product_ID=p.Product_ID INNER JOIN product_prices AS pp ON pp.Product_ID=p.Product_ID WHERE pp.Price_Starts_On<=NOW() AND pp.Quantity=1 %s ORDER BY pp.Price_Starts_On DESC", mysql_real_escape_string($clientString)));
	new DataQuery(sprintf("ALTER TABLE temp_prices ADD INDEX Product_ID (Product_ID)"));
	
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_markup SELECT sp.Product_ID, sp.Cost FROM supplier_product AS sp LEFT JOIN product_in_categories AS cat ON cat.Product_ID=sp.Product_ID WHERE sp.Preferred_Supplier='Y' %s GROUP BY sp.Product_ID", mysql_real_escape_string($clientString)));
	new DataQuery(sprintf("ALTER TABLE temp_markup ADD INDEX Product_ID (Product_ID)"));

	$used = array();
	$products = array();
	$orders = array();
	
	$data = new DataQuery(sprintf("SELECT Product_ID, Price_Starts_On FROM temp_prices ORDER BY Price_Starts_On DESC"));
	while($data->Row) {
		if(!isset($used[$data->Row['Product_ID']])) {
			$used[$data->Row['Product_ID']] = true;
		} else {
			new DataQuery(sprintf("DELETE FROM temp_prices WHERE Product_ID=%d AND Price_Starts_On='%s'", $data->Row['Product_ID'], $data->Row['Price_Starts_On']));
		}

		$data->Next();
	}
	$data->Disconnect();
	
	$data = new DataQuery(sprintf("SELECT p.Product_ID, COUNT(DISTINCT ol.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantity FROM temp_markup AS m INNER JOIN temp_prices AS p ON p.Product_ID=m.Product_ID INNER JOIN order_line AS ol ON ol.Product_ID=m.Product_ID AND ol.Despatch_ID>0 INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) GROUP BY m.Product_ID", mysql_real_escape_string($period)));
	while($data->Row) {
		$orders[$data->Row['Product_ID']] = array('Orders' => $data->Row['Orders'], 'Quantity' => $data->Row['Quantity']);
		
		$data->Next();
	}
	$data->Disconnect();
	
	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.Price_Base_Our, p.Price_Base_RRP, (((p.Price_Base_Our/m.Cost)*100)-100) AS Markup, m.Cost FROM temp_markup AS m INNER JOIN temp_prices AS p ON p.Product_ID=m.Product_ID GROUP BY m.Product_ID ORDER BY p.Price_Starts_On DESC, Markup DESC", mysql_real_escape_string($period)));
	while($data->Row) {
		$item = $data->Row;
		$item['Orders'] = isset($orders[$data->Row['Product_ID']]['Orders']) ? $orders[$data->Row['Product_ID']]['Orders'] : 0;
		$item['Quantity'] = isset($orders[$data->Row['Product_ID']]['Quantity']) ? $orders[$data->Row['Product_ID']]['Quantity'] : 0;

		if(($item['Cost'] > 0) && ($item['Price_Base_Our'] > 0)) {
			$item['Markup'] = number_format($item['Markup'], 2, '.', '') . '%';
		} else {
			$item['Markup'] = '-%';
		}

		if(($item['Cost'] > 0) && ($item['Price_Base_Our'] > 0)) {
			$item['SortBy'] = (($item['Price_Base_Our']/$item['Cost'])*100)-100;
		} else {
			$item['SortBy'] = 0;
		}

		$products[$data->Row['Product_ID']] = $item;

		$data->Next();
	}
	$data->Disconnect();
	
	foreach($products as $product) {
		$form->AddField(sprintf('target_%d', $product['Product_ID']), sprintf('Target for \'%s\'', $product['Product_Title']), 'text', '', 'float', 1, 11, false, 'size="5"');
	}
	
	$sortedProducts = array();

	foreach($products as $product) {
		$sortedProducts[$product['SortBy']][] = $product;
 	}

	krsort($sortedProducts);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			foreach($products as $product) {
				$target = $form->GetValue(sprintf('target_%d', $product['Product_ID']));
				
				if(!empty($target)) {
					$price = new ProductPrice();
					$price->ProductID = $product['Product_ID'];
					$price->PriceOurs = (($target + 100) / 100) * $product['Cost'];
					$price->PriceRRP = $product['Price_Base_RRP'];
					$price->Quantity = 1;
					$price->PriceStartsOn = date('Y-m-d H:i:s');
					$price->Add();
				}	
			}
		
			redirect(sprintf("Location: %s?action=report&cat=%d&sub=%s&period=%d", $_SERVER['PHP_SELF'], $form->GetValue('cat'), $form->GetValue('sub'), $form->GetValue('period')));
		}
	}
	
	$page = new Page('Markup Report');
	$page->Display('header');
	
	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cat');
	echo $form->GetHTML('sub');
	echo $form->GetHTML('period');
	?>

	<br />
	<h3>Product Markup</h3>
	<p>Below are the details of the markup values for your supplied products.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Rank</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Product Name</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Quickfind</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Orders</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Quantity</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Preferred Supplier Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Current Price</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Markup</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Target</strong></td>
		</tr>

		<?php
		$count = 1;
		  
		foreach($sortedProducts as $products) {
			foreach($products as $product) {
			  	?>
	
			  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				  	<td><?php print $count; ?></td>
				  	<td><a target="_blank" href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><?php echo $product['Product_Title']; ?></a></td>
				  	<td><?php echo $product['Product_ID']; ?></td>
				  	<td><?php echo $product['Orders']; ?></td>
				  	<td><?php echo $product['Quantity']; ?></td>
				  	<td align="right" nowrap="nowrap">&pound;<?php echo number_format($product['Cost'], 2, '.', ','); ?></td>
				  	<td align="right" nowrap="nowrap">&pound;<?php echo number_format($product['Price_Base_Our'], 2, '.', ','); ?></td>
				  	<td align="right" nowrap="nowrap"<?php print ($product['Markup'] < 0) ? ' style="color: red;"' : (($product['Markup'] > 0) ? ' style="color: green;"' : ' style="color: orange;"'); ?>><?php echo $product['Markup']; ?></td>
				  	<td align="right" nowrap="nowrap"><?php echo $form->GetHTML('target_'.$product['Product_ID']); ?>%</td>
				  </tr>
	
			  	<?php
			  	$count++;
			}
		}
		?>

	</table>
	<br />
	
	<input type="submit" class="btn" name="update" value="update markup" />
	<br /><br />
	
	<input type="button" class="btn" value="recalculate banding" onclick="window.self.location.href='<?php print $_SERVER['PHP_SELF']; ?>?action=reband'" />
	<input type="button" class="btn" value="export to csv" onclick="window.self.location.href='<?php print $_SERVER['PHP_SELF']; ?>?action=export'" />

	<?php
	echo $form->Close();
	
	$page->Display('footer');
	include('lib/common/app_footer.php');
}

function GetChildIDS($cat){
	$string = '';
	$children = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID = %d",$cat));
	while($children->Row){
		$string .= "OR cat.Category_ID = ".$children->Row['Category_ID']." ";
		$string .= GetChildIDS($children->Row['Category_ID']);
		$children->Next();
	}
	$children->Disconnect();
	return $string;
}
?>