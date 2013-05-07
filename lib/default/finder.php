<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Bulb Finder</title>
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
	<!-- <script type="text/javascript">
		var finderGroups = new Array();
	<?php
		foreach($specificationGroups as $group) {
			echo sprintf('finderGroups.push(\'bulbFinderSelect_%d\');', $group['Group_ID']);
			echo sprintf('finderGroups.push(%s);', ($groups[$group['Group_ID']]) ? 'true' : 'false');
		}
		?>
	</script> -->
	<?php /*
	<script type="text/javascript">
		using("mootools.XHR");
	</script>
	
		
		
		
		function getFinderResults() {
			var xhr = new XHR({
				onSuccess: function(response) {
					updateFinderResults(response);
				},

				onFailure: function() {
					updateFinderResults(0);
				}
			});

			var values = new Array();
			var combinations = new Array();
			
			var filterElement = null;

			for(var i=0; i<finderGroups.length; i=i+2) {
				filterElement = document.getElementById(finderGroups[i]);

				if(filterElement) {
					if(filterElement.value > 0) {
						if(!finderGroups[i+1]) {
							values.push(filterElement.value);
						} else {
							combinations.push(filterElement.value);
						}
					}
				}
			}

			if((values.length > 0) || (combinations.length > 0)) {
				xhr.send('ignition/lib/util/loadBulbFinder.php?values=' + values.join(',') + '&combinations=' + combinations.join(','));
			} else {
				updateFinderResults(0);
			}
		}
		
		function updateFinderResults(matches) {
			var results = document.getElementById('results');
			
			if(results) {
				results.style.display = 'table-row';	
			}
			
			var resultsMatches = document.getElementById('results-matches');
			
			if(resultsMatches) {
				if(matches > 0) {
					resultsMatches.innerHTML = matches + ' matches';
				} else {
					resultsMatches.innerHTML = '<?php echo $zeroMatch; ?>';
				}
			}
			
			var resultsShow = document.getElementById('results-show');
		
			if(resultsShow) {
				resultsShow.style.display = (matches > 0) ? '' : 'none';	
			}
		}
	</script>
	*/ ?>
	<link rel="stylesheet" type="text/css" href="/css/new.css" />
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
					<h1>Bulb Finder</h1>
					<p>Use our simple bulb finder below to find your bulb.</p>

					<?php include('lib/templates/bought.php'); ?>
					
					<?php
					echo $form->Open();
					echo $form->GetHTML('confirm');
					?>
					
					<table style="width:100%;" class="form bulbFinderForm">
						<tr>
							<th colspan="2">Bulb Details</th>
						</tr>
					
						<?php
						foreach($groups as $groupId=>$groupData) {
							?>
							
							<tr>
								<td style="width:28%; vertical-align:top;"><?php echo $form->GetLabel('group_' . $groupId); ?></td>
								<td style="width:72%; vertical-align:top;" class="bulbFinderSelect_<?php echo $groupId; ?>"><?php echo $form->GetHtml('group_' . $groupId); ?></td>
							</tr>
						
							<?php
						}
						?>
						
						<tr <?php echo empty($sql) ? 'style="display: none;"' : ''; ?> class="right-results">
							<td style="width:28%; vertical-align:top;"><strong>Results</strong></td>
							<td style="width:72%; vertical-align:top;">
								
								<div class="loader"></div>
								<div class="results">
									<div class="spacer">
										<strong>Results</strong><br />
										<span class="right-results-matches"><?php echo ($totalResults > 0) ? sprintf('%d matches', $totalResults) : $zeroMatch; ?></span>
									</div>
									

									<div class="spacer right-results-show" <?php echo ($totalResults > 0) ? '' : 'style="display: none;"'; ?>>
										<br />
										<input type="submit" class="submit" name="search" value="Show Bulbs" />
									</div>
								</div>
							</td>
						</tr>
					</table>
					<br />
					
					<?php
					echo $form->Close();
					
					if(!empty($sql)) {
						if($table->Table->TotalRows > 0) {
							?>

							<table class="list">

								<?php
								while($table->Table->Row){
									$subProduct = new Product();
									$subProduct->ID = $table->Table->Row['Product_ID'];
									$subProduct->Name = strip_tags($table->Table->Row['Product_Title']);
									$subProduct->HTMLTitle = strip_tags($table->Table->Row['Product_Title']);
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