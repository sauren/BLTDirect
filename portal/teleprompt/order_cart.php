<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Postage.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TelePrompt.php');

$session->Secure(2);

$minimumMarkup = Setting::GetValue('minimum_markup_percent');
$minimumMarkupThreshold = Setting::GetValue('minimum_markup_alternative_threshold');
$minimumMarkupAlternative = Setting::GetValue('minimum_markup_alternative_percent');

$cart = new Cart($session, true);
$cart->GetLines();
$cart->Calculate();

if($action == 'remove') {
   if(isset($_REQUEST['line']) && is_numeric($_REQUEST['line'])) {
		$line = new CartLine;
		$line->Remove($_REQUEST['line']);

		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

} elseif($action == 'removecoupon') {
	$cart->Coupon->ID = 0;
	$cart->Update();

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));

} elseif($action == 'continue') {
	redirect("Location: order_checkout.php");
}

if(isset($_REQUEST['changePostage']) && is_numeric($_REQUEST['changePostage']) && $_REQUEST['changePostage'] > 0){
	$cart->Postage = $_REQUEST['changePostage'];
	$cart->Update();

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

if(isset($_REQUEST['shipping'])){
	if($_REQUEST['shipping'] == 'standard'){
		$cart->IsCustomShipping = 'N';
		$cart->Update();
		$cart->Calculate();
	}
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('coupon', 'Coupon Code', 'text', '', 'alpha_numeric', 1, 15, false);
$form->AddField('freeText', 'Free Text', 'text', $cart->FreeText, 'paragraph', 0, 255, false, 'style="width:100%;"');
$form->AddField('freeTextValue', 'Free Text Value', 'text', (isset($cart->FreeTextValue) ? $cart->FreeTextValue : '0.00'), 'float', 0, 11, false, 'size="10"');

for($i=0; $i < count($cart->Line); $i++){
   $form->AddField('qty_' . $cart->Line[$i]->ID, 'Quantity of ' . $cart->Line[$i]->Product->Name, 'text', $cart->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="3"');
}

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
	if($form->Valid) {
		$quantitiesUpdated = false;
		for($i=0; $i < count($cart->Line); $i++){
			if(is_numeric($form->GetValue('qty_' . $cart->Line[$i]->ID)) && ($cart->Line[$i]->Quantity != $form->GetValue('qty_' . $cart->Line[$i]->ID)) && $form->GetValue('qty_' . $cart->Line[$i]->ID) > 0) {
				 $cart->Line[$i]->Quantity = $form->GetValue('qty_' . $cart->Line[$i]->ID);
				 $quantitiesUpdated = true;
			}

			$cart->Line[$i]->Update();
		}

		$tmpCoupon = $form->GetValue('coupon');
		if(!empty($tmpCoupon)){
			if($quantitiesUpdated){
				$cart->Reset();
			}
			$coupon = new Coupon;
			if($coupon->Check($form->GetValue('coupon'), $cart->SubTotal, $cart->Customer->ID)){
				$cart->Coupon->ID = $coupon->ID;
			} else {
				foreach($coupon->Errors as $key=>$value){
					$form->AddError($value, 'coupon');
				}
			}
		}

		$cart->FreeText = $form->GetValue('freeText');
		$cart->FreeTextValue = $form->GetValue('freeTextValue');
		$cart->Calculate();
		$cart->Update();

		redirect("Location: order_cart.php");
	}
}

$page = new Page('Create New Order Manually', '');
$page->Display('header');
?>

<script language="javascript" type="text/javascript">
		function changeDelivery(id){
			var url="<?php echo $_SERVER['PHP_SELF']; ?>?changePostage=" + id;
			window.location.href = url;
		}
        function confirmRemove(id){
            if(confirm('Are you sure you would like to remove this product from your cart?')) {
                window.location.href = 'order_cart.php?action=remove&line=' + id;
            }
        }
</script>
<table width="100%" border="0">
  <tr>
    <td width="250" valign="top"><?php include('order_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top">
    
    <?php
    $prompt = new TelePrompt();
	$prompt->Output('orderviewcart');
	
	echo $prompt->Body;
	?>
	
	<strong>Shopping Cart</strong>

	<p>Click the Checkout button to continue with your order.</p>
    <?php
    if(!$form->Valid){
        echo $form->GetError();
        echo "<br>";
    }

    echo $form->Open();
    echo $form->GetHtml('confirm');

    if(count($cart->Line) > 0){
        if(!empty($cart->Coupon->ID)){
            $cart->Coupon->Get();
            echo '<table cellspacing="0" class="cartCoupon"><tr><td><img src="./images/discount_1.gif" border="0" />';
            echo '</td><td><strong>';
            echo $cart->Coupon->Name . '</strong> (Ref: ' . strtoupper($cart->Coupon->Reference) .' )<br />';
            echo $cart->Coupon->Description . '<br />';
            echo sprintf('<span class="smallGreyText">Only one coupon may be added per order.
            You may use this coupon %d times till expiry. ', $cart->Coupon->UsageLimit);
            echo $cart->Coupon->GetExpiryString();
            echo '</span>';
            echo '<br /><br /><a href="order_cart.php?action=removecoupon">Click Here to remove this coupon from your order<a/>';
            echo '</td></tr></table>';
        } else {
            echo $form->GetLabel('coupon') . '<br />';
            echo $form->GetHtml('coupon');
            echo '<span class="smallGreyText">Click \'Update\' to continue.</span>';
        }
    }
    ?>
	<br />

    <table cellspacing="0" class="catProducts">
        <tr>
            <th>&nbsp;</th>
            <th>Qty</th>
            <th>Product</th>
			<th style="text-align: right;">Price</th>
			<th style="text-align: right;">Discount</th>
			<th style="text-align: right;">Your Price</th>
			<th style="text-align: right;">Line Total</th>
        </tr>
    <?php
        for($i=0; $i < count($cart->Line); $i++){
    ?>
		<tr <?php print ($cart->Line[$i]->Product->IsDemo == 'Y') ? 'style="background-image: url(images/demo.jpg);"' : ''; ?>>
            <td><a href="javascript:confirmRemove(<?php echo $cart->Line[$i]->ID; ?>);">  <img src="images/icon_trash_1.gif" alt="Remove <?php echo $cart->Line[$i]->Product->Name; ?>" width="16" height="16" border="0" /></a></td>
            <td>
            <?php
            echo $form->GetHTML('qty_' . $cart->Line[$i]->ID);
            ?>
            </td>
            <td>
                <a href="./order_product.php?pid=<?php echo $cart->Line[$i]->Product->ID;?>" title="Click to View <?php echo $cart->Line[$i]->Product->Name; ?>"><strong><?php echo $cart->Line[$i]->Product->Name; ?></strong></a><br />
                <span class="smallGreyText"><?php
                    echo "Quickfind Code: " . $cart->Line[$i]->Product->ID;
                ?> </span>

				<?php
				if(!empty($cart->Line[$i]->Discount) && ($cart->Line[$i]->FreeOfCharge == 'N')){
					$discountVal = explode(':', $cart->Line[$i]->DiscountInformation);
					if(trim($discountVal[0]) == 'azxcustom') {
						$showDiscount = 'Custom Discount';
					} else {
						$showDiscount = $cart->Line[$i]->DiscountInformation;
					}
					if(!empty($showDiscount)) {
						echo sprintf("<br />(%s - &pound;%s)",$showDiscount, number_format($cart->Line[$i]->Discount, 2, '.',','));
					} else {
						echo sprintf("<br />(&pound;%s)",number_format($cart->Line[$i]->Discount, 2, '.',','));
					}
				}
				?>
			</td>
            <td align="right">&pound;<?php echo number_format($cart->Line[$i]->Price, 2, '.', ','); ?></td>
            <td align="right">&pound;<?php echo number_format($cart->Line[$i]->Discount / $cart->Line[$i]->Quantity, 2, '.', ','); ?></td>
            <td align="right">&pound;<?php echo number_format($cart->Line[$i]->Price - ($cart->Line[$i]->Discount / $cart->Line[$i]->Quantity), 2, '.', ','); ?></td>
            <td align="right">&pound;<?php echo number_format($cart->Line[$i]->Total - $cart->Line[$i]->Discount, 2, '.', ','); ?></td>
        </tr>
    <?php
        }

        if(count($cart->Line) == 0){
    ?>
        <tr>
			<td colspan="7" align="center">Your Shopping Cart is Empty</td>
        </tr>
	<?php
		} else {
	?>
	<tr>
		<td colspan="2" nowrap="nowrap">
			Free text:
		</td>
		<td colspan="4">
			<?php echo $form->GetHTML('freeText'); ?>
		</td>
		<td align="right" nowrap="nowrap">
			<?php echo '&pound; '.$form->GetHTML('freeTextValue'); ?>
		</td>
	</tr>

    <?php
        }
    ?>
        <tr>
            <td colspan="5"><img src="images/icon_trash_1.gif" width="16" height="16" border="0" align="absmiddle" /> = Remove</td>
            <td align="right">Sub Total: </td>
            <td align="right">&pound;<?php echo number_format($cart->SubTotal, 2, '.', ','); ?></td>
        </tr>
    </table>
        <br />
			<table border="0" width="100%" cellpadding="0" cellspacing="0">
				<tr>
				  <td width="150" valign="top">
				  <?php if($cart->TotalLines > 0){ ?>
				  <p><input name="action" type="submit" class="btn" value="update" /></p>
				  <?php } ?>
			      <p> Cart Weight: <?php echo $cart->Weight; ?>Kg.<br />
			        <span class="smallGreyText">(Approx.)</span></p></td>
				  <td valign="top" align="right">
				  <?php
				  if($cart->TotalLines > 0){
				 	 if($cart->Warning) {
				  		for($i=0; $i<count($cart->Warnings); $i++){
				  			?>

						  	<div style="text-align: left;">
								<p class="alert"><?php echo $cart->Warnings[$i]; ?></p>
							</div>
							<br />

							<?php
				  		}
				  	}

				  	if(!$cart->Error){
				  ?>
						<table border="0" cellpadding="5" cellspacing="0" class="catProducts">
							<tr>
								<th colspan="2">Tax &amp; Shipping</th>
							</tr>
							<tr>
							  <td>Delivery Option:</td>
								<td align="right">
									<?php echo $cart->PostageOptions; ?>
								</td>
							</tr>
							<tr>
								<td>Shipping to <strong><?php echo $cart->Location; ?></strong>:<br />
								  <a href="order_changeLocation.php" title="Click for Information on Shipping Prices">(Change Location)</a>
								  </td>
								<td align="right">
									<?php if($cart->FoundPostage && $cart->IsCustomShipping=='N'){
										echo '&pound;' . number_format($cart->ShippingTotal, 2, ".", ",");
									} else {
										echo "Select Postage Option";
									}
									?>
								</td>
							</tr>

				  			<?php
							if($cart->ShippingMultiplier > 1) {
								?>

								<tr>
									<td style="background-color: #ffc;" valign="top">
										Shipping Breakdown<br /><br />

										<?php
										for($i=0; $i<count($cart->ShippingLine); $i++) {
											echo sprintf('<span style="font-size: 9px; color: #333;">%d x %skg @ &pound;%s</span><br />', $cart->ShippingLine[$i]->Quantity, $cart->ShippingLine[$i]->Weight, number_format($cart->ShippingLine[$i]->Charge, 2, '.', ','));
										}
										?>
									</td>
									<td style="background-color: #ffc;" valign="top" align="right">
										&nbsp;<br /><br />

										<?php
										for($i=0; $i<count($cart->ShippingLine); $i++) {
											echo sprintf('<span style="font-size: 9px; color: #333;">&pound;%s</span><br />', number_format($cart->ShippingLine[$i]->Charge * $cart->ShippingLine[$i]->Quantity, 2, '.', ','));
										}
										?>
									</td>
								</tr>

								<?php
							}

							if(!empty($cart->Discount)) {
								?>

								<tr>
									<td>Discount:</td>
									<td align="right">-&pound;<?php echo number_format($cart->Discount, 2, ".", ","); ?></td>
								</tr>

								<?php
							}
							?>

							<tr>
								<td>VAT:</td>
								<td align="right">&pound;<?php echo number_format($cart->TaxTotal, 2, ".", ","); ?></td>
							</tr>
							<tr>
								<td>Total:</td>
								<td align="right">&pound;<?php echo number_format($cart->Total, 2, ".", ","); ?></td>
							</tr>
						</table>
						<p><br />
						    <input name="action" type="submit" class="btn" value="continue" />
					</p>
					<?php } else { ?>
					<p><strong>Sorry...</strong><br />We do not currently have any shipping settings for your deliveries to <?php echo $cart->Location; ?> <a href="order_changeLocation.php" title="Click for Information on Shipping Prices">(Change Location)</a> on one or more of the products in your Shopping Cart. Please call us on <strong><?php echo $GLOBALS['COMPANY_PHONE']; ?></strong> and we will be happy to arrange shipping for you.</p></td>
					<?php
						}
					}
					?>
				</tr>
			</table>

			<?php echo $form->Close(); ?>

			<?php
			if($cart->Customer->ID > 0) {
				$lastProducts = array();
				$products = array();

				$data = new DataQuery(sprintf("SELECT * FROM customer_product WHERE Customer_ID=%d", mysql_real_escape_string($cart->Customer->ID)));
				while($data->Row) {
					$products[] = $data->Row;

					$data->Next();
				}
				$data->Disconnect();

				new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_order SELECT ol.Product_ID, MAX(o.Created_On) AS Last_Ordered_On FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE o.Customer_ID=%d GROUP BY ol.Product_ID", mysql_real_escape_string($cart->Customer->ID)));
				new DataQuery(sprintf("ALTER TABLE temp_order ADD INDEX Product_ID (Product_ID)"));

				for($i=0; $i<count($products); $i++) {
					$product = new CustomerProduct($products[$i]['Customer_Product_ID']);
					$product->Product->Get();

					$data = new DataQuery(sprintf("SELECT Last_Ordered_On FROM temp_order WHERE Product_ID=%d AND Last_Ordered_On!='0000-00-00 00:00:00'", mysql_real_escape_string($products[$i]['Product_ID'])));
					if($data->TotalRows > 0) {
						if(!isset($lastProducts[$data->Row['Last_Ordered_On']])) {
							$lastProducts[$data->Row['Last_Ordered_On']] = array();
						}

						$lastProducts[$data->Row['Last_Ordered_On']][] = $product->Product;
					}
					$data->Disconnect();
				}

				ksort($lastProducts);

				$index = 0;

				if(count($lastProducts) > 0) {
					?>

					<h3>Your Top 5 Previously Ordered Bulbs</h3>
					<p>Add these to your cart if required, if not please checkout.</p>

					<table cellspacing="0" class="catProducts">
					<tr>
						<th>Last Ordered</th>
						<th>Product</th>
						<th>Price</th>
						<th>&nbsp;</th>
					</tr>

					<?php
					foreach($lastProducts as $lastOrdered=>$products) {
						foreach($products as $product) {
							$index++;

							if($index <= 5) {
								?>

								<tr>
									<td><?php print cDatetime($lastOrdered, 'shortdate'); ?></td>
									<td>
										<a href="order_product.php?pid=<?php echo $product->ID; ?>"><strong><?php echo $product->Name; ?></strong></a><br />
										<span class="smallGreyText"><?php echo "Quickfind Code: " . $product->ID; ?></span>
									</td>
									<td align="right">&pound;<?php echo number_format($product->PriceCurrent, 2, '.', ','); ?></td>
									<td align="right"><input type="button" name="buy" value="Buy" class="submit" onclick="window.self.location.href='order_customise.php?action=customise&quantity=1&product=<?php echo $product->ID; ?>'" /></td>
								</tr>

								<?php
							}
						}
					}
					?>

					</table>

					<?php
				}
			}
			?>

	</td>
  </tr>
</table>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>