<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	
if($action == 'report') {
	$session->Secure(2);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			redirect(sprintf('Location: ?action=report&parent=%d&subfolders=%s', $form->GetValue('parent'), $form->GetValue('subfolders')));
		}
	}

	$page = new Page('Product Costs Report', 'Select report criteria.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Products Costs.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Filter out products sold for particular orders.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->AddRow('','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Subfolders', 'hidden', 'N', 'boolean', 1, 1, false);
	
	$page = new Page('Product Costs Report', '');
	$page->Display('header');

	$sqlSelect = sprintf('SELECT p.Product_ID, p.Product_Title, o.Quantity AS Quantity_3_Months, o2.Quantity AS Quantity_12_Months, o.Cost AS Cost_3_Months, o2.Cost AS Cost_12_Months, o.Price AS Price_3_Months, o2.Price AS Price_12_Months ');
	$sqlFrom = sprintf('FROM product AS p LEFT JOIN (SELECT ol.Product_ID, SUM(ol.Quantity) AS Quantity, SUM(ol.Cost * ol.Quantity) AS Cost, SUM(ol.Line_Total - ol.Line_Discount) AS Price FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE o.Created_On>=ADDDATE(NOW(), INTERVAL -3 MONTH) GROUP BY ol.Product_ID) AS o ON o.Product_ID=p.Product_ID LEFT JOIN (SELECT ol.Product_ID, SUM(ol.Quantity) AS Quantity, SUM(ol.Cost * ol.Quantity) AS Cost, SUM(ol.Line_Total - ol.Line_Discount) AS Price FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE o.Created_On>=ADDDATE(NOW(), INTERVAL -12 MONTH) GROUP BY ol.Product_ID) AS o2 ON o2.Product_ID=p.Product_ID ');
	$sqlWhere = sprintf('WHERE p.Discontinued=\'N\' ');
	$sqlMisc = sprintf('ORDER BY p.Product_Title ASC');

	if($form->GetValue('parent') != 0) {
		$sqlFrom .= sprintf('INNER JOIN product_in_categories AS c ON c.Product_ID=p.Product_ID ');

		if($form->GetValue('subfolders') == 'Y') {
			$sqlWhere .= sprintf('AND (c.Category_ID=%d %s) ', mysql_real_escape_string($form->GetValue('parent')), mysql_real_escape_string(getCategories($form->GetValue('parent'))));
		} else {
			$sqlWhere .= sprintf('AND c.Category_ID=%d ', mysql_real_escape_string($form->GetValue('parent')));
		}
	}
	?>

	<br />
	<h3>Products</h3>
	<p>Listing products with two lowest supplier costs.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Product ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Name</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Sold</strong><br />(3 Months)</td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Sold</strong><br />(12 Months)</td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Revenue</strong><br />(3 Months)</td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Revenue</strong><br />(12 Months)</td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Profit</strong><br />(3 Months)</td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Profit</strong><br />(12 Months)</td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Price</strong></td>
		</tr>

		<?php
		$totalQuantity3 = 0;
		$totalQuantity12 = 0;
		$totalRevenue3 = 0;
		$totalRevenue12 = 0;
		$totalProfit3 = 0;
		$totalProfit12 = 0;
		
		$data = new DataQuery(sprintf("%s%s%s%s", $sqlSelect, $sqlFrom, $sqlWhere, $sqlMisc));
		while($data->Row) {
			$product = new Product($data->Row['Product_ID']);
			
			$totalQuantity3 += $data->Row['Quantity_3_Months'];
			$totalQuantity12 += $data->Row['Quantity_12_Months'];
			$totalRevenue3 += $data->Row['Price_3_Months'];
			$totalRevenue12 += $data->Row['Price_12_Months'];
			$totalProfit3 += $data->Row['Price_3_Months'] - $data->Row['Cost_3_Months'];
			$totalProfit12 += $data->Row['Price_12_Months'] - $data->Row['Cost_12_Months'];
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo $data->Row['Product_ID']; ?></td>
				<td><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>"><?php echo strip_tags($data->Row['Product_Title']); ?></a></td>
				<td align="right"><?php echo $data->Row['Quantity_3_Months']; ?></td>
				<td align="right"><?php echo $data->Row['Quantity_12_Months']; ?></td>
				<td align="right">&pound;<?php echo number_format($data->Row['Price_3_Months'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($data->Row['Price_12_Months'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($data->Row['Price_3_Months'] - $data->Row['Cost_3_Months'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($data->Row['Price_12_Months'] - $data->Row['Cost_12_Months'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo $product->PriceCurrent; ?></td>
			</tr>
			
			<?php
			$data2 = new DataQuery(sprintf("SELECT sp.Cost, IF(c2.Contact_ID IS NULL, CONCAT_WS(' ', p.Name_First, p.Name_Last), CONCAT_WS(' ', o.Org_Name, CONCAT('(', CONCAT_WS(' ', p.Name_First, p.Name_Last), ')'))) AS Supplier FROM supplier_product AS sp INNER JOIN supplier AS s ON s.Supplier_ID=sp.Supplier_ID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID LEFT JOIN person AS p ON p.Person_ID=c.Person_ID WHERE sp.Cost>0 AND sp.Product_ID=%d ORDER BY sp.Cost ASC LIMIT 0, 2", $data->Row['Product_ID']));
			while($data2->Row) {
				?>
				
				<tr>
					<td></td>
					<td colspan="7"><?php echo $data2->Row['Supplier']; ?></td>
					<td align="right">&pound;<?php echo $data2->Row['Cost']; ?></td>
				</tr>
				
				<?php
				$data2->Next();
			}
			$data2->Disconnect();
			
			$data->Next();
		}
		$data->Disconnect();
		?>
			
		<tr>
			<td></td>
			<td></td>
			<td align="right"><strong><?php echo $totalQuantity3; ?></strong></td>
			<td align="right"><strong><?php echo $totalQuantity12; ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalRevenue3, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalRevenue12, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalProfit3, 2, '.', ','); ?></strong></td>
			<td align="right"><strong>&pound;<?php echo number_format($totalProfit12, 2, '.', ','); ?></strong></td>
			<td></td>
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