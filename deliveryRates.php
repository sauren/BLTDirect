<?php
require_once('lib/common/appHeader.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>BLT Direct Delivery Rates</title>
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
              <h1>Delivery Rates</h1>
			  <p>Our shopping cart defaults to United Kingdom, England &amp; Wales. Simply start adding product to your shopping cart to find out the shipping costs. If you are not within England or Wales you should follow the instructions below for international visitors. </p>

			  <h3>Deliveries Within the United Kingdom</h3>
			  <p>The table below highlights the delivery costs of our Standard Light Bulbs.</p>

				<table cellpadding="0" cellspacing="0" width="100%">
				  	<tr>
				  		<td width="50%" valign="top">

				  			<table cellpadding="0" cellspacing="0" class="homeProducts">
								<tr>
									<th colspan="3">Delivery Rates for <span style="color: #0a0;">Standard Light Bulbs</span></th>
								</tr>
								<tr>
									<td>&nbsp;</td>
									<td align="right"><strong>Under 10Kg</strong></td>
									<td align="right"><strong>Over 10Kg</strong></td>
								</tr>
								<tr>
									<td><strong>Standard 2-6 Days</strong></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr>
									<td>Under &pound;45.00 (ex. VAT)</td>
									<td align="right">&pound;4.45</td>
									<td align="right">&pound;4.45</td>
								</tr>
								<tr>
									<td>Over &pound;45.00 (ex. VAT)</td>
									<td align="right"><span style="color: #0a0;">FREE</span></td>
									<td align="right"><span style="color: #0a0;">FREE</span></td>
								</tr>
								<tr>
									<td><strong>Next Day Delivery </strong></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr>
									<td>All Orders</td>
									<td align="right">&pound;13.95</td>
									<td align="right">+&pound;0.73 per additional kilo</td>
								</tr>
								<tr>
									<td colspan="3">&nbsp;</td>
								</tr>
								<tr>
									<th colspan="3">All Other Product Types </th>
								</tr>
								<tr>
									<td colspan="3">
										<p>Please use the shopping cart to find out your exact shipping costs for the following items which start at &pound;4.45</p>
										<ul style="margin-top: 0;">
											<li>Bathroom Light Fittings </li>
											<li>Control Gear</li>
											<li>Fluorescent Tubes 450mm - 900mm</li>
											<li>Heater Lamps</li>
											<li>Light Fittings</li>
										</ul>

										<p>Courier service for the following items from &pound;7.00</p>
										<ul style="margin-top: 0;">
											<li>Projector Lamp</li>
											<li>Fluorescent Tubes 1200mm - 1800mm</li>
											<li>Fluorescent Tubes 1800mm - 2400mm</li>
											<li>Sunbed Tubes</li>
										</ul>
									</td>
								</tr>
							</table>

				  		</td>
				  		<td width="50%" valign="top">
				  			<div style="padding: 0 10px 0 10px;">
					  			<h3>Free Deliveries</h3>
					  			<p>Light bulb orders qualify for free delivery in the following areas where the order value is over &pound;45.00 (ex. VAT). Light fittings, fluorescent tubes, and control gear do not qualify for free shipping.</p>
					  			<br />

					  			<div style="text-align: center;"><img src="/images/delivery_map.gif" width="210" height="304" alt="Regions of free delivery." /></div>
					  			<br />

					  			<p style="background-color: #54A854; margin: 0; padding: 5px; color: #fff; border: 1px solid #439B25;">BLT Direct offer free delivery on orders over &pound;45.00 (ex. VAT) for England, Wales, and Southern Scotland.</p><br />
					  			<p style="background-color: #FBEE5B; margin: 0; padding: 5px; color: #000; border: 1px solid #E7D830;">Northern Ireland, Scottish Highlands and Isles, Isle of Man, and the Channel Islands only qualify for free shipping where the consignment weight is under 2kgs and the order value is over &pound;45.00 (ex. VAT).</p><br />
							</div>
				  		</td>
				  	</tr>
				  </table><br />

			  <p>&nbsp;</p>
			  <p><strong>Please Note:</strong> if you have not selected a postage option, or if no shipping prices are available, the Mini Cart on the right of this page will show a shipping cost of &pound;0.00. If this happens please click the &quot;View&quot; button on the Mini Cart to read more.</p>

			  <h3><a name="International" id="International">International Deliveries</a></h3>
			  <div style="text-align: center;"><img src="images/delivery_world_map.gif" width="550" height="330" alt="International Delivery Rates" /></div>

			  <p>We try our hardest to provide our online customers with the most competitive shipping and handling no matter your location. Unfortunately this makes our shipping and handling calculations very complicated. If you would like to find out the shipping costs for your location please use our shipping and handling calculator built into our shopping cart. You can change your location by clicking on the &quot;Change Location&quot; link beneath the Shipping location within the shopping cart (pictured below).</p>
              <img src="images/cart_changeLocation_1.gif" width="491" height="141" alt="Change Location" />
			  <p>You will then be redirected to another page where you will be able to choose the Country and Region you would like your purchase to be shipped to (pictured below). Click submit and you will be redirected back to the shopping cart where the new Shipping Charges will be displayed. </p>
			  <img src="images/cart_changeLocation_2.gif" width="198" height="236" alt="Shipping Charges" />
			<p>If we do not have any prices for your location you will be prompted. If this happens please call us on <strong><?php echo Setting::GetValue('telephone_sales_hotline'); ?></strong> and we will be happy to provide you with a quotation for Shipping your purchase to your chosen destination. </p>
			<p>If you have any further queries with regard to our Shipping costs please <a href="support.php">contact us</a>.</p>

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
<?php include('lib/common/appFooter.php'); ?>
