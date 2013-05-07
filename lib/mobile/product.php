<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/mobile.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title><?php echo $product->Name; ?></title>
	<!-- InstanceEndEditable -->
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/>
	<link rel="stylesheet" type="text/css" href="/css/lightbulbs.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="/css/lightbulbs_print.css" media="print" />
	<link rel="stylesheet" type="text/css" href="/css/Navigation.css" />
	<link rel="stylesheet" type="text/css" href="/css/Menu.css" />
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'Y') {
		?>
		<link rel="stylesheet" type="text/css" href="/css/Trade.css" />
        <?php
	}
	?>
    
	<link rel="shortcut icon" href="/favicon.ico" />
	<script language="javascript" type="text/javascript" src="/js/generic.js"></script>
	<script language="javascript" type="text/javascript" src="/js/evance_api.js"></script>
	<script language="javascript" type="text/javascript" src="/js/mootools.js"></script>
	<script language="javascript" type="text/javascript" src="/js/evance.js"></script>
	<script language="javascript" type="text/javascript" src="/js/bltdirect.js"></script>
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'N') {
		?>
		<script language="javascript" type="text/javascript" src="/js/bltdirect/template.js"></script>
        <?php
	}
	?>
    
	<script language="javascript" type="text/javascript">
	//<![CDATA[
		<?php
		for($i=0; $i<count($GLOBALS['Cache']['Categories']); $i=$i+2) {
			echo sprintf("menu1.add('navProducts%d', 'navProducts', '%s', '%s', null, 'subMenu');", $i, $GLOBALS['Cache']['Categories'][$i], $GLOBALS['Cache']['Categories'][$i+1]);
		}
		?>
	//]]>
	</script>
	<link rel="stylesheet" type="text/css" href="/css/MobileSplash.css" />
    <link rel="stylesheet" type="text/css" href="/css/new.css" />
   	<link rel="stylesheet" type="text/css" href="/css/mobile/new.css" />
	<!-- InstanceBeginEditable name="head" -->
	<meta name="keywords" content="<?php echo $product->MetaKeywords; ?>" />
	<meta name="description" content="<?php echo $product->MetaDescription; ?>" />
	<?php
	if($product->IntegrationID > 0) {
		echo '<meta name="robots" content="noindex, nofollow" />';
	}
	?>
	<script type="text/javascript" src="/js/tabs.js"></script>
	<script type="text/javascript">
		<?php
		if(!empty($specEquivalentWattage) && !empty($specWattage) && !empty($specLampLife)) {
			?>
			
			function calculateSaving() {
				var savingElement = document.getElementById('energy-saving-total');
				
				var inputQuantity = document.getElementById('energy-saving-input-quantity');
				var inputRate = document.getElementById('energy-saving-input-rate');
				
				var specEquivalentWattage = <?php echo $specEquivalentWattage; ?>;
				var specWattage = <?php echo $specWattage; ?>;
				var specLampLife = <?php echo $specLampLife; ?>;

				if(savingElement && inputQuantity && inputRate) {
					var saving = (specEquivalentWattage - specWattage) * (parseFloat(inputRate.value) / 100 / 1000) * specLampLife * parseInt(inputQuantity.value);

					if(isNaN(saving)) {
						saving = 0;
					}
					
					savingElement.innerHTML = saving.toFixed(2);
				}
			}
		
			<?php
		}
		?>
		
		function showReview() {
			var inputElement = document.getElementById('product-review-input');
			
			if(inputElement) {
				inputElement.style.display = 'block';
			}
			
			var createElement = document.getElementById('product-review-create');
			
			if(createElement) {
				createElement.style.display = 'none';
			}
		}
		
		function setImage(image, title) {
			var imageElement = document.getElementById('product-image-main');
			
			if(imageElement) {
				imageElement.src = image;
				imageElement.alt = title;
			}
		}
		
		addContent('overview');
		addContent('specifications');
		addContent('related');
		addContent('relatedenergysaving');
		addContent('components');
		addContent('reviews');
		addContent('enquire');
	</script>
	<!-- InstanceEndEditable -->
</head>
<body>

	<a name="top"></a>

    <div id="Page">
        <div id="PageContent">
            <div class="right rightIcon">
            	<a href="http://www.bltdirect.com/" title="Light Bulbs, Lamps and Tubes Direct"><img src="../../images/logo_125.png" alt="Light Bulbs, Lamps and Tubes Direct" /></a><br />
            	<?php echo Setting::GetValue('telephone_sales_hotline'); ?>
            </div>
            
            <!-- InstanceBeginEditable name="pageContent" -->
              		<h1><?php echo $product->Name; ?></h1>

					<?php include('lib/templates/bought.php'); ?>
					
					<?php
					if(($product->Discontinued == 'Y') && ($product->DiscontinuedShowPrice == 'Y')) {
						if($product->SupersededBy > 0) {
							$superseded = new Product();
							$superseded->Get($product->SupersededBy);  

							$bubble = new Bubble('Superseded!', sprintf('This product is discontinued and has been superseded by the <strong><a href="?pid=%d">%s</a></strong>.', $superseded->ID, $superseded->Name));
							?>
							
							<div class="attention">
								<div class="attention-icon attention-icon-warning"></div>
								<div class="attention-info attention-info-warning">
									<span class="attention-info-title">Item Superseded</span><br />
									This item was discontinued on <?php echo cDatetime($product->DiscontinuedOn, 'longdate'); ?> and has been superseded by <a href="?pid=<?php echo $superseded->ID; ?>"><?php echo $superseded->Name; ?></a>.
									
									<?php
									if(!empty($product->DiscontinuedBecause)) {
										?>
										
										<br /><br />
										<?php echo $product->DiscontinuedBecause; ?>
										
										<?php	
									}
									?>
								</div>
							</div>
							
							<?php
						} else {
							?>
							
							<div class="attention">
								<div class="attention-icon attention-icon-warning"></div>
								<div class="attention-info attention-info-warning">
									<span class="attention-info-title">Item Discontinued</span><br />
									This product was discontinued on <?php echo cDatetime($product->DiscontinuedOn, 'longdate'); ?> and is no longer available.
									
									<?php
									if(!empty($product->DiscontinuedBecause)) {
										?>
										
										<br /><br />
										<?php echo $product->DiscontinuedBecause; ?>
										
										<?php	
									}
									?>
								</div>
							</div>
							
							<?php							
						}
					}
					
					if($product->Discontinued == 'N') {
						$isStockWarning = false;
						
						$warnCategoriesStock = array(1634);

						$data = new DataQuery(sprintf("SELECT Category_ID FROM product_in_categories WHERE Product_ID=%d", mysql_real_escape_string($product->ID)));
						while($data->Row) {
							$categories = getCategories($data->Row['Category_ID']);
							
							foreach($warnCategoriesStock as $categoryItem) {
								if(in_array($categoryItem, $categories)) {
									$isStockWarning = true;
								}		
							}
							
							$data->Next();	
						}
						$data->Disconnect();

						if($isStockWarning) {
							?>
							
							<div class="attention">
								<div class="attention-icon attention-icon-warning"></div>
								<div class="attention-info attention-info-warning">
									<span class="attention-info-title">Stock Warning</span><br />
									For coloured bulbs please call our sales lines on <?php echo Setting::GetValue('telephone_sales_hotline'); ?> between 8:30 and 17:00. We are currently holding very limited stock - please call to check availability before placing your order.
								</div>
							</div>
							
							<?php
						}

						if($product->IsNonReturnable == 'Y') {
							?>
							
							<div class="attention">
								<div class="attention-icon attention-icon-warning"></div>
								<div class="attention-info attention-info-warning">
									<span class="attention-info-title">Non-returnable Item</span><br />
									This product is a special order item that is non-returnable. Please ensure you are ordering the correct product. For further information please call our sales line on <?php echo Setting::GetValue('telephone_sales_hotline'); ?>.
								</div>
							</div>
							
							<?php
						}

						$data = new DataQuery(sprintf("SELECT Backorder_Expected_On FROM warehouse_stock WHERE Product_ID=%d AND Is_Backordered='Y' ORDER BY Backorder_Expected_On ASC LIMIT 0, 1", mysql_real_escape_string($product->ID)));
						if($data->TotalRows > 0) {
							?>
							
							<div class="attention">
								<div class="attention-icon attention-icon-warning"></div>
								<div class="attention-info attention-info-warning">
									<span class="attention-info-title">Temporarily Unavailable</span><br />
									This product is out of stock until <?php echo cDatetime($data->Row['Backorder_Expected_On'], 'longdate'); ?>. You are still able to order this product but delivery will be after this date.
								</div>
							</div>
							
							<?php
						}
						$data->Disconnect();
					}
					?>
					
					<div class="product-image">
						<div class="product-image-main">
							<?php
							if(!empty($product->DefaultImage->Large->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$product->DefaultImage->Large->FileName)) {
								?>
								
								<img id="product-image-main" src="<?php echo $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$product->DefaultImage->Large->FileName; ?>" alt="<?php echo $product->Name; ?>" />
								
								<?php
							}
							?>
						</div>
						
						<?php
						$thumbNails = 0;
						
						foreach($product->Image as $image) {
							if(file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$image->Thumb->FileName)) {
								$thumbNails++;
							}	
						}
						
						foreach($product->Example as $image) {
							if(file_exists($GLOBALS['PRODUCT_EXAMPLE_IMAGES_DIR_FS'].$image->Thumb->FileName)) {
								$thumbNails++;
							}	
						}
						
						if($thumbNails > 1) {
							?>
							
							<div class="product-image-thumb">
								<?php
								foreach($product->Image as $image) {
									if(file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$image->Thumb->FileName)) {
										$image->Thumb->Width /= 2;
										$image->Thumb->Height /= 2;
										?>
										
										<div class="product-image-thumb-item">
											<img src="<?php echo $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$image->Thumb->FileName; ?>" alt="<?php echo $image->Name; ?>" width="<?php echo $image->Thumb->Width; ?>" height="<?php echo $image->Thumb->Height; ?>" onmouseover="setImage('<?php echo $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$image->Large->FileName; ?>', '<?php echo $image->Name; ?>');" />
										</div>
										
										<?php
									}
								}
								
								foreach($product->Example as $image) {
									if(file_exists($GLOBALS['PRODUCT_EXAMPLE_IMAGES_DIR_FS'].$image->Thumb->FileName)) {
										$image->Thumb->Width /= 2;
										$image->Thumb->Height /= 2;
										?>
										
										<div class="product-image-thumb-item product-image-thumb-item-example">
											<a href="<?php echo $GLOBALS['PRODUCT_EXAMPLE_IMAGES_DIR_WS'].$image->Large->FileName; ?>" title="Click to expand" rel="lightbox">
												<img src="<?php echo $GLOBALS['PRODUCT_EXAMPLE_IMAGES_DIR_WS'].$image->Thumb->FileName; ?>" alt="<?php echo $image->Name; ?>" width="<?php echo $image->Thumb->Width; ?>" height="<?php echo $image->Thumb->Height; ?>" onclick="setExample('<?php echo $GLOBALS['PRODUCT_EXAMPLE_IMAGES_DIR_WS'].$image->Large->FileName; ?>', '<?php echo $image->Name; ?>');" />
											</a>
										</div>
										
										<?php
									}
								}
								?>
							</div>
							
							<?php
						}
						?>
					</div>
					
					<?php
					if(($product->Discontinued == 'Y') && ($product->DiscontinuedShowPrice == 'N')) {
						if($product->SupersededBy > 0) {
							$superseded = new Product();
							$superseded->Get($product->SupersededBy);  

							$bubble = new Bubble('Superseded!', sprintf('This product is discontinued and has been superseded by the <strong><a href="?pid=%d">%s</a></strong>.', $superseded->ID, $superseded->Name));
							?>
							
							<div class="attention product-attention">
								<div class="attention-icon attention-icon-warning"></div>
								<div class="attention-info attention-info-warning">
									<span class="attention-info-title">Item Superseded</span><br />
									This item was discontinued on <?php echo cDatetime($product->DiscontinuedOn, 'longdate'); ?> and has been superseded by <a href="?pid=<?php echo $superseded->ID; ?>"><?php echo $superseded->Name; ?></a>.
									
									<?php
									if(!empty($product->DiscontinuedBecause)) {
										?>
										
										<br /><br />
										<?php echo $product->DiscontinuedBecause; ?>
										
										<?php	
									}
									?>
								</div>
							</div>
							
							<?php
						} else {
							?>
							
							<div class="attention product-attention">
								<div class="attention-icon attention-icon-warning"></div>
								<div class="attention-info attention-info-warning">
									<span class="attention-info-title">Item Discontinued</span><br />
									This product was discontinued on <?php echo cDatetime($product->DiscontinuedOn, 'longdate'); ?> and is no longer available.
									
									<?php
									if(!empty($product->DiscontinuedBecause)) {
										?>
										
										<br /><br />
										<?php echo $product->DiscontinuedBecause; ?>
										
										<?php	
									}
									?>
								</div>
							</div>
							
							<?php							
						}
					} else {
						?>
						
						<div class="product-buy">
						
							<div class="product-stars">
								<div class="product-stars-items">
									<?php
									$rating = $product->ReviewAverage;
									$ratingStars = number_format($rating * $GLOBALS['PRODUCT_REVIEW_RATINGS'], 1, '.', '');
									$ratingHtml = '';
			
									for($i=0; $i<$GLOBALS['PRODUCT_REVIEW_RATINGS']; $i++) {
										$ratingHtml .= sprintf('<img src="/images/new/product/star%s.png" alt="Product Rating" />', (ceil($ratingStars) > $i) ? '-solid' : '');
									}
									
									echo sprintf('<a href="#tab-reviews" onclick="setContent(\'reviews\');" title="Reviews for %s">%s</a>', $product->Name, $ratingHtml);
									?>
								</div>
								<div class="product-stars-text"><?php echo count($product->Review); ?> customer reviews</div>
								<div class="clear"></div>
							</div>
						
							<div class="product-price">
								<div class="product-price-sale">
									<div class="product-price-sale-icon"></div>
								</div>
								
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
								
								if($session->Customer->Contact->IsTradeAccount == 'N') {
									if($product->PriceRRP > 0) {
										?>
										
										<div class="product-price-amount">
											<div class="product-price-amount-text">
												<strong>RRP</strong><br />
												<span class="product-price-amount-old colour-blue">&pound;<?php echo number_format($product->PriceRRP, 2, '.', ','); ?></span>
											</div>
										</div>
										
										<?php
				  					}
				  				
									if($product->PriceOurs < $product->PriceCurrent) {
										?>
										
										<div class="product-price-amount">
											<div class="product-price-amount-text">
												<strong>Was</strong><br />
												<span class="product-price-amount-old colour-blue">&pound;<?php echo number_format($product->PriceOurs, 2, '.', ','); ?></span>
											</div>
										</div>
										
										<?php
				  					}
				  					?>
				  					
				  					<div class="product-price-amount">
										<div class="product-price-amount-text">
											<strong>Price</strong><br />
											<span class="product-price-amount-current colour-red"><strong>&pound;<?php echo number_format($product->PriceCurrent, 2, '.', ','); ?></strong></span>
										</div>
									</div>
									
									<?php
								} else {
									?>
										
									<div class="product-price-amount">
										<div class="product-price-amount-text">
											<strong>Retail</strong><br />
											<span class="product-price-amount-old colour-blue">&pound;<?php echo number_format($retailPrice, 2, '.', ','); ?></span>
										</div>
									</div>
									<div class="product-price-amount">
										<div class="product-price-amount-text">
											<strong>Trade</strong><br />
											<span class="product-price-amount-current colour-red"><strong>&pound;<?php echo number_format($product->PriceCurrent, 2, '.', ','); ?></strong></span>
										</div>
									</div>
									
									<?php
								}
				  				?>
								
								<div class="product-price-amount">
									<div class="product-price-amount-text">
										<strong>Inc. VAT</strong><br />
										<span class="product-price-amount-current colour-grey">&pound;<?php echo number_format($product->PriceCurrentIncTax, 2, '.', ','); ?></span>
									</div>
								</div>
								<div class="product-price-saving">
									<div class="product-price-saving-text">
										<strong>SAVE</strong><br />
										<span class="product-price-saving-percent"><?php echo $product->PriceSavingPercent; ?>%</span>
									</div>
								</div>
							</div>
							
							<?php
							if($session->Customer->Contact->IsTradeAccount == 'N') {
								if($product->PriceCurrent == $product->PriceOurs) {					
									$prices = array();

									$data = new DataQuery(sprintf("SELECT Quantity, Price_Base_Our FROM product_prices WHERE Price_Starts_On<=NOW() AND Quantity>=%d AND Product_ID=%d ORDER BY Price_Starts_On DESC", mysql_real_escape_string($product->OrderMin), mysql_real_escape_string($product->ID)));
									while($data->Row) {
										if(!isset($prices[$data->Row['Quantity']])) {
											$prices[$data->Row['Quantity']] = $data->Row['Price_Base_Our'];
										}
										
										$data->Next();
									}
									$data->Disconnect();

									ksort($prices);

									if(count($prices) > 1) {
										?>
										
										<table class="product-quantity">
											<tr>
												<th>Quantity</th>
												<th style="text-align: right;">Bulk Price</th>
												<th style="text-align: right;">Saving</th>
											</tr>
											
											<?php
											$index = 0;
											
											foreach($prices as $quantity=>$price) {
												$quantityRange = $quantity;
												$saving = round((($product->PriceCurrent - $price) / $product->PriceRRP) * 100, 2);

												if(($index + 1) == count($prices)) {
				                					$quantityRange .= '+';
				                				}
												?>
												
												<tr>
													<td><?php echo $quantityRange; ?></td>
													<td align="right">&pound;<?php echo number_format($price, 2, '.', ','); ?> each</td>
													<td align="right"><?php echo ($saving > 0) ? number_format($saving, 2, '.', ',') . '%' : ''; ?></td>
												</tr>
												
												<?php
												$index++;
											}
											?>
											
										</table>
							
										<?php
									}
								}
							} else {
								$data = new DataQuery(sprintf("SELECT p.Postage_Title, MIN(s.Per_Delivery) AS Delivery FROM shipping AS s INNER JOIN geozone AS g ON g.Geozone_ID=s.Geozone_ID INNER JOIN geozone_assoc AS ga ON ga.Geozone_ID=g.Geozone_ID AND ga.Country_ID=%d INNER JOIN postage AS p ON p.Postage_ID=s.Postage_ID WHERE s.Shipping_Class_ID=%d AND s.Weight_Threshold=0 AND s.Over_Order_Amount=0 GROUP BY s.Postage_ID ORDER BY p.Postage_Title ASC", mysql_real_escape_string($GLOBALS['SYSTEM_COUNTRY']), mysql_real_escape_string($product->ShippingClass->ID)));
								if($data->TotalRows > 0) {
									?>
									
									<table class="product-quantity">
										<tr>
											<th>Postage</th>
											<th style="text-align: right;">Delivery Rate</th>
										</tr>
										
										<?php
										while($data->Row) {
											?>
											
											<tr>
												<td><?php echo $data->Row['Postage_Title']; ?></td>
												<td align="right">&pound;<?php echo number_format($data->Row['Delivery'], 2, '.', ','); ?></td>
											</tr>
											
											<?php
											$data->Next();
										}
										?>
										
									</table>
									
									<?php
								}
								$data->Disconnect();
							}
							
							?>
									
							<div class="product-button">
							
								<div class="product-button-buy">
									<form name="buy" action="customise.php" method="post">
										<input type="hidden" name="action" value="customise" />
										<input type="hidden" name="direct" value="<?php echo $_SERVER['REQUEST_URI']; ?>" />
										<input type="hidden" name="product" value="<?php echo $product->ID; ?>" />
										<input type="hidden" name="category" value="<?php echo $category->ID; ?>" />
										
										<div class="product-button-buy-field">
											<input type="text" name="quantity" value="<?php echo ($product->OrderMin > 0) ? $product->OrderMin : 1; ?>" size="3" maxlength="4" class="product-button-buy-field-text" />
											<input type="image" name="buy" value="buy" src="/images/new/product/buy.png" />
										</div>
									</form>
								</div>
								
								<?php
								if($session->IsLoggedIn) {
									$customerProduct = new CustomerProduct();
									$customerProduct->Product->ID = $product->ID;
									$customerProduct->Customer->ID = $session->Customer->ID;
									
									if(!$customerProduct->Exists()) {
										?>

										<div class="product-button-favourite">
											<a href="?action=favourite&amp;pid=<?php echo $product->ID; ?>&amp;cat=<?php echo $category->ID; ?>"><img src="/images/new/product/favourite.png" width="137" height="25" alt="Favourite Bulb" /></a>
										</div>
										
										<?php
									}
								}
								?>
								
								<div class="clear"></div>
							</div>
						
							<?php
							if(count($product->LinkObject) > 0) {
								?>
								
								<div class="product-similar">
									<p><strong><?php echo !empty($product->SimilarText) ? $product->SimilarText : 'Not what you were looking for? Try these.'; ?></strong></p>
								
									<?php
									foreach($product->LinkObject as $link) {
										$link->image->Directory = $GLOBALS['CACHE_DIR_FS'];
										$link->image->FileName = $link->asset->hash;
										$link->image->GetDimensions();
										$link->image->Width /= 1.5;
										$link->image->Height /= 1.5;
										?>
										
										<div class="product-similar-item">
											<div class="product-similar-item-image">
												<a href="<?php echo htmlspecialchars($link->url); ?>"><img src="asset.php?hash=<?php echo $link->asset->hash; ?>" alt="<?php echo $link->name; ?>" width="<?php echo $link->image->Width; ?>" height="<?php echo $link->image->Height; ?>" /></a>
											</div>
											
											<a href="<?php echo htmlspecialchars($link->url); ?>"><?php echo stripslashes($link->name); ?></a>
										</div>
										
										<?php
									}
									?>
									
									<div class="clear"></div>
								</div>
								
								<?php
							}
							?>
								
						</div>
						
						<?php
					}
					?>
					
					<div class="clear"></div>
					
					<div class="product-bar">
						<table>
							<tr>
								<td class="product-bar-item product-bar-item-ident">
									<div>
										<div class="product-bar-item-data">Quickfind</div>
										<strong class="colour-red text-large"><?php echo $product->ID; ?></strong>
										<div class="clear"></div>
									</div>
									<div>
										<div class="product-bar-item-data">Part No.</div>
										<strong class="colour-black"><?php echo $product->SKU; ?></strong>
										<div class="clear"></div>
									</div>
								</td>
								
								<?php
								if($session->Customer->Contact->IsTradeAccount == 'Y') {
									$stockData = new DataQuery(sprintf("SELECT SUM(ws.Quantity_In_Stock) AS Stock FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=%d", mysql_real_escape_string($product->ID)));
									if($stockData->TotalRows > 0) {
										if($stockData->Row['Stock'] > 0) {
											?>
									
											<td class="product-bar-item product-bar-item-stock">
												<strong class="colour-black">In Stock <span class="colour-orange"><?php echo $stockData->Row['Stock']; ?></span></strong><br />
												Available for delivery
											</td>
										
											<?php
										}
									}
									$stockData->Disconnect();
								}

								if($product->ShippingClass->ID == 45) {
									?>
									
									<td class="product-bar-item product-bar-item-shipping">
										<a href="deliveryRates.php">
											<strong class="colour-black"><span class="colour-red">FREE</span> Shipping Available</strong><br />
											On orders over &pound;45.00
										</a>
									</td>
									
									<?php
								} elseif($product->ShippingClass->ID == 64) {
									?>
									
									<td class="product-bar-item product-bar-item-coloured">
										<strong class="colour-black">Customise Product</strong><br />
										Lamps coloured to order
									</td>
								
									<?php
								}
								?>
								
								<td class="product-bar-item product-bar-item-accounts">
									<a href="creditAccount.php">
										<strong class="colour-blue">Corporate Accounts</strong><br />
										Credit accounts available
									</a>
								</td>
							</tr>
						</table>
					</div>
					
					<div class="tab-bar">
						<div class="tab-bar-item" id="tab-bar-item-overview">
							<a href="javascript: void(0);">Overview</a><br />
							<span class="tab-bar-item-sub">product details</span>
						</div>
					</div>
					
					<div class="tab-content">
						<div class="tab-content-item" id="tab-content-item-overview">
							<div class="tab-content-side">
								<div class="tab-content-title">
									<a name="tab-overview"></a>
									<h2>Product Summary</h2>
									this product at a glance
								</div>
								
								<div class="product-basic-specification">

									<?php
									$specColumns = 2;
									$specIndex = 0;
									$specCount = 0;
									
									$data = new DataQuery(sprintf("SELECT psg.Name, psg.Reference, psv.Value, CONCAT_WS(' ', psv.Value, psg.Units) AS UnitValue FROM product_specification AS ps LEFT JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID LEFT JOIN product_specification_group AS psg ON psv.Group_ID=psg.Group_ID AND psg.Is_Hidden='N' WHERE ps.Product_ID=%d AND psg.Is_Visible='Y' AND ps.Is_Primary='Y' ORDER BY psg.Name ASC", mysql_real_escape_string($product->ID)));
									if($data->TotalRows > 0) {
										?>
										
										<table class="list list-thin list-border">
										
											<?php
											while($data->Row) {
												if($specIndex == 0) {
													echo '<tr>';
												}
												?>

												<td class="list-image product-basic-specification-image">
												
													<?php
													$fileName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Reference']));

													if($fileName == 'rating') {
														$fileName .= '-' . strtolower($data->Row['Value']);
													}
													
													if(file_exists($GLOBALS['DIR_WS_ROOT'] . '/images/new/specification/' . $fileName . '.png')) {
														?>
													
														<img src="/images/new/specification/<?php echo $fileName; ?>.png" alt="<?php echo $data->Row['Name']; ?>" />
													
														<?php
													}
													?>
													
												</td>
												<td class="product-basic-specification-text">
													<strong><?php echo str_replace('/', ' / ', $data->Row['UnitValue']); ?></strong><br />
													<span class="colour-grey"><?php echo str_replace('/', ' / ', $data->Row['Name']); ?></span>
												</td>

												<?php
												$specIndex++;
												$specCount++;
												
												if(($specIndex == $specColumns) || ($specCount == $data->TotalRows)) {
													$specIndex = 0;
													
													echo '</tr>';
												}
												
												$data->Next();
											}
											?>
											
										</table>
										
										<?php
									}
									$data->Disconnect();
									?>

								</div>
								
								<div class="bullets">
									<ul>
										<li><a href="javascript:void(0);">Full Specification</a></li>
										
										<?php
										if(count($product->AlternativeCode) > 0) {
											?>
											
											<li><a href="javascript:void(0);">Alternative Part Codes</a></li>
											
											<?php
										}
										?>
										
									</ul>
								</div>
								
							</div>
							
							<div class="tab-content-guttering">
								<div class="tab-content-title">
									<h2><?php echo $product->Name; ?></h2>
									<?php
									if($product->Manufacturer->ID > 0) {
										echo sprintf('from %1$s ', $product->Manufacturer->Name);
									}
									
									if(!empty($product->Model)) {
										echo sprintf('(Model: %1$s)', $product->Model);
									}
									?>
								</div>
								
								<?php
								if(!empty($specEquivalentWattage) && !empty($specWattage) && !empty($specLampLife)) {
									$saving = ($specEquivalentWattage - $specWattage) * (12 / 100 / 1000) * $specLampLife;
									?>
									
									<div class="product-saving">
										<div class="attention">
											<div class="attention-info attention-info-general">
												<span class="attention-info-title">Energy Savings</span><br />
												Potential saving for <input class="text" type="text" value="1" size="3" maxlength="3" onkeyup="calculateSaving();" id="energy-saving-input-quantity" /> bulb over its manufacturers predicted life: <strong>&pound;<span id="energy-saving-total"><?php echo number_format($saving, 2, '.', ','); ?></span></strong><br /><br />
												Assuming electricity is charged at <span style="nowrap"><input class="text" type="text" value="12" size="4" maxlength="4" onkeyup="calculateSaving();" id="energy-saving-input-rate" />p</span> per kWh rate (most electricity companies charge approx. 12p per kWh).
											</div>
										</div>
									</div>
									
									<?php
								}
								
								if(isHtml($product->Description)) {
									echo $product->Description;
								} else {
									echo sprintf('<p>%s</p>', nl2br($product->Description));
								}

								if(count($product->Download) > 0) {
									?>
									
									<div class="tab-content-section">
									
										<div class="tab-content-title">
											<h3>Product Downloads</h3>
											available for this item
										</div>
										
										<?php
										if(!empty($product->Download)) {
											?>
											
											<table class="list">

												<?php
												foreach($product->Download as $download) {
													?>

													<tr>
														<td class="list-image" width="1%">
															<?php
															$items = explode('.', $download->file->FileName);
															
															$fileExtension = $items[count($items) - 1];
															$fileImage = 'images/icons/mimetypes/' . $fileExtension . '.png';

															if(file_exists($GLOBALS['DIR_WS_ROOT'] . $fileImage)) {
																echo sprintf('<a href="%s%s" target="_blank"><img src="%s" alt="%s" /></a>', $GLOBALS['PRODUCT_DOWNLOAD_DIR_WS'], $download->file->FileName, $fileImage, $download->name);
															}
															?>
														</td>
														<td>
															<a href="<?php echo $GLOBALS['PRODUCT_DOWNLOAD_DIR_WS'].$download->file->FileName; ?>" target="_blank"><?php echo $download->name; ?></a><br />
															<?php echo $download->description; ?>
														</td>
													</tr>

													<?php
												}
												?>

											</table>
											
											<?php
										}
										?>
										
									</div>
									
									<?php
								}
								
								if(count($product->Barcode) > 0) {
									?>
									
									<div class="tab-content-section">
									
										<div class="tab-content-title">
											<h3>Product Barcodes</h3>
											associated with this item
										</div>
										<br />
										
										<?php
										if(!empty($product->Barcode)) {
											?>
											
											<table class="list list-thin list-border-vertical">
												<?php
												foreach($product->Barcode as $barcode) {
													?>

													<tr>
														<td class="list-image" width="1%"><img src="images/icons/barcode.png" alt="Barcode" /></td>
														<td><?php echo $barcode['Barcode']; ?></td>
													</tr>
														
													<?php
												}
												?>
											</table>

											<?php
										}
										?>
										
									</div>
									
									<?php
								}
								?>
								
								<div class="tab-content-section">
									<div class="tab-content-title">
										<h3>Additional Information</h3>
										for product items in general
									</div>
							
									<div class="bullets">
										<ul>
											<li><a href="/deliveryRates.php">Delivery Information</a></li>
											<li><a href="/lampBaseExamples.php">Lamp Base Example</a></li>
											<li><a href="/energy-saving-bulbs.php">Energy Saving Comparisons</a></li>
											<li><a href="/beamangles.php">Beam Angles</a></li>
											<li><a href="/lampColourTemperatures.php">Colour Temperature Chart</a></li>
										</ul>
									</div>
								</div>
							</div>
							<div class="clear"></div>
								
						</div>
					</div>

					<?php if(count($product->Spec)) { ?>
					<div class="tab-bar">
						<div class="tab-bar-item" id="tab-bar-item-specifications">
							<a href="javascript: void(0);">Specifications</a><br />
							<span class="tab-bar-item-sub">technical information</span>
						</div>
					</div>

					<div class="tab-content">
						<div class="tab-content-item" id="tab-content-item-specifications">
							<?php
							if(count($product->AlternativeCode) > 0) {
								?>
								
								<div class="tab-content-side">
									<div class="tab-content-title">
										<h2>Part Codes</h2>
										of alternative stock identifiers
									</div>
									
									<?php
									if(!empty($product->AlternativeCode)) {
										?>
											
										<table class="list list-thin list-border">

											<?php
											foreach($product->AlternativeCode as $code) {
												?>

												<tr>
													<td><?php echo $code['Code']; ?></td>
												</tr>

												<?php
											}
											?>

										</table>
										
										<?php
									}
									?>
									
								</div>
									
								<div class="tab-content-guttering">
								
								<?php
							}
							?>
																
							<div class="tab-content-title">
								<a name="tab-specifications"></a>
								<h2>Technical Specifications</h2>
								for <?php echo $product->Name; ?>
							</div>
							
							<?php
							if(!empty($product->Spec)) {
								?>
										
								<table class="list list-thin list-border-vertical">

									<?php
									$columns = array();
									$columnsMax = 1;
									$columnIndex = 0;
									$rowIndex = 0;
									
									foreach($product->Spec as $spec) {
										if($rowIndex >= (count($product->Spec) / $columnsMax)) {
											$columnIndex++;
											$rowIndex = 0;
										}

										$columns[$columnIndex][] = $spec;
										$rowIndex++;
									}
									
									for($j=0; $j<count($columns[0]); $j++) {
										?>

										<tr>

											<?php
											for($k=0; $k<count($columns); $k++) {
												if(isset($columns[$k][$j])) {
													?>
												
													<td width="<?php echo 50 / $columnsMax; ?>%"><?php echo $columns[$k][$j]['Name']; ?></td>
													<td width="<?php echo 50 / $columnsMax; ?>%" class="list-heavy"><?php echo $columns[$k][$j]['UnitValue']; ?></td>
												
													<?php
												} else {
													?>
													
													<td></td>
													
													<?php
												}
											}
											?>

										</tr>

										<?php
									}
									?>

								</table>
							
								<?php
							}
							
							if(count($product->AlternativeCode) > 0) {
								?>
							
								</div>
								<div class="clear"></div>
								
								<?php
							}
							?>
							
						</div>
					</div>
					<?php } ?>

					<?php if(count($product->RelatedType[''])) { ?>
					<div class="tab-bar">
						<div class="tab-bar-item" id="tab-bar-item-related">
							<a href="javascript: void(0);">Related</a><br />
							<span class="tab-bar-item-sub"><?php echo count($product->RelatedType['']); ?> related items</span>
						</div>
					</div>

					<div class="tab-content">
						<div class="tab-content-item" id="tab-content-item-related">
							<div class="tab-content-title">
								<a name="tab-related"></a>
								<h2>Products Related</h2>
								to <?php echo $product->Name; ?>
							</div>
							
							<?php
							if(!empty($product->RelatedType[''])) {
								?>
								
								<table class="list">

									<?php
									foreach($product->RelatedType[''] as $related) {
										$subProduct = new Product();
										$subCategory = $category;

										if($subProduct->Get($related['Product_ID'])) {
											include('lib/templates/productLine.php');
										}
									}
									?>

								</table>
								
								<?php
							}
							?>
							
						</div>
					</div>
					<?php } ?>
					

					<?php if(count($product->RelatedType['Energy Saving Alternative'])) { ?>
					<div class="tab-bar">
						<div class="tab-bar-item" id="tab-bar-item-relatedenergysaving">
							<a href="javascript: void(0);">Energy Saving</a><br />
							<span class="tab-bar-item-sub"><?php echo count($product->RelatedType['Energy Saving Alternative']); ?> alternatives</span>
						</div>
					</div>

					<div class="tab-content">
						<div class="tab-content-item" id="tab-content-item-relatedenergysaving">
							<div class="tab-content-title">
								<a name="tab-relatedenergysaving"></a>
								<h2>Energy Saving Alternatives</h2>
								for <?php echo $product->Name; ?>
							</div>
							
							<?php
							if(!empty($product->RelatedType['Energy Saving Alternative'])) {
								?>
								
								<table class="list">

									<?php
									$hideSavings = false;
									
									foreach($product->RelatedType['Energy Saving Alternative'] as $related) {
										$subProduct = new Product();
										$subCategory = $category;

										if($subProduct->Get($related['Product_ID'])) {
											include('lib/templates/productLine.php');
										}
									}

									unset($hideSavings);
									?>

								</table>
								
								<?php
							}
							?>
							
						</div>
					</div>
					<?php } ?>

					<?php if(count($product->Component)) { ?>
					<div class="tab-bar">
						<div class="tab-bar-item" id="tab-bar-item-components">
							<a href="javascript: void(0);">Components</a><br />
							<span class="tab-bar-item-sub">includes <?php echo count($product->Component); ?> products</span>
						</div>
					</div>

					<div class="tab-content">
						<div class="tab-content-item" id="tab-content-item-components">
							<div class="tab-content-title">
								<a name="tab-components"></a>
								<h2>Product Components</h2>
								of <?php echo $product->Name; ?>
							</div>
							
							<?php
							if(!empty($product->Component)) {
								?>
							
								<table class="list">

									<?php
									foreach($product->Component as $component) {
										$subProduct = new Product();
										$subCategory = $category;

										if($subProduct->Get($component['Product_ID'])) {
											$componentQuantity = $component['Component_Quantity'];
											
											include('lib/templates/productLine.php');

											unset($componentQuantity);
										}
									}
									?>

								</table>
								
								<?php
							}
							?>
							
						</div>
					</div>
					<?php } ?>

					<div class="tab-bar">
						<div class="tab-bar-item" id="tab-bar-item-reviews">
							<a href="javascript: void(0);">Reviews</a><br />
							<span class="tab-bar-item-sub"><?php echo count($product->Review); ?> customer reviews</span>
						</div>
					</div>

					<div class="tab-content">
						<div class="tab-content-item" id="tab-content-item-reviews">
							<div class="tab-content-side">
								<div class="tab-content-title">
									<h2><?php echo count($product->Review); ?> Reviews</h2>
									submitted by customers
								</div>
								
								<div class="product-review-overview">
									<table class="list list-thin list-border">
										
										<?php
										for($i=$GLOBALS['PRODUCT_REVIEW_RATINGS']; $i>0; $i--) {
											$ratingStars = '';
											$ratingFrequency = 0;
											
											for($j=0; $j<$GLOBALS['PRODUCT_REVIEW_RATINGS']; $j++) {
												$ratingStars .= sprintf('<img src="/images/new/product/rating%s.png" alt="Product Rating" />', (ceil($i) > $j) ? '-solid' : '');
											}
											
											for($j=0; $j<count($product->Review); $j++) {
												if(($product->Review[$j]['Rating'] * $GLOBALS['PRODUCT_REVIEW_RATINGS']) == $i) {
													$ratingFrequency++;
												}
											}
											?>

											<tr>
												<td class="product-review-overview-star"><?php echo $ratingStars; ?></td>
												<td class="product-review-overview-extent">
													<div class="product-review-overview-extent-percent">
														
														<?php
														if($ratingFrequency > 0) {
															$ratingWidth = ($ratingFrequency / count($product->Review)) * 100;
															
															echo sprintf('<div class="product-review-overview-extent-percent-ratio" style="width: %s%%;"></div>', $ratingWidth);
														}
														?>
														
													</div>
												</td>
												<td class="product-review-overview-frequency">(<?php echo $ratingFrequency; ?>)</td>
											</tr>

											<?php

										}
										?>

									</table>
								</div>
							</div>
							
							<div class="tab-content-guttering">		
								<div class="tab-content-title">
									<a name="tab-reviews"></a>
									<h2>Customer Reviews</h2>
									for <?php echo $product->Name; ?>
									
									<?php
									if(!empty($product->Review)) {
										?>
										
										<div class="product-review-summary">
											<p>Average Customer Review</p>
											
											<div class="product-stars">
												<?php
												$rating = $product->ReviewAverage;
												$ratingStars = number_format($rating * $GLOBALS['PRODUCT_REVIEW_RATINGS'], 1, '.', '');

												for($i=0; $i<$GLOBALS['PRODUCT_REVIEW_RATINGS']; $i++) {
													?>
													
													<div class="product-stars-item <?php echo (ceil($ratingStars) > $i) ? 'product-stars-item-solid' : ''; ?>"></div>
													
													<?php
												}
												?>
												
												<div class="product-stars-score"><?php echo round($ratingStars) . '/' . $GLOBALS['PRODUCT_REVIEW_RATINGS']; ?></div>
												<div class="clear"></div>
											</div>
										</div>
									
										<?php
									}
									
									if(!$session->IsLoggedIn) {
										?>
										
										<div class="product-review-create">
											<input type="button" name="create" value="Create Review" class="button" onclick="redirect('gateway.php');" />
											<p>You must be logged in to submit a review.</p>
										</div>
										
										<?php
									} else {
										if(isset($_REQUEST['reviews']) && ($_REQUEST['reviews'] == 'thanks')) {
											?>
											
											<div class="attention">
												<div class="attention-info attention-info-feedback">
													<span class="attention-info-title">Thank You For Your Review</span><br />
													Thank you for taking the time to review this product. Your review will become visible to other customers once approved.
												</div>
											</div>

											<?php
										} else {
											?>
										
											<div class="product-review-create" id="product-review-create" <?php echo (!$formReview->Valid) ? 'style="display: none;"' : ''; ?>>
												<input type="button" name="create" value="Create Review" class="button" onclick="showReview();" />
												<p>Share you product experiences and thoughts with others.</p>
											</div>
											
											<div class="product-review-input" id="product-review-input" <?php echo ($formReview->Valid) ? 'style="display: none;"' : ''; ?>>
											
												<?php
												if(!$formReview->Valid) {
													?>
							
													<div class="attention">
														<div class="attention-icon attention-icon-warning"></div>
														<div class="attention-info attention-info-warning">
															<span class="attention-info-title">Please Correct The Following</span><br />
															
															<ol>
															
																<?php
																for($i=0; $i<count($formReview->Errors); $i++) {
																	echo sprintf('<li>%s</li>', $formReview->Errors[$i]);
																}
																?>
																
															</ol>
														</div>
													</div>
													
													<?php
												}
												
												echo $formReview->Open();
												echo $formReview->GetHTML('confirm');
												echo $formReview->GetHTML('form');
												echo $formReview->GetHTML('tab');
												echo $formReview->GetHTML('pid');
												echo $formReview->GetHTML('cat');

												echo sprintf('<p>Please enter a title for your review <small>(50 chars. max)</small><br />%s</p>', $formReview->GetHTML('title'));
												echo sprintf('<p>Enter your review below<br />%s</p>', $formReview->GetHTML('review'));
												echo sprintf('<p>What would you rate this product?<br />%s</p>', $formReview->GetHTML('rating'));

												echo '<input name="submit" type="submit" value="Submit For Approval" class="button" />';

												echo $formReview->Close();
												?>
												
											</div>
										
											<?php
										}
									}
									
									if(!empty($product->Review)) {
										?>
									
										<table class="list">

											<?php
											foreach($product->Review as $review) {
												?>
												
												<tr>
													<td>
														<div class="product-stars">
															<?php
															$ratingStars = number_format($review['Rating'] * $GLOBALS['PRODUCT_REVIEW_RATINGS'], 1, '.', '');

															for($i=0; $i<$GLOBALS['PRODUCT_REVIEW_RATINGS']; $i++) {
																?>
																
																<div class="product-stars-item <?php echo (ceil($ratingStars) > $i) ? 'product-stars-item-solid' : ''; ?>"></div>
																
																<?php
															}
															?>
															
															<div class="product-stars-quote">&quot;<?php echo $review['Title']; ?>&quot;</div>
															<div class="clear"></div>
														</div>
														<div class="product-review-test">
															<p><span class="colour-black">By <?php echo !empty($review['Country_Name']) ? $review['Customer_Name'] : '<em>Anonymous</em>'; ?> (<?php echo !empty($review['Country_Name']) ? $review['Country_Name'] : '<em>Unknown</em>'; ?>), <?php echo date('j M Y', strtotime($review['Created_On'])); ?></span><br /><?php echo nl2br(stripslashes($review['Review'])); ?></p>
														</div>
													</td>
												</tr>
												
												<?php
											}
											?>

										</table>
										
										<?php
									}
									?>
							
								</div>
							</div>
							
							<div class="clear"></div>
						</div>
					</div>
					
					<div class="tab-bar">
						<div class="tab-bar-item" id="tab-bar-item-enquire">
							<a href="javascript: void(0);">Enquire</a><br />
							<span class="tab-bar-item-sub">product enquiry</span>
						</div>
					</div>

					<div class="tab-content">
						<div class="tab-content-item" id="tab-content-item-enquire">
							<div class="tab-content-title">
								<a name="tab-enquire"></a>
								<h2>Product Enquiry</h2>
								enquire about <?php echo $product->Name; ?>
							</div>
							
							<?php
							if(isset($_REQUEST['enquire']) && ($_REQUEST['enquire'] == 'thanks')) {
								?>

								<div class="attention">
									<div class="attention-info attention-info-feedback">
										<span class="attention-info-title">Thank You For Your Enquiry</span><br />
										Your details have been sent to us and we will be in contact with you as soon as possible.
									</div>
								</div>
									
								<?php
							} else {
								if(!$formEnquiry->Valid) {
									?>
			
									<div class="attention">
										<div class="attention-icon attention-icon-warning"></div>
										<div class="attention-info attention-info-warning">
											<span class="attention-info-title">Please Correct The Following</span><br />
											
											<ol>
											
												<?php
												for($i=0; $i<count($formEnquiry->Errors); $i++) {
													echo sprintf('<li>%s</li>', $formEnquiry->Errors[$i]);
												}
												?>
												
											</ol>
										</div>
									</div>
									
									<?php
								}
							}
							?>
							
							<p>Please complete the fields below. Required fields are marked with an asterisk (*).</p>
							
							<?php												
							echo $formEnquiry->Open();
							echo $formEnquiry->GetHTML('confirm');
							echo $formEnquiry->GetHTML('form');
							echo $formEnquiry->GetHTML('tab');
							echo $formEnquiry->GetHTML('pid');
							echo $formEnquiry->GetHTML('cat');

							if(!$session->IsLoggedIn) {
								?>
								
								<div class="form-block">
									<div class="form-column">
										<p>Personal title <?php echo $formEnquiry->GetIcon('title'); ?><br /><?php echo $formEnquiry->GetHTML('title'); ?></p>
									</div>
									<div class="form-column">
										<p>Business name <?php echo $formEnquiry->GetIcon('businessname'); ?><br /><?php echo $formEnquiry->GetHTML('businessname'); ?></p>
									</div>
									<div class="form-column">
										<p>Form validation code <?php echo $formEnquiry->GetIcon('code'); ?><br /><?php echo $formEnquiry->GetHTML('code'); ?></p>
									</div>
									<div class="clear"></div>
								</div>
								
								<div class="form-block">
									<div class="form-column">
										<p>First name <?php echo $formEnquiry->GetIcon('firstname'); ?><br /><?php echo $formEnquiry->GetHTML('firstname'); ?></p>
									</div>
									<div class="form-column">
										<p>Last name <?php echo $formEnquiry->GetIcon('lastname'); ?><br /><?php echo $formEnquiry->GetHTML('lastname'); ?></p>
									</div>
									<div class="form-column">
										<span class="captcha">
											<img src="securimage.php" alt="Click to change form validation image" onclick="this.src = 'securimage.php?sid=' + Math.random();" />
										</span>
										
										<object type="application/x-shockwave-flash" data="/ignition/packages/Securimage/securimage_play.swf?audio=/ignition/packages/Securimage/securimage_play.php&amp;bgColor1=#fff&amp;bgColor2=#fff&amp;iconColor=#777&amp;borderWidth=1&amp;borderColor=#000" width="19" height="19">
											<param name="movie" value="/ignition/packages/Securimage/securimage_play.swf?audio=/ignition/packages/Securimage/securimage_play.php&amp;bgColor1=#fff&amp;bgColor2=#fff&amp;iconColor=#777&amp;borderWidth=1&amp;borderColor=#000" />
										</object>

									</div>
									<div class="clear"></div>
								</div>
								<div class="form-block">
									<div class="form-column">
										<p>E-mail address <?php echo $formEnquiry->GetIcon('email'); ?><br /><?php echo $formEnquiry->GetHTML('email'); ?></p>
									</div>
									<div class="form-column">
										<p>Phone number <?php echo $formEnquiry->GetIcon('phone'); ?><br /><?php echo $formEnquiry->GetHTML('phone'); ?></p>
									</div>
									<div class="clear"></div>
								</div>
								
								<?php
							}
							
							echo sprintf('<p>Enter your enquiry to us %s<br />%s</p>', $formEnquiry->GetIcon('message'), $formEnquiry->GetHTML('message'));
							echo '<input name="submit" type="submit" value="Submit Enquiry" class="button" />';

							echo $formEnquiry->Close();
							?>
							
						</div>
					</div>

					<?php include('lib/templates/back.php'); ?>

					<!-- InstanceEndEditable -->
            
            <div class="clear"></div>
        </div>
    </div>

	<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-1618935-2']);
  _gaq.push(['_setDomainName', 'bltdirect.com']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>

	<!-- InstanceBeginEditable name="Tracking Script" -->
<!--
<script>
var parm,data,rf,sr,htprot='http'; if(self.location.protocol=='https:')htprot='https';
rf=document.referrer;sr=document.location.search;
if(top.document.location==document.referrer||(document.referrer == '' && top.document.location != '')) {rf=top.document.referrer;sr=top.document.location.search;}
data='cid=256336&rf=' + escape(rf) + '&sr=' + escape(sr); parm=' border="0" hspace="0" vspace="0" width="1" height="1" '; document.write('<img '+parm+' src="'+htprot+'://stats1.saletrack.co.uk/scripts/stinit.asp?'+data+'">');
</script>
<noscript>
<img src="http://stats1.saletrack.co.uk/scripts/stinit.asp?cid=256336&rf=JavaScript%20Disabled%20Browser" width="0" height="0" />
</noscript>
-->
<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>