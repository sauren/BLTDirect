<?php require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CategoryBreadCrumb.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');

$product = new Product();

if(param('product')) {
	$productId = param('product');
	$productId = str_replace($GLOBALS['PRODUCT_PREFIX'], '', $productId);
	if(is_numeric($productId)) {
		$product->ID = $productId;
	}
}

if(!$product->Get()) {
	redirectTo('index.php');
}

$productsAdded = array();
$productsAdded[] = $product->ID;

if(id_param('quantityText')){
    $productQty = id_param('quantityText');
} else if(id_param('quantity')){ 
    $productQty = id_param('quantity');
} else {
    $productQty = 1;
}
$productCat = id_param('category', 0);

// new security check for direct
$redirectStr = param('direct');
if(!empty($redirectStr)){
	$redirectStr = (strlen(strip_tags($redirectStr)) == strlen($redirectStr))?$redirectStr:'';
}

$product->GetOptions();

$action = param('action', '');
$action = strtolower($action);

if($action == 'customise') {
$cartLineId = $cart->AddLine($product->ID, $productQty);
	
	if(($cartLineId !== false) && (count($product->Options->Group) > 0)) {
		$_SESSION['CartLineID'] = $cartLineId;
		
		redirectTo(sprintf('?quantity=%d&category=%d&product=%s%s', $productQty, $productCat, $product->PublicID(), ($redirectStr) ? sprintf('&direct=%s', urlencode(urldecode($redirectStr))) : ''));
	} else {
		if(!empty($redirectStr)) {
			$_SESSION['Cart'] = 'added';
			$_SESSION['CartLineID'] = $cartLineId;

			$product->GetRelatedByType('Energy Saving Alternative');
			
			if(!empty($product->RelatedType['Energy Saving Alternative'])) {
				redirectTo(sprintf('productSwitching.php?id=%d&direct=%s', $cartLineId, $redirectStr));
			}
			
			if(!preg_match('/^([a-z]+):\/\//i', urldecode($redirectStr))) {
				redirectTo(urldecode($redirectStr));	
			}
			
			redirectTo('cart.php');
		}
		
		redirectTo('cart.php');
	}
}

$htmlBuffer = '';

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 6);
$form->SetValue('action', 'add');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('direct', 'Direct', 'hidden', '', 'anything');
$form->AddField('product', 'Product', 'hidden', $product->PublicID(), 'numeric_unsigned', 1, 11);
$form->AddField('category', 'Category', 'hidden', $productCat, 'numeric_unsigned', 1, 11);
$form->AddField('quantity', 'Quantity', 'hidden', $productQty, 'numeric_unsigned', 1, 11);

for($i=0; $i < count($product->Options->Group); $i++){
	$htmlBuffer .= sprintf("<h3 class=\"productOptionGroup\">%s</h3><br /><p>%s</p><p>\n",
						$product->Options->Group[$i]->Name,
						$product->Options->Group[$i]->Description
						);
	$pog = "pog_" . $product->Options->Group[$i]->ID;
	if(strtolower($product->Options->Group[$i]->Type) == 's'){
		$form->AddField($pog, $product->Options->Group[$i]->Name, 'radio', '', 'numeric_unsigned', 1, 11, false);
	}

	for($j=0; $j < count($product->Options->Group[$i]->Item); $j++){
		$optionInput = "";
		$optionLabel = "";
		$optionPrice = 0;
		$optionGroup = "";
		$optionId = "";

		switch(strtolower($product->Options->Group[$i]->Type)){
			case 'm':
				$form->AddField($pog . "_" . $product->Options->Group[$i]->Item[$j]->ID, $product->Options->Group[$i]->Item[$j]->Name, 'checkbox', $product->Options->Group[$i]->Item[$j]->IsSelected, 'boolean', NULL, NULL, false);
				$optionGroup = $pog . "_" . $product->Options->Group[$i]->Item[$j]->ID;

				if($action == 'add' && param('confirm')){
					if(strtolower($form->GetValue($optionGroup)) == 'y' && $product->Options->Group[$i]->Item[$j]->UseProductID > 0){
						$cart->AddLine($product->Options->Group[$i]->Item[$j]->UseProductID, $product->Options->Group[$i]->Item[$j]->Quantity * $productQty);
						$productsAdded[] = $product->Options->Group[$i]->Item[$j]->UseProductID;
					}
				}
				break;
			case 's':
				$form->AddOption($pog, $product->Options->Group[$i]->Item[$j]->ID, $product->Options->Group[$i]->Item[$j]->Name);
				if((strtolower($product->Options->Group[$i]->Item[$j]->IsSelected) == 'y') && (!param('action') || $action != 'add')) $form->SetValue($pog, $product->Options->Group[$i]->Item[$j]->ID);
				$optionGroup = $pog;
				$optionId = $j+1;
				if($action == 'add' && param('confirm')){
					if($form->GetValue($pog) == $product->Options->Group[$i]->Item[$j]->ID && $product->Options->Group[$i]->Item[$j]->UseProductID > 0){
						$cart->AddLine($product->Options->Group[$i]->Item[$j]->UseProductID, $product->Options->Group[$i]->Item[$j]->Quantity * $productQty);
						$productsAdded[] = $product->Options->Group[$i]->Item[$j]->UseProductID;
					}
				}
				break;
		}

		$optionInput = $form->GetHtml($optionGroup, $optionId);
		$optionLabel = $form->GetLabel($optionGroup, $optionId);

		if($product->Options->Group[$i]->Item[$j]->Quantity > 1){
			$optionLabel = $product->Options->Group[$i]->Item[$j]->Quantity . " x " . $optionLabel;
		}

		if($product->Options->Group[$i]->Item[$j]->UseProductID > 0){
			$tempProduct = new Product($product->Options->Group[$i]->Item[$j]->UseProductID);
			$optionPrice = $tempProduct->PriceCurrent * $product->Options->Group[$i]->Item[$j]->Quantity;
		} else {
			$optionPrice = $product->Options->Group[$i]->Item[$j]->Price * $product->Options->Group[$i]->Item[$j]->Quantity;
		}

		if($optionPrice == 0){
			$optionPrice = '';
		} else {
			$optionPrice = '(+&pound;' . number_format($optionPrice, 2, '.', ',') . ')';
		}

		$htmlBuffer .= sprintf('%s %s %s<br />', $optionInput, $optionLabel, $optionPrice);
	}
}

if(($action == 'add') && param('confirm')) {
	if(strlen($form->GetValue('direct')) > 0) {
		$_SESSION['Cart'] = 'added';
		
		$product->GetRelatedByType('Energy Saving Alternative');
			
		if(!empty($product->RelatedType['Energy Saving Alternative'])) {
			redirectTo(sprintf('productSwitching.php?id=%d&direct=%s', $cartLineId, $redirectStr));
		}

		redirectTo(urldecode($redirectStr));
	}
	
	redirect("Location: cart.php");
}

$breadCrumb = new CategoryBreadCrumb();
$breadCrumb->Get($productCat, true);
?>
                    <?php
                    $shownCustomPrice = false;
					
					if($session->IsLoggedIn) {
						if($session->Customer->Contact->IsTradeAccount == 'N') {
							if(count($discountCollection->Line) > 0){
								list($discountAmount, $discountName) = $discountCollection->DiscountProduct($product, 1);

								if($discountAmount < $product->PriceCurrent)  {
				  					$shownCustomPrice = true;
				  					
				  					$product->PriceCurrent = $discountAmount;
				  					
				  					$product->PriceCurrentIncTax = $product->PriceCurrent + $globalTaxCalculator->GetTax($discountAmount, $product->TaxClass->ID);
				  					$product->PriceCurrentIncTax = round($product->PriceCurrentIncTax, 2);
								}
							}
						}
					}

					if(!$shownCustomPrice) {
						if($session->Customer->Contact->IsTradeAccount == 'Y') {
							$retailPrice = $product->PriceCurrent;
							$tradeCost = ($product->CacheRecentCost > 0) ? $product->CacheRecentCost : $product->CacheBestCost;
							
							$product->PriceOurs = ContactProductTrade::getPrice($session->Customer->Contact->ID, $product->ID);
							$product->PriceOurs = ($product->PriceOurs <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $product->ID) / 100) + 1) : $product->PriceOurs;
							
							$product->PriceCurrent = $product->PriceOurs;
							
							$product->PriceCurrentIncTax = $product->PriceCurrent + $globalTaxCalculator->GetTax($product->PriceCurrent, $product->TaxClass->ID);
							$product->PriceCurrentIncTax = round($product->PriceCurrentIncTax, 2);

							$product->PriceSaving = $retailPrice - $product->PriceCurrent;
							$product->PriceSavingPercent = round(($product->PriceSaving / $retailPrice) * 100);
						}
					}
					?>
                    <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Added to Cart</span></div>
                    <div class="maincontent">
                   <div class="maincontent1">								
<?php /*?>			<p class="breadcrumb"><a href="index.php" title="Light Bulbs, Lamps and Tubes Direct Home Page">Home</a> / <a href="products.php">Products</a> <?php if(isset($breadCrumb)) echo $breadCrumb->Text; ?></p><?php */?>
			<table border="0" cellpadding="5" cellspacing="0">
				<tr>
					<td><img src="<?php echo (empty($product->DefaultImage->Thumb->FileName) || !file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$product->DefaultImage->Thumb->FileName))?"images/template/image_coming_soon_3.jpg":"images/products/".$product->DefaultImage->Thumb->FileName; ?>" border="0" /></td>
					<td>
						<strong><?php echo $productQty . " x " . $product->Name; ?></strong><br />
						<span class="currentPrice">&pound;<?php echo number_format($productQty * $product->PriceCurrent, 2, '.', ','); ?></span><br />
						<span class="smallGreyText">Excludes VAT &amp; Shipping</span>
					</td>
				</tr>
			</table>
			<br />
			<br />
			<h1>Additional Options</h1>
			<p>The product added to your shopping cart has additional options. Please select the options you would like from below.</p>
			<br />
			<?php
				echo $form->Open();
				echo $form->GetHTML('action');
				echo $form->GetHTML('confirm');
				echo $form->GetHTML('direct');
				echo $form->GetHTML('product');
				echo $form->GetHTML('category');
				echo $form->GetHTML('quantity');
				echo $form->GetHTML('direct');
				echo $htmlBuffer;
			?>
			<br />
			<input type="submit" name="Continue" value="continue" class="submit" />
			<?php echo $form->Close(); ?>
            </div>
            </div>
            
<?php
include("ui/footer.php"); 
include('../lib/common/appFooter.php'); ?>