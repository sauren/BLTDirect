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
	<div class="grid-product-item-image">
		<?php
		if(!empty($subProduct->DefaultImage->Thumb->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$subProduct->DefaultImage->Thumb->FileName)) {
			echo sprintf('<a href="product.php?pid=%d%s" title="%s"><img src="%s%s" alt="%s" /></a>', $subProduct->ID, !empty($analyticsTag) ? sprintf('&amp;%s', $analyticsTag) : '', $subProduct->Name, $GLOBALS['PRODUCT_IMAGES_DIR_WS'], $subProduct->DefaultImage->Thumb->FileName, $subProduct->Name);
		}
		?>
	</div>

	<div class="product-detail">
		<div class="grid-product-item-name">
			<a href="./product.php?pid=<?php echo $subProduct->ID; ?><?php echo !empty($analyticsTag) ? sprintf('&amp;%s', $analyticsTag) : ''; ?>" title="<?php echo $subProduct->Name; ?>"><?php echo $outputName; ?></a>
		</div>
		
		<span class="product-detail-ident">QuickFind #: <?php echo $subProduct->PublicID(); ?>,<br />Part Number: <?php echo $outputPartNumber; ?></span>
	</div>
	
	<div class="product-rating">
		<?php
		$ratingStars = number_format($subProduct->ReviewAverage * $GLOBALS['PRODUCT_REVIEW_RATINGS'], 1, '.', '');
		
		if($ratingStars > 0) {
			$ratingHtml = '';
			
			for($i=0; $i<$GLOBALS['PRODUCT_REVIEW_RATINGS']; $i++) {
				$ratingHtml .= sprintf('<img src="images/new/product/rating%s.png" alt="Product Rating" />', (ceil($ratingStars) > $i) ? '-solid' : '');
			}
			
			echo sprintf('<a href="product.php?pid=%d%s%s&amp;tab=reviews" title="Reviews for %s">%s</a>', $subProduct->ID, isset($subCategory) ? '&amp;cat=' . $subCategory->ID : '', !empty($analyticsTag) ? sprintf('&amp;%s', $analyticsTag) : '', $subProduct->Name, $ratingHtml);
		}
		?>
	</div>
	
	<?php if(($subProduct->Discontinued == 'N') || ($subProduct->DiscontinuedShowPrice == 'Y')) { ?>
		<div class="product-pricing">
			<div class="custom-product-price-container">
				<?php
					$priceStyle = '';
					if(isset($subCategory)){
						$priceStyle = sprintf('style="%s"', $subCategory->GetPriceStyling());
					}
				?>
				<div class="custom-pricing">
					<span class="price-amount" <?php echo $priceStyle; ?>>&pound;<?php echo number_format($subProduct->PriceCurrent, 2, '.', ','); ?></span><br />
					<span class="price-amount-tax colour-grey">&pound;<?php echo number_format($subProduct->PriceCurrentIncTax, 2, '.', ','); ?> inc. VAT</span>
				</div>
				<?php if(isset($subCategory) && $subCategory->ShowBuyButton != 'N' || !isset($subCategory)) { ?>
				<div class="buy-button-container">
					<form name="buy" action="./customise.php" method="post">
						<input type="hidden" name="action" value="customise" />
						<input type="hidden" name="direct" value="<?php echo urlencode($cartDirect); ?>" />
						<input type="hidden" name="product" value="<?php echo $subProduct->ID; ?>" />
						<input type="hidden" name="quantity" value="<?php echo ($subProduct->OrderMin > 0) ? $subProduct->OrderMin : 1; ?>" />
						<input type="submit" name="buy" value="Buy" class="button" />
					</form>
				</div>
				<?php } ?>
			</div>
			<?php if(isset($subCategory) && $subCategory->CategoryMode == 'Box Rate' && isset($compProduct->ID)){ ?>
				<div class="custom-product-price-container boxrate-container">
					<?php $priceStyle = sprintf('style="%s"', $subCategory->GetPriceStyling());	?>
					<div class="custom-pricing">
						<span class="price-amount-tax colour-grey">&times;<?php echo $subProduct->Component[0]['Component_Quantity']; ?> <strong>Rate</strong></span><br />
						<span class="price-amount" <?php echo $priceStyle; ?>>&pound;<?php echo number_format($compProduct->PriceCurrent, 2, '.', ','); ?></span><br />
					</div>
					<?php if($subCategory->ShowBuyButton != 'N'){ ?>
					<div class="buy-button-container">
						<form name="buy" action="./customise.php" method="post">
							<input type="hidden" name="action" value="customise" />
							<input type="hidden" name="direct" value="<?php echo urlencode($cartDirect); ?>" />
							<input type="hidden" name="product" value="<?php echo $compProduct->ID; ?>" />
							<input type="hidden" name="quantity" value="<?php echo ($compProduct->OrderMin > 0) ? $compProduct->OrderMin : 1; ?>" />
							<input type="submit" name="buy" value="Buy" class="button" />
						</form>
					</div>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
	<?php } ?>	
	<div class="clear"></div>
</div>
