<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Category.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
    <title>Projector Lamps from BLT Direct</title>
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
    <meta name="Keywords" content="Projector Lamps, projector lamp, quality projector lamps, cheap projector lamp, data projector lamps, branded projector lamps" />
	<meta name="Description" content="Quality projector lamps and bulbs from the leading providers of light bulbs, BLT Direct." />
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
					<h1>Projector Lamps</h1>
					<p class="breadCrumb"><a href="/index.php" title="Light Bulbs, Lamps and Tubes Direct Home Page">Home</a> / <a href="/Projector-Lamps.php">Projector Lamps</a></p>

					<div style="float: right; padding: 0 0 10px 10px;">
						<img src="/images/diamonde.jpg" width="224" height="90" alt="Projector Lamps" title="Projector Lamps" />
					</div>
					
					<p>Here at BLT Direct, we not only specialise in <a href="http://www.bltdirect.com/products.php?cat=15&amp;nm=Energy+Saving+Light+Bulbs">low energy light bulbs</a> and <a href="http://www.bltdirect.com/products.php?cat=241&amp;nm=LED+Light+Bulbs">LED light bulbs</a>, we also have a range of quality <a href="http://www.bltdirect.com/acer">projector lamps</a> and bulbs. We only sell lamps that have been through strict manufacturer's processes and perform to those high standards to bring you the best. We do not sell copy lamps, whether they are branded or not, as this can reduce the projector's performance level and pose a risk to your health and safety.</p>
					
					<p>We sell two different types of lamps; the Original Manufacturers Lamp, which is supplied by the manufacturer that made the original projector, giving you optimum performance, and Diamond Lamps by Philips UHP and Osram VIP and P-VIP, which are authorised projector lamps from Philips and Osram. These <strong>projector lamps</strong> are lower in cost compared to the Original Manufacturers Lamp and come with a four month warranty, so should anything go wrong in this time, you can send back for a free exchange. We recommend that you check your projector's warranty before purchasing these <strong>projector lamps</strong> because although they are endorsed by a number of different projector manufacturers, they have been known to invalidate some warranties.</p>
					
					<p>We have <strong>projector lamps</strong> for big brands such as Acer, Hewlett Packard, Dell, Polaroid, Samsung, Sony and Sanyo, amongst others. Whatever projector you have, we're sure we have the lamp to suit!</p>
					
					<p>Once you have found your <a href="http://www.bltdirect.com/sony">projector lamp</a>, you can order safely and securely online or call our expert sales team on <strong>01473 716 418</strong> 24-hours a day, seven days a week to help you further with your purchase.</p>

					<div style="clear: both;"></div>

					<table class="bluebox" cellspacing="0" border="0">
						<tr>
							<td valign="top" width="200" rowspan="2" align="center"><img src="images/projector_lamps_1.gif" alt="Projector Lamps" width="148" height="120" class="img" /></td>
							<td valign="top" colspan="2">
								<strong style="font-size: 12px;">Projector Lamps Selector</strong><br />Please select the manufacturer and model that you require from the drop-down menu.
							</td>
						</tr>
						<tr>
							<td valign="top">

								<table cellspacing="0" border="0">
									<tr>
										<th style="text-align: left;">Manufacturer:</th>
										<td>
											<select name="manufactuerer" onchange="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?manufacturer=' + this.value;">
												<option value="">-- Select --</option>

												<?php
												$data = new DataQuery(sprintf("SELECT m.Manufacturer_ID, m.Manufacturer_Name FROM manufacturer AS m INNER JOIN product AS p ON p.Manufacturer_ID=m.Manufacturer_ID WHERE p.Is_Active='Y' AND p.Discontinued='N' AND p.Integration_ID>0 GROUP BY m.Manufacturer_ID ORDER BY m.Manufacturer_Name ASC"));
												while($data->Row) {
													echo sprintf('<option value="%s"%s>%s</option>', $data->Row['Manufacturer_ID'], (($data->Row['Manufacturer_ID'] == id_param('manufacturer')) ? ' selected="selected"' : ''), $data->Row['Manufacturer_Name']);

													$data->Next();
												}
												$data->Disconnect();
												?>
											</select>
									  </td>
									</tr>

									<?php
									if(id_param('manufacturer')) {
										?>

										<tr>
											<th style="text-align: left;">Model:</th>
											<td>
												<select name="model" onchange="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?manufacturer=<?php echo id_param('manufacturer'); ?>&amp;model=' + this.value;">
													<option value="">-- Select --</option>

													<?php
													$data = new DataQuery(sprintf("SELECT p.Model FROM product AS p WHERE p.Is_Active='Y' AND p.Discontinued='N' AND p.Integration_ID>0 AND p.Manufacturer_ID=%d GROUP BY p.Model ORDER BY p.Model ASC", mysql_real_escape_string(id_param('manufacturer'))));
													while($data->Row) {
														echo sprintf('<option value="%s"%s>%s</option>', $data->Row['Model'], (($data->Row['Model'] == param('model')) ? ' selected="selected"' : ''), $data->Row['Model']);

														$data->Next();
													}
													$data->Disconnect();
													?>

												</select>
										  </td>
										</tr>

										<?php
									} else {
										?>

										<tr>
											<td colspan="2">&nbsp;</td>
										</tr>

										<?php
									}

									if(param('model')) {
										?>

										<tr>
											<th style="text-align: left;">Lamp:</th>
											<td>
												<select name="lamp" onchange="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?manufacturer=<?php echo id_param('manufacturer'); ?>&amp;model=<?php echo urlencode(param('model')); ?>&amp;product=' + this.value;">
													<option value="">-- Select --</option>

													<?php
													$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title FROM product AS p WHERE p.Is_Active='Y' AND p.Discontinued='N' AND p.Integration_ID>0 AND p.Manufacturer_ID=%d AND p.Model LIKE '%s' ORDER BY p.Product_Title ASC", id_param('manufacturer'), mysql_real_escape_string(param('model'))));
													while($data->Row) {
														echo sprintf('<option value="%s"%s>%s</option>', $data->Row['Product_ID'], (($data->Row['Product_ID'] == id_param('product')) ? ' selected="selected"' : ''), strip_tags($data->Row['Product_Title']));

														$data->Next();
													}
													$data->Disconnect();
													?>

												</select>
										  </td>
										</tr>

										<?php
									} else {
										?>

										<tr>
											<td colspan="2">&nbsp;</td>
										</tr>

										<?php
									}
									?>

								</table>

							</td>
						</tr>
					</table>
					<br />

					<?php
					if(id_param('product')) {
						$product = new Product(id_param('product'));
						?>

						<table class="whitebox" cellspacing="0" border="0">
							<tr>
								<td valign="top" width="200" rowspan="2" align="center"><img id="projectorImage" src="images/projector_lamps_1.gif" alt="Projector Lamps" width="148" height="120" class="img" /></td>
								<td valign="top" colspan="2">
									<strong style="font-size: 12px;"><?php echo $product->Name; ?></strong>
								</td>
							</tr>
							<tr>
								<td valign="top">

									<?php
			            			?>

			                		<p>
			                			Our Price <span class="currentPrice">&pound;<?php echo number_format($product->PriceCurrent, 2, '.', ','); ?></span><br />
			                			<span class="smallGreyText">(&pound;<?php echo number_format($product->PriceCurrentIncTax, 2, '.', ','); ?> inc. VAT)</span><br /><br />

			                			<?php
			                			$data = new DataQuery(sprintf("SELECT SUM(Quantity_In_Stock) AS Quantity FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d", mysql_real_escape_string($GLOBALS['JL_WAREHOUSE']), mysql_real_escape_string($product->ID)));
			                			if(($data->TotalRows > 0) && ($data->Row['Quantity'] > 0)) {
						                	echo sprintf('<span style="color: #c00;">Stock Available:</span> %s', $data->Row['Quantity']);
			                			} else {
			                				echo sprintf('<span style="color: #c00;">Not in Stock</span><br />Available within %s day(s)', $product->DespatchDaysMax - 2);
			                			}
			                			$data->Disconnect();
						                ?>

			            			</p>

						            <?php echo $product->GetBuyIt(); ?>
			                    	<br />

			                    	<p><span class="callTag">Buy Online or Call: <span class="phone"><?php echo Setting::GetValue('telephone_sales_hotline'); ?></span></span></p>
			                    	<p><span class="smallGreyText">SKU #: <?php echo $product->SKU; ?></span></p>

								</td>
							</tr>
						</table>
						<br />

						<?php
					}
					?>

					<p>We stock many projector lamps, if the projector lamps you require are not availble please call our sales helpline on <?php echo Setting::GetValue('telephone_sales_hotline'); ?> for a quotation. </p>

					<br /><br /><br /><br />

		              <table class="whitebox" cellspacing="0" border="0">
		              	<tr>
		              		<td>

								<p><strong>For other projector lamps, ANSI Coded Projector Lamps, A1 Projector Lamps range, photographic and studio lamps, please see our Specialist Lamps section below.</strong></p>

								<?php
								$category = new Category(92);

								if(!empty($category->CategoryOrder)){
									$sql = sprintf("select * from product_categories where Category_Parent_ID=%d and Is_Active='Y' order by %s", $category->ID, $category->CategoryOrder);
								} else {
									$sql = sprintf("select * from product_categories where Category_Parent_ID=%d and Is_Active='Y' ORDER BY Category_Title", $category->ID);
								}

								$children = new DataQuery($sql);

								$subCategory = new Category();

								if($category->ShowImages == 'Y') {
									$productColumns = 3;
									if($children->TotalRows > 0){
										echo "<table class=\"productCategories clear\">";
										$tempColumn = 0;
										$rows = 0;
										while($children->Row){
											$subCategory->ID = $children->Row['Category_ID'];
											$subCategory->Name = $children->Row['Category_Title'];
											$subCategory->MetaTitle = $children->Row['Meta_Title'];

											$url = $subCategory->GetUrl();

											++$tempColumn;
											++$rows;
											if($tempColumn == 1) echo "<tr>";

											$tempStr = "<td>%s<br /><p>%s</p></td>";
											// Check Image
											if(!empty($children->Row['Category_Thumb']) && file_exists($GLOBALS['CATEGORY_IMAGES_DIR_FS'].$children->Row['Category_Thumb'])) {
												$image =  sprintf("<a href=\"%s\"><img src=\"/images/categories/%s\" alt=\"%s\"/></a>", $url, $children->Row['Category_Thumb'], $children->Row['Meta_Title']);
											} else {
												$image =  sprintf("<a href=\"%s\"><img src=\"/images/template/image_coming_soon_2.jpg\" alt=\"%s\"/></a>", $url, $children->Row['Meta_Title']);
											}
											$link = sprintf('<a href="%s" title="%s">%s</a>', $url, $children->Row['Meta_Title'], htmlentities($children->Row['Category_Title']));
											echo sprintf($tempStr, $image, $link);
											//Check Column
											if(($tempColumn == $productColumns) || ($rows == $children->TotalRows)){
												echo "</tr>";
												$tempColumn = 0;
											}
											$children->Next();
										}
										echo "</table>";
									}
								} else {
									$productColumns = 2;
									if($children->TotalRows > 0){
										$childrenArr = array();

										while($children->Row){
											$subCategory->ID = $children->Row['Category_ID'];
											$subCategory->Name = $children->Row['Category_Title'];
											$subCategory->MetaTitle = $children->Row['Meta_Title'];

											$url = $subCategory->GetUrl();

											$childrenArr[] = sprintf('<a href="%s" title="%s">%s</a>', $url, $children->Row['Meta_Title'],  $children->Row['Category_Title']);
											$children->Next();
										}

										$tempColumn = 0;
										$rows = 0;
										$columnArr = array();
										$col = 0;
										$count = 0;

										for($i=0;$i < count($childrenArr); $i++) {
											if($count >= (count($childrenArr) / $productColumns)) {
												$col++;
												$count = 0;
											}

											$columnArr[$col][] = $childrenArr[$i];
											$count++;
										}

										echo "<table class=\"productCategories clear\">";

										for($i=0;$i < count($columnArr[0]); $i++) {

											echo "<tr>";

											for($j=0;$j < $productColumns; $j++) {
												if(isset($columnArr[$j][$i])) {
													$link = $columnArr[$j][$i];
												} else {
													$link = '&nbsp;';
												}

												echo sprintf("<td style=\"text-align: left;\">%s</td>", $link);
											}

											echo "</tr>";
										}

										echo "</table>";
									}
								}

								$children->Disconnect();
								?>

							</td>
						</tr>
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
