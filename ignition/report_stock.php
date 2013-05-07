<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

if($action == 'report') {
	$session->Secure(2);
	report();
	exit;
} else {
	$session->Secure(2);
	start();
	exit;
}

function start() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('warehouse', 'Warehouse', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('warehouse', '', '');
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', NULL, NULL, false);
	
	$data = new DataQuery(sprintf("SELECT b.Branch_Name, w.Warehouse_ID FROM branch AS b INNER JOIN warehouse AS w ON w.Type_Reference_ID=b.Branch_ID WHERE w.Type='B' ORDER BY b.Branch_Name ASC"));
	while($data->Row) {
		$form->AddOption('warehouse', $data->Row['Warehouse_ID'], $data->Row['Branch_Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			redirectTo(sprintf('?action=report&warehouse=%s&parent=%d&subfolders=%s', $form->GetValue('warehouse'), $form->GetValue('parent'), $form->GetValue('subfolders')));
		}
	}

	$page = new Page('Stock Report', 'Please choose a warehouse for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on stock.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Select a warehouse for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('warehouse'), $form->GetHTML('warehouse').$form->GetIcon('warehouse'));
	echo $webForm->Close();
	echo $window->CloseContent();
	
	echo $window->AddHeader('Select a category to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->Close();
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
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('warehouse', 'Warehouse', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'hidden', 'N', 'boolean', null, null, false);
	$form->AddField('order', 'Order', 'hidden', 'ASC', 'paragraph', 3, 4);
	
	$locations = array();

	$data = new DataQuery(sprintf('SELECT p.Product_ID, ws.Stock_ID, ws.Shelf_Location, ws.Quantity_In_Stock, ws.Is_Writtenoff FROM product AS p INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Quantity_In_Stock>0 AND ws.Shelf_Location<>\'\' AND ws.Warehouse_ID=%d', mysql_real_escape_string($form->GetValue('warehouse'))));
	while($data->Row) {
		if(!isset($locations[$data->Row['Product_ID']])) {
			$locations[$data->Row['Product_ID']] = array();	
		}
		
		$locations[$data->Row['Product_ID']][] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

	$sqlFrom = '';
	$sqlWhere = '';
	
	if($form->GetValue('parent') != 0) {
		$sqlFrom .= sprintf(" INNER JOIN product_in_categories AS c ON c.Product_ID=p.Product_ID ");

		if($form->GetValue('subfolders')) {
			$sqlWhere .= sprintf(" AND (c.Category_ID=%d %s) ", mysql_real_escape_string($form->GetValue('parent')), mysql_real_escape_string(getCategories($form->GetValue('parent'))));
		} else {
			$sqlWhere .= sprintf(" AND c.Category_ID=%d ", mysql_real_escape_string($form->GetValue('parent')));
		}
	}
	
	$countStocked = 0;
	$countStockedTemporarily = 0;
	
	$products = array();
	
	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Position_Orders_Recent, p.Position_Quantities_3_Month, p.Position_Quantities_12_Month, p.Position_Orders_3_Month, p.Position_Orders_12_Month, p.Product_Title, p.Is_Stocked, p.Is_Stocked_Temporarily, p.Stock_Level_Alert, p.Stock_Reorder_Quantity, SUM(ws.Cost*ws.Quantity_In_Stock) AS Cost_Registered, SUM(p.CacheBestCost*ws.Quantity_In_Stock) AS Cost_Best, SUM(p.CacheRecentCost*ws.Quantity_In_Stock) AS Cost_Recent, SUM(ws.Quantity_In_Stock) AS Quantity FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID INNER JOIN product AS p ON ws.Product_ID=p.Product_ID%s WHERE w.Warehouse_ID=%d AND p.Product_Type<>'G'%s GROUP BY p.Product_ID ORDER BY p.Position_Orders_Recent %s, p.Product_ID ASC", $sqlFrom, mysql_real_escape_string($form->GetValue('warehouse')), $sqlWhere, mysql_real_escape_string($form->GetValue('order'))));
	while($data->Row) {
		$products[] = $data->Row;
		
		if($data->Row['Is_Stocked'] == 'Y') {
			$countStocked++;
		}
	
		if($data->Row['Is_Stocked_Temporarily'] == 'Y') {
			$countStockedTemporarily++;
		}		
	
		$data->Next();	
	}
	$data->Disconnect();

	foreach($products as $product) {
		$form->AddField('stocked_' . $product['Product_ID'], 'Stocked', 'checkbox', $product['Is_Stocked'], 'boolean', 1, 1, false);
		$form->AddField('stockedtemporarily_' . $product['Product_ID'], 'Stocked Temporarily', 'checkbox', $product['Is_Stocked_Temporarily'], 'boolean', 1, 1, false);
	}
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Valid) {
			if(isset($_REQUEST['settemporary'])) {
				foreach($products as $product) {
					if(($product['Is_Stocked'] == 'N') && ($product['Is_Stocked_Temporarily'] == 'N')) {
						$object = new Product($product['Product_ID']);
						$object->StockedTemporarily = 'Y';
						$object->Update();
					}
				}

				redirectTo(sprintf('?action=report&warehouse=%s&parent=%d&subfolders=%s&order=%s', $form->GetValue('warehouse'), $form->GetValue('parent'), $form->GetValue('subfolders'), $form->GetValue('order')));
			} else {
				foreach($products as $product) {
					if(($form->GetValue('stocked_' . $product['Product_ID']) != $product['Is_Stocked']) || ($form->GetValue('stockedtemporarily_' . $product['Product_ID']) != $product['Is_Stocked_Temporarily'])) {
						$object = new Product($product['Product_ID']);
						$object->Stocked = $form->GetValue('stocked_' . $product['Product_ID']);
						$object->StockedTemporarily = $form->GetValue('stockedtemporarily_' . $product['Product_ID']);
						$object->Update();
					}
				}
				
				redirectTo(sprintf('?action=report&warehouse=%s&parent=%d&subfolders=%s&order=%s', $form->GetValue('warehouse'), $form->GetValue('parent'), $form->GetValue('subfolders'), $form->GetValue('order')));
			}
		}
	}
	
	$script = sprintf('<script language="javascript" type="text/javascript">
		var toggleLocations = function(id) {
			var element = document.getElementById(\'locations-\' + id);
			var image = document.getElementById(\'image-\' + id);
			
			if(element) {
				if(element.style.display == \'table-row\') {
					element.style.display = \'none\';
					image.src = \'images/button-plus.gif\';
				} else {
					element.style.display = \'table-row\';
					image.src = \'images/button-minus.gif\';
				}
			}
		}
		</script>');

	$page = new Page('Stock Report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->AddToHead($script);
	$page->Display('header');
	
	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('warehouse');
	echo $form->GetHTML('parent');
	echo $form->GetHTML('subfolders');
	echo $form->GetHTML('order');
	?>
	
	<br />
	<h3>Products Summary</h3>
	<br />
	
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Item</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Value</strong></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>Products</td>
			<td align="right"><?php echo count($products); ?></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>Stocked</td>
			<td align="right"><?php echo $countStocked; ?></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>Stocked Temporarily</td>
			<td align="right"><?php echo $countStockedTemporarily; ?></td>
		</tr>
	</table>
	
	<br />
	<h3>Products Stocked</h3>
	<br />
	
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;;"></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong><a href="?action=report&warehouse=<?php echo $form->GetValue('warehouse'); ?>&parent=<?php echo $form->GetValue('parent'); ?>&subfolders=<?php echo $form->GetValue('subfolders'); ?>&order=<?php echo (strtoupper($form->GetValue('order')) == 'ASC') ? 'DESC' : 'ASC'; ?>">Position</a></strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Product Name</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Location</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Alert Level</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Reorder Quantity</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Position Quantities 3 Month</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Position Quantities 12 Month</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Position Orders 3 Month</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Position Orders 12 Month</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Registered Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Best Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Recent Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Current Price</strong> </td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity in Stock</strong> </td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Written Off</strong> </td>
			<td style="border-bottom:1px solid #aaaaaa;" align="center"><strong>Stocked</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="center"><strong>Stocked Temporarily</strong></td>
		</tr>
		  
		<?php
		$totalPrice = 0;
		$totalCostBest = 0;
		$totalCostRecent = 0;
		$totalCostRegistered = 0;
		$totalStock = 0;

		foreach($products as $product) {
			if((($form->GetValue('order') == 'ASC') && ($product['Position_Orders_Recent'] > 0)) || (($form->GetValue('order') == 'DESC') && ($product['Position_Orders_Recent'] == 0))) {
				$priceFind = new DataQuery(sprintf("SELECT * FROM product_prices WHERE Product_ID=%d AND Price_Starts_On<=NOW() Order By Price_Starts_On desc", mysql_real_escape_string($product['Product_ID'])));

				$totalPrice += $product['Quantity'] * $priceFind->Row['Price_Base_Our'];
				$totalCostRegistered += $product['Cost_Registered'];
				$totalCostBest += $product['Cost_Best'];
				$totalCostRecent += $product['Cost_Recent'];
				$totalStock += $product['Quantity'];
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td width="1%">
					
						<?php
						if(isset($locations[$product['Product_ID']]) && (count($locations[$product['Product_ID']]) > 1)) {
							?>
							
							<a href="javascript:toggleLocations(<?php echo $product['Product_ID']; ?>);"><img src="images/button-plus.gif" alt="Toggle Locations" id="image-<?php echo $product['Product_ID']; ?>" /></a>
							
							<?php
						}
						?>
						
					</td>
					<td><?php echo ($product['Position_Orders_Recent'] > 0) ? $product['Position_Orders_Recent'] : ''; ?></td>
					<td><a target="_blank" href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><?php echo $product['Product_Title']; ?></a></td>
					<td><?php echo $product['Product_ID']; ?></td>
					<td>
						<?php
						if(isset($locations[$product['Product_ID']])) {
							if(count($locations[$product['Product_ID']]) == 1) {
								echo $locations[$product['Product_ID']][0]['Shelf_Location'];
							} else {
								echo '<em>Multiple</em>';	
							}
						}
						?>
					</td>
					<td align="right"><?php echo $product['Stock_Level_Alert']; ?></td>
					<td align="right"><?php echo $product['Stock_Reorder_Quantity']; ?></td>
					<td align="right"><?php echo $product['Position_Quantities_3_Month']; ?></td>
					<td align="right"><?php echo $product['Position_Quantities_12_Month']; ?></td>
					<td align="right"><?php echo $product['Position_Orders_3_Month']; ?></td>
					<td align="right"><?php echo $product['Position_Orders_12_Month']; ?></td>
					<td align="right">&pound;<?php echo number_format($product['Cost_Registered'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($product['Cost_Best'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($product['Cost_Recent'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($priceFind->Row['Price_Base_Our'], 2, '.', ','); ?></td>
					<td align="right"><?php echo $product['Quantity']; ?></td>
					<td>
						<?php
						if(isset($locations[$product['Product_ID']])) {
							if(count($locations[$product['Product_ID']]) == 1) {
								echo $locations[$product['Product_ID']][0]['Is_Writtenoff'];
							} else {
								echo 'Multiple';	
							}
						}
						?>
					</td>
					<td align="center"><?php echo $form->GetHTML('stocked_' . $product['Product_ID']); ?></td>
					<td align="center"><?php echo $form->GetHTML('stockedtemporarily_' . $product['Product_ID']); ?></td>
				</tr>
				<tr style="display: none;" id="locations-<?php echo $product['Product_ID']; ?>">
					<td></td>
					<td colspan="17">
					
						<table width="100%" border="0">
							<tr>
								<td style="border-bottom:1px solid #aaaaaa;" width="33%"><strong>Position</strong></td>
								<td style="border-bottom:1px solid #aaaaaa;" width="33%" align="right"><strong>Quantity</strong></td>
								<td style="border-bottom:1px solid #aaaaaa;" width="34%" align="center"><strong>Written Off</strong></td>
							</tr>
							
							<?php
							if(isset($locations[$product['Product_ID']])) {
								foreach($locations[$product['Product_ID']] as $location) {
									?>
									
									<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
										<td><?php echo $location['Shelf_Location']; ?></td>
										<td align="right"><?php echo $location['Quantity_In_Stock']; ?></td>
										<td align="center"><?php echo $location['Is_Writtenoff']; ?></td>
									</tr>
					
									<?php
								}
							}
							?>
							
						</table>
						<br />
					
					</td>
				</tr>
					
				<?php
				$priceFind->Disconnect();
			}
		}
		
		foreach($products as $product) {
			if((($form->GetValue('order') == 'ASC') && ($product['Position_Orders_Recent'] == 0)) || (($form->GetValue('order') == 'DESC') && ($product['Position_Orders_Recent'] > 0))) {
				$priceFind = new DataQuery(sprintf("SELECT * FROM product_prices WHERE Product_ID=%d AND Price_Starts_On<=NOW() Order By Price_Starts_On desc", mysql_real_escape_string($product['Product_ID'])));

				$totalPrice += $product['Quantity'] * $priceFind->Row['Price_Base_Our'];
				$totalCostRegistered += $product['Cost_Registered'];
				$totalCostBest += $product['Cost_Best'];
				$totalCostRecent += $product['Cost_Recent'];
				$totalStock += $product['Quantity'];
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td width="1%">
					
						<?php
						if(isset($locations[$product['Product_ID']]) && (count($locations[$product['Product_ID']]) > 1)) {
							?>
							
							<a href="javascript:toggleLocations(<?php echo $product['Product_ID']; ?>);"><img src="images/button-plus.gif" alt="Toggle Locations" id="image-<?php echo $product['Product_ID']; ?>" /></a>
							
							<?php
						}
						?>
						
					</td>
					<td><?php echo ($product['Position_Orders_Recent'] > 0) ? $product['Position_Orders_Recent'] : ''; ?></td>
					<td><a target="_blank" href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><?php echo $product['Product_Title']; ?></a></td>
					<td><?php echo $product['Product_ID']; ?></td>
					<td>
						<?php
						if(isset($locations[$product['Product_ID']])) {
							if(count($locations[$product['Product_ID']]) == 1) {
								echo $locations[$product['Product_ID']][0]['Shelf_Location'];
							} else {
								echo '<em>Multiple</em>';	
							}
						}
						?>
					</td>
					<td align="right"><?php echo $product['Stock_Level_Alert']; ?></td>
					<td align="right"><?php echo $product['Stock_Reorder_Quantity']; ?></td>
					<td align="right"><?php echo $product['Position_Quantities_3_Month']; ?></td>
					<td align="right"><?php echo $product['Position_Quantities_12_Month']; ?></td>
					<td align="right"><?php echo $product['Position_Orders_3_Month']; ?></td>
					<td align="right"><?php echo $product['Position_Orders_12_Month']; ?></td>
					<td align="right">&pound;<?php echo number_format($product['Cost_Registered'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($product['Cost_Best'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($product['Cost_Recent'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($priceFind->Row['Price_Base_Our'], 2, '.', ','); ?></td>
					<td align="right"><?php echo $product['Quantity']; ?></td>
					<td align="center"><?php echo $form->GetHTML('stocked_' . $product['Product_ID']); ?></td>
					<td align="center"><?php echo $form->GetHTML('stockedtemporarily_' . $product['Product_ID']); ?></td>
				</tr>
				<tr style="display: none;" id="locations-<?php echo $product['Product_ID']; ?>">
					<td></td>
					<td colspan="17">
					
						<table width="100%" border="0">
							<tr>
								<td style="border-bottom:1px solid #aaaaaa;" width="50%"><strong>Position</strong></td>
								<td style="border-bottom:1px solid #aaaaaa;" width="50%" align="right"><strong>Quantity</strong></td>
							</tr>
							
							<?php
							if(isset($locations[$product['Product_ID']])) {
								foreach($locations[$product['Product_ID']] as $location) {
									?>
									
									<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
										<td><?php echo $location['Shelf_Location']; ?></td>
										<td align="right"><?php echo $location['Quantity_In_Stock']; ?></td>
									</tr>
					
									<?php
								}
							}
							?>
							
						</table>
						<br />
					
					</td>
				</tr>
					
				<?php
				$priceFind->Disconnect();
			}
		}
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalCostRegistered, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalCostBest, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalCostRecent, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalPrice, 2, '.', ','); ?></strong></td>
			<td align="right"><strong><?php echo $totalStock; ?></strong></td>
			<td></td>
			<td></td>
		</tr>
	</table>
	<br />
	
	<input type="submit" name="update" value="update" class="btn" />
	<input type="submit" name="settemporary" value="set non stocked temporary" class="btn" />
		  
	<?php
}

function getCategories($categoryId) {
	$string = '';

	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row){
		$string .= sprintf("OR c.Category_ID=%d %s ", mysql_real_escape_string($data->Row['Category_ID']), mysql_real_escape_string(getCategories($data->Row['Category_ID'])));

		$data->Next();
	}
	$data->Disconnect();

	return $string;
}