<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Login/Register</title>
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
	<link rel="stylesheet" type="text/css" href="/css/login.css" />
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
			<?php
				if($isCheckout){

			?>
			<h1>Secure Checkout</h1>
					<p>Are you an existing BLT Direct customer? Or, are you new to BLT Direct? Select the appropriate button below.</p>
			<?php
				} elseif($isReturns){
			?>
				<h1>Returns</h1>
					<p>Our aim is to provide our customers with the highest level of service possible and this extends to our customer friendly policies.</p>
					<p>Please <strong><u>Do Not</u></strong> return any item to BLT Direct without first following our simple to use return procedure.</p>
					<p>Please note all returns are subject to section 8 of our Terms &amp; Conditions; <a href="/terms.php#warranties" title="Terms &amp; Conditions: Warranties &amp; Returns">8. Warranties &amp; Returns</a>.</p>
					<p>Should you experience a problem at any stage of our returns procedure please contact our customer service team on 01473 559501 or alternatively by email at customerservices@bltdirect.com.</p>
					<p>If you have received damaged, faulty or incorrect items or have ordered incorrectly please login below.</p>
			<?php
				} elseif($isCancellation) {
			?>
				<h1>Cancellations</h1>
					<p>If you would like to cancel entire orders or individual products, please login and navigate to the appropriate order.</p>

			<?php
				} elseif($isDuplication) {
			?>
				<h1>Duplicate A Past Order</h1>
					<p>If you would like to duplicate entire or partial orders, please login and navigate to the appropriate order.</p>


			<?php
				} elseif($isIntroduce) {
			?>
				<h1>Introduce A Friend</h1>
					<p>Introduce a friend and your friend will get a <?php print Setting::GetValue('customer_coupon_discount'); ?>% discount and you will earn a credit to <?php print Setting::GetValue('customer_coupon_discount'); ?>% of your friend's first order to spend against your future purchases with BLT Direct.</p>
					<p>Log in to get your coupon code. Just ask your friend to quote this coupon code in his or her purchase with us. The coupon will be valid until <?php print date('jS F Y', mktime(0, 0, 0, date('m'), date('d'), date('Y')+1)); ?>.</p>

            <?php
				} else  {
			?>
				<h1>Login / Register</h1>
				<p>You are at a gateway to a secure area of our website. Please choose from one of the following options to proceed.</p>

			<?php
				}
			?>
			<p>&nbsp;</p>


			<?php
				if(!$login->Valid){
					echo $login->GetError();
					echo "<br>";
				}

				echo $login->Open();
				echo $login->GetHtml('action');
				echo $login->GetHtml('confirm');
				echo $login->GetHtml('direct');
			?>
			<!-- new stuff -->
			<div id="loginBox" class="gatewayBox">
				<div class="container">
					<div class="title">Existing Customer</div>
					<div class="content">
						<p><?php echo $login->GetLabel('username'); ?>:<br />
						<?php echo $login->GetHtml('username'); ?></p>
						<p><?php echo $login->GetLabel('password'); ?>:<br />
						<?php echo $login->GetHtml('password'); ?></p>
					</div>
					<input type="image" src="/images/login/blueBox_continue.gif" alt="Login" class="image" />
				</div>
			</div>
			<?php echo $login->Close();
				if(!$isReturns && !$isCancellation && !$isDuplication) {
				?>

				<div id="newCustomerBox" class="gatewayBox">
					<div class="container">
						<div class="title">New Customer</div>
						<div class="content">
							<p>New to BLT Direct? Create an account and benefit from future discount schemes.</p>
						</div>
						<a href="/register.php?direct=<?php echo $direct; ?>"><img src="/images/login/greenBox_continue.gif" class="image" alt="Register" /></a>
					</div>

				</div>

			<?php
			}
			 if($isCheckout){
			 ?>
			<div id="expressCheckoutBox" class="gatewayBox">
				<div class="container">
					<div class="title">Express Checkout</div>
					<div class="content">
                        <p>In a hurry? A faster way of checking out. Ideal for one-off sales.</p>
					</div>
					<a href="/register.php?express=true&amp;direct=<?php echo $direct; ?>"><img src="/images/login/orangeBox_continue.gif" alt="Express-Checkout" class="image"/></a>
				</div>
			</div>
			<?php } ?>


			<div class="clear"></div>
			<br />
			<br />
			<br />
			<br />

				<?php
					if(!$assistant->Valid){
						echo $assistant->GetError();
						echo "<br>";
					}
					
					echo $assistant->Open();
					echo $assistant->GetHtml('action');
					echo $assistant->GetHtml('confirm');
					echo $assistant->GetHtml('direct');
				?>
				<h3>Forgotten Password?</h3>
				<?php
				if(isset($_REQUEST['assistant']) && ($_REQUEST['assistant'] == 'successful')) {
					echo "<span class=\"alert\">Your password reset information has been sent to your email address.</span><br />";
				} else {
					if(!$assistant->Valid){
						echo "<span class=\"alert\">Sorry, we could not find your entry in our database.</span><br />";
						echo "<br>";
					} else {
						?>
				        <!-- <p>If you have forgotten your password don't worry. Simply enter your email address below and we'll send you your login details.</p> -->
				        <?php
					}
				}

				echo sprintf('%s : %s', $assistant->GetLabel('emailOrUser'), $assistant->GetHtml('emailOrUser')); ?>
				<input type="submit" class="greySubmit" name="continue" value="continue" />
				<?php echo $assistant->Close(); ?>
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