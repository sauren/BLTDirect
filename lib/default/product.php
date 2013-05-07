<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title><?php echo $product->Name; ?></title>
	<!-- InstanceEndEditable -->

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en" />
	<link rel="stylesheet" type="text/css" href="css/lightbulbs.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="css/lightbulbs_print.css" media="print" />
	<link rel="stylesheet" type="text/css" href="css/Navigation.css" />
	<link rel="stylesheet" type="text/css" href="css/Menu.css" />
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'Y') {
		?>
		<link rel="stylesheet" type="text/css" href="css/Trade.css" />
        <?php
	}
	?>
	<link rel="shortcut icon" href="favicon.ico" />
<!--    <script type='text/javascript' src='http://api.handsetdetection.com/sites/js/43071.js'></script>-->
	<script language="javascript" type="text/javascript" src="js/generic.js"></script>
	<script language="javascript" type="text/javascript" src="js/evance_api.js"></script>
	<script language="javascript" type="text/javascript" src="js/mootools.js"></script>
	<script language="javascript" type="text/javascript" src="js/evance.js"></script>
	<script language="javascript" type="text/javascript" src="js/bltdirect.js"></script>
    <script language="javascript" type='text/javascript' src="js/api.js"></script>
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'N') {
		?>
		<script language="javascript" type="text/javascript" src="js/bltdirect/template.js"></script>
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
	<!-- InstanceBeginEditable name="head" -->
	<meta name="Keywords" content="<?php echo $product->MetaKeywords; ?>" />
	<meta name="Description" content="<?php echo $product->MetaDescription; ?>" />
	<script type="text/javascript" src="js/slimbox.js"></script>

	<link rel="stylesheet" type="text/css" href="css/jquery.fancybox.css" />
	<!--<script type="text/javascript" src="/js/jquery-1.8.0.min.js"></script>-->
	<script type="text/javascript" src="js/fancybox.js"></script>


	<link rel="stylesheet" href="css/slimbox.css" type="text/css" media="screen" />
	<link rel="canonical" href="<?php echo sprintf('http://localhost/steve/product.php?pid=%d', $product->ID); ?>" />
	<?php
	if($product->IntegrationID > 0) {
		echo '<meta name="robots" content="noindex, nofollow" />';
	}
	?>
	<link rel="stylesheet" type="text/css" href="css/new.css" />
	<script type="text/javascript" src="js/tabs.js"></script>
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

		function toggleUpgrades() {
			var element = document.getElementById('product-upgrade');
			
			if(element) {
				element.style.display = (element.style.display == 'none') ? 'block' : 'none';
			}
		}

		function closeUpgrades() {
			var element = document.getElementById('product-upgrade');
			
			if(element) {
				element.style.display = 'none';
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
		addContent('examples');
	</script>


	<script type="text/javascript">
		jQuery(document).ready(function() {

			jQuery("a[data-video]").click(function(e){
					e.preventDefault();

					jQuery.fancybox({
			            'padding'       : 0,
			            'autoScale'     : false,
			            'transitionIn'  : 'none',
			            'transitionOut' : 'none',
			            'title'			: this.title,
			            'width'         : 640,
			            'height'        : 385,
			            'href'          : this.href.replace(new RegExp("watch\\?v=", "i"), 'v/'),
			            'type'          : 'swf',
			            'swf'           : {
				            'wmode'             : 'transparent',
				            'allowfullscreen'   : 'true'
				            }
	       			 });
				return false;
			});
		});
	</script>
	<!-- InstanceEndEditable -->
</head>
<body>

    <div id="Wrapper">
        <div id="Header">
            <div id="HeaderInner">
                <?php require('lib/templates/header.php'); ?>
            </div>
        </div>
        <div id="PageWrapper">
            <div id="Page">
                <div id="PageContent">
                    <?php
                    if(strtolower(Setting::GetValue('site_message_active')) == 'true') {
                        ?>

                        <div id="SiteMessage">
                            <div id="SiteMessageLeft">
                                <div id="SiteMessageRight">
                                    <marquee scrollamount="4"><?php echo Setting::GetValue('site_message_value'); ?></marquee>
                                </div>
                            </div>
                        </div>

                        <?php
                    }
                    ?>
                    
                    <a name="top"></a>
                    
                    <!-- InstanceBeginEditable name="pageContent" -->
              		<h1><?php echo $product->Name; ?></h1>
					<p class="breadcrumb"><a href="/">Home</a> <?php echo isset($breadCrumb) ? $breadCrumb->Text : ''; ?></p>

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
										$image->Thumb->Width = round($image->Thumb->Width / 2);
										$image->Thumb->Height = round($image->Thumb->Height / 2);
										?>
										
										<div class="product-image-thumb-item">
											<img src="<?php echo $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$image->Thumb->FileName; ?>" alt="<?php echo $image->Name; ?>" width="<?php echo $image->Thumb->Width; ?>" height="<?php echo $image->Thumb->Height; ?>" onmouseover="setImage('<?php echo $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$image->Large->FileName; ?>', '<?php echo $image->Name; ?>');" />
										</div>
										
										<?php
									}
								}
								
								foreach($product->Example as $image) {
									if(file_exists($GLOBALS['PRODUCT_EXAMPLE_IMAGES_DIR_FS'].$image->Thumb->FileName)) {
										$image->Thumb->Width = round($image->Thumb->Width / 2);
										$image->Thumb->Height = round($image->Thumb->Height / 2);
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

						/*Youtube*/
						$youtubeCount = count($yt_Videos->Videos);
						if($youtubeCount > 0) {?>
							<div class="product-video-thumb">
							<?php foreach($yt_Videos->Videos as $video){
								$url = $video['Youtube_Url'];
       							preg_match('/.*[?&]v=([^&]+)/i', $url, $matches);
        						$videoId = $matches[1];
        						$pid=param('pid');

								get_product_yt_video($videoId, $pid);
							}?>
							</div>
					<?php }
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

							<?php
							if(!empty($product->Quality)) {
								?>
								
								<div class="product-quality product-quality-<?php echo strtolower($product->Quality); ?>" id="product-quality">
								
									<?php
									if(($product->Quality == 'Value') && !empty($product->QualityLinkType['Premium'])) {
										?>

										<div class="product-quality-upgrade">
											<a href="javascript:toggleUpgrades();">Upgrade</a>
										</div>

										<?php
									}
									?>

									<div class="product-quality-type"><?php echo $product->Quality; ?> Range</div>
									<div class="clear"></div>
								</div>

								<?php
								if(!empty($product->QualityLinkType['Premium'])) {
									?>

									<div class="product-quality-options">

										<div class="product-upgrade" id="product-upgrade" style="display: none;">
											<div class="product-upgrade-arrow">
												<div class="product-upgrade-arrow-image"></div>
											</div>
											<div class="product-upgrade-box">
												<a class="product-upgrade-close" href="javascript:closeUpgrades();"></a>
												<div class="product-upgrade-title">
													<h2>Premium Range</h2>
													Upgrade this product to...
												</div>
												
												<div class="product-upgrade-product">

													<?php
													if(!empty($product->QualityText)) {
														?>

														<div class="product-upgrade-product-quality"><?php echo $product->QualityText; ?></div>

														<?php
													}

													foreach($product->QualityLinkType['Premium'] as $quality) {
														$subProduct = new Product();

														if($subProduct->Get($quality['Product_ID'])) {
															$cartDirect = 'product.php?pid=' . $subProduct->ID;
															$analyticsTag = 'upgrade';

															include('lib/templates/productPanel.php');

															unset($analyticsTag);
															unset($cartDirect);

															break;
														}
													}
													?>

													<div class="clear"></div>
												</div>
											</div>
										</div>

									</div>

									<?php
								}
							}
							?>
						
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
												<th class="alignRight">Bulk Price</th>
												<th class="alignRight">Saving</th>
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
													<td class="alignRight">&pound;<?php echo number_format($price, 2, '.', ','); ?> each</td>
													<td class="alignRight"><?php echo ($saving > 0) ? number_format($saving, 2, '.', ',') . '%' : ''; ?></td>
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
												<td class="alignRight">&pound;<?php echo number_format($data->Row['Delivery'], 2, '.', ','); ?></td>
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
									<form action="customise.php" method="post" name="buy" id="buy">
										<input type="hidden" name="action" value="customise" />
										<input type="hidden" name="direct" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" />
										<input type="hidden" name="product" value="<?php echo $product->ID; ?>" />
										<input type="hidden" name="category" value="<?php echo $category->ID; ?>" />
										
										<div class="product-button-buy-field">
											<input type="text" name="quantity" value="<?php echo ($product->OrderMin > 0) ? $product->OrderMin : 1; ?>" size="3" maxlength="4" class="product-button-buy-field-text" />
											<input type="image" name="buy" alt="Buy" src="/images/new/product/buy.png" />
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
												<a href="<?php echo htmlspecialchars($link->url); ?>"><img src="asset.php?hash=<?php echo $link->asset->hash; ?>" alt="<?php echo $link->name; ?>" width="<?php echo $link->image->Width; ?>" height="<?php echo number_format($link->image->Height); ?>" /></a>
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
						<div class="tab-bar-item <?php echo ($tab == 'overview') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-overview" onclick="setContent('overview');">
							<a href="javascript: void(0);">Overview</a><br />
							<span class="tab-bar-item-sub">product details</span>
						</div>
						
						<?php
						if(count($product->Spec)) {
							?>
							
							<div class="tab-bar-item <?php echo ($tab == 'specifications') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-specifications" onclick="setContent('specifications');">
								<a href="javascript: void(0);">Specifications</a><br />
								<span class="tab-bar-item-sub">technical information</span>
							</div>
						
							<?php
						}
						
						if(count($product->RelatedType[''])) {
							?>
							
							<div class="tab-bar-item <?php echo ($tab == 'related') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-related" onclick="setContent('related');">
								<a href="javascript: void(0);">Related</a><br />
								<span class="tab-bar-item-sub"><?php echo count($product->RelatedType['']); ?> related items</span>
							</div>
							
							<?php
						}
						
						if(count($product->RelatedType['Energy Saving Alternative'])) {
							?>
							
							<div class="tab-bar-item <?php echo ($tab == 'relatedenergysaving') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-relatedenergysaving" onclick="setContent('relatedenergysaving');">
								<a href="javascript: void(0);">Energy Saving</a><br />
								<span class="tab-bar-item-sub"><?php echo count($product->RelatedType['Energy Saving Alternative']); ?> alternatives</span>
							</div>
							
							<?php
						}

						if(count($product->Component)) {
							?>
							
							<div class="tab-bar-item <?php echo ($tab == 'components') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-components" onclick="setContent('components');">
								<a href="javascript: void(0);">Components</a><br />
								<span class="tab-bar-item-sub">includes <?php echo count($product->Component); ?> products</span>
							</div>
							
							<?php
						}
						?>
						
						<div class="tab-bar-item <?php echo ($tab == 'reviews') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-reviews" onclick="setContent('reviews');">
							<a href="javascript: void(0);">Reviews</a><br />
							<span class="tab-bar-item-sub"><?php echo count($product->Review); ?> customer reviews</span>
						</div>
						
						<div class="tab-bar-item <?php echo ($tab == 'enquire') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-enquire" onclick="setContent('enquire');">
							<a href="javascript: void(0);">Enquire</a><br />
							<span class="tab-bar-item-sub">product enquiry</span>
						</div>
						
						<?php
						if(strtolower($productType) == 'led') {
							if($session->IsLoggedIn) {
								if($hasCustomerBought) {
									?>
									
									<div class="tab-bar-item <?php echo ($tab == 'examples') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-examples" onclick="setContent('examples');">
										<a href="javascript: void(0);">Examples</a><br />
										<span class="tab-bar-item-sub">submit example</span>
									</div>
									
									<?php
								}
							}
						}
						?>
						
						<div class="clear"></div>
					</div>
					
					<div class="tab-content">
						<div class="tab-content-item" id="tab-content-item-overview" <?php echo ($tab == 'overview') ? '' : 'style="display: none;"'; ?>>
							<div class="tab-content-side">
								<div class="tab-content-title">
									<a id="tab-overview"></a>
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

												<tr>
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
												
												if(($specCount & 1 == 1) && ($specCount == $data->TotalRows)){
													echo '<td>&nbsp;</td><td>&nbsp;</td>';
												}

												if(($specIndex == $specColumns) || ($specCount == $data->TotalRows)) {
													$specIndex = 0;
													
													echo '</tr>';
												}
												
												$data->Next();
											}
											?>
											
								  </tr>
								  </table>
										
										<?php
									}
									$data->Disconnect();
									?>

								</div>
								
								<div class="bullets">
									<ul>
										<li><a href="javascript:void(0);" onclick="setContent('specifications');">Full Specification</a></li>
										
										<?php
										if(count($product->AlternativeCode) > 0) {
											?>
											
											<li><a href="javascript:void(0);" onclick="setContent('specifications');">Alternative Part Codes</a></li>
											
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
									echo sprintf('%s', nl2br($product->Description));
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
														<td class="list-image" style="width:1%">
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
														<td class="list-image" style="width:1%"><img src="images/icons/barcode.png" alt="Barcode" /></td>
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
						
						<div class="tab-content-item" id="tab-content-item-specifications" <?php echo ($tab == 'specifications') ? '' : 'style="display: none;"'; ?>>
							
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
								<a id="tab-specifications"></a>
								<h2>Technical Specifications</h2>
								for <?php echo $product->Name; ?>
							</div>
							
							<?php
							if(!empty($product->Spec)) {
								?>
										
								<table class="list list-thin list-border-vertical">

									<?php
									$columns = array();
									$columnsMax = 2;
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
												
													<td style="width:<?php echo 50 / $columnsMax; ?>%"><?php echo $columns[$k][$j]['Name']; ?></td>
													<td style="width:<?php echo 50 / $columnsMax; ?>%" class="list-heavy"><?php echo $columns[$k][$j]['UnitValue']; ?></td>
												
													<?php
												} else {
													?>
													
													<td></td><td></td>
													
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
						
						<div class="tab-content-item" id="tab-content-item-related" <?php echo ($tab == 'related') ? '' : 'style="display: none;"'; ?>>
							<div class="tab-content-title">
								<a id="tab-related"></a>
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
						
						<div class="tab-content-item" id="tab-content-item-relatedenergysaving" <?php echo ($tab == 'relatedenergysaving') ? '' : 'style="display: none;"'; ?>>
							<div class="tab-content-title">
								<a id="tab-relatedenergysaving"></a>
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
						
						<div class="tab-content-item" id="tab-content-item-components" <?php echo ($tab == 'components') ? '' : 'style="display: none;"'; ?>>
							<div class="tab-content-title">
								<a id="tab-components"></a>
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
						
						<div class="tab-content-item" id="tab-content-item-reviews" <?php echo ($tab == 'reviews') ? '' : 'style="display: none;"'; ?>>
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
												$ratingStars .= sprintf('<img src="images/new/product/rating%s.png" alt="Product Rating" />', (ceil($i) > $j) ? '-solid' : '');
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
								
								<?php
								if(strtolower($productType) == 'led') {
									if($session->IsLoggedIn) {
										if($hasCustomerBought) {
											?>
									
											<div class="bullets">
												<ul>
													<li><a href="javascript:void(0);" onclick="setContent('examples');">Submit your examples</a></li>
												</ul>
											</div>
											
											<?php
										}
									}
								}
								?>
								
							</div>
							
							<div class="tab-content-guttering">		
								<div class="tab-content-title">
									<a id="tab-reviews"></a>
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
						
						<div class="tab-content-item" id="tab-content-item-enquire" <?php echo ($tab == 'enquire') ? '' : 'style="display: none;"'; ?>>
							<div class="tab-content-title">
								<a id="tab-enquire"></a>
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
										
										<object type="application/x-shockwave-flash" data="<?php echo rawurlencode('ignition/packages/Securimage/securimage_play.swf?audio=/ignition/packages/Securimage/securimage_play.php&amp;bgColor1=#fff&amp;bgColor2=#fff&amp;iconColor=#777&amp;borderWidth=1&amp;borderColor=#000'); ?>" width="19" height="19">
											<param name="movie" value="<?php echo rawurlencode('ignition/packages/Securimage/securimage_play.swf?audio=/ignition/packages/Securimage/securimage_play.php&amp;bgColor1=#fff&amp;bgColor2=#fff&amp;iconColor=#777&amp;borderWidth=1&amp;borderColor=#000'); ?>" />
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
						
						<?php
						if(strtolower($productType) == 'led') {
							if($session->IsLoggedIn) {
								if($hasCustomerBought) {
									?>
								
									<div class="tab-content-item" id="tab-content-item-examples" <?php echo ($tab == 'examples') ? '' : 'style="display: none;"'; ?>>
										<div class="tab-content-title">
											<a id="tab-examples"></a>
											<h2>Product Examples</h2>
											submit your product example for <?php echo $product->Name; ?>
										</div>
										
										<?php
										if(isset($_REQUEST['examples']) && ($_REQUEST['examples'] == 'thanks')) {
											?>

											<div class="attention">
												<div class="attention-info attention-info-feedback">
													<span class="attention-info-title">Thank You For Your Example</span><br />
													Your image has been sent to us and we will review and publish it as soon as possible.
												</div>
											</div>
												
											<?php
										} else {
											if(!$formExamples->Valid) {
												?>
						
												<div class="attention">
													<div class="attention-icon attention-icon-warning"></div>
													<div class="attention-info attention-info-warning">
														<span class="attention-info-title">Please Correct The Following</span><br />
														
														<ol>
														
															<?php
															for($i=0; $i<count($formExamples->Errors); $i++) {
																echo sprintf('<li>%s</li>', $formExamples->Errors[$i]);
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
										echo $formExamples->Open();
										echo $formExamples->GetHTML('confirm');
										echo $formExamples->GetHTML('form');
										echo $formExamples->GetHTML('tab');
										echo $formExamples->GetHTML('pid');
										echo $formExamples->GetHTML('cat');

										echo sprintf('<p>Select your example image %s<br />%s</p>', $formExamples->GetIcon('image'), $formExamples->GetHTML('image'));
										echo '<input name="submit" type="submit" value="Submit Example" class="button" />';

										echo $formExamples->Close();
										?>
										
									</div>
									
									<?php
								}
							}
						}
						?>
						
					</div>
					
					<?php include('lib/templates/back.php'); ?>
					<?php include('lib/templates/recent.php'); ?>

					<!-- InstanceEndEditable -->
                </div>
            </div>
            <div id="PageFooter">
                <ul class="links">
                    <li><a href="./terms.php" title="BLT Direct Terms and Conditions of Use and Sale">Terms and Conditions</a></li>
                    <li><a href="./privacy.php" title="BLT Direct Privacy Policy">Privacy Policy</a></li>
                    <li><a href="./company.php" title="About BLT Direct">About Us</a></li>
                    <li><a href="./sitemap.php" title="Map of Site Contents">Site Map</a></li>
                    <li><a href="./support.php" title="Contact BLT Direct">Contact Us</a></li>
                    <li><a href="./index.php" title="Light Bulbs">Light Bulbs</a></li>
                    <li><a href="./products.php?cat=1251&amp;nm=Christmas+Lights" title="Christmas Lights">Christmas Lights</a></li> 
                    <li><a href="./Projector-Lamps.php" title="Projector Lamps">Projector Lamps</a></li>
                    <li><a href="./articles.php" title="Press Releases/Articles">Press Releases/Articles</a></li>
                </ul>
                
                <p class="copyright">Copyright &copy; BLT Direct, 2005. All Right Reserved.</p>
            </div>
        </div>
        <div id="LeftNav">
            <?php require('lib/templates/left.php'); ?>
        </div>
        <div id="RightNav">
            <?php require('lib/templates/right.php'); ?>
        
            <div id="Azexis">
                <a href="http://www.azexis.com" target="_blank" title="Web Designers">Web Designers</a>
            </div>
        </div>
    </div>
	<script src="<?php print ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT']) ? 'http://www' : 'https://ssl'; ?>.google-analytics.com/urchin.js" type="text/javascript"></script>
	<script type="text/javascript">
	//<![CDATA[
		_uacct = "UA-1618935-2";
		urchinTracker();
	//]]>
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