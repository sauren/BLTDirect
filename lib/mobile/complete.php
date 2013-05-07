<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/mobile.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Order Complete</title>
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
			<h1>Order Complete</h1>
			<?php
			if(strlen($_REQUEST['o']) > 0) {
				?>
				<p>Thank you for shopping with <?php echo $GLOBALS['COMPANY']; ?>. Your order (Ref. <strong><?php echo $order->Prefix . $order->ID; ?></strong>) was successfully completed. Your order will be subject to security checks before your credit card is charged. When contacting us regarding your order please remember to quote your order reference number. We recommend that you print this page for your records, however a full history of your online orders is available through '<a href="accountcenter.php">My Account</a>'. </p>
				<?php
			} else {
				?>
				<p>Thank you for shopping with <?php echo $GLOBALS['COMPANY']; ?>. Your order was successfully completed. Your order will be subject to security checks before your credit card is charged. When contacting us regarding your order please remember to quote your order reference number. We recommend that you print this page for your records, however a full history of your online orders is available through '<a href="accountcenter.php">My Account</a>'.</p>
				<?php
			}

			if(strlen($_REQUEST['o']) > 0) {
				echo $form->Open();
				echo $form->GetHTML('action');
				echo $form->GetHTML('confirm');
				echo $form->GetHTML('o');

				if(!$form->Valid){
					echo $form->GetError();
					echo "<br>";
				}
			?>
			<br />
			<table border="0" cellpadding="10" cellspacing="0" class="bluebox">
              <?php
              if($order->PaymentMethod->Reference != 'google') {
                	?>
                	<tr>
		                <td><h3 class="blue">Add Your Own Reference</h3>
		                <p>If you have your own purchasing or order reference number enter it below: </p>
		                <p>
						<?php echo $form->GetHTML('custom'); ?>
						</p>
						</td>
		              </tr>
              	<?php
              }
                ?>
              <tr>
                <td>

                <h3 class="blue">Delivery Instructions</h3>
                    <p>If you have special delivery requirements for your order please use the field below: </p>
                    <p><?php echo $form->GetHTML('delivery'); ?></p>
                  </td>
              </tr>
              <tr>
                <td>

                <h3 class="blue">Add Additional Information</h3>
                    <p>If you would like to add additional information to your order please use the field below: </p>
                    <p><?php echo $form->GetHTML('message'); ?></p>
                    <p>When you click Continue below you will be redirected to a summary of your order. </p>
                    <p>
                      <input name="Submit" type="submit" class="submit" value="Continue" tabindex="<?php echo $form->GetTabIndex(); ?>" />
                    </p></td>
              </tr>
            </table>
			<?php
			echo $form->Close();
			}
			?>

			<!-- Yahoo Code for Purchase Convertion Page -->
			<script type="text/javascript">
			<!-- Overture Services Inc. 07/15/2003
			var cc_tagVersion = "1.0";
			var cc_accountID = "4005137100";
			var cc_marketID =  "1";
			var cc_protocol="http";
			var cc_subdomain = "convctr";
			if(location.protocol == "https:")
			{
				cc_protocol="https";
				cc_subdomain="convctrs";
			}
			var cc_queryStr = "?" + "ver=" + cc_tagVersion + "&aID=" + cc_accountID + "&mkt=" + cc_marketID +"&ref=" + escape(document.referrer);
			var cc_imageUrl = cc_protocol + "://" + cc_subdomain + ".overture.com/images/cc/cc.gif" + cc_queryStr;
			var cc_imageObject = new Image();
			cc_imageObject.src = cc_imageUrl;
			// -->
			</script>

			<!-- MSN Code for Purchase Convertion Page -->
			<script type="text/javascript">
			microsoft_adcenterconversion_domainid = 53924;
			microsoft_adcenterconversion_cp = 5050;
			</script>
			<script src=" https://0.r.msn.com/scripts/microsoft_adcenterconversion.js"></script>
			<noscript><img width="1" height="1" src="https://53924.r.msn.com/?type=1&cp=1"/></noscript>
			<!-- InstanceEndEditable -->
            
            <div class="clear"></div>
        </div>
    </div>

	<?php if(param('paymenttype')!='card'){ ?>

	<script type="text/javascript">
	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', 'UA-1618935-2']);
	  _gaq.push(['_setDomainName', 'bltdirect.com']);
	  _gaq.push(['_trackPageview']);

	  _gaq.push(['_addTrans',
	    '<?php echo $order->Prefix . $order->ID; ?>',           // order ID - required
	    'BLT Direct', // affiliation or store name
	    '<?php echo number_format($order->Total, 2, ".", ""); ?>',          // total - required
	    '<?php echo number_format($order->TotalTax, 2, ".", ""); ?>',           // tax
	    '<?php echo number_format($order->TotalShipping, 2, ".", ""); ?>',              // shipping
	    '<?php echo $order->Shipping->Address->City; ?>',       // city
	    '<?php echo $order->Shipping->Address->Region->Name; ?>',     // state or province
	    '<?php echo $order->Shipping->Address->Country->Name; ?>'             // country
	  ]);

	   // add item might be called for every item in the shopping cart
	   // where your ecommerce engine loops through each item in the cart and
	   // prints out _addItem for each
	<?php
		for($i=0; $i < count($order->Line); $i++){
			if($order->Line[$i]->Product->ID > 0) {
				$itemPrice = ($order->Line[$i]->Price-($order->Line[$i]->Discount/$order->Line[$i]->Quantity));
				$itemTotal = ($order->Line[$i]->Price-($order->Line[$i]->Discount/$order->Line[$i]->Quantity))*$order->Line[$i]->Quantity;
			} else {
				$itemPrice = $order->Line[$i]->Price;
				$itemTotal = $order->Line[$i]->Price * $order->Line[$i]->Quantity;
			}
			if($order->Line[$i]->Product->ID > 0) {
				$productTitle = $order->Line[$i]->Product->Name;
			} else {
				$productTitle = $order->Line[$i]->AssociativeProductTitle;
			}
	?>
	  _gaq.push(['_addItem',
	    '<?php echo $order->Prefix . $order->ID; ?>',           // order ID - required
	    '<?php echo $order->Line[$i]->Product->ID; ?>',           // SKU/code - required
	    '<?php echo htmlentities($productTitle); ?>',        // product name
	    '',   // category or variation
	    '<?php echo number_format($itemPrice, 2, ".", ""); ?>',          // unit price - required
	    '<?php echo $order->Line[$i]->Quantity; ?>'               // quantity - required
	  ]);
	<?php } ?>

	  //submits transaction to the Analytics servers
	  _gaq.push(['_trackTrans']); 


	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();
	</script>


	<!-- Google -->
	<!-- Google Code for lead Conversion Page -->
	<script type="text/javascript">
	<!--
		var google_conversion_id = 1070689084;
		var google_conversion_language = "en";
		var google_conversion_format = "1";
		var google_conversion_color = "666666";
		var google_conversion_value = "<?php echo number_format(($order->Total-$order->TotalTax), 2, ".", ""); ?>";
		var google_conversion_label = "fnS1CNLkPRC81sX-Aw";
	//-->
	</script>
	<script type="text/javascript" src="http://www.googleadservices.com/pagead/conversion.js">
	</script>
	<noscript>
		<div style="display:inline;">
		<img height="1" width="1" style="border-style:none;" alt="" src="http://www.googleadservices.com/pagead/conversion/1070689084/?value=0&amp;label=fnS1CNLkPRC81sX-Aw&amp;guid=ON&amp;script=0"/>
		</div>
	</noscript>
<?php }?>

	<!-- InstanceBeginEditable name="Tracking Script" -->

<!--
<script>
var parm,data,htprot='http';
data = 'cid=256336&cs=<?php echo $order->Total; ?>&it=<?php echo count($order->Line); ?>&oi=<?php echo $order->ID; ?>';
parm=' border="0" hspace="0" vspace="0" width="1" height="1"'; if(self.location.protocol=='https:')htprot='https';
document.write('<img '+parm+' src="'+htprot+'://stats1.saletrack.co.uk/scripts/stexit.asp?'+ data + '">');  </script>
<noscript>
<img src="http://stats1.saletrack.co.uk/scripts/stexit.asp?cid=256336&cs=<?php echo $order->Total; ?>&it=<?php echo count($order->Line); ?>&oi=<?php echo $order->ID; ?>" border="0" width="0" height="0">
</noscript>
-->


<?php
if(strlen($_REQUEST['o']) == 0) {
	?>

<SCRIPT type="text/javascript">
<!-- Yahoo!
window.ysm_customData = new Object();
window.ysm_customData.conversion = "transId=,currency=,amount=";

var ysm_accountid  = "1JGL043GL8AJ7C61MK9SVDEMIDG";
document.write("<SCR" + "IPT language='JavaScript' type='text/javascript' "
+ "SRC=//" + "srv1.wa.marketingsolutions.yahoo.com" +
"/script/ScriptServlet" + "?aid=" + ysm_accountid
+ "></SCR" + "IPT>");

// -->
</SCRIPT>

<script type="text/javascript">if (!window.mstag) mstag = {loadTag : function(){},time : (new Date()).getTime()};</script>
<script id="mstag_tops" type="text/javascript" src="//flex.atdmt.com/mstag/site/3fa51111-b16d-424a-932c-6438dbb7b744/mstag.js"></script>
<script type="text/javascript">mstag.loadTag("conversion", {cp:"5050",dedup:"1"})</script>
<noscript><iframe src="//flex.atdmt.com/mstag/tag/3fa51111-b16d-424a-932c-6438dbb7b744/conversion.html?cp=5050&dedup=1" frameborder="0" scrolling="no" width="1" height="1" style="visibility: hidden; display: none;"></iframe></noscript>

<?php
}
?>

<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>