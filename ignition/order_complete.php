<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

$session->Secure(2);

$o = base64_decode($_REQUEST['o']);
$orderNum = new Cipher($o);
$orderNum->Decrypt();

$order = new Order($orderNum->Value);
$order->GetLines();
$order->Customer->Get();
$order->Customer->Contact->Get();
$order->Customer->Contact->Person->Get();

$page = new Page('Create a New Order Manually', '');
$page->Display('header');

if($action == 'setprefix'){
	if(isset($_REQUEST['plainlabel']) && ($_REQUEST['plainlabel'] == 'Y')) {
		$order->IsPlainLabel = 'Y';
	}

	$order->CustomID = $_REQUEST['custom'];
	$order->Update();

	$note = new OrderNote;
	$note->Message = $_REQUEST['message'];
	$note->OrderID = $order->ID;
	$note->IsPublic = 'Y';

	if(!empty($note->Message)){
		$note->Add();
		$order->Customer->Get();
		$order->Customer->Contact->Get();
		$note->SendToAdmin($order->Customer->Contact->Person->GetFullName(), $order->Customer->GetEmail());
	}
	?>

	<strong>Order Complete</strong>
		<?php if($order->Sample == 'Y') { ?>
		<p>The order (Ref. <strong><?php echo $order->Prefix . $order->ID; ?></strong>) was successfully completed. An email confirming your order has been sent to you and a full history of your online orders is available through My Account on our website. </p>
		<?php } else { ?>
		<p>The order (Ref. <strong><?php echo $order->Prefix . $order->ID; ?></strong>) was successfully completed. The order will be subject to security checks before the credit card is charged. Inform the customer that when contacting us regarding the order please remember to quote their order reference number. An email confirming the order has been sent to the customer and a full history of their online orders is available through My Account on our website.</p>
		<?php } ?>


		<table width="100%"  border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
              <tr>
                <?php if($order->Sample == 'N') { ?>
                <td valign="top" class="billing"><p><strong>Organisation/Individual:</strong><br />
                        <?php echo $order->GetBillingAddress(); ?></p></td>
                <?php } ?>
                <td valign="top" class="shipping"><p><strong>Shipping Address:</strong><br />
                        <?php echo $order->GetShippingAddress(); ?></p></td>
                <?php if($order->Sample == 'N') { ?>
                <td valign="top" class="shipping"><p><strong>Invoice Address:</strong><br />
                        <?php echo $order->GetInvoiceAddress(); ?></p></td>
                <?php } ?>
              </tr>
            </table><br />

            <input type="button" class="btn" name="continue" value="continue" onclick="window.self.location.href = 'order_details.php?orderid=<?php echo $order->ID; ?>';" />

            </td>
            <td align="right" valign="top"><table cellpadding="0" cellspacing="0" border="0" class="invoicePaymentDetails">
              <tr>
                <th valign="top"> Order Ref: </th>
              <td valign="top"><strong><?php echo $order->Prefix . $order->ID; ?></strong></td>
              </tr>
			  <tr>
                <th valign="top"> Customer Ref: </th>
              <td valign="top"><strong><?php echo $order->CustomID; ?></strong></td>
              </tr>
              <tr>
                <th valign="top">Order Date:</th>
                <td valign="top"><?php echo cDatetime($order->OrderedOn, 'longdate'); ?></td>
              </tr>
              <tr>
                <th valign="top">Customer: </th>
                <td valign="top"><?php echo $order->Customer->Contact->Person->GetFullName(); ?></td>
              </tr>
               <?php if($order->Sample == 'N') { ?>
              <tr>
                <th valign="top">&nbsp;</th>
                <td valign="top">&nbsp;</td>
              </tr>
              <tr>
                <th valign="top">Payment Method:</th>
                <td valign="top"><?php echo $order->GetPaymentMethod(); ?>&nbsp;</td>
              </tr>
              <tr>
                <th valign="top">Card:</th>
                <td valign="top"><?php echo $order->Card->PrivateNumber(); ?>&nbsp;</td>
              </tr>
              <tr>
                <th valign="top">Expires: </th>
                <td valign="top"><?php echo $order->Card->Expires; ?>&nbsp;</td>
              </tr>
              <?php } ?>
            </table></td>
          </tr>
        </table><br />

		  <?php if($order->Sample == 'N') { ?>
		<table cellspacing="0" class="catProducts">
			<tr>
				<th>Qty</th>
				<th>Product</th>
				<th>Quickfind</th>
				<th>Price</th>
				<th>Line Total</th>
			</tr>
		<?php
			for($i=0; $i < count($order->Line); $i++){
		?>
			<tr>
				<td><?php echo $order->Line[$i]->Quantity; ?>x</td>
				<td><?php echo $order->Line[$i]->Product->Name; ?></td>
				<td><?php echo $order->Line[$i]->Product->ID; ?></td>
				<td align="right">&pound;<?php echo number_format($order->Line[$i]->Price, 2, '.', ','); ?></td>
				<td align="right">&pound;<?php echo number_format($order->Line[$i]->Total, 2, '.', ','); ?></td>
			</tr>
		<?php
			}
		?>
			<tr>
				<td colspan="4" align="right">Sub Total:</td>
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
								<?php
									$order->Postage->Get();
									echo $order->Postage->Name;
								?>
							</td>
						</tr>
						<tr>
							<td>Shipping:</td>
							<td align="right">&pound;<?php echo ($order->TotalShipping == 0)?'FREE': number_format($order->TotalShipping, 2, ".", ","); ?></td>
						</tr>
						<tr>
							<td>Discount:</td>
							<td align="right">-&pound;<?php echo number_format($order->TotalDiscount, 2, ".", ","); ?></td>
						</tr>
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

		<table cellspacing="0" class="catProducts">
			<tr>
				<th>Qty</th>
				<th>Product</th>
				<th>Quickfind</th>
			</tr>
		<?php
			for($i=0; $i < count($order->Line); $i++){
		?>
			<tr>
				<td><?php echo $order->Line[$i]->Quantity; ?>x</td>
				<td><?php echo $order->Line[$i]->Product->Name; ?></td>
				<td><?php echo $order->Line[$i]->Product->ID; ?></td>
			</tr>
		<?php
			}
		?>
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
							<th colspan="2">Shipping</th>
						</tr>
						<tr>
						  <td>Delivery Option:</td>
							<td align="right">
								<?php
									$order->Postage->Get();
									echo $order->Postage->Name;
								?>
							</td>
						</tr>
					</table>
			  </td>
			</tr>
		</table>

		<?php
	}
} else {
   ?>

	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<input type="hidden" name="action" value="setprefix" />
		<input type="hidden" name="o" value="<?php echo $_REQUEST['o']; ?>" />

		<p>
			<strong>Customer's Order Reference</strong><br />
			<input type="text" name="custom" value="" />
		</p>

		<p>
			<strong>Additional Information or Requirements</strong><br />
			<textarea name="message" style="width:90%; height:100px;"></textarea>
		</p>

		<p>
			<strong>Is Plain Label</strong><br />
			<input type="checkbox" name="plainlabel" value="Y" />
		</p>

		<input type="submit" class="btn" name="go" value="go" />
	</form>

<?php
}

$page->Display('footer');
require_once('lib/common/app_footer.php');