<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Quote.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/QuoteLine.php');

$session->Secure();

if(id_param('quoteid')){
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM quote AS q INNER JOIN customer AS c ON c.Customer_ID=q.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) AND q.Quote_ID=%d", mysql_real_escape_string($session->Customer->Contact->Parent->ID), mysql_real_escape_string($session->Customer->Contact->ID), mysql_real_escape_string(id_param('quoteid'))));
	if($data->Row['Counter'] == 0) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
	$data->Disconnect();

	$quote = new Quote(id_param('quoteid'));
	$quote->GetLines();

    if($action == "change") {
        $cart = new Cart($session);
        $cart->GenerateFromQuote($quote);

        redirect("Location: cart.php");
    } elseif($action == "order"){
        $quote = new Quote(id_param('quoteid'));
        $cart = new Cart($session);
        $cart->GenerateFromQuote($quote);
        redirect("Location: summary.php");
    }
} else {
    redirect('Location: quotes.php');
}
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">My Quotes</span></div>
<div class="maincontent">
<div class="maincontent1">
		<div id="orderConfirmation">
			<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a><?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a></p>
		</div>
<?php
		if(id_param('quoteid')){
			$quote = new Quote(id_param('quoteid'));
			$quote->GetLines();
			$quote->Postage->Get();

			$notes = new DataQuery(sprintf("select Quote_Note_ID from quote_note where Quote_ID=%d", mysql_real_escape_string($quote->ID)));
			$numNotes = $notes->TotalRows;
			$notes->Disconnect();
?>

<p><a href="quotes.php">&laquo; Back to My Quotes</a> | <a href="quoteNotes.php?qid=<?php echo $quote->ID; ?>">Quote Notes (<?php echo $numNotes; ?>)</a> </p>

		<table width="95%"  border="0" cellspacing="0" cellpadding="0">
		  <tr>
			<td valign="top">
				<table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses"  width="95%">
					<tr>
					  <td valign="top" class="billing" width="50%"><p><strong>Organisation/Individual:</strong><br />
							  <?php echo $quote->Billing->GetFullName();  ?> <br />
							  <?php echo $quote->Billing->Address->GetFormatted('<br />');  ?></p></td>
					  <td valign="top" class="shipping" width="50%"><p><strong>Shipping Address:</strong><br />
							  <?php echo $quote->Shipping->GetFullName();  ?> <br />
							  <?php echo $quote->Shipping->Address->GetFormatted('<br />');  ?></p></td>
                              </tr><tr>
					  <td valign="top" class="shipping"><p><strong>Invoice Address:</strong><br />
							  <?php echo $quote->Invoice->GetFullName();  ?> <br />
							  <?php echo $quote->Invoice->Address->GetFormatted('<br />');  ?></p></td>
					</tr>
				</table>
			</td>
            </tr>
            <tr>
			<td valign="top"><table cellpadding="0" cellspacing="0" border="0" class="invoicePaymentDetails">
				<tr>
				  <th valign="top"> Quote Ref: </th>
				  <td valign="top"><strong><?php echo $quote->Prefix . $quote->ID; ?></strong></td>
				</tr>
				<?php if(!empty($quote->CustomID)){ ?>
				<tr>
				  <th valign="top"> Your Ref: </th>
				  <td valign="top"><strong><?php echo $quote->CustomID; ?></strong></td>
				</tr>
				<?php } ?>
				<tr>
				  <th valign="top">Quote Date:</th>
				  <td valign="top"><?php echo cDatetime($quote->QuotedOn, 'longdate'); ?></td>
				</tr>
				<tr>
				  <th valign="top">Status: </th>
				  <td valign="top"><?php echo $quote->Status; ?></td>
				</tr>
				<tr>
				  <th valign="top">&nbsp;</th>
				  <td valign="top">&nbsp;</td>
				</tr>
			</table></td>
		  </tr>
		</table>
	  <br />
		<table cellspacing="0" class="catProducts">
		<tr>
			<th>Qty</th>
			<th>Product</th>
			<th>Status</th>
			<th>Quickfind</th>
			<th>Price</th>
			<th>Line Total</th>
		</tr>
	<?php
		for($i=0; $i < count($quote->Line); $i++){
	?>
		<tr>
			<td><?php echo $quote->Line[$i]->Quantity; ?>x</td>
			<td><a href="product.php?pid=<?php echo $quote->Line[$i]->Product->PublicID();?>"><?php echo $quote->Line[$i]->Product->Name; ?></a></td>
			<?php
				if(strtolower($quote->Line[$i]->Status) == 'cancelled'){
			?>
			<td colspan="2" align="center">Cancelled</td>
			<?php
				} else {
			?>
			<td><?php echo $quote->Status; ?></td>
<?php } ?>
			<td><a href="product.php?pid=<?php echo $quote->Line[$i]->Product->PublicID();?>"><?php echo $quote->Line[$i]->Product->PublicID(); ?></a></td>
			<td align="right">&pound;<?php echo number_format($quote->Line[$i]->Price, 2, '.', ','); ?></td>
			<td align="right">&pound;<?php echo number_format($quote->Line[$i]->Total, 2, '.', ','); ?></td>
		</tr>
	<?php
		}
	?>
		<tr>
			<td colspan="5" align="right">Sub Total:</td>
			<td align="right">&pound;<?php echo number_format($quote->SubTotal, 2, '.', ','); ?></td>
		</tr>
	</table>
	<br />
	<table border="0" width="100%" cellpadding="0" cellspacing="0">
		<tr>
		  <td width="150" valign="top">
		  <p> Cart Weight: <?php echo $quote->Weight; ?>Kg.<br />
			<span class="smallGreyText">(Approx.)</span></p></td>
		  <td valign="top" align="right">
				<table border="0" cellpadding="5" cellspacing="0" class="catProducts">
					<tr>
						<th colspan="2">Tax &amp; Shipping</th>
					</tr>
					<tr>
					  <td>Delivery Option:</td>
						<td align="right">
							<?php echo $quote->Postage->Name; ?>
						</td>
					</tr>
					<tr>
						<td>Shipping:</td>
						<td align="right">&pound;<?php echo ($quote->TotalShipping == 0)?'FREE': number_format($quote->TotalShipping, 2, ".", ","); ?></td>
					</tr>
		  <?php
						if(!empty($quote->TotalDiscount)) {
					?>
						<tr>
							<td>
								Discount:
							<?php
								if(!empty($quote->Coupon->ID)){
									$quote->Coupon->Get();
									echo sprintf('<br /><span class="smallGreyText">%s (%s)</span>', $quote->Coupon->Name, $quote->Coupon->Reference);
								}
							?>
							</td>
							<td align="right">-&pound;<?php echo number_format($quote->TotalDiscount, 2, ".", ","); ?></td>
						</tr>
					<?php
						}
					?>
					<tr>
						<td>VAT:</td>
						<td align="right">&pound;<?php echo number_format($quote->TotalTax, 2, ".", ","); ?></td>
					</tr>
					<tr>
						<td>Total:</td>
						<td align="right">&pound;<?php echo number_format($quote->Total, 2, ".", ","); ?></td>
					</tr>
				</table>
		  </td>
		</tr>
	</table>
	<form method="post" action="<?php $_SERVER['PHP_SELF'] ?>">
		<input type="hidden" name="quoteid" value="<?php echo $quote->ID ?>" />
        <?php if(strtolower($quote->Status) == 'pending') { ?>
		<input type="submit" class="submit" name="action" value="change" \ />
		<input type="submit" class="submit" name="action" value="order" \ />
        <?php } ?>
	</form>
	<?php
		} else {
			echo "Sorry, No Quotation reference was received.";
		}
	?>
	</div>
    </div>
    <?php include("ui/footer.php");
include('../lib/common/appFooter.php'); ?>