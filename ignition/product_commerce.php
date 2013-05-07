<?php
require_once ('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$product = new Product($_REQUEST['pid']);

$form = new Form("product_commerce.php");
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('pid', 'ID', 'hidden', $product->ID, 'numeric_unsigned', 1, 11);
$form->AddField('salesStart', 'Start Sales On', 'datetime', $product->SalesStart, 'datetime', (cDatetime(getDatetime(), 'y') - 1), (cDatetime(getDatetime(), 'y') + 10));
$form->AddField('salesEnd', 'End Sales On', 'datetime', $product->SalesEnd, 'datetime', (cDatetime(getDatetime(), 'y') - 1), (cDatetime(getDatetime(), 'y') + 10));
$form->AddField('active', 'Active Product', 'checkbox', $product->IsActive, 'boolean', 1, 1, false);
$form->AddField('returnable', 'Non Returnable', 'checkbox', $product->IsNonReturnable, 'boolean', 1, 1, false);
$form->AddField('dangerous', 'Dangerous Item', 'checkbox', $product->IsDangerous, 'boolean', 1, 1, false);
$form->AddField('profitcontrol', 'Profit Control', 'checkbox', $product->IsProfitControl, 'boolean', 1, 1, false);
$form->AddField('automaticreview', 'Automatic Review', 'checkbox', $product->IsAutomaticReview, 'boolean', 1, 1, false);
$form->AddField('discontinuedshowprice', 'Discontinued Show Price', 'checkbox', $product->DiscontinuedShowPrice, 'boolean', 1, 1, false);
$form->AddField('despatchMin', 'Estimated Despatch Days (min)', 'text', $product->DespatchDaysMin, 'numeric_unsigned', 1, 2, false);
$form->AddField('despatchMax', 'Estimated Despatch Days (max)', 'text', $product->DespatchDaysMax, 'numeric_unsigned', 1, 2, false);
$form->AddField('guarantee', 'Product Guarantee (days)', 'text', $product->Guarantee, 'numeric_unsigned', 1, 10, false);
$form->AddField('orderMin', 'Min Order Quantity', 'text', $product->OrderMin, 'numeric_unsigned', 1, 5, false);
$form->AddField('orderMax', 'Max Order Quantity', 'text', $product->OrderMax, 'numeric_unsigned', 1, 5, false);
$form->AddField('discountLimit', 'Discount Limit (%)', 'text', $product->DiscountLimit, 'numeric_unsigned', 1, 11, false);
$form->AddField('band', 'Product Band', 'select', $product->Band->ID, 'numeric_unsigned', 1, 11, false);
$form->AddOption('band', '0', 'Select Band/Type');

$data = new DataQuery("SELECT * FROM product_band");
do {
	$form->AddOption('band', $data->Row['Product_Band_ID'], sprintf("%s - %s", $data->Row['Band_Ref'], $data->Row['Band_Title']));
	$data->Next();
} while ($data->Row);
$data->Disconnect();

$form->AddField('shippingClass', 'Shipping Class', 'select', $product->ShippingClass->ID, 'numeric_unsigned', 1, 11);
$form->AddOption('shippingClass', '', 'Select Shipping Class');

$shipping = new DataQuery("select * from shipping_class");
do {
	$form->AddOption('shippingClass', $shipping->Row['Shipping_Class_ID'], $shipping->Row['Shipping_Class_Title']);
	$shipping->Next();
} while ($shipping->Row);
$shipping->Disconnect();

$form->AddField('taxClass', 'Tax Class', 'select', $product->TaxClass->ID, 'numeric_unsigned', 1, 11);
$form->AddOption('taxClass', '0', '(Default)');

$tax = new DataQuery("SELECT * FROM tax_class");
do {
	$form->AddOption('taxClass', $tax->Row['Tax_Class_ID'], $tax->Row['Tax_Class_Title']);
	$tax->Next();
} while ($tax->Row);
$tax->Disconnect();

if (isset($_REQUEST['confirm'])) {
	if ($form->Validate()) {
		$product->SalesStart = $form->GetValue('salesStart');
		$product->SalesEnd = $form->GetValue('salesEnd');
		$product->IsActive = $form->GetValue('active');
		$product->IsNonReturnable = $form->GetValue('returnable');
		$product->IsDangerous = $form->GetValue('dangerous');
		$product->IsProfitControl = $form->GetValue('profitcontrol');
		$product->IsAutomaticReview = $form->GetValue('automaticreview');
		$product->DiscontinuedShowPrice = $form->GetValue('discontinuedshowprice');
		$product->DespatchDaysMin = $form->GetValue('despatchMin');
		$product->DespatchDaysMax = $form->GetValue('despatchMax');
		$product->Guarantee = $form->GetValue('guarantee');
		$product->OrderMin = $form->GetValue('orderMin');
		$product->OrderMax = $form->GetValue('orderMax');
		$product->ShippingClass->ID = $form->GetValue('shippingClass');
		$product->TaxClass->ID = $form->GetValue('taxClass');
		$product->Band->ID = $form->GetValue('band');

		if(is_numeric($form->GetValue('discountLimit')) && $form->GetValue('discountLimit') >= 0 && $form->GetValue('discountLimit') <= 100){
			$product->DiscountLimit = $form->GetValue('discountLimit');
		} else {
			$product->DiscountLimit = null;
		}

		$product->Update();

		redirect(sprintf("Location: product_profile.php?pid=%d", $product->ID));
	}
}

$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> > Commerce Settings', $_REQUEST['pid']), 'The more information you supply the better your system will become');
$page->Display('header');

if (!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Update');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('pid');

echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('salesStart'), $form->GetHTML('salesStart') . $form->GetIcon('salesStart'));
echo $webForm->AddRow($form->GetLabel('salesEnd'), $form->GetHTML('salesEnd') . $form->GetIcon('salesEnd'));
echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
echo $webForm->AddRow($form->GetLabel('returnable'), $form->GetHTML('returnable') . $form->GetIcon('returnable'));
echo $webForm->AddRow($form->GetLabel('dangerous'), $form->GetHTML('dangerous') . $form->GetIcon('dangerous'));
echo $webForm->AddRow($form->GetLabel('profitcontrol'), $form->GetHTML('profitcontrol') . $form->GetIcon('profitcontrol'));
echo $webForm->AddRow($form->GetLabel('automaticreview'), $form->GetHTML('automaticreview') . $form->GetIcon('automaticreview'));
echo $webForm->AddRow($form->GetLabel('discontinuedshowprice'), $form->GetHTML('discontinuedshowprice') . $form->GetIcon('discontinuedshowprice'));
echo $webForm->AddRow($form->GetLabel('despatchMin'), $form->GetHTML('despatchMin') . $form->GetIcon('despatchMin'));
echo $webForm->AddRow($form->GetLabel('despatchMax'), $form->GetHTML('despatchMax') . $form->GetIcon('despatchMax'));
echo $webForm->AddRow($form->GetLabel('guarantee'), $form->GetHTML('guarantee') . $form->GetIcon('guarantee'));
echo $webForm->AddRow($form->GetLabel('orderMin'), $form->GetHTML('orderMin') . $form->GetIcon('orderMin'));
echo $webForm->AddRow($form->GetLabel('orderMax'), $form->GetHTML('orderMax') . $form->GetIcon('orderMax'));
echo $webForm->AddRow($form->GetLabel('discountLimit'), $form->GetHTML('discountLimit') . $form->GetIcon('discountLimit') . ' <i>leave blank for no limit</i>');
echo $webForm->AddRow($form->GetLabel('band'), $form->GetHTML('band') . $form->GetIcon('band'));
echo $webForm->AddRow($form->GetLabel('shippingClass'), $form->GetHTML('shippingClass') . $form->GetIcon('shippingClass'));
echo $webForm->AddRow($form->GetLabel('taxClass'), $form->GetHTML('taxClass') . $form->GetIcon('taxClass'));
echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_profile.php?pid=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $product->ID, $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$page->Display('footer');

require_once ('lib/common/app_footer.php');