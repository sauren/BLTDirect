<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Discount Schema Markup Report', 'Please choose a schema for your report');
	$year = cDatetime(getDatetime(), 'y');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', NULL, NULL, false);
	$form->AddField('schema', 'Discount Schema', 'select', '', 'anything', 0, 128);
	$form->AddOption('schema', '', '-- Select Schema --');

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

		if($form->Validate()){
			report($form->GetValue('schema'), $form->GetValue('parent'), ($form->GetValue('subfolders') =='Y') ? true : false);
			exit;
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
	echo $window->AddHeader('Select one of the discount schemas to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('schema'), $form->GetHTML('schema'));
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

function report($schema, $cat, $sub){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductBand.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountSchema.php');

	$useAllProductsInitialised = false;
	$reportedAnything = false;
	$schemas = array();

	$data = new DataQuery(sprintf("SELECT Discount_Schema_ID FROM discount_schema WHERE Discount_Ref LIKE '%%%s%%' ORDER BY Discount_Title", mysql_real_escape_string($schema)));
	while($data->Row) {
		$schemas[] = new DiscountSchema($data->Row['Discount_Schema_ID']);
		$data->Next();
	}
	$data->Disconnect();

	$page = new Page('Discount Schema Markup Report', '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
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
				$sqlProducts .= $glue.implode(' OR '.$glue, $productsArr);
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

				$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_markup
												SELECT p.Product_ID, sp.Cost
												FROM product AS p
												INNER JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
												WHERE sp.Preferred_Supplier='Y'"));
				$data->Disconnect();
			}

			$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.Price_Base_Our, (((p.Price_Base_Our/m.Cost)*100)-100) AS Markup, m.Cost
										FROM temp_markup AS m
										INNER JOIN temp_prices AS p ON p.Product_ID=m.Product_ID
										INNER JOIN product_in_categories AS pc ON pc.Product_ID=p.product_ID
										%s
										ORDER BY p.Price_Starts_On DESC, Markup DESC", $sqlCategories));
		} else {
			$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_prices_for_schema
											SELECT p.Product_ID, p.Product_Title, pp.Price_Base_Our, pp.Price_Starts_On
											FROM product AS p
											INNER JOIN product_prices AS pp ON pp.Product_ID=p.Product_ID
											WHERE pp.Price_Starts_On<=Now() %s
											ORDER BY pp.Price_Starts_On DESC", $sqlProducts));
			$data->Disconnect();

			$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_markup_for_schema
											SELECT p.Product_ID, sp.Cost
											FROM product AS p
											INNER JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
											WHERE sp.Preferred_Supplier='Y' %s", $sqlProducts));
			$data->Disconnect();

			$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.Price_Base_Our, (((p.Price_Base_Our/m.Cost)*100)-100) AS Markup, m.Cost
										FROM temp_markup_for_schema AS m
										INNER JOIN temp_prices_for_schema AS p ON p.Product_ID=m.Product_ID
										INNER JOIN product_in_categories AS pc ON pc.Product_ID=p.product_ID
										%s
										ORDER BY p.Price_Starts_On DESC, Markup DESC", $sqlCategories));
		}



		while($data->Row) {
			if(!isset($used[$data->Row['Product_ID']])) {
				$used[$data->Row['Product_ID']] = true;

				$item = array();

				$item['Cost'] = $data->Row['Cost'];
				$item['Product_ID'] = $data->Row['Product_ID'];
				$item['Product_Title'] = strip_tags($data->Row['Product_Title']);
				$item['Price_Base_Our'] = $data->Row['Price_Base_Our'];
				$item['Discounted_Price'] = $data->Row['Price_Base_Our'] * ((100 - $schema->Discount) / 100);

				if(($item['Cost'] > 0) && ($item['Price_Base_Our'] > 0)) {
					$item['Markup'] = number_format((($item['Price_Base_Our']/$item['Cost'])*100)-100, 2, '.', '') . '%';
				} else {
					$item['Markup'] = '-%';
				}

				if(($item['Cost'] > 0) && ($item['Discounted_Price'] > 0)) {
					$item['Discounted_Markup'] = number_format((($item['Discounted_Price']/$item['Cost'])*100)-100, 2, '.', '') . '%';
				} else {
					$item['Discounted_Markup'] = '-%';
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

		if(count($products) > 0) {
			$reportedAnything = true;
			$sortedProducts = array();

			foreach($products as $key => $product) {
				$sortedProducts[$product['SortBy']][] = $product;
			}

			krsort($sortedProducts);

			$band = new ProductBand($schema->UseBand);
			?>

			<h3><br />Discount Schema Markup for <?php print $schema->Name; ?> - <?php print $band->Name; ?> (<?php print $schema->Discount; ?>% off)</h3>
			<p>The markup values of products once this discount schema has been applied.</p>

			<table width="100%" border="0">
			  <tr>
				<td style="border-bottom:1px solid #aaaaaa"><strong>Product Name</strong></td>
				<td style="border-bottom:1px solid #aaaaaa"><strong>Quickfind</strong></td>
				<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Preferred Supplier Cost</strong></td>
				<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Current Price</strong></td>
				<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Current Markup</strong></td>
				<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Discount Price</strong></td>
				<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Discount Markup</strong></td>
			  </tr>

			  <?php
			  foreach($sortedProducts as $key => $products) {
			  	foreach($products as $product) {
			  		?>

			  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				  	<td><a target="_blank" href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><?php echo $product['Product_Title']; ?></a></td>
				  	<td align="right"><?php echo $product['Product_ID']; ?></td>
				  	<td align="right">&pound;<?php echo number_format($product['Cost'], 2, '.', ','); ?></td>
				  	<td align="right">&pound;<?php echo number_format($product['Price_Base_Our'], 2, '.', ','); ?></td>
				  	<td align="right"<?php print ($product['Markup'] < 0) ? ' style="color: red;"' : (($product['Markup'] > 0) ? ' style="color: green;"' : ' style="color: orange;"'); ?>><?php echo $product['Markup']; ?></td>
				  	<td align="right">&pound;<?php echo number_format($product['Discounted_Price'], 2, '.', ','); ?></td>
				  	<td align="right"<?php print ($product['Discounted_Markup'] < 0) ? ' style="color: red;"' : (($product['Discounted_Markup'] > 0) ? ' style="color: green;"' : ' style="color: orange;"'); ?>><?php echo $product['Discounted_Markup']; ?></td>
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

			$data = new DataQuery(sprintf("DROP TABLE temp_markup_for_schema"));
			$data->Disconnect();
		}
	}

	if($useAllProductsInitialised) {
		$data = new DataQuery(sprintf("DROP TABLE temp_prices"));
		$data->Disconnect();

		$data = new DataQuery(sprintf("DROP TABLE temp_markup"));
		$data->Disconnect();
	}

	if(!$reportedAnything) {
		echo '<p>There is no data to report on for the given criteria.</p>';
	}

	$page->Display('footer');
}

function GetChildIDS($cat) {
	$string = "";

	$children = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($cat)));
	while($children->Row) {
		$string .= sprintf(' OR pc.Category_ID=%d', $children->Row['Category_ID']);
		$string .= GetChildIDS($children->Row['Category_ID']);

		$children->Next();
	}
	$children->Disconnect();

	return $string;
}
?>