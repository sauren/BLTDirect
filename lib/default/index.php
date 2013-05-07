<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CategoryBreadCrumb.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountCollection.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cache.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Pages.php");

function getProduct($productId) {
	global $session;
	global $discountCollection;

	$product = new Product($productId);

	$product->GetReviews();

	$item = array();
	$item['Product'] = $product;
	$item['RRPPrice'] = number_format($product->PriceRRP, 2, '.', ',');
	
	if($session->IsLoggedIn && ($session->Customer->Contact->IsTradeAccount == 'Y')) {
		$tradeCost = ($product->CacheRecentCost > 0) ? $product->CacheRecentCost : $product->CacheBestCost;
		
		$item['OurPrice'] = number_format(ContactProductTrade::getPrice($session->Customer->Contact->ID, $product->ID), 2, '.', ',');
		$item['OurPrice'] = number_format(($item['OurPrice'] <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $product->ID) / 100) + 1) : $item['OurPrice'], 2, '.', ',');
		
		$item['CurrentPrice'] = $item['OurPrice'];
		$item['PercentSaving'] = 0;
	} else {
		$item['OurPrice'] = number_format($product->PriceOurs, 2, '.', ',');
		$item['CurrentPrice'] = number_format($product->PriceCurrent, 2, '.', ',');
		$item['PercentSaving'] = $product->PriceSavingPercent;
	}
	
	$item['BuyPrice'] = number_format($item['CurrentPrice'], 2, '.', ',');

	if($session->IsLoggedIn) {
		if($session->Customer->Contact->IsTradeAccount == 'N') {
			if(count($discountCollection->Line) > 0){
				list($tempLineTotal, $discountName) = $discountCollection->DiscountProduct($product, 1);

				if($tempLineTotal < $product->PriceCurrent)  {
					$priceSavingPercent = ($product->PriceRRP > 0) ? round((($product->PriceRRP - $tempLineTotal) / $product->PriceRRP) * 100) : 0;

					if($priceSavingPercent > 0) {
						$item['BuyPrice'] = number_format($tempLineTotal, 2, '.', ',');
						$item['PercentSaving'] = $priceSavingPercent;
					}
				}
			}
		}
	}
	
	return $item;
}

if($session->Customer->Contact->IsTradeAccount == 'Y') {
	redirectTo('accountcenter.php');	
}

$limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 10;

$productStore = array();

$category = new Category();
$category->Name = 'Products';
$category->MetaKeywords = 'Bulbs, Lamps, Tubes, Sunbed, Lightbulbs, Lighting, Lights';
$category->MetaDescription = 'Suppliers of Bulbs, Lamps and Tubes.';
$category->Description = '';
$category->ID = 0;

if(isset($_REQUEST['cat']) && !empty($_REQUEST['cat']) && is_numeric($_REQUEST['cat'])){
	$category->Get($_REQUEST['cat']);

	$breadCrumb = new CategoryBreadCrumb();
	$breadCrumb->Get($category->ID);
}

$productCollection = array(
	'Offers' => array(),
	'Popular' => array(),
	'Recent' => array(),
	'Spotlight' => array(),
);

$data = new DataQuery(sprintf("SELECT po.Product_ID FROM product_offers AS po INNER JOIN product AS p ON p.Product_ID=po.Product_ID WHERE p.Is_Active='Y' AND p.Discontinued='N' AND p.Is_Demo_Product='N' AND p.Is_Complementary='N' AND ((p.Sales_Start>=NOW() AND p.Sales_End<NOW()) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')) GROUP BY po.Product_ID ORDER BY RAND() LIMIT 0, %d", mysql_real_escape_string($limit)));
while($data->Row) {
	if(count($productCollection['Offers']) < $limit) {
		$productId = $data->Row['Product_ID'];
		$productCollection['Offers'][] = isset($productStore[$productId]) ? $productStore[$productId] : getProduct($productId);
	}
	$data->Next();
}
$data->Disconnect();

$data = Cache::getData('product.best_sellers');
$dataSize = count($data);

for($i=0; $i<$dataSize; $i++) {
	if(count($productCollection['Popular']) < $limit) {
		$productId = $data[$i]['ProductID'];
		$productCollection['Popular'][] = isset($productStore[$productId]) ? $productStore[$productId] : getProduct($productId);
	}
}

$recentCacheName = 'index_recent';
$recentCacheLimit = 10;

if(!CacheFile::isCached($recentCacheName)) {
	$recentCache = array();
	
	$data = new DataQuery(sprintf("SELECT p.Product_ID FROM product AS p WHERE p.Is_Active='Y' AND p.Discontinued='N' AND p.Is_Demo_Product='N' AND p.Is_Complementary='N' AND p.Integration_ID=0 AND ((p.Sales_Start>=NOW() AND p.Sales_End<NOW()) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) ORDER BY p.Created_On DESC LIMIT 0, %d", mysql_real_escape_string($recentCacheLimit)));
	while($data->Row) {
		$recentCache[] = $data->Row['Product_ID'];

		$data->Next();
	}
	$data->Disconnect();
	
	CacheFile::save($recentCacheName, implode("\n", $recentCache));
}

$recentCache = CacheFile::load($recentCacheName);

if($recentCache !== false) {
	foreach($recentCache as $cache) {
		if(count($productCollection['Recent']) < $limit) {
			$productCollection['Recent'][] = isset($productStore[$cache]) ? $productStore[$cache] : getProduct($cache);
		}
	}
}

$spotlightCacheName = 'index_spotlight';
$spotlightCacheLimit = 10;

if(!CacheFile::isCached($spotlightCacheName)) {
	$spotlightCache = array();

	$data = new DataQuery(sprintf("SELECT p.Product_ID FROM product AS p INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID=%d WHERE p.Is_Active='Y' AND p.Discontinued='N' AND p.Is_Demo_Product='N' AND p.Is_Complementary='N' AND p.Integration_ID=0 AND ((p.Sales_Start>=NOW() AND p.Sales_End<NOW()) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) ORDER BY RAND() LIMIT 0, %d", SPOTLIGHT_CATEGORY_ID, mysql_real_escape_string($spotlightCacheLimit)));
	while($data->Row) {
		$spotlightCache[] = $data->Row['Product_ID'];

		$data->Next();
	}
	$data->Disconnect();

	CacheFile::save($spotlightCacheName, implode("\n", $spotlightCache));
}

$spotlightCache = CacheFile::load($spotlightCacheName);

if($spotlightCache !== false) {
	foreach($spotlightCache as $cache) {
		if(count($productCollection['Spotlight']) < $limit) {
			$productCollection['Spotlight'][] = isset($productStore[$cache]) ? $productStore[$cache] : getProduct($cache);
		}
	}
}


$recentArticles = new RowSet(<<<SQL
SELECT ac.*, a.Article_Description
FROM article_category ac
JOIN article a on a.Article_Category_ID = ac.Article_Category_ID
ORDER BY ac.Created_On DESC LIMIT 4
SQL
);

$homepage = new Pages($GLOBALS['HOME_PAGE_ID']);
$homepage->GetViewableBanners();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Light Bulbs | Energy Saving Light Bulbs, LED Lights and Light Fittings | All from BLT Direct</title>
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
  	<meta name="verify-v1" content="sNc2ziupBJ/+k9FxpqYWZj5ZbDaxsesIdTPwDN5v0u4=" />
	<meta name="Keywords" content="Lighting, Light Bulbs, LED Lights, Lamps, Light Fittings, Energy Saving Light Bulbs" />
	<meta name="Description" content="BLT Direct can supply everything you need to light up your home, garden or office. Specialists in energy saving light bulbs, projector lamps and halogen lamps, BLT Direct offer superb quality lighting for the right price." />
	<link href="./css/Banner.css" rel="stylesheet" type="text/css" />
	<link href="./css/Tab.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="./js/thumbnailGallery.js"></script>
	<script type="text/javascript">
	//<![CDATA[
		var tab1 = new bltdirect.ui.Tab();
		tab1.addTab('Offers');
		tab1.addTab('Spotlight')
		tab1.addTab('Popular');
		tab1.addTab('Recent');
		tab1.addTabItem('Offers', 'OffersTabHeader');
		tab1.addTabItem('Offers', 'OffersTabItems');
		tab1.addTabItem('Spotlight', 'SpotlightTabHeader');
		tab1.addTabItem('Spotlight', 'SpotlightTabItems');
		tab1.addTabItem('Popular', 'PopularTabHeader');
		tab1.addTabItem('Popular', 'PopularTabItems');
		tab1.addTabItem('Recent', 'RecentTabHeader');
		tab1.addTabItem('Recent', 'RecentTabItems');
	//]]>
	</script>

	<script type="text/javascript">
	//<![CDATA[
		jQuery(function($) {
			$(".tabItemBody .TabContentsSection").thumbnailGallery({imageSelector: ".TabBodyItem"});
		});
	//]]>
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
					<h1>BLT Direct - Your One Stop Shop for Light Bulbs and Other Lighting Products</h1>
					<p>Buy online or Call <strong><?php echo Setting::GetValue('telephone_sales_hotline'); ?></strong>.</p>

					<div class="hide">Get your Energy Saving &amp; LED Light Bulbs today
					Energy saving and LED light bulbs make use of modern technology to reduce your overall electricity costs and consumption.</div>

					<?php include('lib/templates/bought.php'); ?>

					<?php render_partial('lib/common/slideshow', array('banners'=>$homepage->Banners)); ?>

					<div id="Banner">
						<table>
							<tr>
								<td class="third">
									<div id="BannerBaseBackground">
										<a id="BannerBase" href="./lampBaseExamples.php" title="View our Lamp Base examples">&nbsp;</a>
									</div>
								</td>
								<td class="third">
									<div id="BannerTubeBackground">
										<a id="BannerTube" href="./fluorescent_tubes.php" title="Fluorescent Tube Finder">&nbsp;</a>
									</div>
								</td>
								<td class="third">
									<div id="BannerDeliveryBackground">
										<div id="BannerDelivery">
											<a id="BannerDeliveryCurve" href="./deliveryRates.php" title="Free Shipping on light bulbs delivered to the UK from BLT Direct">&nbsp;</a>
										</div>
									</div>
								</td>
							</tr>
						</table>
					</div>

					<br />
					
					<div class="Tab">
						<div class="TabHeader">

							<div id="OffersTabHeader">
								<div class="TabHeaderItem TabItemOn">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Offers');" title="Special Offers on light bulbs, lamps and tubes">
												<span class="TabItemOffers">
													<span class="label">Special Offers</span>
												</span>
											</a>
										</div>
									</div>
								</div>
								<div class="TabHeaderItem TabItemOff">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Spotlight');" title="Light bulbs in the spotlight">
												<span class="TabItemSpotlight">
													<span class="label">In The Spotlight</span>
												</span>
											</a>
										</div>
									</div>
								</div>
								<div class="TabHeaderItem TabItemOff">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Popular');" title="Our best selling light bulbs">
												<span class="TabItemPopular">
													<span class="label">Best Sellers</span>
												</span>
											</a>
										</div>
									</div>
								</div>
								<div class="TabHeaderItem TabItemOff">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Recent');" title="New light bulb products">
												<span class="TabItemRecent">
													<span class="label">New Products</span>
												</span>
											</a>
										</div>
									</div>
								</div>
							</div>
							
							<div id="SpotlightTabHeader" style="display: none;">
								<div class="TabHeaderItem TabItemOff">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Offers');" title="Special Offers on light bulbs, lamps and tubes">
												<span class="TabItemOffers">
													<span class="label">Special Offers</span>
												</span>
											</a>
										</div>
									</div>
								</div>
								<div class="TabHeaderItem TabItemOn">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Spotlight');" title="Light bulbs in the spotlight">
												<span class="TabItemSpotlight">
													<span class="label">In The Spotlight</span>
												</span>
											</a>
										</div>
									</div>
								</div>
								<div class="TabHeaderItem TabItemOff">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Popular');" title="Our best selling light bulbs">
												<span class="TabItemPopular">
													<span class="label">Best Sellers</span>
												</span>
											</a>
										</div>
									</div>
								</div>
								<div class="TabHeaderItem TabItemOff">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Recent');" title="New light bulb products">
												<span class="TabItemRecent">
													<span class="label">New Products</span>
												</span>
											</a>
										</div>
									</div>
								</div>
							</div>

							<div id="PopularTabHeader" style="display: none;">
								<div class="TabHeaderItem TabItemOff">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Offers');" title="Special Offers on light bulbs, lamps and tubes">
												<span class="TabItemOffers">
													<span class="label">Special Offers</span>
												</span>
											</a>
										</div>
									</div>
								</div>
								<div class="TabHeaderItem TabItemOff">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Spotlight');" title="Light bulbs in the spotlight">
												<span class="TabItemSpotlight">
													<span class="label">In The Spotlight</span>
												</span>
											</a>
										</div>
									</div>
								</div>
								<div class="TabHeaderItem TabItemOn">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Popular');" title="Our best selling light bulbs">
												<span class="TabItemPopular">
													<span class="label">Best Sellers</span>
												</span>
											</a>
										</div>
									</div>
								</div>
								<div class="TabHeaderItem TabItemOff">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Recent');" title="New light bulb products">
												<span class="TabItemRecent">
													<span class="label">New Products</span>
												</span>
											</a>
										</div>
									</div>
								</div>
							</div>

							<div id="RecentTabHeader" style="display: none;">
								<div class="TabHeaderItem TabItemOff">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Offers');" title="Special Offers on light bulbs, lamps and tubes">
												<span class="TabItemOffers">
													<span class="label">Special Offers</span>
												</span>
											</a>
										</div>
									</div>
								</div>
								<div class="TabHeaderItem TabItemOff">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Spotlight');" title="Light bulbs in the spotlight">
												<span class="TabItemSpotlight">
													<span class="label">In The Spotlight</span>
												</span>
											</a>
										</div>
									</div>
								</div>
								<div class="TabHeaderItem TabItemOff">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Popular');" title="Our best selling light bulbs">
												<span class="TabItemPopular">
													<span class="label">Best Sellers</span>
												</span>
											</a>
										</div>
									</div>
								</div>
								<div class="TabHeaderItem TabItemOn">
									<div class="TabHeaderItemLeft">
										<div class="TabHeaderItemRight">
											<a href="javascript:tab1.showTab('Recent');" title="New light bulb products">
												<span class="TabItemRecent">
													<span class="label">New Products</span>
												</span>
											</a>
										</div>
									</div>
								</div>
							</div>

							<div class="clear"></div>

						</div>

						<?php foreach (array("Offers", "Spotlight", "Popular", "Recent") as $tabType) { ?>
						<div id="<?php echo $tabType ?>TabItems" class="tabItemBody" <?php echo $tabType != "Offers" ? 'style="display: none;"' : '' ?>>
							<div class="TabContents">
								<div class="TabContentsBorderLeft">
									<div class="TabContentsBorderRight">
										<div class="TabContentsBorderBottom">
											<div class="TabContentsBorderBottomLeft">
												<div class="TabContentsBorderBottomRight">
													<div class="TabContentsSection">
														<div class="leftBtn"></div>

														<div class="mask">
															<div class="slider">
																<?php
																$count = 0;
																$displayIndex = 0;

																foreach($productCollection[$tabType] as $productInfo) {
																	
																	$prices = array();
																	$prices[$productInfo['RRPPrice']] = $productInfo['RRPPrice'];
																	$prices[$productInfo['OurPrice']] = $productInfo['OurPrice'];
																	$prices[$productInfo['CurrentPrice']] = $productInfo['CurrentPrice'];

																	$maxLength = isset($maxLength) ? $maxLength : 40;
																	$outputName = $productInfo['Product']->HTMLTitle;

																	$outputPartNumber = $productInfo['Product']->SKU;

																	if(strlen($outputPartNumber) > $maxLengthPartNumber) {
																		$outputPartNumber = substr($outputPartNumber, 0, $maxLengthPartNumber-3) . '...';
																	}
																	$productReviews = new Product($productInfo['Product']->ID);
																	$productReviews->GetReviews();
																	$productReviews->GetComponents();

																	if(count($productReviews->Component)){
																		if($productReviews->Component[0]['Component_Of_Product_ID'] != $productReviews->ID){
																			$compProduct = new Product($productReviews->Component[0]['Component_Of_Product_ID']);
																			$compProduct->GetPrice();
																		} else {
																			$compProduct = false;
																		}
																	}
																	
																	krsort($prices);
																	?>

																	<div class="TabBodyItem TabBodyItemLine">

																		<?php
																		if(($productInfo['PercentSaving'] > 0) && ($productInfo['CurrentPrice'] < $productInfo['OurPrice'])) {
																			echo sprintf('<div class="TabBodyItemSave">Save<br />%s%%</div>', $productInfo['PercentSaving']);
																		}
																		?>

																		<div class="TabBodyItemImage">
																			<a href="/product.php?pid=<?php echo $productInfo['Product']->ID; ?>" title="Click to View <?php echo strip_tags($productInfo['Product']->Name); ?>"><img src="<?php echo (!empty($productInfo['Product']->DefaultImage->Thumb->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$productInfo['Product']->DefaultImage->Thumb->FileName)) ? $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$productInfo['Product']->DefaultImage->Thumb->FileName : './images/template/image_coming_soon_3.jpg'; ?>" alt="<?php echo strip_tags($productInfo['Product']->Name); ?>" /></a>
																		</div>

																		<div class="TabBodyItemText">

																			<div class="grid-product-item-name">
																			<span class="title"><a href="/product.php?pid=<?php echo $productInfo['Product']->ID; ?>" title="Click to View <?php echo strip_tags($productInfo['Product']->Name); ?>"><?php echo $outputName; ?></a></span>
																			</div>

																			<span class="product-detail-ident">QuickFind #: <?php echo $productInfo['Product']->ID; ?></span>
																			<span class="product-detail-ident"> Part Number: <?php echo $outputPartNumber; ?></span>
																		</div>

																		<div class="TabBodyItemReview">

																		<?php 
																		$ratingStars = number_format($productInfo["Product"]->ReviewAverage * $GLOBALS['PRODUCT_REVIEW_RATINGS'], 1, '.', '');

																		
																		if($ratingStars > 0) {
																		$ratingHtml = '';

																		for($i=0; $i<$GLOBALS['PRODUCT_REVIEW_RATINGS']; $i++) {
																		$ratingHtml .= sprintf('<img src="./images/new/product/rating%s.png" alt="Product Rating" />', (ceil($ratingStars) > $i) ? '-solid' : '');
																		}

																		echo sprintf('<a href="/product.php?pid=%d&amp;tab=reviews" title="Reviews for %s">%s</a>', $productInfo['Product']->ID, $productInfo['Product']->Name, $ratingHtml);
																			}
																		?>
																		</div>
		
																		<div class="TabBodyItemPrice">
																			<table class="grid-price-table">
																				<tr>
																					<td>
																						<span class="price-amount colour-red">&pound;<?php echo number_format($productInfo['BuyPrice'], 2, '.', ','); ?></span><br />
																						<span class="price-amount-tax colour-grey">&pound;<?php echo number_format($productInfo["Product"]->PriceCurrentIncTax, 2, '.', ','); ?> inc. VAT</span>
																					</td>
																					<td style="text-align:right">
																						<form action="./customise.php" method="post" name="buy" id="buy">
																							<input type="hidden" name="action" value="customise" />
																							<input type="hidden" name="direct" value="<?php echo urlencode($cartDirect); ?>" />
																							<input type="hidden" name="product" value="<?php echo $productInfo['Product']->ID; ?>" />
																							<input type="hidden" name="quantity" value="<?php echo ($productInfo['Product']->OrderMin > 0) ? $productInfo['Product']->OrderMin : 1; ?>" />
																							<input type="submit" name="buy" value="Buy" class="button" />
																						</form>
																					</td>
																				</tr>
																				<?php /*if(isset($compProduct->ID) && $compProduct->ID > 0){ ?>
																					<tr>
																						<td style="padding-top:5px;">
																							<span class="colour-grey"><?php echo sprintf('&times;%s', $productReviews->Component[0]['Component_Quantity']); ?> <strong>Rate</strong></span><br />
																							<span class="price-amount colour-red">&pound;<?php echo number_format(($compProduct->PriceCurrent), 2, '.', ','); ?></span><br />
																						</td>
																						<td style="text-align:right; padding-top:5px;">
																							<form name="buy" action="/customise.php" method="post">
																								<input type="hidden" name="action" value="customise" />
																								<input type="hidden" name="direct" value="<?php echo urlencode($cartDirect); ?>" />
																								<input type="hidden" name="product" value="<?php echo $compProduct->ID; ?>" />
																								<input type="hidden" name="quantity" value="<?php echo ($compProduct->OrderMin > 0) ? $compProduct->OrderMin : 1; ?>" />
																								<input type="submit" name="buy" value="Buy" class="button" />
																							</form>
																						</td>
																					</tr>
																				<?php }*/ ?>
																			</table>
																		</div>
																	</div>

																	<?php
																	$count++;
																	$displayIndex++;
																}
																?>
															</div>
														</div>

														<div class="rightBtn"></div>

														<div class="clear"></div>
													</div>

													<?php if ($tabType == "Offers") { ?>
													<div class="TabContentsFooter">
														<p><a href="./offers.php" title="View all light bulbs, lamps and tubes special offers">View all our special offers on light bulbs, lamps and tubes</a></p>
													</div>
													<?php } else { ?>
													<div class="TabContentsFooter">
														<p><a href="./products.php" title="Browse our complete light bulbs, lamps and tubes product range">Browse our complete product range of light bulbs, lamps and tubes</a></p>
													</div>
													<?php } ?>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php } ?>
					</div>
					
					<div class="recentArticles">
						<h2>Recent Light Bulb Press Releases/Articles</h2>

						<p class="allArticles">
							View all of our <strong><a href="articles.php" title="Press Releases/Articles">Press Releases/Articles</a></strong>
						</p>

						<div class="recentArticleBox">
							<?php foreach ($recentArticles as $num=>$recentArticle) { ?>
							<div class="recentArticle <?php echo ($num % 2) == 0 ? "odd" : "even" ?> <?php echo $num < 2 ? "top" : "" ?>">
								<div class="pad">
									<h3><a href="article.php?id=<?php echo $recentArticle->Article_Category_ID ?>" title="<?php echo $recentArticle->Category_Title ?>"><?php echo $recentArticle->Category_Title ?></a></h3>

									<span class="articleDate"><?php echo cDatetime($recentArticle->Created_On, 'shortdate'); ?></span>

									<div>
										<?php echo truncate(strip_tags($recentArticle->Article_Description), 200) ?>
									</div>

									<span class="moreLink"><a href="article.php?id=<?php echo $recentArticle->Article_Category_ID ?>" title="<?php echo $recentArticle->Category_Title ?>">Read More ▶</a></span>
								</div>
							</div>

							<?php if (($num-1 % 2) == 0) { ?>
							<div class="clear"></div>
							<?php } ?>
							<?php } ?>
						</div>

						<div class="clear"></div>
					</div>

					<br />
					

					<h2>Light Bulbs, Lamps and Tubes from BLT Direct</h2>

					<div class="TextColumns">
						<div class="TextColumn2">
							<p>BLT Direct has been a leading online supplier of <a href="./products.php">light bulbs</a>, <a href="./light-fittings">light fittings</a> and <a href="./products.php?cat=92&amp;nm=Specialist+Lamps">specialist lamps</a> in the UK and around the world since the early days of the Internet. Our website provides instant access to almost 10,000 light bulb products including <a href="./products.php?cat=15&amp;nm=Energy+Saving+Light+Bulbs">Energy Saving Light Bulbs</a>, <strong>Halogen Light Bulbs, Fluorescent Tubes, Incandescent Light Bulbs, Metal Halide Lamps, <a href="/products.php?cat=241&amp;nm=LED+Light+Bulbs">LED Light Bulbs</a>, Compact Fluorescent Lamps, Sodium Lamps, Mercury Lamps, Sunbed Tubes, Specialist Lamps and Ballasts.</strong></p>
							<p>We also have the <a href="./offers.php">latest deals</a> on light bulbs, lamps and other electrical accessories for all your <a href="./lighting">lighting</a> needs on our Special Offers page. Whatever you're looking for, we're sure we'll have it! Our low energy bulbs are designed to save you electricity costs as well saving the environment so you can do your bit to help protect an already overburdened eco system.</p>
							<p>BLT Direct has the lighting solutions to fulfil all your needs whether you require standard <a href="./products.php?cat=66&amp;nm=Incandescent+Light+Bulbs">incandescent light bulbs</a>, tubes or other more specialist products. Because we do exclusive online business only, we are able to provide our huge range of <strong>lighting</strong> products to our customers at exceptional prices from high quality manufacturers delivered right to your doorstep quickly and efficiently.</p>
						</div>
						<div class="TextColumnDivider">&nbsp;</div>
						<div class="TextColumn2">
							<p>BLT Direct is an established company with a good reputation within the UK lighting industry. If you have difficulty finding the light bulbs, lamps or tubes you are looking for, contact our friendly and experienced sales team who will be pleased to deal with your lighting enquiries. Our expertise means we will be able to advise you of the best products, whether you want <strong>LED lights</strong> or regular <strong>light bulbs</strong>. Call us today or shop securely online for a wealth of <strong>lighting</strong> products.</p>

							<ul class="ListType1">
								<li><a href="./deliveryRates.php" title="BLT Direct Delivery Rates">Delivery Rates</a></li>
								<li><a href="./energy-saving-bulbs.php" title="Calculate Savings">Calculate Savings</a></li>
								<li><a href="./lampColourTemperatures.php" title="Lamp Colour Temperature Guide">Colour Temperatures</a></li>
								<li><a href="./fluorescent_tubes.php" title="Fluorescent Tube Finder">Fluorescent Tube Finder</a></li>
								<li><a href="./security.php" title="Security at BLT Direct">Security at BLT</a></li>
								<li><a href="./links.php" title="Useful Lightbulb Links from BLT Direct">Useful Links</a></li>
								<li><a href="http://twitter.com/bltdirect" title="Visit Us at Twitter" target="_blank">Visit Us at Twitter</a></li>
							</ul>
						</div>
					</div>
					<div class="clear"></div>

					<hr />
					<br />

					<div id="OtherLogos">
						<div id="OtherLogosLeft">
							<a href="http://checkout.google.com" target="_blank" title="Google Checkout"><img src="./images/paymentTypes_2.gif" height="45" width="121" alt="Google Checkout" /></a>
							<a href="http://www.protx.com" target="_blank" title="Protx"><img src="./images/paymentTypes_3.gif" height="45" width="130" alt="Protx" /></a>
						</div>
						<div id="OtherLogosRight">
							<div id="OtherLogosLightingAssociation">
								<a href="http://www.lightingassociation.com" target="_blank" title="The Lighting Association"><img src="./images/logo_lightingAssociation.gif" height="50" width="86" alt="The Lighting Association" /></a>
							</div>
							<a href="http://www.energysavingtrust.org.uk" target="_blank" title="Energy Saving Trust"><img src="./images/logo_energySaving.gif" height="67" width="66" alt="Energy Saving Trust" /></a>
							<a href="http://www.recycle-more.co.uk" target="_blank" title="Recycle More"><img src="./images/logo_recycleMore.gif" height="67" width="67" alt="Recycle More" /></a>
						</div>
						<div class="clear"></div>

						<div id="OtherLogosCentre">
							<div class="paymentTypes">
								<a class="paymentType visa" title="VISA"></a>
								<a class="paymentType visaelectron" title="VISA Electron"></a>
								<a class="paymentType mastercard" title="Master Card"></a>
								<a class="paymentType googlecheckout last" title="Google Checkout"></a>
								<a class="clear"></a>
							</div>
						</div>
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