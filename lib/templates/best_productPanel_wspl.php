 <?php
$gridClass = isset($gridClass) ? $gridClass : '';

$maxLength = isset($maxLength) ? $maxLength : 40;
$maxLengthPartNumber = 14;

$analyticsTag = isset($analyticsTag) ? $analyticsTag : '';

$cartDirect = isset($cartDirect) ? $cartDirect : $_SERVER['REQUEST_URI'];

if(!empty($analyticsTag)) {
	$cartDirect .= strstr($cartDirect, '?') ? sprintf('&%s', $analyticsTag) : sprintf('?%s', $analyticsTag);
}

$subProduct->DefaultImage->Thumb->GetDimensions();
$subProduct->GetReviews();
$subProduct->GetComponents();

$shownCustomPrice = false;

if($session->IsLoggedIn) {
	if($session->Customer->Contact->IsTradeAccount == 'N') {
		if(count($discountCollection->Line) > 0){
			list($discountAmount, $discountName) = $discountCollection->DiscountProduct($subProduct, 1);

			if($discountAmount < $subProduct->PriceCurrent)  {
				$shownCustomPrice = true;

				$subProduct->PriceCurrent = $discountAmount;
				  			
				$subProduct->PriceCurrentIncTax = $subProduct->PriceCurrent + $globalTaxCalculator->GetTax($discountAmount, $subProduct->TaxClass->ID);
				$subProduct->PriceCurrentIncTax = round($subProduct->PriceCurrentIncTax, 2);
			}
		}
	}
}

if(!$shownCustomPrice) {
	if($session->Customer->Contact->IsTradeAccount == 'Y') {
		$tradeCost = ($subProduct->CacheRecentCost > 0) ? $subProduct->CacheRecentCost : $subProduct->CacheBestCost;
		$subProduct->PriceOurs = ContactProductTrade::getPrice($session->Customer->Contact->ID, $subProduct->ID);
		$subProduct->PriceOurs = ($subProduct->PriceOurs <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $subProduct->ID) / 100) + 1) : $subProduct->PriceOurs;

		$subProduct->PriceCurrent = $subProduct->PriceOurs;
		
		$subProduct->PriceCurrentIncTax = $subProduct->PriceCurrent + $globalTaxCalculator->GetTax($subProduct->PriceCurrent, $subProduct->TaxClass->ID);
		$subProduct->PriceCurrentIncTax = round($subProduct->PriceCurrentIncTax, 2);
	}
}

$outputName = $subProduct->HTMLTitle;
$outputPartNumber = $subProduct->SKU;

if(strlen($outputPartNumber) > $maxLengthPartNumber) {
	$outputPartNumber = substr($outputPartNumber, 0, $maxLengthPartNumber-3) . '...';
}

$compProduct = false;
if(isset($subCategory) && $subCategory->CategoryMode == 'Box Rate' && count($subProduct->Component)){
	if($subProduct->Component[0]['Component_Of_Product_ID'] != $subProduct->ID){
		$compProduct = new Product($subProduct->Component[0]['Component_Of_Product_ID']);
		$compProduct->GetPrice();
	}
}

?>

<div class="grid-product-item<?php echo !empty($gridClass) ? sprintf(' %s', $gridClass) : ''; ?>">
<table width="100%">
<tr>
	<td width="30%" rowspan="3"><div class="grid-product-item-image">
		<?php
		if(!empty($subProduct->DefaultImage->Thumb->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$subProduct->DefaultImage->Thumb->FileName)) {
			echo sprintf('<a href="product.php?pid=%d%s" title="%s"><img src="%s%s" alt="%s" height="80px" width="95px" /></a>', $subProduct->ID, !empty($analyticsTag) ? sprintf('&amp;%s', $analyticsTag) : '', $subProduct->Name, $GLOBALS['PRODUCT_IMAGES_DIR_WS'], $subProduct->DefaultImage->Thumb->FileName, $subProduct->Name);
		}
		?>
	</div></td>

	<td width="70%"><div class="product-detail">
    <table><tr><td valign="middle" colspan="2">
		<div class="grid-product-item-name">
			<a href="./product.php?pid=<?php echo $subProduct->ID; ?><?php echo !empty($analyticsTag) ? sprintf('&amp;%s', $analyticsTag) : ''; ?>" title="<?php echo $subProduct->Name; ?>"><?php echo $outputName; ?></a>
		</div></td>
        <tr><td>&nbsp;</td></tr>
    <tr>
	<?php if(($subProduct->Discontinued == 'N') || ($subProduct->DiscontinuedShowPrice == 'Y')) { ?>
		<td width="50%">
			<div class="custom-product-price-container">
				<?php
					$priceStyle = '';
					if(isset($subCategory)){
						$priceStyle = sprintf('style="%s"', $subCategory->GetPriceStyling());
					}
				?>
                </div>
				<div class="custom-pricing">
					<span class="price-amount" <?php echo $priceStyle; ?>>&pound;<?php echo number_format($subProduct->PriceCurrent, 2, '.', ','); ?></span>
				</div>
                </td>
                <td width="50%">
				<?php if(isset($subCategory) && $subCategory->ShowBuyButton != 'N' || !isset($subCategory)) { ?>
				<div class="buy-button-container">
					<form name="buy" action="./customise.php" method="post">
						<input type="hidden" name="action" value="customise" />
						<input type="hidden" name="direct" value="<?php echo urlencode($cartDirect); ?>" />
						<input type="hidden" name="product" value="<?php echo $subProduct->ID; ?>" />
						<input type="hidden" name="quantity" value="<?php echo ($subProduct->OrderMin > 0) ? $subProduct->OrderMin : 1; ?>" />
                        <input type="image" name="buy" value=" " class="button1" style="margin-top:-40px;" />
					</form>
				</div>
                </td>
				<?php } ?>
        </tr>
        <tr><td colspan="2">					<span class="price-amount-tax colour-grey">&pound;<?php echo number_format($subProduct->PriceCurrentIncTax, 2, '.', ','); ?> inc. VAT</span></td></tr></table>
      </div>
      </td>
      </tr>
	<?php } ?>
    </table>
</div>
