<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

$session->Secure();
$order = new Order();

if($order->Get(id_param('orderid'))) {
	if($order->Customer->ID == $session->Customer->ID)
	$order->GetLines();
	else {
		redirect('Location: orders.php');
	}
}
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Order Confirmation</span></div>
<div class="maincontent">
<div class="maincontent1">
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

</div>
</div>
<?php 
include("ui/footer.php");
require_once('../lib/common/appFooter.php');