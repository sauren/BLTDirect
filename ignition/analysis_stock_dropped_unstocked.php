<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(2);
view();
exit;

function view() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('months', 'Months Supply', 'select', '1', 'numeric_unsigned', 1, 11);

	for($i=1; $i<=12; $i++) {
		$form->AddOption('months', $i, $i);
	}

	$unstockedGrouped = array();
	
	$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Title, p.SKU, COUNT(DISTINCT ol.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantities FROM (SELECT ol.Product_ID, ol.Order_ID, ol.Quantity FROM order_line AS ol INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'S\' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 UNION ALL SELECT pc.Product_ID, ol.Order_ID, ol.Quantity*pc.Component_Quantity AS Quantity FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type=\'S\' INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0) AS ol INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND (p.LockedSupplierID=0 OR p.Is_Stocked=\'N\') GROUP BY ol.Product_ID ORDER BY Orders DESC', mysql_real_escape_string($form->GetValue('months')), mysql_real_escape_string($form->GetValue('months'))));
	while($data->Row) {
		$unstockedGrouped[] = $data->Row;

		$data->Next();	
	}
	$data->Disconnect();

	$page = new Page('Analysis / Stock Dropped Unstocked', 'Analysing dropped stock for unstocked products.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Analysis parameters');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Configure your analysis parameters here.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('months'), $form->GetHTML('months'));
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	?>

	<br />
	<h3>Dropped Not Locked Or Not Stocked Products Grouped</h3>
	<br />

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Orders</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity</strong></td>
		</tr>

		<?php
		if(!empty($unstockedGrouped)) {
			foreach($unstockedGrouped as $stockedData) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $stockedData['Product_Title']; ?></td>
					<td><?php echo $stockedData['SKU']; ?></td>
					<td><a href="product_profile.php?pid=<?php echo $stockedData['Product_ID']; ?>" target="_blank"><?php echo $stockedData['Product_ID']; ?></a></td>
					<td align="right"><?php echo $stockedData['Orders']; ?></td>
					<td align="right"><?php echo $stockedData['Quantities']; ?></td>
				</tr>
				
				<?php
			}
		} else {
			?>
			
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="5" align="center">There are no items available for viewing.</td>
			</tr>
			
			<?php			
		}
		?>
		
	</table>
		
	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}