<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('collection', 'Product Collection', 'select', '', 'numeric_unsigned', 1, 11, true);
	$form->AddOption('collection', '', '');

	$data = new DataQuery(sprintf("SELECT * FROM product_collection ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('collection', $data->Row['ProductCollectionID'], $data->Row['Name']);
		
		$data->Next();
	}
	$data->Disconnect();
		
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			report($form->GetValue('collection'));
			exit;
		}
	}

	$page = new Page('Warehouse Savings Report', 'Please choose a collection for your report');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow("Report on Warehouse Savings.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select a product count for this report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('collection'), $form->GetHTML('collection'));
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

function report($collectionId) {
	$page = new Page('Warehouse Saving Reports');
	$page->Display('header');

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_order SELECT pca.ProductID AS Product_ID, SUM(ol.Quantity) AS Quantity, SUM(ol.Cost*ol.Quantity)/SUM(ol.Quantity) AS Average_Cost, SUM(ol.Cost*ol.Quantity) AS Total_Cost FROM product_collection_assoc AS pca INNER JOIN order_line AS ol ON ol.Product_ID=pca.ProductID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -12 MONTH) WHERE pca.ProductCollectionID=%d GROUP BY pca.ProductID", mysql_real_escape_string($collectionId)));
	new DataQuery(sprintf("ALTER TABLE temp_order ADD INDEX Product_ID (Product_ID)"));
	
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_purchase SELECT pca.ProductID AS Product_ID, pl.Purchase_Line_ID, pl.Cost FROM product_collection_assoc AS pca INNER JOIN purchase_line AS pl ON pl.Product_ID=pca.ProductID INNER JOIN purchase AS p ON p.Purchase_ID=pl.Purchase_ID WHERE pca.ProductCollectionID=%d AND p.Price_Enquiry_ID>0", mysql_real_escape_string($collectionId)));
	new DataQuery(sprintf("ALTER TABLE temp_purchase ADD INDEX Product_ID (Product_ID)"));
	new DataQuery(sprintf("ALTER TABLE temp_purchase ADD INDEX Purchase_Line_ID (Purchase_Line_ID)"));

	$used = array();
	
	$data = new DataQuery(sprintf("SELECT Product_ID, Purchase_Line_ID FROM temp_purchase ORDER BY Purchase_Line_ID DESC"));
	while($data->Row) {
		if(!isset($used[$data->Row['Product_ID']])) {
			$used[$data->Row['Product_ID']] = true;
		} else {
			new DataQuery(sprintf("DELETE FROM temp_purchase WHERE Purchase_Line_ID=%d", $data->Row['Purchase_Line_ID']));
		}
		
		$data->Next();
	}
	$data->Disconnect();
	
	$reportData = array();
	
	$data = new DataQuery(sprintf("SELECT pca.ProductID AS Product_ID, p.Product_Title, tu.Quantity, tu.Average_Cost AS Average_Supply_Cost, tu.Total_Cost AS Total_Supply_Cost, tp.Cost AS Purchase_Cost FROM product_collection_assoc AS pca INNER JOIN product AS p ON p.Product_ID=pca.ProductID LEFT JOIN temp_order AS tu ON tu.Product_ID=pca.ProductID LEFT JOIN temp_purchase AS tp ON tp.Product_ID=pca.ProductID WHERE pca.ProductCollectionID=%d ORDER BY p.Product_Title ASC", mysql_real_escape_string($collectionId)));
	while($data->Row) {
		$reportData[] = $data->Row;
		
		$data->Next();
	}
	$data->Disconnect();
	?>
	
	<br />
	<h3>Estimated Savings</h3>
	<p>Estimated savings made by stocking products in warehouse rather than supplying on demand during the last 12 months.</p>
	
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;" align="left"><strong>Product ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="left"><strong>Product</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="left"><strong>Quantity Sold</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Average Supply Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Total Supply Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Purchase Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Total Purchase Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Saving</strong></td>
		</tr>
	
		<?php
		$total = 0;
		
		foreach($reportData as $item) {
			if($item['Purchase_Cost'] > 0) {
				$saving = $item['Total_Supply_Cost'] - ($item['Purchase_Cost'] * $item['Quantity']);
				?>
		
				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td align="left"><?php echo $item['Product_ID']; ?></td>
					<td align="left"><a href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>" target="_blank"><?php echo $item['Product_Title']; ?></a></td>
					<td align="left"><?php echo $item['Quantity']; ?></td>
					<td align="right"><?php echo number_format($item['Average_Supply_Cost'], 2, '.', ','); ?></td>
					<td align="right"><?php echo number_format($item['Total_Supply_Cost'], 2, '.', ','); ?></td>
					<td align="right"><?php echo number_format($item['Purchase_Cost'], 2, '.', ','); ?></td>
					<td align="right"><?php echo number_format($item['Purchase_Cost'] * $item['Quantity'], 2, '.', ','); ?></td>
					<td align="right"><?php echo sprintf('%s%s', ($saving > 0) ? '+' : '', number_format($saving, 2, '.', ',')); ?></td>
				</tr>
		
				<?php
				$total += $saving;
			}
		}
		?>
		
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td align="right"><strong><?php echo sprintf('%s%s', ($saving > 0) ? '+' : '', number_format($total, 2, '.', ',')); ?></strong></td>
		</tr>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}