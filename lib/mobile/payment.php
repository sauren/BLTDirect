<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/mobile.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Checkout Payment</title>
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

	<a name="top"></a>

    <div id="Page">
        <div id="PageContent">
            <div class="right rightIcon">
            	<a href="http://www.bltdirect.com/" title="Light Bulbs, Lamps and Tubes Direct"><img src="../../images/logo_125.png" alt="Light Bulbs, Lamps and Tubes Direct" /></a><br />
            	<?php echo Setting::GetValue('telephone_sales_hotline'); ?>
            </div>
            
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
				    <td><input type="submit" class="submit" name="Place Order" value="Place Order" id="placeOrder" on /></td>
				</tr>
			</table>
			<?php echo $form->Close(); ?>
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