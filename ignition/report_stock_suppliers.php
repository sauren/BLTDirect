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

function start(){
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

	$page = new Page('Stock Suppliers Report', 'Please choose a warehouse for your report');
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
	
	$suppliers = array();

	$data = new DataQuery(sprintf('SELECT p.Product_ID, sp.Supplier_ID, sp.Supplier_Product_ID, sp.Cost, IF(LENGTH(o.Org_Name)>0, o.Org_Name, CONCAT_WS(\' \', pr.Name_First, pr.Name_Last)) AS Supplier FROM product AS p INNER JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID AND sp.Cost>0 INNER JOIN supplier AS s ON s.Supplier_ID=sp.Supplier_ID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS pr ON pr.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID ORDER BY Supplier ASC'));
	while($data->Row) {
		if(!isset($suppliers[$data->Row['Product_ID']])) {
			$suppliers[$data->Row['Product_ID']] = array();	
		}
		
		$suppliers[$data->Row['Product_ID']][] = $data->Row;

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
	
	$products = array();
	
	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.LockedSupplierID, p.Position_Orders_Recent, p.Position_Quantities_3_Month, p.Position_Quantities_12_Month, p.Position_Orders_3_Month, p.Position_Orders_12_Month, p.Product_Title, p.Is_Stocked, p.Is_Stocked_Temporarily, p.CacheBestCost AS Cost_Best, p.CacheRecentCost AS Cost_Recent, SUM(ws.Quantity_In_Stock) AS Quantity FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID INNER JOIN product AS p ON ws.Product_ID=p.Product_ID%s WHERE w.Warehouse_ID=%d AND p.Product_Type<>'G' AND p.Is_Stocked='Y'%s GROUP BY p.Product_ID ORDER BY p.Position_Orders_Recent %s, p.Product_ID ASC", $sqlFrom, mysql_real_escape_string($form->GetValue('warehouse')), $sqlWhere, mysql_real_escape_string($form->GetValue('order'))));
	while($data->Row) {
		$products[] = $data->Row;
		
		$data->Next();	
	}
	$data->Disconnect();

	foreach($products as $product) {
		$form->AddField('lockedsupplier_' . $product['Product_ID'], 'Locked Supplier', 'select', $product['LockedSupplierID'], 'numeric_unsigned', 1, 11);
		$form->AddOption('lockedsupplier_' . $product['Product_ID'], 0, '');
	
		if(isset($suppliers[$product['Product_ID']])) {
			foreach($suppliers[$product['Product_ID']] as $supplier) {
				$form->AddOption('lockedsupplier_' . $product['Product_ID'], $supplier['Supplier_ID'], sprintf('%s [&pound;%s]', $supplier['Supplier'], $supplier['Cost']));
			}
		}
	}
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Valid) {
			foreach($products as $product) {
				if($form->GetValue('lockedsupplier_' . $product['Product_ID']) != $product['LockedSupplierID']) {
					$object = new Product($product['Product_ID']);
					$object->LockedSupplierID = $form->GetValue('lockedsupplier_' . $product['Product_ID']);
					$object->Update();
				}
			}
			
			redirectTo(sprintf('?action=report&warehouse=%s&parent=%d&subfolders=%s&order=%s', $form->GetValue('warehouse'), $form->GetValue('parent'), $form->GetValue('subfolders'), $form->GetValue('order')));
		}
	}
	
	$page = new Page('Stock Suppliers Report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
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
	
	<h3>Products Stocked</h3>
	
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Product Name</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Position Quantities 3 Month</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Position Quantities 12 Month</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Position Orders 3 Month</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Position Orders 12 Month</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Best Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Recent Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Current Price</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Locked Supplier</strong></td>
		</tr>
		  
		<?php
		$totalPrice = 0;
		$totalCostBest = 0;
		$totalCostRecent = 0;

		foreach($products as $product) {
			if((($form->GetValue('order') == 'ASC') && ($product['Position_Orders_Recent'] > 0)) || (($form->GetValue('order') == 'DESC') && ($product['Position_Orders_Recent'] == 0))) {
				$priceFind = new DataQuery(sprintf("SELECT * FROM product_prices WHERE Product_ID=%d AND Price_Starts_On<=NOW() Order By Price_Starts_On desc", mysql_real_escape_string($product['Product_ID'])));

				$totalPrice += $product['Quantity'] * $priceFind->Row['Price_Base_Our'];
				$totalCostBest += $product['Cost_Best'];
				$totalCostRecent += $product['Cost_Recent'];
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><a target="_blank" href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><?php echo $product['Product_Title']; ?></a></td>
					<td><?php echo $product['Product_ID']; ?></td>
					<td align="right"><?php echo $product['Position_Quantities_3_Month']; ?></td>
					<td align="right"><?php echo $product['Position_Quantities_12_Month']; ?></td>
					<td align="right"><?php echo $product['Position_Orders_3_Month']; ?></td>
					<td align="right"><?php echo $product['Position_Orders_12_Month']; ?></td>
					<td align="right">&pound;<?php echo number_format($product['Cost_Best'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($product['Cost_Recent'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($priceFind->Row['Price_Base_Our'], 2, '.', ','); ?></td>
					<td><?php echo $form->GetHTML('lockedsupplier_' . $product['Product_ID']); ?></td>
				</tr>
					
				<?php
				$priceFind->Disconnect();
			}
		}
		
		foreach($products as $product) {
			if((($form->GetValue('order') == 'ASC') && ($product['Position_Orders_Recent'] == 0)) || (($form->GetValue('order') == 'DESC') && ($product['Position_Orders_Recent'] > 0))) {
				$priceFind = new DataQuery(sprintf("SELECT * FROM product_prices WHERE Product_ID=%d AND Price_Starts_On<=NOW() Order By Price_Starts_On desc", mysql_real_escape_string($product['Product_ID'])));

				$totalPrice += $product['Quantity'] * $priceFind->Row['Price_Base_Our'];
				$totalCostBest += $product['Cost_Best'];
				$totalCostRecent += $product['Cost_Recent'];
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><a target="_blank" href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><?php echo $product['Product_Title']; ?></a></td>
					<td><?php echo $product['Product_ID']; ?></td>
					<td align="right"><?php echo $product['Position_Quantities_3_Month']; ?></td>
					<td align="right"><?php echo $product['Position_Quantities_12_Month']; ?></td>
					<td align="right"><?php echo $product['Position_Orders_3_Month']; ?></td>
					<td align="right"><?php echo $product['Position_Orders_12_Month']; ?></td>
					<td align="right">&pound;<?php echo number_format($product['Cost_Best'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($product['Cost_Recent'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($priceFind->Row['Price_Base_Our'], 2, '.', ','); ?></td>
					<td><?php echo $form->GetHTML('lockedsupplier_' . $product['Product_ID']); ?></td>
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
			<td align="right"><strong>&pound;<?php echo number_format($totalCostBest, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalCostRecent, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalPrice, 2, '.', ','); ?></strong></td>
			<td></td>
		</tr>
	</table>
	<br />
	
	<input type="submit" name="update" value="update" class="btn" />
		  
	<?php
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