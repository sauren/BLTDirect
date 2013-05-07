<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleRequest.php');

$session->Secure();
$order = new Order();

if(id_param('orderid') && $order->Get(id_param('orderid'))) {
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) AND o.Is_Sample='N' AND o.Order_ID=%d", mysql_real_escape_string($session->Customer->Contact->Parent->ID), mysql_real_escape_string($session->Customer->Contact->ID), mysql_real_escape_string(id_param('orderid'))));

	if($data->Row['Counter'] == 0) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
	$data->Disconnect();

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
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">My Orders</span></div>
<div class="maincontent">
<div class="maincontent1">
			<div id="orderConfirmation">
						<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a> <?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a> | <a href="?action=logout">Logout</a></p>			</div>

			<?php
			if(id_param('orderid')) {
				$order->Postage->Get();
				$notes = new DataQuery(sprintf("select Order_Note_ID from order_note where Order_ID=%d AND Is_Public='Y'", $order->ID));
				$numNotes = $notes->TotalRows;
				$notes->Disconnect();
			?>
				<p><a href="/orders.php">&laquo; Back to My Orders</a> | <a href="/orderNotes.php?oid=<?php echo $order->ID; ?>">Order Notes (<?php echo $numNotes; ?>)</a> </p>


<?php if($order->Status=='Incomplete' || $order->Status == 'Unauthenticated') {?>
<div class="statusMessage">
	<h1>This Order is <?php echo $order->Status; ?></h1>
	<br />
	<p>IMPORTANT: This order reached the payment stage during checkout, but did not successfully received payment details.</p>
	<p>If you would like to continue with this order please add your card details by clicking the button below.</p>
	
	<br />
	<input type="button" name="card" value="Add Card Details"  class="submit" onclick="window.location.href='paymentServer.php?action=change&amp;orderid=<?php echo $order->ID; ?>'" />
</div>
<br />
<?php } ?>



				<table width="100%"  border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td valign="top">
						<table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
							<tr>
							  <td valign="top" class="billing"><p> <strong>Organisation/Individual:</strong><br />
									  <?php echo $order->GetBillingAddress();  ?></p></td>
                              </tr><tr>
							  <td valign="top" class="shipping"><p> <strong>Shipping Address:</strong><br />
									  <?php echo $order->GetShippingAddress();  ?></p></td>
                               </tr><tr>
							  <td valign="top" class="shipping"><p> <strong>Invoice Address:</strong><br />
									  <?php echo $order->GetInvoiceAddress();  ?></p></td>
							</tr>
						</table>
					</td></tr><tr>
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

                <?php
                if((strtolower($order->Status) != 'despatched') && (strtolower($order->Status) != 'cancelled')){
				 ?>
                <input name="Cancel Order" class="submit" type="button" id="Cancel Order" value="Cancel Entire Order" class="btn" onclick="confirmRequest('orders.php?orderid=<?php echo $order->ID; ?>&action=cancel', 'Are you sure you wish to cancel this order?');" />&nbsp;
                <?php
                }
				  ?>
				  <input name="Duplicate Order" class="submit" type="button" id="Duplicate Order" value="Duplicate Order" class="btn" onclick="window.location.href='orders.php?orderid=<?php echo $order->ID; ?>&action=duplicate';" /><br />

		      <br />

		        <table cellspacing="0" class="catProducts" width="100%">
					<tr>
						<th>&nbsp;</th>
						<th>Qty</th>
						<th>Product</th>
						<th>Despatched</th>
						<th>Courier</th>
						<th>Tracking Ref.</th>
						<th>Invoice</th>
						<th>Quickfind</th>
						<th>Price</th>
						<th>Your Price</th>
						<th>Line Total</th>

						<?php
						if($order->Backordered == 'Y') {
							?>
							<th>Backorder</th>
							<?php
						}
						?>

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
					<?php
					if(strtolower($order->Line[$i]->Status) == 'cancelled'){
					?>
					<td colspan="4" align="center">Cancelled</td>
					<?php
					} else {
					?>
					<td align="center"><?php if(!empty($order->Line[$i]->DespatchID)){
						echo '<a href="despatch_note.php?despatchid=' . $order->Line[$i]->DespatchID . '" target="_blank"><img src="/images/icon_tick_2.gif" border="0" alt="Click Here to view the despatch note." /></a>';
					}; ?>&nbsp;

					</td>
					<td>
						<?php
						if(!empty($order->Line[$i]->DespatchID)) {
							if($despatch->Courier->IsTrackingActive == 'Y') {
								echo sprintf('<a href="%s" target="_blank">%s</a>', $despatch->Courier->URL, $despatch->Courier->Name);
							} else {
								echo $despatch->Courier->Name;
							}
						}
						?>
						&nbsp;
					</td>
					<td>
						<?php
						if(!empty($order->Line[$i]->DespatchID)) {
							if($despatch->Courier->IsTrackingActive == 'Y') {
								echo empty($despatch->Consignment) ? '-' : $despatch->Consignment;
							} else {
								echo '-';
							}
						}
						?>
						&nbsp;
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

				<tr>
				<?php
				if($isEditable && ($order->PaymentMethod->Reference != 'google')) {
					?>
					<td colspan="9"><img src="../ignition/images/icon_trash_1.gif" align="absmiddle" alt="Cancels a product" border="0" /> = cancel product line</td>
					<?php
				} else {
					?>
					<td colspan="9">&nbsp;</td>
					<?php
				}
				?>
					<td align="right">Sub Total:</td>
					<td align="right">&pound;<?php echo number_format($subTotal, 2, '.', ','); ?></td>

					<?php
					if($order->Backordered == 'Y') {
						echo '<td>&nbsp;</td>';
					}
					?>

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
			
			<?php } else { ?>
			
			<p>Below is a list of your recent orders. Your most recent orders are displayed first.  If you wish to duplicate a previous order click the <img src="images/icon_pages_1.gif" alt="Duplicate order" border="0" /> icon next to the order date.</p>
			<table cellspacing="0" class="myAccountOrderHistory">
				<tr>
				 	<th><strong>Order Date</strong></th>
					<th><strong>Order Number</strong></th>
					<th><strong>Ordered By</strong></th>
					<th><strong>Order Total</strong></th>
					<th><strong>Status</strong></th>
					<th style="text-align: center;"><strong>Backordered</strong></th>
				</tr>

			<?php
			$contacts = array();

			if($session->Customer->Contact->HasParent) {
				$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact WHERE Parent_Contact_ID=%d", mysql_real_escape_string($session->Customer->Contact->Parent->ID)));
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
					 		<td><a href="orders.php?orderid=<?php echo $data->Row['Order_ID']; ?>&amp;action=duplicate"><img src="images/icon_pages_1.gif" alt="Duplicate this order" border="0"  /></a>&nbsp;<a href="orders.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo cDatetime($data->Row['Ordered_On'], 'longdate'); ?></a></td>
							<td><a href="orders.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo $data->Row['Order_Prefix'] . $data->Row['Order_ID']; ?></a></td>
							<td><?php echo $data->Row['Name']; ?></td>
							<td>&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
							<td><?php echo $status; ?></td>
							<td align="center"><?php echo ($data->Row['Backordered'] == 'Y') ? 'Yes' : 'No'; ?></td>
				    </tr>
					<?php
					$data->Next();
				}

					?>

					<tr>
					 	<td colspan="6"><img src="images/icon_pages_1.gif" alt="Duplicate this order" border="0"  /> = duplicate order</td>
				  </tr>

					<?php
			}
			$data->Disconnect();
			echo "</table>";
			}?>
</table>
</div>
</div>
<?php include("ui/footer.php");
include('../lib/common/appFooter.php'); ?>