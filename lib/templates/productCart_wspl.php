<?php
$rowClass = isset($rowClass) ? $rowClass : '';

$subProduct->DefaultImage->Thumb->GetDimensions();
$subProduct->GetRelatedByType('Energy Saving Alternative');

if($subProduct->Discontinued == 'N') {
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
			$retailPrice = $subProduct->PriceCurrent;
			$tradeCost = ($subProduct->CacheRecentCost > 0) ? $subProduct->CacheRecentCost : $subProduct->CacheBestCost;
			
			$subProduct->PriceOurs = ContactProductTrade::getPrice($session->Customer->Contact->ID, $subProduct->ID);
			$subProduct->PriceOurs = ($subProduct->PriceOurs <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $subProduct->ID) / 100) + 1) : $subProduct->PriceOurs;

			$subProduct->PriceCurrent = $subProduct->PriceOurs;
			
			$subProduct->PriceCurrentIncTax = $subProduct->PriceCurrent + $globalTaxCalculator->GetTax($subProduct->PriceCurrent, $subProduct->TaxClass->ID);
			$subProduct->PriceCurrentIncTax = round($subProduct->PriceCurrentIncTax, 2);
		}
	}
}
?>

<tr class="list-thin <?php echo !empty($subProduct->RelatedType['Energy Saving Alternative']) ? 'list-none' : ''; ?> <?php echo $rowClass; ?>">
	<td><a href="javascript:confirmRemove(<?php echo $subCartLine->ID; ?>);"><img src="images/icon_trash_1.gif" alt="Remove <?php echo $subProduct->Name; ?>" width="16" height="16" /></a></td>
	<td><?php echo $form->GetHTML('qty_' . $subCartLine->ID); ?></td>
    </tr>
    <tr>
	<td class="list-image" width="35%">
		<?php
		if(!empty($subProduct->DefaultImage->Thumb->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$subProduct->DefaultImage->Thumb->FileName)) {
			echo sprintf('<a href="product.php?pid=%d%s" title="%s"><img src="%s%s" alt="%s" /></a>', $subProduct->ID, isset($subCategory) ? '&amp;cat=' . $subCategory->ID : '', htmlspecialchars($subProduct->Name), $GLOBALS['PRODUCT_IMAGES_DIR_WS'], $subProduct->DefaultImage->Thumb->FileName, htmlspecialchars($subProduct->Name));
		}
		?>
	</td>
	<td width="65%">
		<div class="product-detail">
			<?php
			if($subProduct->ID > 0) {
				?>
				
				<a href="./product.php?pid=<?php echo $subProduct->ID; ?><?php echo isset($subCategory) ? '&amp;cat=' . $subCategory->ID : ''; ?>" title="<?php echo htmlspecialchars($subProduct->Name); ?>"><?php echo $subProduct->Name; ?></a><br />
				<span class="product-detail-ident">QuickFind #: <?php echo $subProduct->PublicID(); ?>, Part Number: <?php echo $subProduct->SKU; ?></span><br />
			
				<?php
				if(!empty($subCartLine->Discount)) {
					$discount = explode(':', $subCartLine->DiscountInformation);
					$discountOutput = (trim($discount[0]) == 'azxcustom') ? 'Custom Discount' : $subCartLine->DiscountInformation;
					?>
					
					<br />

					<span class="smallGreyText"><?php echo sprintf('%s (&pound;%s Discount)', $discountOutput, number_format($subCartLine->Discount, 2, '.',',')); ?></span><br />

					<?php
				}
			} else {
				?>
				
				<?php echo htmlspecialchars($subProduct->AssociativeProductTitle); ?><br />
			
				<?php
			}
			?>	
		</div>
	</td>
    </tr>
    <tr>
	<td>
		<div class="price-sale colour-red">
			<?php
			$priceOutput = $subCartLine->Price * $subCartLine->Quantity;

			/*
			if($session->IsLoggedIn) {
				if($subProduct->ID > 0) {
					if($subCartLine->Price != ($subCartLine->Price-($subCartLine->Discount/$subCartLine->Quantity))) {
						$priceOutput = ($subCartLine->Price-($subCartLine->Discount/$subCartLine->Quantity))*$subCartLine->Quantity;
					}
				}
			}
			*/
			?>
			
			<span class="colour-blue">&pound;<?php echo number_format($priceOutput, 2, '.', ','); ?></span>
		</div>
	</td>
	<td>
		<div class="price-sale colour-red">
			<span class="price-amount">&pound;<?php echo number_format(($subProduct->ID > 0) ? (($subCartLine->Price-($subCartLine->Discount/$subCartLine->Quantity))*$subCartLine->Quantity) : $subCartLine->Price * $subCartLine->Quantity, 2, '.', ','); ?></span>
		</div>
	</td>
</tr>