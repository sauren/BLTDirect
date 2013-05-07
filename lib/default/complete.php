<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Order Complete</title>
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
			<noscript><img width="1" height="1" src="https://53924.r.msn.com/?type=1&amp;cp=1"/></noscript>
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

<script type="text/javascript">
<!-- Yahoo!
window.ysm_customData = new Object();
window.ysm_customData.conversion = "transId=,currency=,amount=";

var ysm_accountid  = "1JGL043GL8AJ7C61MK9SVDEMIDG";
document.write("<SCR" + "IPT language='JavaScript' type='text/javascript' "
+ "SRC=//" + "srv1.wa.marketingsolutions.yahoo.com" +
"/script/ScriptServlet" + "?aid=" + ysm_accountid
+ "></SCR" + "IPT>");

// -->
</script>

<script type="text/javascript">if (!window.mstag) mstag = {loadTag : function(){},time : (new Date()).getTime()};</script>
<script id="mstag_tops" type="text/javascript" src="//flex.atdmt.com/mstag/site/3fa51111-b16d-424a-932c-6438dbb7b744/mstag.js"></script>
<script type="text/javascript">mstag.loadTag("conversion", {cp:"5050",dedup:"1"})</script>
<noscript><iframe src="//flex.atdmt.com/mstag/tag/3fa51111-b16d-424a-932c-6438dbb7b744/conversion.html?cp=5050&amp;dedup=1" frameborder="0" scrolling="No" width="1" height="1" style="visibility: hidden; display: none;"></iframe></noscript>

<?php
}
?>

<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>