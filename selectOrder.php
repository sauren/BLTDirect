<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleRequest.php');

$session->Secure();

if(id_param('orderid')){
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) AND o.Is_Sample='N' AND o.Order_ID=%d", mysql_real_escape_string($session->Customer->Contact->Parent->ID), mysql_real_escape_string($session->Customer->Contact->ID), mysql_real_escape_string(id_param('orderid'))));
	if($data->Row['Counter'] == 0) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
	$data->Disconnect();

	$order = new Order(id_param('orderid'));
	$order->PaymentMethod->Get();
	$order->GetLines();
}

if(param('action') && id_param('line')){
	$line = new OrderLine();

	if($line->Get(id_param('line'))) {
		if($line->Order == $order->ID) {
			$order->CancelLines(array($line), 'Buyer cancelled items');
			$order->NotifyCancellation(array($line));
		}
	}

	redirect("Location: orders.php?orderid=". $order->ID);
}

if(param('action') == 'duplicate') {
	$cart->Reset();

	for($i = 0; $i < count($order->Line); $i++) {
		if($order->Line[$i]->Product->Get()) {
			if($order->Line[$i]->Product->Discontinued == 'N') {
				$cart->AddLine($order->Line[$i]->Product->ID, $order->Line[$i]->Quantity);
			}
		}
	}

	redirect("Location: cart.php");
}

if(param('action') == 'cancel') {
	$order->Cancel('Buyer cancelled the order.');
	$order->NotifyCancellation();

	redirect("Location: orders.php?orderid=". $order->ID);
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
	<title>My Returns</title>
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
			<h1>My Returns</h1>
			<br />

			<?php
			if(param('type')){
				$type = param('type');
				echo $type;
			}
			if(id_param('orderid')) {
				$order->Postage->Get();

				$notes = new DataQuery(sprintf("select Order_Note_ID from order_note where Order_ID=%d AND Is_Public='Y'", mysql_real_escape_string($order->ID)));
				$numNotes = $notes->TotalRows;
				$notes->Disconnect();
			?>
				<p><a href="/orders.php">&laquo; Back to My Orders</a> | <a href="/orderNotes.php?oid=<?php echo $order->ID; ?>">Order Notes (<?php echo $numNotes; ?>)</a> </p>
				<table width="100%"  border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td valign="top">
						<table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
							<tr>
							  <td valign="top" class="billing"><p> <strong>Organisation/Individual:</strong><br />
									  <?php echo $order->GetBillingAddress();  ?></p></td>
							  <td valign="top" class="shipping"><p> <strong>Shipping Address:</strong><br />
									  <?php echo $order->GetShippingAddress();  ?></p></td>
							  <td valign="top" class="shipping"><p> <strong>Invoice Address:</strong><br />
									  <?php echo $order->GetInvoiceAddress();  ?></p></td>
							</tr>
						</table>
					</td>
                    <td align="right" valign="top">
                    <table cellpadding="0" cellspacing="0" border="0" class="invoicePaymentDetails">
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
                        <tr class="paymentCard" <?php echo ($order->PaymentMethod->Reference == 'card') ? '' : 'style="display: none;"'; ?>>
							<th>Card</th>

							<?php
							if((strtolower($order->Status) != 'cancelled') && (strtolower($order->Status) != 'despatched') && (strtolower($order->Status) != 'partially despatched') && (strtolower($order->Status) != 'unauthenticated') && (strtolower($order->Status) != 'incomplete')) {
								?>
								<td><?php echo $order->Card->PrivateNumber(); ?> (<a href="paymentServer.php?orderid=<?php echo $order->ID; ?>&amp;action=change">Change</a>)</td>
								<?php
							} else {
                                ?>
								<td><?php echo $order->Card->PrivateNumber(); ?></td>
								<?php
							}
							?>

						</tr>
                    </table></td>
                  </tr>
                </table>
                <br />
                <table cellspacing="0" class="catProducts">
					<tr>
						<th>&nbsp;</th>
						<th>Qty</th>
						<th>Product</th>
						<th></th>
						<th>Quantity</th>
						<th>&nbsp;</th>
						<th>Your Price</th>
						<!--<th>Courier</th>
						<th>Tracking Ref.</th>-->
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

				if(!empty($order->Line[$i]->DespatchID)) {
					$despatch = new Despatch($order->Line[$i]->DespatchID);
					$despatch->Courier->Get();
				}
				?>

				<tr>
					<td>
						<?php
						if($isEditable && ($order->Line[$i]->Status != 'Cancelled') && ($order->PaymentMethod->Reference != 'google')) {
							?>
							<a href="orders.php?orderid=<?php echo $order->ID; ?>&amp;action=remove&amp;line=<?php echo $order->Line[$i]->ID; ?>"><img src="images/icon_trash_1.gif" alt="Cancel" border="0" /></a>
							<?php
						} elseif($isEditable) {
							echo '<img style="visibility: hidden;" src="images/icon_trash_1.gif" alt="" border="0"  />';
						}
						?>
					</td>
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
					<td><input type="checkbox" name="return" /></input></td>
					<td>
					<select onfocus="set_quantity(this.value);" name="quantity">
							<option selected="selected" value="1">1</option>
					  </select></td><td/>&nbsp;</td>
					<td align="right">&pound;<?php echo number_format($itemTotal, 2, '.', ','); ?></td>

					<?php
					if($order->Backordered == 'Y') {
						if(stristr($order->Line[$i]->Status, 'Backordered')) {
							?>
							<td>Expected:<br /><?php print ($order->Line[$i]->BackorderExpectedOn > '0000-00-00 00:00:00') ? cDatetime($order->Line[$i]->BackorderExpectedOn, 'shortdate') : 'Unknown'; ?></td>
							<?php
						} else {
							echo '<td>&nbsp;</td>';
						}
					}
					?>
				</tr>
			<?php
			}

			 if($order->PaymentMethod->Reference != 'google') {
			 	if($order->FreeTextValue != 0) {
			?>
			<tr>
				<td>&nbsp;</td>
				<td colspan="<?php echo ($order->Backordered == 'Y') ? 8 : 7; ?>">
					<?php echo $order->FreeText; ?>&nbsp;
				</td>
				<td align="right">
					&pound;<?php echo number_format($order->FreeTextValue, 2); ?>
				</td>
			</tr>
			<?php
			 	}
			 }
			?>
			</table>
                <br />

                <?php
                if((strtolower($order->Status) != 'despatched') && (strtolower($order->Status) != 'cancelled')){
				 ?>
                <input name="Cancel Order" class="submit" type="button" id="Cancel Order" value="Cancel Entire Order" class="btn" onclick="confirmRequest('orders.php?orderid=<?php echo $order->ID; ?>&action=cancel', 'Are you sure you wish to cancel this order?');" />&nbsp;
                <?php
                }
				  ?>
				 <?php  // Put in the right code for Returning the whole order. ?>
				  <input name="Return Order" class="submit" type="button" id="Return Order" value="Return Order" class="btn" onclick="window.location.href='orders.php?orderid=<?php echo $order->ID; ?>&action=duplicate';" />
				   <input name="Return selected" class="submit" type="button" id="Return Selected" value="Return Selected" class="btn" onclick="window.location.href='orders.php?orderid=<?php echo $order->ID; ?>&action=duplicate';" />
		      <br />

		     <br />
			
			<?php } else { ?>
			
			<p>Please select the order that you would like to request a return for.</p>
			<table cellspacing="0" class="myAccountOrderHistory">
				<tr>
				 	<th>Order Date</th>
					<th>Order Number</th>
					<th>Order Total</th>
				</tr>

			<?php
			$contacts = array();

			if($session->Customer->Contact->HasParent) {
				$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact WHERE Parent_Contact_ID=%d", $session->Customer->Contact->Parent->ID));
				while($data->Row) {
					$contacts[] = $data->Row['Contact_ID'];
					
					$data->Next();	
				}
				$data->Disconnect();
			} else {
				$contacts[] = $session->Customer->Contact->ID;
			}

			$data = new DataQuery(sprintf("SELECT o.*, CONCAT_WS(' ', p2.Name_First, p2.Name_Last) AS Name FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE c.Contact_ID IN (%s) AND o.Is_Sample='N' AND o.Status<>'Unauthenticated' ORDER BY o.Order_ID DESC", implode(', ', $contacts)));
			if($data->TotalRows == 0) {
					?>

					<tr>
						<td colspan="6" align="center">There are no orders available for viewing.</td>
				  </tr>

				  <?php
			} else {
				while($data->Row) {
					$status = ucfirst($data->Row['Status']);
					
					if($data->Row['Is_Declined'] == 'Y') {
						$status = '<strong style="color: #c60909;">Payment Error</strong>';	
					}
					?>
					 <tr>
					 		<td><a href="selectOrder.php?orderid=<?php echo $data->Row['Order_ID']; ?>&amp;action=duplicate"><img src="images/icon_pages_1.gif" alt="Duplicate this order" border="0"  /></a>&nbsp;<a href="selectOrder.php?orderid=<?php echo $data->Row['Order_ID']; ?>&amp;type=<?php echo $type; ?>"><?php echo cDatetime($data->Row['Ordered_On'], 'longdate'); ?></a></td>
							<td><a href="selectOrder.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo $data->Row['Order_Prefix'] . $data->Row['Order_ID']; ?></a></td>
							<td>&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
				    </tr>
					<?php
					$data->Next();
				}

					?>

					<?php
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
