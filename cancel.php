<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

$session->Secure();

if(param('action') && id_param('line')){
	$line = new OrderLine(id_param('line'));
	$line->Status = 'Cancelled';
	$line->Update();
}

if(id_param('orderid')){
	$order = new Order(id_param('orderid'));
    if($order->Customer->ID == $session->Customer->ID)
        $order->GetLines();
    else {
        redirect('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

if(strtolower(param('action')) == 'cancel') {
	if(strtolower($order->Status) == 'partially despatched'){
		for($i=0; $i < count($order->Line); $i++){
			if(empty($order->Line[$i]->DespatchID) && empty($order->Line[$i]->InvoiceID)){
				$order->Line[$i]->Status = 'Cancelled';
				$order->Line[$i]->Update();
			}
		}

		$order->Status = 'Despatched';
		$order->Update();
	} else {
		for($i=0; $i < count($order->Line); $i++){
			$order->Line[$i]->Status = 'Cancelled';
			$order->Line[$i]->Update();
		}

		$order->Status = 'Cancelled';
		$order->Update();
	}
} elseif(param('action') && id_param('line')){
	$validLines = 0;

	if((strtolower($order->Status) != 'partially despatched') && (strtolower($order->Status) != 'despatched')) {
		for($i=0; $i < count($order->Line); $i++){
			if($order->Line[$i]->Status != 'Cancelled') {
				$validLines++;
			}
		}

		if($validLines == 0) {
			$order->Status = 'Cancelled';
			$order->Update();
		}

		$order->Recalculate();
	}

	redirect("Location: cancel.php?orderid=". $order->ID);
}

$isEditable = false;

if((strtolower($order->Status) != 'despatched') && (strtolower($order->Status) != 'partially despatched') && (strtolower($order->Status) != 'cancelled')){
	$isEditable = true;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Cancellations</title>
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
			<h1>Cancellations</h1>

			<?php
				if(id_param('orderid')){
					$order->Postage->Get();

					$notes = new DataQuery(sprintf("select Order_Note_ID from order_note where Order_ID=%d", mysql_real_escape_string($order->ID)));
					$numNotes = $notes->TotalRows;
					$notes->Disconnect();
			?>
				<p><a href="cancel.php">&laquo; Back to Cancellations</a> | <a href="/cancelNotes.php?oid=<?php echo $order->ID; ?>">Order Notes (<?php echo $numNotes; ?>)</a> </p>
				<table width="100%"  border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td valign="top">
						<table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
							<tr>
							  <td valign="top" class="billing"><p><strong>Organisation/Individual:</strong><br />
									  <?php echo $order->Billing->GetFullName();  ?> <br />
									  <?php echo $order->Billing->Address->GetFormatted('<br />');  ?></p></td>
							  <td valign="top" class="shipping"><p><strong>Shipping Address:</strong><br />
									  <?php echo $order->Shipping->GetFullName();  ?> <br />
									  <?php echo $order->Shipping->Address->GetFormatted('<br />');  ?></p></td>
							  <td valign="top" class="shipping"><p><strong>Invoice Address:</strong><br />
									  <?php echo $order->Invoice->GetFullName();  ?> <br />
									  <?php echo $order->Invoice->Address->GetFormatted('<br />');  ?></p></td>
							</tr>
						</table>
					</td>
                    <td align="right" valign="top"><table cellpadding="0" cellspacing="0" border="0" class="invoicePaymentDetails">
                        <tr>
                          <th valign="top"> Order Ref: </th>
                          <td valign="top"><strong><?php echo $order->Prefix . $order->ID; ?></strong></td>
                        </tr>
						<?php if(!empty($order->CustomID)){ ?>
						<tr>
                          <th valign="top"> Your Ref: </th>
                          <td valign="top"><strong><?php echo $order->CustomID; ?></strong></td>
                        </tr>
						<?php } ?>
                        <tr>
                          <th valign="top">Order Date:</th>
                          <td valign="top"><?php echo cDatetime($order->OrderedOn, 'longdate'); ?></td>
                        </tr>
						<tr>
                          <th valign="top">Status: </th>
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

                <p>Cancel your entire order or select individual lines to cancel by clicking the <img src="images/icon_trash_1.gif" alt="Cancel product line" border="0" /> icon.</p>

                <?php
				  if((strtolower($order->Status) != 'despatched') && (strtolower($order->Status) != 'cancelled')){
				 ?>
                <input name="Cancel Order" class="submit" type="button" id="Cancel Order" value="Cancel Entire Order" class="btn" onclick="confirmRequest('cancel.php?orderid=<?php echo $order->ID; ?>&action=cancel', 'Are you sure you wish to cancel this order?');" /><br />
                <?php
				  }
				  ?>

		      <br />
		        <table cellspacing="0" class="catProducts">
				<tr>
					<th>Qty</th>
					<th>Product</th>
					<th>Despatched</th>
					<th>Invoice</th>
					<th>Quickfind</th>
					<th>Price</th>
					<th>Line Total</th>
				</tr>
			<?php
				for($i=0; $i < count($order->Line); $i++){
			?>
				<tr>
					<td>
						<?php
						if($isEditable && ($order->Line[$i]->Status != 'Cancelled')) {
							?>
							<a href="cancel.php?orderid=<?php echo $order->ID; ?>&amp;action=remove&amp;line=<?php echo $order->Line[$i]->ID; ?>"><img src="images/icon_trash_1.gif" alt="Cancel" border="0" /></a>
							<?php
						} else {
							echo '<img style="visibility: hidden;" src="images/icon_trash_1.gif" alt="" border="0" />';
						}
						?>
						<?php echo $order->Line[$i]->Quantity; ?>x</td>
					<td><?php echo $order->Line[$i]->Product->Name; ?></td>
					<?php
						if(strtolower($order->Line[$i]->Status) == 'cancelled'){
					?>
					<td colspan="2" class="center">Cancelled</td>
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
					<td><?php echo $order->Line[$i]->Product->ID; ?></td>
					<td align="right">&pound;<?php echo number_format($order->Line[$i]->Price, 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($order->Line[$i]->Total, 2, '.', ','); ?></td>
				</tr>
			<?php
				}
			?>
			 <?php
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
				<?php
				if($isEditable) {
					?>
					<td colspan="5"><img src="ignition/images/icon_trash_1.gif" align="absmiddle" alt="Cancels a product" border="0" /> = cancel product line</td>
					<?php
				} else {
					?>
					<td colspan="5">&nbsp;</td>
					<?php
				}
				?>
					<td align="right">Sub Total:</td>
					<td align="right">&pound;<?php echo number_format($order->SubTotal, 2, '.', ','); ?></td>
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
								if(!empty($order->TotalDiscount)) {
							?>
								<tr>
									<td>
										Discount:
									<?php
										if(!empty($order->Coupon->ID)){
											$order->Coupon->Get();
											echo sprintf('<br /><span class="smallGreyText">%s (%s)</span>', $order->Coupon->Name, $order->Coupon->Reference);
										}
									?>
									</td>
									<td align="right">-&pound;<?php echo number_format($order->TotalDiscount, 2, ".", ","); ?></td>
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

		<?php } else { ?>

			<p>Below is a list of your orders available for cancellation. Your most recent orders are displayed first.</p>
			<table cellspacing="0" class="myAccountOrderHistory">
				<tr>
				 	<th>Order Date</th>
					<th>Order Number</th>
					<th>Order Total</th>
					<th>Status</th>
				</tr>

			<?php
				$data = new DataQuery(sprintf("select * from orders where Customer_ID=%d AND Status<>'Despatched' AND Status<>'Partially Despatched' AND Status<>'Cancelled' AND Status NOT IN ('Incomplete', 'Unauthenticated') order by Ordered_On DESC", mysql_real_escape_string($session->Customer->ID)));
				if($data->TotalRows == 0) {
					?>

					 <tr>
					 	<td colspan="4" class="center">There are no orders available for cancellation.</td>

				  </tr>

				  <?php

				} else {
					while($data->Row){
				?>
				 <tr>
					 	<td><a href="cancel.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo cDatetime($data->Row['Ordered_On'], 'longdate'); ?></a></td>
						<td><a href="cancel.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo $data->Row['Order_Prefix'] . $data->Row['Order_ID']; ?></a></td>
						<td>&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
						<td><?php echo ucfirst($data->Row['Status']); ?></td>
				  </tr>
				<?php
						$data->Next();
					}
				}
				$data->Disconnect();

				echo "</table>";
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
<?php include('lib/common/appFooter.php'); ?>
