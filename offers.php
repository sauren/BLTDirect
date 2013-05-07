<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CategoryBreadCrumb.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountCollection.php");

$category = new Category();
$category->Name = "Products";
$category->MetaKeywords = "Bulbs, Lamps, Tubes, Sunbed, Lightbulbs, lighting, lights";
$category->MetaDescription = "Suppliers of Bulbs, Lamps and Tubes.";
$category->Description = "";
$category->ID = 0;

if(id_param('cat')){
	$category->Get(id_param('cat'));

	$breadCrumb = new CategoryBreadCrumb();
	$breadCrumb->Get($category->ID);
}

$dicountCollection = new DiscountCollection();
$dicountCollection->Get($session->Customer);

$offers = array();

$count = 0;
$limit = 0;

$data = new DataQuery(sprintf("SELECT po.Product_ID FROM product_offers AS po INNER JOIN product AS p ON p.Product_ID=po.Product_ID WHERE p.Is_Active='Y' AND p.Discontinued='N' AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')) GROUP BY po.Product_ID"));
while($data->Row) {
	$product = new Product($data->Row['Product_ID']);
	$useCustomPrice = false;

	if($session->IsLoggedIn) {
		if(count($dicountCollection->Line) > 0){
			list($tempLineTotal, $discountName) = $dicountCollection->DiscountProduct($product, 1);

			if($tempLineTotal < $product->PriceCurrent)  {
				$priceSavingPercent = round((($product->PriceRRP - $tempLineTotal) / $product->PriceRRP) * 100);

				if($priceSavingPercent > 0) {
					$useCustomPrice = true;

					if(($limit == 0) || ($count < $limit)) {
						$item = array();
						$item['Product'] = $product;
						$item['RRPPrice'] = number_format($product->PriceRRP, 2, '.', ',');
						$item['OurPrice'] = number_format($product->PriceOurs, 2, '.', ',');
						$item['CurrentPrice'] = number_format($product->PriceCurrent, 2, '.', ',');
						$item['BuyPrice'] = number_format($tempLineTotal, 2, '.', ',');
						$item['PercentSaving'] = $priceSavingPercent;

						$offers[] = $item;

						$count++;
					}
				}
			}
		}
	}

	if(!$useCustomPrice) {
		if($product->PriceSavingPercent > 0) {
			if(($limit == 0) || ($count < $limit)) {
				$item = array();
				$item['Product'] = $product;
				$item['RRPPrice'] = number_format($product->PriceRRP, 2, '.', ',');
				$item['OurPrice'] = number_format($product->PriceOurs, 2, '.', ',');
				$item['CurrentPrice'] = number_format($product->PriceCurrent, 2, '.', ',');
				$item['BuyPrice'] = $item['CurrentPrice'];
				$item['PercentSaving'] = $product->PriceSavingPercent;

				$offers[] = $item;

				$count++;
			}
		}
	}

	$data->Next();
}
$data->Disconnect();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Special Offers</title>
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
	<meta name="Keywords" content="light bulbs, light bulb, lightbulbs, lightbulb, lamps, fluorescent, tubes, osram, energy saving, sylvania, philips, ge, halogen, low energy, metal halide, candle, dichroic, gu10, projector, blt direct" />
	<meta name="Description" content="We specialise in supplying lamps, light bulbs and fluorescent tubes, Our stocks include Osram,GE, Sylvania, Omicron, Pro lite, Crompton, Ushio and Philips light bulbs, " />
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
              <h1>Special Offers</h1>
			<p class="breadcrumb"><a href="/index.php" title="Light Bulbs, Lamps and Tubes Direct Home Page">Home</a> / <a href="/products.php">Products</a></p>
			<div class="categoryDescription">
				<img src="images/icon_offers_1.jpg" width="80" height="79" class="imageLeft" alt="Special Offers on Light Bulbs" />
			    <h3>Get Great Discounts Online </h3>
			    <p>Save more and get more with our amazing  discounts only available online. </p>
			    <p>Have you seen a better offer? If you've seen the same product elsewhere at a lower price please don't hesitate to <a href="support.php">contact us</a>. </p>
			</div>
			<br />

				<table cellspacing="0" class="catProducts">
					<tr>
						<th colspan="2">Special Offer</th>
						<th colspan="2">Price</th>
					</tr>

					<?php
					foreach($offers as $offer) {
						$prices = array();
						$prices[$offer['RRPPrice']] = $offer['RRPPrice'];
						$prices[$offer['OurPrice']] = $offer['OurPrice'];
						$prices[$offer['CurrentPrice']] = $offer['CurrentPrice'];
						$prices[$offer['BuyPrice']] = $offer['BuyPrice'];

						krsort($prices);
						?>

						<tr>
							<td align="center"><a href="/product.php?pid=<?php echo $offer['Product']->ID; ?>" title="Click to View <?php echo $offer['Product']->Name; ?>"><img src="<?php echo (!empty($offer['Product']->DefaultImage->Thumb->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$offer['Product']->DefaultImage->Thumb->FileName)) ? $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$offer['Product']->DefaultImage->Thumb->FileName : '/images/template/image_coming_soon_3.jpg'; ?>" alt="<?php echo $offer['Product']->Name; ?>" /></a></td>
							<td>
								<a href="/product.php?pid=<?php echo $offer['Product']->ID; ?>" title="Click to View <?php echo $offer['Product']->Name; ?>"><strong><?php echo $offer['Product']->Name; ?></strong><br />
								<span class="smallGreyText">QuickFind #: <?php echo $offer['Product']->ID; ?>, Part Number: <?php echo $offer['Product']->SKU; ?></span></a>
							</td>
					  		<td align="right" class="price">

								<?php
								$index = 0;

								foreach($prices as $price) {
									if($index < (count($prices) - 1)) {
										echo sprintf('<span class="oldPrice">&pound;%s</span><br />', number_format($price, 2, '.', ''));
									}

									$index++;
								}

								$index = 0;

								foreach($prices as $price) {
									if($index == (count($prices) - 1)) {
										echo sprintf('&pound;%s', number_format($price, 2, '.', ''));
									}

									$index++;
								}
								?>

							</td>
							<td nowrap="nowrap" align="right"><?php echo $offer['Product']->GetBuyIt(); ?></td>
						</tr>

						<?php
					}
					?>

				</table>

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
	<img src="http://stats1.saletrack.co.uk/scripts/stinit.asp?cid=256336&rf=JavaScri
	pt%20Disabled%20Browser" border="0" width="0" height="0" />
	</noscript>
	-->
	
	<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>
<?php include('lib/common/appFooter.php'); ?>