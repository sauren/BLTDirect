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
} elseif($action == 'cleanse') {
	$session->Secure(2);
	cleanse();
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
	
	$data = new DataQuery(sprintf("SELECT b.Branch_Name, w.Warehouse_ID FROM branch AS b INNER JOIN warehouse AS w ON w.Type_Reference_ID=b.Branch_ID WHERE w.Type='B' ORDER BY b.Branch_Name ASC"));
	while($data->Row) {
		$form->AddOption('warehouse', $data->Row['Warehouse_ID'], $data->Row['Branch_Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			redirectTo(sprintf('?action=report&warehouse=%d', $form->GetValue('warehouse')));
		}
	}

	$page = new Page('Stock Cleanse Report', 'Please choose a warehouse for your report');
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

	echo $window->Open();
	echo $window->AddHeader('Select a warehouse for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('warehouse'), $form->GetHTML('warehouse').$form->GetIcon('warehouse'));
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	$products = array();
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('warehouse', 'Warehouse', 'hidden', '0', 'numeric_unsigned', 1, 11);

	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Position_Orders_Recent, p.Position_Quantities_3_Month, p.Position_Quantities_12_Month, p.Position_Orders_3_Month, p.Position_Orders_12_Month, p.Product_Title, p.Is_Stocked, p.Is_Stocked_Temporarily, SUM(ws.Cost*ws.Quantity_In_Stock) AS Cost_Registered, SUM(p.CacheBestCost*ws.Quantity_In_Stock) AS Cost_Best, SUM(p.CacheRecentCost*ws.Quantity_In_Stock) AS Cost_Recent, SUM(ws.Quantity_In_Stock) AS Quantity FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID INNER JOIN product AS p ON ws.Product_ID=p.Product_ID AND p.Is_Stocked='N' WHERE w.Warehouse_ID=%d AND p.Product_Type<>'G' GROUP BY p.Product_ID HAVING Quantity=0 ORDER BY p.Position_Orders_Recent, p.Product_ID ASC", mysql_real_escape_string($form->GetValue('warehouse'))));
	while($data->Row) {
		$products[] = $data->Row;
	
		$data->Next();	
	}
	$data->Disconnect();

	$page = new Page('Stock Cleanse Report');
	$page->Display('header');
	?>
	
	<br />
	<h3>Products Not Stocked</h3>
	<br />
	
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Position</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Product Name</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Position Quantities 3 Month</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Position Quantities 12 Month</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Position Orders 3 Month</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Position Orders 12 Month</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Registered Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Best Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Recent Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Current Price</strong> </td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity in Stock</strong> </td>
		</tr>
		  
		<?php
		foreach($products as $product) {
			$data2 = new DataQuery(sprintf("SELECT * FROM product_prices WHERE Product_ID=%d AND Price_Starts_On<=NOW() Order By Price_Starts_On desc", mysql_real_escape_string($product['Product_ID'])));
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo ($product['Position_Orders_Recent'] > 0) ? $product['Position_Orders_Recent'] : ''; ?></td>
				<td><a target="_blank" href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>"><?php echo $product['Product_Title']; ?></a></td>
				<td><?php echo $product['Product_ID']; ?></td>
				<td align="right"><?php echo $product['Position_Quantities_3_Month']; ?></td>
				<td align="right"><?php echo $product['Position_Quantities_12_Month']; ?></td>
				<td align="right"><?php echo $product['Position_Orders_3_Month']; ?></td>
				<td align="right"><?php echo $product['Position_Orders_12_Month']; ?></td>
				<td align="right">&pound;<?php echo number_format($product['Cost_Registered'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($product['Cost_Best'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($product['Cost_Recent'], 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($data2->Row['Price_Base_Our'], 2, '.', ','); ?></td>
				<td align="right"><?php echo $product['Quantity']; ?></td>
			</tr>
					
			<?php
			$data2->Disconnect();
		}
		?>

	</table>
	<br />

	<input class="btn" type="button" name="cleanse" value="cleanse" onclick="window.self.location.href = '?action=cleanse&warehouse=<?php echo $form->GetValue('warehouse'); ?>';" />
	<input class="btn" type="button" name="print" value="print" onclick="popUrl('report_stock_cleanse_print.php?warehouse=<?php echo $form->GetValue('warehouse'); ?>', 800, 600);" />

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function cleanse() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'cleanse', 'alpha', 7, 7);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('warehouse', 'Warehouse', 'hidden', '0', 'numeric_unsigned', 1, 11);

	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Position_Orders_Recent, p.Position_Quantities_3_Month, p.Position_Quantities_12_Month, p.Position_Orders_3_Month, p.Position_Orders_12_Month, p.Product_Title, p.Is_Stocked, p.Is_Stocked_Temporarily, SUM(ws.Cost*ws.Quantity_In_Stock) AS Cost_Registered, SUM(p.CacheBestCost*ws.Quantity_In_Stock) AS Cost_Best, SUM(p.CacheRecentCost*ws.Quantity_In_Stock) AS Cost_Recent, SUM(ws.Quantity_In_Stock) AS Quantity FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID INNER JOIN product AS p ON ws.Product_ID=p.Product_ID AND p.Is_Stocked='N' WHERE w.Warehouse_ID=%d AND p.Product_Type<>'G' GROUP BY p.Product_ID HAVING Quantity=0 ORDER BY p.Position_Orders_Recent, p.Product_ID ASC", mysql_real_escape_string($form->GetValue('warehouse'))));
	while($data->Row) {
		$product = new Product($data->Row['Product_ID']);
		$product->StockMonitor = 'N';
		$product->StockedTemporarily = 'N';
		$product->Update();
		
		$data2 = new DataQuery(sprintf("SELECT ws.Stock_ID FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID WHERE w.Warehouse_ID=%d AND ws.Product_ID=%d", mysql_real_escape_string($form->GetValue('warehouse')), $data->Row['Product_ID']));
		while($data2->Row) {
			new DataQuery(sprintf("DELETE FROM warehouse_stock WHERE Stock_ID=%d", $data2->Row['Stock_ID']));

			$data2->Next();
		}
		$data2->Disconnect();
	
		$data->Next();
	}
	$data->Disconnect();

	redirectTo(sprintf('?action=report&warehouse=%d', $form->GetValue('warehouse')));
}