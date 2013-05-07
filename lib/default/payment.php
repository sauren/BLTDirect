<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Checkout Payment</title>
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
	<script type="text/javascript">
	function disableSubmit(){
		var placeOrder = document.getElementById('placeOrder');
		placeOrder.disabled = true;
	}

	function toggleType(obj) {
		var e = document.getElementById('issue');

		if(e) {
			switch(obj.value) {
				case '5':
				case '6':
					e.removeAttribute('disabled');
					break;
				default:
					e.setAttribute('disabled', 'disabled');
					break;
			}
		}
	}

	var disableIssue = <?php echo (($form->GetValue('cardType') == 5) || ($form->GetValue('cardType') == 6) || ($form->GetValue('cardType') == 7)) ? 'false' : 'true'; ?>

	window.onload = function() {
		if(disableIssue) {
			var e = document.getElementById('issue');

			if(e) {
				e.setAttribute('disabled', 'disabled');
			}
		}
	}
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
			<h1>Payment</h1>
			<p>Finally, please select your preferred payment method and your credit card information below.</p>
			<?php
			if(!$form->Valid){
				echo $form->GetError();
				echo "<br />";
			}
			$form->OnSubmit('disableSubmit();');

			echo $form->Open();
			echo $form->GetHtml('action');
			echo $form->GetHtml('confirm');

			$displayRadio = false;
			if(strtoupper($cart->Customer->IsCreditActive) == 'Y' && $cart->Customer->CreditRemaining > 0 && $cart->Customer->CreditRemaining >= $total){
				$displayRadio = true;
			?>
			<table cellspacing="0" class="checkoutPayment">
				<tr>
					<td colspan="2"><?php echo $form->GetHTML('isOnAccount', 1); ?><strong><?php echo $form->GetLabel('isOnAccount', 1); ?></strong></td>
				</tr>
				<tr>
					<td align="right" width="50%">Charge My Credit Account for:</td>
					<td><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
				</tr>
				<tr>
					<td align="right" width="50%">My Monthly Credit Allowance:</td>
					<td>&pound;<?php echo number_format($cart->Customer->CreditLimit, 2, '.', ','); ?></td>
				</tr>
				<tr>
					<td align="right" width="50%">Remaining Credit Before Spend:</td>
					<td>&pound;<?php echo number_format($cart->Customer->CreditRemaining, 2, '.', ','); ?></td>
				</tr>
				<tr>
					<td align="right" width="50%">Remaining Credit After Spend:</td>
					<td>&pound;<?php echo number_format($cart->Customer->CreditRemaining-$total, 2, '.', ','); ?></td>
				</tr>
				<tr>
					<td align="right" width="50%">My Credit Terms:</td>
					<td><?php echo$cart->Customer->CreditPeriod; ?> Days</td>
				</tr>
			</table>

			<br />
			<?php } elseif(strtoupper($cart->Customer->IsCreditActive) == 'Y' && ($cart->Customer->CreditRemaining <= 0 || $cart->Customer->CreditRemaining < $total)){ ?>
			<table cellspacing="0" class="checkoutPayment">
				<tr>
					<td colspan="2"><strong>Credit Account Customer</strong></td>
				</tr>
				<tr>
					<td colspan="2"><span class="alert"><img src="ignition/images/icon_alert_2.gif" align="absmiddle" />
					Your Credit Account has insufficient funds remaining this month to purchase on credit (See Details Below). You may continue with purchase via Credit/Debit Card.</span></td>
				</tr>
				<tr>
					<td align="right" width="50%">Charge My Credit Account for:</td>
					<td><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
				</tr>
				<tr>
					<td align="right" width="50%">My Monthly Credit Allowance:</td>
					<td>&pound;<?php echo number_format($cart->Customer->CreditLimit, 2, '.', ','); ?></td>
				</tr>
				<tr>
					<td align="right" width="50%">Remaining Credit Before Spend:</td>
					<td>&pound;<?php echo number_format($cart->Customer->CreditRemaining, 2, '.', ','); ?></td>
				</tr>
				<tr>
					<td align="right" width="50%">Remaining Credit After Spend:</td>
					<td>&pound;<?php echo number_format($cart->Customer->CreditRemaining-$total, 2, '.', ','); ?></td>
				</tr>
				<tr>
					<td align="right" width="50%">My Credit Terms:</td>
					<td><?php echo$cart->Customer->CreditPeriod; ?> Days</td>
				</tr>
			</table>
			<br />
			<?php } ?>

			<table cellspacing="0" class="checkoutPayment">
				<tr>
					<td colspan="2"><?php echo ($displayRadio)?$form->GetHTML('isOnAccount', 2):''; ?><strong><?php echo $form->GetLabel('isOnAccount', 2); ?></strong></td>
				</tr>
				<tr>
					<td align="right" width="50%">Charge My Credit Card for:</td>
					<td><strong>&pound;<?php echo number_format($total, 2, '.', ','); ?></strong></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
				    <td><input type="submit" class="submit" name="Place Order" value="Place Order" id="placeOrder" on="on" /></td>
				</tr>
			</table>
			<?php echo $form->Close(); ?>
			
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