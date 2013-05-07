<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

$products = array();

$data = new DataQuery(sprintf("SELECT Product_ID, SKU, Product_Title, Stock_Reorder_Quantity, Stock_Level_Alert FROM product WHERE Is_Stocked='Y' AND (Stock_Reorder_Quantity=0 OR Stock_Level_Alert=0) ORDER BY Product_ID ASC"));
while($data->Row) {
	$products[] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

foreach($products as $productData) {
	$form->AddField(sprintf('reorderquantity_%d', $productData['Product_ID']), sprintf('Reorder Quantity for \'%s\'', $productData['Product_Title']), 'text', $productData['Stock_Reorder_Quantity'], 'numeric_unsigned', 1, 11, true, 'size="10"');
	$form->AddField(sprintf('alertlevel_%d', $productData['Product_ID']), sprintf('Alert Level for \'%s\'', $productData['Product_Title']), 'text', $productData['Stock_Level_Alert'], 'numeric_unsigned', 1, 11, true, 'size="10"');
}

if(isset($_REQUEST['confirm'])) {
	foreach($products as $productData) {
		$product = new Product($productData['Product_ID']);
		$product->StockReorderQuantity = $form->GetValue(sprintf('reorderquantity_%d', $productData['Product_ID']));
		$product->StockAlert = $form->GetValue(sprintf('alertlevel_%d', $productData['Product_ID']));
		$product->Update();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

$page = new Page('Product Missing Reorder Report', '');
$page->Display('header');

echo $form->Open();
echo $form->GetHTML('confirm');
?>

<br />
<h3>Missing Products Reorder Details</h3>
<p>Listing all stock profile products with absent reorder details.</p>

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Reorder Quantity</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Alert Level</strong></td>
	</tr>

	<?php
	foreach($products as $productData) {
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');" style="height: 24px;">
			<td><?php echo $productData['Product_ID']; ?></td>
			<td><a href="product_profile.php?pid=<?php echo $productData['Product_ID']; ?>" target="_blank"><?php echo $productData['Product_Title']; ?></a></td>
			<td><?php echo $productData['SKU']; ?></td>
			<td><?php echo $form->GetHTML(sprintf('reorderquantity_%d', $productData['Product_ID'])); ?></td>
			<td><?php echo $form->GetHTML(sprintf('alertlevel_%d', $productData['Product_ID'])); ?></td>
		</tr>

		<?php
	}
	?>

</table>
<br />

<input type="submit" name="update" value="update" class="btn" />

<?php
$data->Disconnect();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');