<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductDownload.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductPrice.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');

$session->Secure();

$cart = new Cart($session, true);
$cart->GetLines();
$cart->Calculate();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 1, 12);
$form->SetValue('action', 'update');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('productName', 'Product Name', 'text', '', 'anything', 0, 60, true, 'style="width: 300px;"');
$form->AddField('productDescription', 'Product Description', 'textarea', '', 'anything', 0, 1000, true, 'style="width: 300px;" rows="5"');
$form->AddField('productPrice', 'Price', 'text', '', 'float', 1, 11, true, 'style="width: 100px;"');
$form->AddField('productSupplier', 'Supplier', 'select', '', 'numeric_unsigned', 1, 11, true, 'style="width: 300px;"');
$form->AddField('shippingClass', 'Shipping Class', 'select', '', 'numeric_unsigned', 1, 11, true, 'style="width: 300px;"');
$form->AddField('demoNotes', 'Demo Notes', 'textarea', '', 'anything', null, null, true, 'style="width: 98%;" rows="3"');
$form->AddField('productCost', 'Cost', 'text', '', 'float', 1, 11, true, 'style="width: 100px;"');
$form->AddField('file', 'File', 'file', '', 'file', null, null, false);

$data = new DataQuery(sprintf("SELECT s.Supplier_ID, p.Name_First, p.Name_Last, o.Org_Name FROM supplier s INNER JOIN contact c on s.Contact_ID =  c.Contact_ID INNER JOIN person p on c.Person_ID = p.Person_ID LEFT JOIN contact c2 on c2.Contact_ID = c.Parent_Contact_ID LEFT JOIN organisation o on c2.Org_ID = o.Org_ID"));
while($data->Row) {
	$form->AddOption('productSupplier', $data->Row['Supplier_ID'], (strlen($data->Row['Org_Name']) > 0) ?  sprintf('%s', $data->Row['Org_Name']) : sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last']));

	$data->Next();
}
$data->Disconnect();

$shipping = new DataQuery("select * from shipping_class");
do{
	$form->AddOption('shippingClass',
	$shipping->Row['Shipping_Class_ID'],
	$shipping->Row['Shipping_Class_Title']);
	$shipping->Next();
} while($shipping->Row);
$shipping->Disconnect();

if(($action == 'update') && (strtolower($_REQUEST['confirm']) == 'true')){
	if($form->Validate()){
		$product = new Product();
		$product->IsDemo = 'Y';
		$product->DemoNotes = $form->GetValue('demoNotes');
		$product->Name = $form->GetValue('productName');
		$product->Description = $form->GetValue('productDescription');
		$product->MetaTitle = $product->Name;
		$product->MetaDescription = $product->Name;
		$product->ShippingClass->ID = $form->GetValue('shippingClass');

		$data = new DataQuery(sprintf("SELECT Shipping_Class_ID FROM shipping_class WHERE Is_Default='Y'"));
		if($data->TotalRows > 0) {
			$product->ShippingClass->ID = $data->Row['Shipping_Class_ID'];
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT Tax_Class_ID FROM tax_class WHERE Is_Default='Y'"));
		if($data->TotalRows > 0) {
			$product->TaxClass->ID = $data->Row['Tax_Class_ID'];
		}
		$data->Disconnect();

		$product->Add();

		$price = new ProductPrice();
		$price->PriceOurs = $form->GetValue('productPrice');
		$price->PriceRRP = 0;
		$price->IsTaxIncluded = 'N';
		$price->Quantity = 1;
		$price->ProductID = $product->ID;
		$price->Add();

		$sup = new SupplierProduct();
		$sup->PreferredSup = 'Y';
		$sup->Supplier->ID = $form->GetValue('productSupplier');
		$sup->Product->ID = $product->ID;
		$sup->Cost = $form->GetValue('productCost');
		$sup->Add();
		
		$cart->AddLine($product->ID, 1);
		
		$download = new ProductDownload();
		$download->productId = $product->ID;
		$download->name = 'Download';
		$download->add('file');

		redirect(sprintf("Location: order_cart.php"));
	}
}

$page = new Page('Create a New Order Manually', '');
$page->Display('header');
?>
<table width="100%" border="0">
  <tr>
    <td width="300" valign="top"><?php include('./order_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top"><p><strong>Add New Demo Product</strong></p>

		<?php
		if(!$form->Valid) {
			echo $form->GetError();
		}

		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		?>

		<br />

		<p><strong>Product Name:</strong><br />
		<?php echo $form->GetHtml('productName'); ?></p>

		<p><strong>Product Description:</strong><br />
		<?php echo $form->GetHtml('productDescription'); ?></p>
		
		<p><strong>Download File:</strong><br />
		<?php echo $form->GetHtml('file'); ?></p>

		<p><strong>Price:</strong> (&pound;)<br />
		<?php echo $form->GetHtml('productPrice'); ?></p>

		<p><strong>Cost:</strong> (&pound;)<br />
		<?php echo $form->GetHtml('productCost'); ?></p>

		<p><strong>Supplier:</strong><br />
		<?php echo $form->GetHtml('productSupplier'); ?></p>

		<p><strong>Shipping Class:</strong><br />
		<?php echo $form->GetHtml('shippingClass'); ?></p>
		
		<p><strong>Demo Notes:</strong><br />
		<?php echo $form->GetHtml('demoNotes'); ?></p>

		<input type="submit" name="add" value="add" class="btn" />

		<?php echo $form->Close(); ?>

	</td>
  </tr>
</table>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');