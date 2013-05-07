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
	<script type="text/javascript" src="/js/slimbox.js"></script>
	<link rel="stylesheet" href="/css/slimbox.css" type="text/css" media="screen" />
	<?php
	if($product->IntegrationID > 0) {
		echo '<meta name="robots" content="noindex, nofollow" />';
	}
	?>
	<link rel="stylesheet" type="text/css" href="/css/new.css" />
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
		
		addContent('reviews');
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
					}?>
					
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
						?>
					</div>
					
					
						
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
								
						</div>
					
					<div class="clear"></div>
					
					<div class="tab-bar">
						
						<div class="tab-bar-item tab-bar-item-selected" id="tab-bar-item-reviews" onclick="setContent('reviews');">
							<a href="javascript: void(0);">Reviews</a><br />
							<span class="tab-bar-item-sub"><?php echo count($product->Review); ?> customer reviews</span>
						</div>						
						<div class="clear"></div>
					</div>
					
					<div class="tab-content">

						
						
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
											
											<div class="product-review-input" id="product-review-input">
											
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
									} ?>
							
								</div>
							</div>
							
							<div class="clear"></div>
						</div>

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