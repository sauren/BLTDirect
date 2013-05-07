<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/mobile.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Login/Register</title>
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
	<script language="javascript" type="text/javascript" src="/js/generic.js"></script>
	<script language="javascript" type="text/javascript" src="/js/evance_api.js"></script>
	<script language="javascript" type="text/javascript" src="/js/mootools.js"></script>
	<script language="javascript" type="text/javascript" src="/js/evance.js"></script>
	<script language="javascript" type="text/javascript" src="/js/bltdirect.js"></script>
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'N') {
		?>
		<script language="javascript" type="text/javascript" src="/js/bltdirect/template.js"></script>
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
	<link rel="stylesheet" type="text/css" href="/css/MobileSplash.css" />
    <link rel="stylesheet" type="text/css" href="/css/new.css" />
   	<link rel="stylesheet" type="text/css" href="/css/mobile/new.css" />
	<!-- InstanceBeginEditable name="head" -->
	<link rel="stylesheet" type="text/css" href="/css/login.css">
	<meta name="keywords" content="light bulbs, light bulb, lightbulbs, lightbulb, lamps, fluorescent, tubes, osram, energy saving, sylvania, philips, ge, halogen, low energy, metal halide, candle, dichroic, gu10, projector, blt direct" />
	<meta name="description" content="We specialise in supplying lamps, light bulbs and fluorescent tubes, Our stocks include Osram,GE, Sylvania, Omicron, Pro lite, Crompton, Ushio and Philips light bulbs, " />
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
					<input type="image" src="/images/login/blueBox_continue.gif" class="image" />
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
						<a href="/register.php?direct=<?php echo $direct; ?>"><img src="/images/login/greenBox_continue.gif" class="image" /></a>
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
					<a href="/register.php?express=true&amp;direct=<?php echo $direct; ?>"><img src="/images/login/orangeBox_continue.gif" class="image"/></a>
				</div>
			</div>
			<?php } ?>


			<div class="clear"></div>
			<br />
			<br />
			<br />
			<br />

				<?php
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
				        <!--<p>If you have forgotten your password don't worry. Simply enter your email address below and we'll send you your login details.</p> -->
				        <?php
					}
				}

				echo $assistant->GetHtml('emailOrUser'); ?>
				<input type="submit" class="greySubmit" name="continue" value="continue" />
				<?php echo $login->Close(); ?>
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