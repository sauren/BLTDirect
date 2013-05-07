<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('barcode', 'Barcode', 'text', '', 'paragraph', 1, 120, true, 'onclick="this.select();"');

$data = new DataQuery(sprintf('SELECT * FROM ip_ignore WHERE ip=%u', ip2long($_SERVER['REMOTE_ADDR'])));
if(empty($data->TotalRows)) {
	$fh = fopen($GLOBALS['DATA_DIR_FS'].'local/logs/barcodes.txt', 'a');

	if($fh) {
		fwrite($fh, $form->GetValue('barcode') . "\r\n");
		fclose($fh);
	}
}
$data->Disconnect();

$sqlSelect = '';
$sqlFrom = '';
$sqlWhere = '';
$sqlGroup = '';

if(param('confirm')) {
	if($form->Validate()) {
		$barcode = trim($form->GetValue('barcode'));

		if((strlen($barcode) < 12) || (strlen($barcode) > 14)) {
			redirectTo('search.php?search=' . $barcode);
		}

		$sqlSelect = 'SELECT p.Product_ID, p.Product_Title, p.Discontinued, p.Discontinued_Show_Price, p.Product_Codes, p.Cache_Specs_Primary, p.Meta_Title, p.SKU, p.Order_Min, p.Average_Despatch, p.CacheBestCost, p.CacheRecentCost, pi.Image_Thumb, MIN(ws.Backorder_Expected_On) AS Backorder_Expected_On ';
		$sqlFrom = 'FROM product AS p INNER JOIN product_barcode AS pb ON pb.ProductID=p.Product_ID LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Is_Backordered=\'Y\' LEFT JOIN product_images AS pi ON pi.Product_ID=p.Product_ID AND pi.Is_Active=\'Y\' AND pi.Is_Primary=\'Y\' ';
		$sqlWhere = 'WHERE p.Is_Active=\'Y\' AND p.Is_Demo_Product=\'N\' AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start=\'0000-00-00 00:00:00\' AND p.Sales_End=\'0000-00-00 00:00:00\') OR (p.Sales_Start=\'0000-00-00 00:00:00\' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End=\'0000-00-00 00:00:00\')) ';
		$sqlGroup = 'GROUP BY p.Product_ID ';

		$searchString = $form->GetValue('barcode');
		$searchString = $searchString;
		$searchString = trim(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $searchString));
		
		if(!empty($searchString)) {
			$sqlWhere .= sprintf('AND pb.Barcode LIKE \'%s%%\' ', $searchString);
		}
		
		$productPrices = array();
		$productOffers = array();

		$data = new DataQuery(sprintf("SELECT p.Product_ID, pp.Price_Base_Our, pp.Price_Base_RRP, pp.Quantity, pp.Price_Starts_On %sINNER JOIN product_prices AS pp ON p.Product_ID=pp.Product_ID AND pp.Price_Starts_On<=NOW() %s", $sqlFrom, $sqlWhere));
		while($data->Row) {
			if(!isset($productPrices[$data->Row['Product_ID']])) {
				$productPrices[$data->Row['Product_ID']] = array();
			}

			$item = array();
			$item['Price_Base_Our'] = $data->Row['Price_Base_Our'];
			$item['Price_Base_RRP'] = $data->Row['Price_Base_RRP'];

			$productPrices[$data->Row['Product_ID']][$data->Row['Quantity']] = $item;

			$data->Next();
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT p.Product_ID, po.Price_Offer, po.Offer_Start_On %sINNER JOIN product_offers AS po ON p.Product_ID=po.Product_ID AND ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')) %s", $sqlFrom, $sqlWhere));
		while($data->Row) {
			if(!isset($productOffers[$data->Row['Product_ID']])) {
				$productOffers[$data->Row['Product_ID']] = array();
			}

			$item = array();
			$item['Price_Offer'] = $data->Row['Price_Offer'];

			$productOffers[$data->Row['Product_ID']][$data->Row['Price_Offer']] = $item;

			$data->Next();
		}
		$data->Disconnect();
	}
}

if(strlen(sprintf('%s%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup)) > 0) {
	$table = new DataTable('products');
	$table->SetSQL(sprintf('%s%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup));
	$table->SetTotalRowSQL(sprintf('SELECT COUNT(DISTINCT p.Product_ID) AS TotalRows %s%s', $sqlFrom, $sqlWhere));
	$table->SetMaxRows(15);
	$table->SetOrderBy('Product_Title');
	$table->Finalise();
	$table->ExecuteSQL();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>BLT Direct Search</title>
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
	<link rel="stylesheet" type="text/css" href="/css/Search.css" />
	<meta name="Keywords" content="light bulbs, light bulb, lightbulbs, lightbulb, lamps, fluorescent, tubes, osram, energy saving, sylvania, philips, ge, halogen, low energy, metal halide, candle, dichroic, gu10, projector, blt direct" />
	<meta name="Description" content="We specialise in supplying lamps, light bulbs and fluorescent tubes, Our stocks include Osram,GE, Sylvania, Omicron, Pro lite, Crompton, Ushio and Philips light bulbs" />
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
        			<h1>Barcode Search</h1>
					<p>Search our extensive product database against barcodes.</p>

					<?php
					if(!$form->Valid) {
						echo $form->GetError();
						echo '<br />';
					}
					
					echo $form->Open();
					echo $form->GetHTML('confirm');
					?>

					<div class="searchGrid">

						<?php
						echo sprintf('<strong>%s</strong><br />Scan or type in your barcode<br /><br />', $form->GetLabel('barcode'));
						echo $form->GetHTML('barcode');
						?>
					
						<input type="submit" name="submit" value="Search" class="submit" />
					</div>
					<div class="searchGrid">
						<div class="searchAlternative">
							Can't find your product?<br />
							<a href="search.php">Use our general search facility.</a>
						</div>
					</div>
					<div class="clear"></div>

					<?php
					echo $form->Close();

					if(strlen(sprintf('%s%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup)) > 0) {
						?>
						
						<br /><br />

						<div class="SearchBox">
							<div id="SearchInformation">
								<p>There are <strong><?php echo $table->TotalRows; ?></strong> products matching your search criteria.</p>
							</div>
						</div>

						<?php
						if($table->Table->TotalRows > 0) {
							?>

							<table class="list">

								<?php
								while($table->Table->Row){
									$subProduct = new Product();
									$subProduct->ID = $table->Table->Row['Product_ID'];
									$subProduct->Name = strip_tags($table->Table->Row['Product_Title']);
									$subProduct->HTMLTitle = preg_replace('/<\/p>$/i', '', preg_replace('/^<p[^>]*>/i', '', $table->Table->Row['Product_Title']));
									$subProduct->Codes = $table->Table->Row['Product_Codes'];
									$subProduct->SpecCachePrimary = $table->Table->Row['Cache_Specs_Primary'];
									$subProduct->MetaTitle = $table->Table->Row['Meta_Title'];
									$subProduct->SKU = $table->Table->Row['SKU'];
									$subProduct->DefaultImage->Thumb->FileName = $table->Table->Row['Image_Thumb'];
									$subProduct->OrderMin = $table->Table->Row['Order_Min'];
									$subProduct->AverageDespatch = $table->Table->Row['Average_Despatch'];
									$subProduct->PriceRRP = 0;
									$subProduct->PriceOurs = 0;
									$subProduct->PriceOffer = 0;
									$subProduct->Discontinued = $table->Table->Row['Discontinued'];
									$subProduct->DiscontinuedShowPrice = $table->Table->Row['Discontinued_Show_Price'];
									$subProduct->CacheBestCost = $table->Table->Row['CacheBestCost'];
									$subProduct->CacheRecentCost = $table->Table->Row['CacheRecentCost'];
											
									if(isset($productPrices[$subProduct->ID])) {
										if(count($productPrices[$subProduct->ID]) > 0) {
											ksort($productPrices[$subProduct->ID]);
											reset($productPrices[$subProduct->ID]);

											if($subProduct->OrderMin < key($productPrices[$subProduct->ID])) {
												$subProduct->OrderMin = key($productPrices[$subProduct->ID]);
											}

											foreach($productPrices[$subProduct->ID] as $quantity=>$price) {
												$subProduct->PriceOurs = $price['Price_Base_Our'];
												$subProduct->PriceRRP = $price['Price_Base_RRP'];

												break;
											}
										}
									}

									if(isset($productOffers[$subProduct->ID])) {
										if(count($productOffers[$subProduct->ID]) > 0) {
											ksort($productOffers[$subProduct->ID]);
											reset($productOffers[$subProduct->ID]);

											$price = current($productOffers[$subProduct->ID]);

											$subProduct->PriceOffer = $price['Price_Offer'];
										}
									}
									
									$subProduct->GetPrice();
									
									include('lib/templates/productLine.php');

									$table->Next();
								}
								?>

							</table>

							<?php
							$table->DisplayNavigation();
						}
						$table->Disconnect();
					}
					?>

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
