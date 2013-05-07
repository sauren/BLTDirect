<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Order Confirmation</title>
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
	<!-- InstanceBeginEditable name="head" --><!-- InstanceEndEditable -->
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
			<h1>Order Confirmation</h1>

			<div id="orderConfirmation">
				<p><strong>Thank you for shopping at BLT Direct. A summary of your order is shown below.</strong></p>
				<p><strong>Make the most of your online facility by going to your account centre where you can add products to your favourites, duplicate an order, and manage any returns.</strong></p>
				<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a><?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a></p>
			</div>

			<?php
			$order->Postage->Get();

			$notes = new DataQuery(sprintf("select Order_Note_ID from order_note where Order_ID=%d", mysql_real_escape_string($order->ID)));
			$numNotes = $notes->TotalRows;
			$notes->Disconnect();
			?>

				<table width="100%"  border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td valign="top">
						<table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
							<tr>
							  <td valign="top" class="billing"><p><strong>Billing Address:</strong><br />
									  <?php echo $order->GetBillingAddress();  ?></p></td>
							  <td valign="top" class="shipping"><p><strong>Shipping Address:</strong><br />
									  <?php echo $order->GetShippingAddress();  ?></p></td>
							</tr>
						</table>
					</td>
                    <td align="right" valign="top"><table cellpadding="0" cellspacing="0" border="0" class="invoicePaymentDetails">
                        <tr>
                          <th valign="top">Order Ref:</th>
                          <td valign="top"><strong><?php echo $order->Prefix . $order->ID; ?></strong></td>
                        </tr>
						<?php if(!empty($order->CustomID)){ ?>
						<tr>
                          <th valign="top">Your Ref:</th>
                          <td valign="top"><strong><?php echo $order->CustomID; ?></strong></td>
                        </tr>
						<?php } ?>
                        <tr>
                          <th valign="top">Order Date:</th>
                          <td valign="top"><?php echo cDatetime($order->OrderedOn, 'longdate'); ?></td>
                        </tr>
						<tr>
                          <th valign="top">Status:</th>
                          <td valign="top"><?php echo ucfirst($order->Status); ?></td>
                        </tr>
                        <tr>
                          <th valign="top">&nbsp;</th>
                          <td valign="top">&nbsp;</td>
                        </tr>
                        <tr>
                          <th valign="top">Payment Method:</th>
                          <td valign="top"><?php echo $order->GetPaymentMethod(); ?></td>
                        </tr>
                        <tr>
                          <th valign="top">Card:</th>
                          <td valign="top"><?php echo $order->Card->PrivateNumber(); ?>&nbsp;</td>
                        </tr>
                        <tr>
                          <th valign="top">Expires: </th>
                          <td valign="top"><?php echo $order->Card->Expires; ?>&nbsp;</td>
                        </tr>
                    </table></td>
                  </tr>
                </table>


		      <br />
		        <table cellspacing="0" class="catProducts">
				<tr>
					<th>Qty</th>
					<th>Product</th>
					<th>Despatched</th>
					<th>Invoice</th>
					<th>Quickfind</th>
					<th>Price</th>
					<th>Your Price</th>
					<th>Line Total</th>
				</tr>
			<?php
			$order->Coupon->Get();
			$order->OriginalCoupon->Get();

			$subTotal = 0;

			for($i=0; $i < count($order->Line); $i++){
				if($order->Line[$i]->Product->ID > 0) {
					$itemTotal = ($order->Line[$i]->Price-($order->Line[$i]->Discount/$order->Line[$i]->Quantity))*$order->Line[$i]->Quantity;
				} else {
					$itemTotal = $order->Line[$i]->Price * $order->Line[$i]->Quantity;
				}

				$subTotal += $itemTotal;
				?>
				<tr>
					<td><?php echo $order->Line[$i]->Quantity; ?>x</td>
					<td>
					<?php
					if($order->Line[$i]->Product->ID > 0) {
						echo $order->Line[$i]->Product->Name;
					} else {
						echo $order->Line[$i]->AssociativeProductTitle;
					}
					?>
					</td>
					<?php
					if(strtolower($order->Line[$i]->Status) == 'cancelled'){
					?>
					<td colspan="2" align="center">Cancelled</td>
					<?php
					} else {
					?>
					<td><?php if(!empty($order->Line[$i]->DespatchID)){
						echo '<a href="despatch_note.php?despatchid=' . $order->Line[$i]->DespatchID . '" target="_blank"><img src="/images/icon_tick_2.gif" border="0" alt="Click Here to view the despatch note." /></a>';
					}; ?>&nbsp;

					</td>

					<td><?php
					if (!empty($order->Line[$i]->InvoiceID)){
						echo '<a href="invoice.php?invoiceid=' . $order->Line[$i]->InvoiceID . '" target="_blank">' . $order->Line[$i]->InvoiceID . "</a>";
					}
						?>&nbsp;</td><?php } ?>
					<td>
					<?php
					if($order->Line[$i]->Product->ID > 0) {
						echo $order->Line[$i]->Product->PublicID();
					} else {
						echo '-';
					}
					?>
					</td>
					<td align="right">&pound;<?php echo number_format($order->Line[$i]->Price, 2, '.', ','); ?></td>
					<?php
					if($order->Line[$i]->Product->ID > 0) {
						if($order->Line[$i]->Price == ($order->Line[$i]->Price-($order->Line[$i]->Discount/$order->Line[$i]->Quantity))) {
							?>
							<td align="right">-</td>
							<?php
						} else {
							?>
							<td align="right">&pound;<?php echo number_format(($order->Line[$i]->Price-($order->Line[$i]->Discount/$order->Line[$i]->Quantity)), 2, '.', ','); ?></td>
							<?php
						}
					} else {
						?>
						<td align="right">-</td>
						<?php
					}
					?>
					<td align="right">&pound;<?php echo number_format($itemTotal, 2, '.', ','); ?></td>
				</tr>
			<?php
			}

			 if($order->FreeTextValue != 0) {
			?>
			<tr>
				<td>&nbsp;</td>
				<td colspan="5">
					<?php echo $order->FreeText; ?>&nbsp;
				</td>
				<td align="right">
					&pound;<?php echo number_format($order->FreeTextValue, 2); ?>
				</td>
			</tr>
			<?php
			 }
			?>
				<tr>
					<td colspan="6">&nbsp;</td>
					<td align="right">Sub Total:</td>
					<td align="right">&pound;<?php echo number_format($subTotal, 2, '.', ','); ?></td>
				</tr>
			</table>
			<br />
			<table border="0" width="100%" cellpadding="0" cellspacing="0">
				<tr>
				  <td width="150" valign="top">
			      <p> Cart Weight: <?php echo $order->Weight; ?>Kg.<br />
			        <span class="smallGreyText">(Approx.)</span></p></td>
				  <td valign="top" align="right">
						<table border="0" cellpadding="5" cellspacing="0" class="catProducts">
							<tr>
								<th colspan="2">Tax &amp; Shipping</th>
							</tr>
							<tr>
							  <td>Delivery Option:</td>
								<td align="right">
									<?php echo $order->Postage->Name; ?>
								</td>
							</tr>
							<tr>
								<td>Shipping:</td>
								<td align="right">&pound;<?php echo ($order->TotalShipping == 0)?'FREE': number_format($order->TotalShipping, 2, ".", ","); ?></td>
							</tr>
				  			<?php
							if($order->Coupon->IsInvisible == 'Y') {
								?>

								<tr>
				                    <td style="color: #f00;">Discount Reward Used</td>
									<td style="color: #f00;" align="right">&pound;<?php echo number_format($order->DiscountReward, 2, '.', ','); ?></td>
								</tr>

								<?php
							}

							if(($order->Coupon->IsInvisible == 'N') || !empty($order->TotalDiscount)) {
							?>
								<tr>
									<td>
										Discount:
									<?php
									if(!empty($order->Coupon->ID)){
										if($order->Coupon->IsInvisible == 'Y') {
											if(!empty($order->OriginalCoupon->ID)){
												$order->OriginalCoupon->Get();
												echo sprintf('<br /><span class="smallGreyText">%s (%s)</span>', $order->OriginalCoupon->Name, $order->OriginalCoupon->Reference);
											}
										} else {
											$order->Coupon->Get();
											echo sprintf('<br /><span class="smallGreyText">%s (%s)</span>', $order->Coupon->Name, $order->Coupon->Reference);
										}
									}
									?>

									</td>
									<td align="right">-&pound;<?php echo number_format($order->TotalDiscount, 2, ".", ","); ?></td>
								</tr>
							<?php
							  } elseif(($order->Coupon->IsInvisible == 'Y') && ($order->OriginalCoupon->ID > 0)) {
							?>
								<tr>
									<td>
										Discount:
									<?php
									if(!empty($order->Coupon->ID)){
										if($order->Coupon->IsInvisible == 'Y') {
											$order->OriginalCoupon->Get();
											echo sprintf('<br /><span class="smallGreyText">%s (%s)</span>', $order->OriginalCoupon->Name, $order->OriginalCoupon->Reference);
										} else {
											$order->Coupon->Get();
											echo sprintf('<br /><span class="smallGreyText">%s (%s)</span>', $order->Coupon->Name, $order->Coupon->Reference);
										}
									}
									?>

									</td>
									<td align="right">-&pound;<?php echo number_format((($order->SubTotal/100)*$order->OriginalCoupon->Discount), 2, ".", ","); ?></td>
								</tr>
							<?php
				 			}
							?>
							<tr>
								<td>VAT:</td>
								<td align="right">&pound;<?php echo number_format($order->TotalTax, 2, ".", ","); ?></td>
							</tr>
							<tr>
								<td>Total:</td>
								<td align="right">&pound;<?php echo number_format($order->Total, 2, ".", ","); ?></td>
							</tr>
						</table>
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


<!-- Google -->
<!-- Google Code for lead Conversion Page -->
<script type="text/javascript">
<!--
var google_conversion_id = 1070689084;
var google_conversion_language = "en_US";
var google_conversion_format = "1";
var google_conversion_color = "666666";
if (1) {
	var google_conversion_value = 1;
}
var google_conversion_label = "order";
//-->
</script>
<script src="https://www.googleadservices.com/pagead/conversion.js"></script>
<noscript>
	<img height="1" width="1" border="0" src="https://www.googleadservices.com/pagead/conversion/1070689084/?value=1&amp;label=order&amp;script=0" />
</noscript>

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

<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>