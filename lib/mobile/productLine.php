<?php
$rowClass = isset($rowClass) ? $rowClass : '';
$hideSpecifications = isset($hideSpecifications) ? $hideSpecifications : false;
$hideSavings = isset($hideSavings) ? $hideSavings : true;
$hideSwitch = isset($hideSwitch) ? $hideSwitch : true;
$componentQuantity = isset($componentQuantity) ? $componentQuantity : 0;

$subProduct->DefaultImage->Thumb->GetDimensions();
$subProduct->GetReviews();

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

if(!$hideSavings) {
	$specType = null;
	$specEquivalentWattage = null;
	$specWattage = null;
	$specLampLife = null;

	if(!empty($groupsType)) {
		$data = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsType), mysql_real_escape_string($subProduct->ID)));
		if($data->TotalRows > 0) {
			$specType = $data->Row['Value'];
		}
		$data->Disconnect();
	}
	
	if(!empty($groupsEquivalentWattage)) {
		$data = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsEquivalentWattage), mysql_real_escape_string($subProduct->ID)));
		if($data->TotalRows > 0) {
			$specEquivalentWattage = preg_replace('/[^0-9.]/', '', $data->Row['Value']);
		}
		$data->Disconnect();
	}

	if(!empty($groupsWattage)) {
		$data = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsWattage), mysql_real_escape_string($subProduct->ID)));
		if($data->TotalRows > 0) {
			$specWattage = preg_replace('/[^0-9.]/', '', $data->Row['Value']);
		}
		$data->Disconnect();
	}

	if(!empty($groupsLampLife)) {
		$data = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsLampLife), mysql_real_escape_string($subProduct->ID)));
		if($data->TotalRows > 0) {
			$specLampLife = preg_replace('/[^0-9.]/', '', $data->Row['Value']);
		}
		$data->Disconnect();
	}
}
?>

<tr <?php echo !empty($rowClass) ? sprintf('class="%s"', $rowClass) : ''; ?>>
	<td>
		<div class="list-image" style="width:110px; float: left;">
			<?php
			if(!empty($subProduct->DefaultImage->Thumb->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$subProduct->DefaultImage->Thumb->FileName)) {
				echo sprintf('<a href="/product.php?pid=%d%s" title="%s"><img src="%s%s" alt="%s" /></a>', $subProduct->ID, isset($subCategory) ? '&amp;cat=' . $subCategory->ID : '', htmlspecialchars($subProduct->Name), $GLOBALS['PRODUCT_IMAGES_DIR_WS'], $subProduct->DefaultImage->Thumb->FileName, htmlspecialchars($subProduct->Name));
			}
			?>
		</div>
		<div style="padding-left: 110px;">
			<div style="float: left; max-width: 250px;">
				<div class="product-detail">
					<?php
					if($componentQuantity > 0) {
						echo $componentQuantity, 'x ';
					}
					?>
					
					<a href="/product.php?pid=<?php echo $subProduct->ID; ?><?php echo isset($subCategory) ? '&amp;cat=' . $subCategory->ID : ''; ?>" title="<?php echo htmlspecialchars($subProduct->Name); ?>"><?php echo htmlspecialchars($subProduct->Name); ?></a><br />
					<span class="product-detail-ident">QuickFind #: <?php echo $subProduct->PublicID(); ?>, Part Number: <?php echo $subProduct->SKU; ?></span><br />
					
					<?php
					switch($renderer) {
						case 'default':
							?>
					
							<span class="product-detail-ident">Alternative Codes: <?php echo $subProduct->Codes; ?></span>
							
							<?php
							if(!$hideSpecifications) {
								if(!empty($subProduct->SpecCachePrimary)) {
									$specs = explode(';', $subProduct->SpecCachePrimary);
									$specGroups = array();
									
									foreach($specs as $specItem) {
										$specItemParts = explode('=', $specItem);
										
										if(count($specItemParts) >= 2) {
											$specGroups[] = sprintf('%s %s', $specItemParts[1], $specItemParts[0]);
										}
									}
									?>
									
									<br /><br />
									<span class="product-detail-ident"><?php echo implode(', ', $specGroups); ?></span>
									
									<?php
								}
							}
							
							if(!$hideSavings) {
								if(!empty($specEquivalentWattage) && !empty($specWattage) && !empty($specLampLife)) {
									$energySaving = ($specEquivalentWattage - $specWattage) * (12 / 100 / 1000) * $specLampLife;
									?>
									
									<br /><br />
									<a href="/product.php?pid=<?php echo $subProduct->ID; ?><?php echo isset($subCategory) ? '&amp;cat=' . $subCategory->ID : ''; ?>" title="<?php echo htmlspecialchars($subProduct->Name); ?>"><span class="colour-green">You could save up to <strong>&pound;<?php echo number_format($energySaving, 2, '.', ','); ?></strong> (click here)</span></a><br />
									<span class="colour-green"><small>over the life of the bulb by converting to this <?php echo !empty($specType) ? $specType : 'Energy Saving'; ?> version</small></span><br />
									<span class="colour-green"><small>rated against a standard halogen or incandescent light bulb</small></span>	

									<?php
								}
							}
							
							if($session->Customer->Contact->IsTradeAccount == 'Y') {
								$stockData = new DataQuery(sprintf("SELECT SUM(ws.Quantity_In_Stock) AS Stock FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=%d", mysql_real_escape_string($subProduct->ID)));
								if(($stockData->TotalRows > 0) && ($stockData->Row['Stock'] > 0)) {
									?>
								
									<br /><br />
									<span class="colour-orange">In stock <strong><?php echo $stockData->Row['Stock']; ?></strong><br /><small>available for delivery</small></span>
									
									<?php
								} else {
									if($subProduct->AverageDespatch > 7) {
										?>
									
										<br /><br />
										<span class="colour-orange">Please call for despatch information<br /><small>phone us on <?php echo Setting::GetValue('telephone_customer_services'); ?></small></span>
										
										<?php
									} elseif($subProduct->AverageDespatch > 0) {
										?>
									
										<br /><br />
										<span class="colour-orange">Normally despatched in <?php echo max($subProduct->AverageDespatch, 1); ?> days<br /><small>available for delivery</small></span>
										
										<?php
									} elseif($subProduct->AverageDespatch == 0) {
										?>
									
										<br /><br />
										<span class="colour-orange">Normally despatched on same day<br /><small>available for delivery</small></span>
										
										<?php
									} else {
										?>
									
										<br /><br />
										<span class="colour-orange">Standard delivery available 2-6 days<br /><small>and available for next day delivery</small></span>
										
										<?php
									}
								}
								$stockData->Disconnect();
							}
							
							break;
					}
					?>
				</div>
				<div>
					<div class="product-rating" style="height: auto;">
						<?php
						$ratingStars = number_format($subProduct->ReviewAverage * $GLOBALS['PRODUCT_REVIEW_RATINGS'], 1, '.', '');
						
						if($ratingStars > 0) {
							$ratingHtml = '';
							
							for($i=0; $i<$GLOBALS['PRODUCT_REVIEW_RATINGS']; $i++) {
								$ratingHtml .= sprintf('<img src="/images/new/product/rating%s.png" alt="Product Rating" />', (ceil($ratingStars) > $i) ? '-solid' : '');
							}
							
							echo sprintf('<a href="/product.php?pid=%d%s&amp;tab=reviews" title="Reviews for %s">%s</a>', $subProduct->ID, isset($subCategory) ? '&amp;cat=' . $subCategory->ID : '', htmlspecialchars($subProduct->Name), $ratingHtml);
						}
						?>
					</div>
				</div>
			</div>

			<div style="float: right;">
				<div style="text-align:right;">
					<?php
					if(($subProduct->Discontinued == 'N') || ($subProduct->DiscontinuedShowPrice == 'Y')) {
						?>
						
						<div class="price-sale colour-red">
							<?php
							if($session->Customer->Contact->IsTradeAccount == 'N') {
								if($subProduct->PriceRRP > 0) {
									?>
									
									<span class="price-amount-old colour-blue">&pound;<?php echo number_format($subProduct->PriceRRP, 2, '.', ','); ?></span><br />
									
									<?php
								}
							} else {
								?>
									
								<span class="price-amount-old colour-blue">&pound;<?php echo number_format($retailPrice, 2, '.', ','); ?></span><br />
								
								<?php
							}
							?>
							
							<span class="price-amount">&pound;<?php echo number_format($subProduct->PriceCurrent, 2, '.', ','); ?></span><br />
							<span class="price-amount-tax colour-grey">&pound;<?php echo number_format($subProduct->PriceCurrentIncTax, 2, '.', ','); ?> inc. VAT</span>
						</div>
						
						<?php
					} else {
						?>
						
						<div class="colour-grey">Discontinued</div>
						
						<?php
					}
					?>
				</div>
				<div style="text-align:center; white-space:nowrap;">
					<?php
					if(!$hideSwitch) {
						?>
												
						<input type="button" name="switch" value="Switch" class="button" onclick="redirect('cart.php?action=switch&line=<?php echo $subCartLine->ID; ?>&product=<?php echo $subProduct->ID; ?>');" />
												
						<?php
					} else {
						if(($subProduct->Discontinued == 'N') || ($subProduct->DiscontinuedShowPrice == 'Y')) {
							?>
							
							<div class="price-buy">
								<form name="buy" action="/customise.php" method="post">
									<input type="hidden" name="action" value="customise" />
									<input type="hidden" name="direct" value="<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" />
									<input type="hidden" name="product" value="<?php echo $subProduct->ID; ?>" />
									
									<div class="price-buy-field">
										<input type="text" name="quantity" value="<?php echo ($subProduct->OrderMin > 0) ? $subProduct->OrderMin : 1; ?>" size="1" maxlength="2" class="price-buy-field-text" />
										<input type="image" name="buy" src="/images/new/product/buy.png" alt="Buy" />
									</div>
								</form>
							</div>
							
							<?php
						}
					}
					?>
				</div>
			</div>
		</div>
	</div>
</tr>