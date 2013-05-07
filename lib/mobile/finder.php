<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/mobile.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Bulb Finder</title>
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
    <link rel="stylesheet" type="text/css" href="/css/new.css" />
	<script type="text/javascript" src="/js/generic.js"></script>
	<script type="text/javascript" src="/js/evance_api.js"></script>
	<script type="text/javascript" src="/js/mootools.js"></script>
	<script type="text/javascript" src="/js/jquery.js"></script>
	<script type="text/javascript" src="/js/evance.js"></script>
	<script type="text/javascript" src="/js/bltdirect.js"></script>


	<link rel="stylesheet" type="text/css" href="/css/MobileSplash.css" />
    <link rel="stylesheet" type="text/css" href="/css/new.css" />
   	<link rel="stylesheet" type="text/css" href="/css/mobile/new.css" />
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'N') {
		?>
		<script type="text/javascript" src="/js/bltdirect/template.js"></script>
        <?php
	}
	?>
    
	<script type="text/javascript">
	//<![CDATA[
		<?php
		for($i=0; $i<count($GLOBALS['Cache']['Categories']); $i=$i+2) {
			echo sprintf("menu1.add('navProducts%d', 'navProducts', '%s', '%s', null, 'subMenu');", $i, $GLOBALS['Cache']['Categories'][$i], $GLOBALS['Cache']['Categories'][$i+1]);
		}
		?>
	//]]>
	</script>
    <script type="text/javascript">
		window.___gcfg = {lang: 'en-GB'};

		(function() {
			var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
			po.src = 'https://apis.google.com/js/plusone.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
		})();
	</script>

	<script type="text/javascript">
	var rightFinderGroups = new Array();
	
	<?php
	foreach($specificationGroups as $group) {
		echo sprintf('rightFinderGroups.push(\'bulbFinderSelect_%d\');', $group['Group_ID']);
		echo sprintf('rightFinderGroups.push(%s);', ($groups[$group['Group_ID']]) ? 'true' : 'false');
	}
	?>
</script>
<script type="text/javascript" src="js/right.js"></script>
	<!-- InstanceBeginEditable name="head" -->
	
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
			<h1>Bulb Finder</h1>
			<p>Use our simple bulb finder below to find your bulb.</p>

			<?php include('lib/templates/bought.php'); ?>
			
			<?php
			echo $form->Open();
			echo $form->GetHTML('confirm');
			?>
			
			<div class="bulbFinder form bulbFinderForm">
				<div class="pad">
					<?php foreach($groups as $groupId=>$groupData) { ?>
					<div class="filterOption">
						<?php echo $form->GetLabel('group_' . $groupId); ?>
						<div class="bulbFinderSelect_<?php echo $groupId; ?>"><?php echo $form->GetHtml('group_' . $groupId); ?></div>
					</div>
					<?php } ?>
				</div>

				<div class="pad right-results">

					<div class="loader"></div>
					<div class="results">
						
						<strong>Results</strong><br />
						<span class="right-results-matches"><?php echo ($totalResults > 0) ? sprintf('%d matches', $totalResults) : $zeroMatch; ?></span>
						
					</div>
				</div>
				<div class="pad">
					<input type="submit" class="submit" name="search" value="Show Bulbs" />
				</div>
			</div>

			<div class="spacer right-results-show" <?php echo ($totalResults > 0) ? '' : 'style="display: none;"'; ?>></div>

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
							
							include('lib/mobile/productLine.php');

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
<img src="http://stats1.saletrack.co.uk/scripts/stinit.asp?cid=256336&rf=JavaScri
pt%20Disabled%20Browser" border="0" width="0" height="0" />
</noscript>
-->

<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>