<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Reserve.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReserveItem.php');

$products = array();

$data = new DataQuery(sprintf("SELECT p.Product_ID, p.SKU, p.Product_Title, p.DropSupplierID, IF(p.DropSupplierExpiresOn<>'0000-00-00 00:00:00', DATE(p.DropSupplierExpiresOn), '') AS DropSupplierExpiresOn, p.DropSupplierQuantity, IF(c.Parent_Contact_ID>0, CONCAT_WS(' ', o.Org_Name, CONCAT('(', CONCAT_WS(' ', pr.Name_First, pr.Name_Last), ')')), CONCAT_WS(' ', pr.Name_First, pr.Name_Last)) AS DropSupplier, sp.Cost, sp.Cost*p.DropSupplierQuantity AS TotalCost, wr.quantity AS Quantity_Reserve FROM product AS p INNER JOIN supplier AS s ON s.Supplier_ID=p.DropSupplierID LEFT JOIN supplier_product AS sp ON sp.Supplier_ID=s.Supplier_ID AND sp.Product_ID=p.Product_ID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS pr ON pr.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID LEFT JOIN warehouse AS w ON w.Type_Reference_ID=p.DropSupplierID AND w.Type='S' LEFT JOIN warehouse_reserve AS wr ON wr.warehouseId=w.Warehouse_ID AND wr.productId=p.Product_ID WHERE p.DropSupplierID>0 AND p.DropSupplierReserved='N' ORDER BY DropSupplier ASC, p.Product_Title ASC"));
while($data->Row) {
	$products[] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

if(isset($_REQUEST['confirm'])) {
	$supplierProducts = array();

	foreach($products as $productData) {
		if($productData['DropSupplierQuantity'] > 0) {
			$supplierProducts[$productData['DropSupplierID']][] = $productData;
		}
	}

	foreach($supplierProducts as $supplierId=>$supplierProduct) {
		$reserve = new Reserve();
		$reserve->supplier->ID = $supplierId;
		$reserve->add();

		foreach($supplierProduct as $productData) {
			$reserveItem = new ReserveItem();
			$reserveItem->reserveId = $reserve->id;
			$reserveItem->product->ID = $productData['Product_ID'];
			$reserveItem->quantity = $productData['DropSupplierQuantity'];
			$reserveItem->quantityRemaining = $reserveItem->quantity;
			$reserveItem->add();

			$product = new Product($productData['Product_ID']);
			$product->DropSupplierReserved = 'Y';
			$product->Update();
		}
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

$page = new Page('Reserve Stock Report', '');
$page->Display('header');

echo $form->Open();
echo $form->GetHTML('confirm');
?>

<br />
<h3>Drop Supplied Products</h3>
<br />

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Supplier</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Quantity</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Expires</strong></td>
		<td style="border-bottom:1px solid #aaaaaa;"><strong>Quantity Reserved</strong></td>
	</tr>

	<?php
	foreach($products as $productData) {
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><?php echo $productData['Product_ID']; ?></td>
			<td><a href="product_profile.php?pid=<?php echo $productData['Product_ID']; ?>" target="_blank"><?php echo $productData['Product_Title']; ?></a></td>
			<td><?php echo $productData['SKU']; ?></td>
			<td><?php echo $productData['DropSupplier']; ?></td>
			<td><?php echo $productData['DropSupplierQuantity']; ?></td>
			<td><?php echo $productData['DropSupplierExpiresOn']; ?></td>
			<td><?php echo $productData['Quantity_Reserve']; ?></td>
		</tr>

		<?php
	}
	?>

</table>
<br />

<input type="submit" name="reserve" value="reserve" class="btn" />

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');