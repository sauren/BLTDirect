<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountBanding.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountBandingBasket.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountBandingBasketLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Postage.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductCart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductCookie.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/gateways/GoogleCheckout.php');

$cart->GetShippingLines();

$bandingBasket = new DiscountBandingBasket($session);
$bandingBasket->Banding->Get();

if($bandingBasket->Total > 0) {
	$cart->DiscountBandingID = ($cart->SubTotal >= $bandingBasket->Banding->Threshold) ? $bandingBasket->Banding->ID : 0;
	$cart->Calculate();
	$cart->Update();
}

$unassociatedProducts = 0;

for($i=0;$i<count($cart->Line);$i++) {
	if($cart->Line[$i]->Product->ID == 0) {
		$unassociatedProducts++;
	}
}

//echo $session->ID;

if($unassociatedProducts > 0) {
	$session->Customer->AvailableDiscountReward = 0;
}

if($action == 'remove') {
	if(id_param('line')) {
		$line = new CartLine();
		$line->Remove(id_param('line'));
		redirect("Location: cart.php");
	}
} elseif($action == 'removecoupon') {
	$cart->Coupon->ID = 0;
	$cart->Update();
	redirect("Location: cart.php");
} elseif($action == 'switch') {
	if(id_param('line')) {
		if(id_param('product')) {
			$cart->ChangeLine(id_param('line'), id_param('product'));
			$cart->Calculate();
		}
	}
	redirect("Location: cart.php");
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('coupon', 'Coupon Code', 'text', '', 'alpha_numeric', 1, 15, false);

for($i=0; $i < count($cart->Line); $i++){
	$form->AddField('qty_' . $cart->Line[$i]->ID, 'Quantity of ' . $cart->Line[$i]->Product->Name, 'text',  $cart->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="3"');
}

if(id_param('changePostage') && (id_param('changePostage') > 0)) {
	$cart->Postage = id_param('changePostage');
	$cart->Update();
	redirect('Location: cart.php');
	
} elseif(param('continueshopping')) {
	redirect('Location: products.php');
}

if($action == 'update' && param('confirm')) {
	if($form->Validate()){
		$quantitiesUpdated = false;
		for($i=0; $i < count($cart->Line); $i++){

			if(is_numeric($form->GetValue('qty_' . $cart->Line[$i]->ID))
			&& ($cart->Line[$i]->Quantity
			!= $form->GetValue('qty_' . $cart->Line[$i]->ID))
			&& $form->GetValue('qty_' . $cart->Line[$i]->ID) > 0)
			{
				$cart->Line[$i]->Quantity = $form->GetValue('qty_' . $cart->Line[$i]->ID);
				$cart->Line[$i]->Update();
				$quantitiesUpdated = true;
			}
		}

		$tmpCoupon = $form->GetValue('coupon');
		if(!empty($tmpCoupon)){
			if($quantitiesUpdated){
				$cart->Calculate();
			}
			$coupon = new Coupon();

			if($coupon->Check($form->GetValue('coupon'), $cart->SubTotal, $cart->Customer->ID, true)){
				$cart->Coupon->ID = $coupon->ID;
				$cart->Update();
			} else {
				foreach($coupon->Errors as $key=>$value){
					$form->AddError($value, 'coupon');
				}
			}
		}
	}

	if($form->Valid){
		redirect("Location: cart.php");
	}
}

if($action == 'checkout'){
	if($bandingBasket->Total == 0) {
		$data = new DataQuery(sprintf("SELECT Discount_Banding_ID FROM discount_banding WHERE Trigger_Low<=%f AND Trigger_High>%f LIMIT 0, 1", mysql_real_escape_string($cart->SubTotal), mysql_real_escape_string($cart->SubTotal)));
		if($data->TotalRows > 0) {
			$bandingBasket->GenerateFromCart($cart, $data->Row['Discount_Banding_ID']);
			$cart->DiscountBandingOffered = 'Y';
			$cart->Update();
			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
		$data->Disconnect();
	}
	redirect("Location: checkout.php");
}

$groupsType = array();
$groupsEquivalentWattage = array();
$groupsWattage = array();
$groupsLampLife = array();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE 'type'"));
while($data->Row) {
	$groupsType[] = $data->Row['Group_ID'];
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE '%%equivalent%%' AND Reference LIKE '%%wattage%%'"));
while($data->Row) {
	$groupsEquivalentWattage[] = $data->Row['Group_ID'];
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE 'wattage'"));
while($data->Row) {
	$groupsWattage[] = $data->Row['Group_ID'];
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE '%%lamp%%' AND Reference LIKE '%%life%%'"));
while($data->Row) {
	$groupsLampLife[] = $data->Row['Group_ID'];
	$data->Next();	
}
$data->Disconnect();
include("ui/nav.php");
include("ui/search.php");
 $holidayPromos = new HolidayPromotion();
	$isChristmas = false;
	if($holidayPromos->IsChristmas()) {
		$isChristmas = true;
	}
	
	$isHalloween = false;
	if($holidayPromos->IsHalloween()) {
		$isHalloween = true;
	}
?>
	<script>
		function confirmRemove(id){
			if(confirm('Are you sure you would like to remove this product from your shopping cart?')) {
				window.location.href = 'cart.php?action=remove&confirm=true&line=' + id;
			}
		}

		function changeDelivery(id){
			var url="<?php echo $_SERVER['PHP_SELF']; ?>?changePostage=" + id;
			window.location.href = url;
		}
	</script>
    <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Your Shopping Cart</span></div>
    <div class="maincontent">
<div class="maincontent1">
		            <p>
		            	Click the Checkout button to continue with your order.<br /><br />
		            	We accept the following payment methods:
					</p>
					<div class="paymentNoticeContainer">
						<div class="paymentTypes">
							<a title="VISA" class="paymentType visa"></a>
							<a title="VISA Electron" class="paymentType visaelectron"></a>
							<a title="Master Card" class="paymentType mastercard"></a>
							<?php /*
							<a title="American Express" class="paymentType americanexpress"></a>
							<a title="Laser" class="paymentType laser"></a>
							<a title="Diners Club" class="paymentType diners"></a>
							<a title="JCB" class="paymentType jcb"></a>
							*/ ?>
							<a title="Google Checkout" class="paymentType googlecheckout last"></a>
							<a class="clear"></a>
						</div>
						<div class="paymentNotice">
							<p>For<u>Maestro</u> and other cards not listed or if you have problems placing your order, please call our sales hotline on 01473 716418 and quote the following cart reference: <strong><?php echo $cart->ID; ?></strong></p>
						</div>
						<div class="clear"></div>
					</div>

					<?php
		            if($cart->FoundPostage) {
		            	echo $cart->PostageMessages();
		            }

		            if(isset($_REQUEST['postage']) && ($_REQUEST['postage'] == 'missing')) {
						$bubble = new Bubble('', '<strong>Postage Missing</strong> - Please select a postage option before checking out.');

						echo '<div class="bubblePostage">';
						echo $bubble->GetHTML();
						echo '</div>';
						echo '<br />';
					}

		            $data = new DataQuery(sprintf("SELECT ga.Geozone_ID FROM geozone_assoc AS ga LEFT JOIN countries AS c ON ga.Country_ID=c.Country_ID OR ga.Country_ID=0 LEFT JOIN regions AS r ON ga.Region_ID=r.Region_ID OR ga.Region_ID=0 WHERE c.Country_ID=%d AND r.Region_ID=%d AND (ga.Geozone_ID=5 OR ga.Geozone_ID=6 OR ga.Geozone_ID=21)", mysql_real_escape_string($cart->ShippingCountry->ID), mysql_real_escape_string($cart->ShippingRegion->ID)));
					if($data->TotalRows > 0) {
						$bubble = new Bubble('Free Deliveries', 'Northern Ireland, Scottish Highlands and Isles, Isle of Man, and the Channel Islands only qualify for free shipping on light bulb orders where the consignment weight is under 2kgs and the order value is over &pound;45.00 (ex. VAT). Please note there is a delivery charge for control gear, fluorescent tubes and light fittings on orders under &pound;45.00 (ex. VAT).');
						echo $bubble->GetHTML();
						echo '<br />';
					}
					$data->Disconnect();

		            $showMessage = false;

					for($i=0; $i<count($cart->Line); $i++) {
						if($cart->Line[$i]->Product->ShippingClass->ID != 45) {
							$showMessage = true;
						}
					}

					if($showMessage && ($cart->SubTotal > 35)) {
						$data = new DataQuery(sprintf("SELECT ga.Geozone_ID FROM geozone_assoc AS ga LEFT JOIN countries AS c ON ga.Country_ID=c.Country_ID OR ga.Country_ID=0 LEFT JOIN regions AS r ON ga.Region_ID=r.Region_ID OR ga.Region_ID=0 WHERE c.Country_ID=%d AND r.Region_ID=%d AND (ga.Geozone_ID=3)", mysql_real_escape_string($cart->ShippingCountry->ID), mysql_real_escape_string($cart->ShippingRegion->ID)));
						if($data->TotalRows > 0) {
							$bubble = new Bubble('Free Deliveries', 'Free shipping is applicable to orders over &pound;45.00 (ex. VAT) on <strong>Light Bulb orders only</strong>. A delivery charge is applied to Control Gear, Fluorescent Tubes, Heater Lamps, Projector Lamps and Light Fittings.');
							echo $bubble->GetHTML();
							echo '<br />';
						}
						$data->Disconnect();
					}

		            if(isset($_REQUEST['transferred']) && ($_REQUEST['transferred'] == 'true')) {
		            	echo '<br /><div style="text-align: center; font-size: 12px; color: #993333;"><p><strong>Thank you for your order which is being processed through our bltdirect.com site.</strong><br />You may continue shopping on this site if you wish.</p></div><br />';
		            }


					if(!$form->Valid){
						echo $form->GetError();
						echo '<br />';
					}

					echo $form->Open();
					echo $form->GetHTML('confirm');
					?>

					<?php if($isChristmas){ ?>
						<div class="float-right">
		            		<a href="products.php?cat=1251&amp;nm=Christmas+Lights" title="View our Christmas Lights">
		            			<img src="images/Christmas-Checkout-Logo.png" alt="Don't forget your Christmas Lights - click here" />
		            		</a>
		            	</div>
		            	<div class="clear"></div>
					<?php } elseif($isHalloween){ ?>
						<div class="float-right">
		            		<a href="products.php?cat=3463&amp;nm=Halloween+Light+Bulbs" title="View our Halloween collection">
		            			<img src="images/Halloween-Checkout-Logo.png" alt="Don't forget your Halloween light bulbs - click here" />
		            		</a>
		            	</div>
		            	<div class="clear"></div>
					<?php } ?>
           		
					<p>
						<?php
						if(!empty($cart->Coupon->ID)) {
							$cart->Coupon->Get();

							echo '<table cellspacing="0" class="cartCoupon"><tr><td><img src="images/discount_1.gif" alt="Discount" />';
							echo '</td><td><strong>';
							echo $cart->Coupon->Name . '</strong> (Ref: ' . strtoupper($cart->Coupon->Reference) .' )<br />';
							echo $cart->Coupon->Description . '<br />';
							echo sprintf('<span class="smallGreyText">Only one coupon may be added per order. You may use this coupon %d times till expiry. ', $cart->Coupon->UsageLimit);
							echo $cart->Coupon->GetExpiryString();
							echo '</span>';
							echo '<br /><br /><a href="cart.php?action=removeCoupon&confirm=true">Click Here to remove this coupon from your order<a/>';
							echo '</td></tr></table>';
						} else {
							echo $form->GetLabel('coupon') . '<br />';
							echo $form->GetHTML('coupon');
							echo '<span class="smallGreyText">Click \'Update\' to continue.</span>';
						}
						?>
				    </p>
				    <br />

					<div class="clear"></div>

	    	<?php
	    	if(count($cart->Line) > 0) {
	    		if(($cart->DiscountBandingOffered == 'Y') && ($cart->DiscountBandingID == 0)) {
					?>

					<table border="0" cellpadding="5" cellspacing="0" class="catProducts" width="100%">
						<tr>
				 			<th><span style="color: #993333;"><?php print $bandingBasket->Banding->Name; ?></span></th>
				 		</tr>
				 		<tr>
				 			<td>
				 				<strong><span style="color: #993333;">The standard value of your shopping cart is &pound;<?php print number_format($cart->SubTotal, 2, '.', ','); ?>.

				 				<?php
				 				if($bandingBasket->Banding->Discount > 0) {
				 					echo sprintf('If you spend another &pound;%s (ex. VAT) you will receive a %d%% discount on this order.', number_format($bandingBasket->Banding->Threshold - $cart->SubTotal, 2, '.', ','), $bandingBasket->Banding->Discount);
				 				} else {
				 					echo sprintf('If you spend another &pound;%s (ex. VAT) you will qualify for the following.', number_format($bandingBasket->Banding->Threshold - $cart->SubTotal, 2, '.', ','));
				 				}
				 				?>

				 				</span></strong>

				 				<?php
				 				if(strlen(trim($bandingBasket->Banding->Notes)) > 0) {
				 					echo '<br /><br />';
				 					echo sprintf('<span style="color: #993333;">%s</span>', $bandingBasket->Banding->Notes);
				 				}
				 				?>
				 			</td>
				 		</tr>
				 		<tr>
				 			<td align="right"><em>Continue to checkout if you do not wish to spend anymore</em></td>
				 		</tr>
				 	</table><br />

					<?php
	    		}
	    	}

	    	if($cart->HasDangerousItems()) {
	    		echo '<br /><span class="alert"><p style="padding: 5px;"><strong>Warning for Germicidal Tubes</strong><br />These germicidal fluorescent tubes give off UVC radiation that is harmful to the human eye. They should only be used in the appropriate sealed unit for water or air purification.</p></span><br />';
	    	}
			?>
			
			<table class="list" width="100%">
			
				<?php
				$cartIds = '';
				$subTotal = 0;

				for($i=0; $i<count($cart->Line); $i++) {
					$subCartLine = $cart->Line[$i];
					$subProduct = $cart->Line[$i]->Product;
					$subForm = $form;

					include('../lib/templates/productCart_wspl.php');
					
					$subTotal += ($cart->Line[$i]->Product->ID > 0) ? (($cart->Line[$i]->Price-($cart->Line[$i]->Discount/$cart->Line[$i]->Quantity))*$cart->Line[$i]->Quantity) : $cart->Line[$i]->Price * $cart->Line[$i]->Quantity;

					$cartIds .= sprintf('cp.Product_ID<>%d AND ', $cart->Line[$i]->Product->ID);
				}
			
				if(strlen($cartIds) > 0) {
					$cartIds = sprintf(" AND (%s)", substr($cartIds, 0, -5));
				}

				if(count($cart->Line) == 0){
					?>
					<tr>
						<td colspan="6" align="center">Your Shopping Cart is Empty</td>
					</tr>
					<?php
				} elseif($session->Customer->AvailableDiscountReward > 0){
					$discount = $session->Customer->AvailableDiscountReward;
					if(($cart->SubTotal-$cart->Discount) < $discount) {
						$discount = ($cart->SubTotal-$cart->Discount);
					}

					$subTotal = ($cart->SubTotal-$cart->Discount)-$session->Customer->AvailableDiscountReward;
					if($subTotal < 0) {
						$subTotal = 0;
					}

					$remaining = $session->Customer->AvailableDiscountReward-($cart->SubTotal-$cart->Discount);
					if($remaining < 0) {
						$remaining = 0;
					}

					$taxTotal = $cart->CalculateCustomTax($subTotal+$cart->ShippingTotal);
						?>

						<tr>
		                    <td>&nbsp;</td>
		                    <td style="color: #f00;">&pound;<?php echo number_format($session->Customer->AvailableDiscountReward, 2, '.', ','); ?></td>
		                    <td style="color: #f00;"><strong>Discount Reward</strong><br /><span class="smallGreyText" style="color: #f00;">Your reward for introducing friends/contacts.<br />Remaining discount reward after order: &pound;<?php print number_format($remaining, 2, '.', ','); ?></span></td>
							<td style="color: #f00;" align="right">-&pound;<?php echo number_format($discount, 2, '.', ','); ?></td>
							<td style="color: #f00;" align="right">-&pound;<?php echo number_format($discount, 2, '.', ','); ?></td>
							<td style="color: #f00;" align="right">-&pound;<?php echo number_format($discount, 2, '.', ','); ?></td>
						</tr>

					<?php
				}
				?>
			
				<tr>
					<td colspan="4"><img src="images/icon_trash_1.gif" width="16" height="16" alt="Remove" /> = Remove</td>
                    </tr>
					<tr>
                    <td width="50%"><?php echo ($cart->ShippingMultiplier > 1) ? '<span class="alert">' : ''; ?>Cart Weight:<?php echo ($cart->ShippingMultiplier > 1) ? '</span>' : ''; ?>
                    <p style="<?php echo ($cart->ShippingMultiplier > 1) ? 'font-weight: bold' : ''; ?>"><?php echo $cart->Weight; ?> Kg</p></td>
					<td width="50%">Sub Total : <p class="price-sale price-amount colour-red">&pound;<?php echo number_format($subTotal, 2, '.', ','); ?></p></td>
				</tr>
			</table>
			<br />

			<p><input name="action" type="submit" class="greySubmit" id="action" value="update" /></p>

			<?php
			echo $form->Close();
			?>

			<div style="width: 100%;">
				<?php
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
					<div>
					<table border="0" width="100%" class="catProducts">
						<tr>
							<th colspan="2">Tax &amp; Shipping</th>
						</tr>
						<tr>
						  <td width="100%"><strong style="color: #f00;">Select</strong> Delivery Option:</td></tr>                          
							<tr><td width="100%">
								<?php echo $cart->PostageOptions; ?>
							</td>
						</tr>
						<tr>
							<td width="50%">
								Shipping to:<br />
								<strong><?php echo $cart->Location; ?></strong><br />
								<?php if($session->IsLoggedIn) {
									echo '<a href="checkout.php?action=change" title="Change Shipping Location">(Change Location)</a>';
								} else {
									echo '<a href="cartDeliveryChanger.php" title="Change Shipping Location">(Change Location)</a>';
								} ?>
							</td>
							<td width="50%">
								<?php
								if($cart->FoundPostage) {
									echo ($cart->ShippingTotal == 0) ? 'FREE' : '&pound;' . number_format($cart->ShippingTotal, 2, '.', ',');
								} else {
									echo 'Select Postage Option';
								}
								?>
							</td>
						</tr>

						<?php
						if($cart->ShippingMultiplier > 1) {
							?>

							<tr>
								<td style="background-color: #ffc;" valign="top" width="50%">
									Shipping Breakdown<br /><br />

									<?php
									for($i=0; $i<count($cart->ShippingLine); $i++) {
										echo sprintf('<span style="font-size: 9px; color: #333;">%d x %skg @ &pound;%s</span><br />', $cart->ShippingLine[$i]->Quantity, $cart->ShippingLine[$i]->Weight, number_format($cart->ShippingLine[$i]->Charge, 2, '.', ','));
									}
									?>
								</td>
								<td style="background-color: #ffc;" valign="top" width="50%">
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

						if($session->Customer->AvailableDiscountReward > 0){
							?>

							<tr>
								<td width="50%">Pre Tax Total:</td>
								<td width="50%">&pound;<?php echo number_format($subTotal+$cart->ShippingTotal, 2, ".", ","); ?></td>
							</tr>
							<tr>
								<td width="50%">VAT @ 20.0%:</td>
								<td width="50%">&pound;<?php echo number_format($taxTotal, 2, ".", ","); ?></td>
							</tr>
							<tr>
								<td width="50%">Total:</td>
								<td width="50%">&pound;<?php echo number_format($subTotal+$cart->ShippingTotal+$taxTotal, 2, ".", ","); ?></td>
							</tr>

							<?php
						} else {
							?>

							<tr>
								<td width="50%">Pre Tax Total:</td>
								<td width="50%">&pound;<?php echo number_format($cart->Total-$cart->TaxTotal, 2, ".", ","); ?></td>
							</tr>
							<tr>
								<td width="50%">VAT @ 20.0%:</td>
								<td width="50%">&pound;<?php echo number_format($cart->TaxTotal, 2, ".", ","); ?></td>
							</tr>
							<tr>
								<td width="50%">Total:</td>
								<td width="50%">&pound;<?php echo number_format($cart->Total, 2, ".", ","); ?></td>
							</tr>

							<?php
						}
						?>

						<?php
						if($cart->Discount > 0){
							?>
						<tr>
							<td width="50%"><strong><span style="color: #993333;">Total Saving:</span></strong></td>
							<td width="50%"><i>&pound;<?php echo number_format($cart->Discount, 2, ".", ","); ?></i></td>
						</tr>
						<?php
						}
			  			?>

					</table>
                    </div>

					<table border="0" width="100%">
						<tr>
							<td align="left">

								<?php
								if(false && (date('Y-m-d H:i:s') > '2007-10-04 00:00:00') && (date('Y-m-d H:i:s') < '2007-10-10 00:00:00') && ($cart->Postage == 1)) {
									echo "<br /><p><em><strong>Delays on Deliverys:</strong></em> A number of Royal Mail strikes are taking place between Thursday 4th and Wednesday 10th October. This may cause delays to our Standard 1-5 Day Service. If you require your delivery within 5 Days please select our 2 Day Courier Service Thank You.</p>";
								} else {
									echo '<br />';
								}
								?>

							</td>
						</tr>
					</table>
					<?php
					echo $form->Open();
					echo $form->GetHTML('confirm');
					?>

					<input name="continueshopping" type="submit" class="greySubmit" value="Continue Shopping" />
				    <input name="action" type="submit" class="submit" value="Checkout" />

					<?php
					echo $form->Close();

					$unassociatedProducts = 0;

					for($i=0;$i<count($cart->Line);$i++) {
						if($cart->Line[$i]->Product->ID == 0) {
							$unassociatedProducts++;
						}
					}

					if($cart->FoundPostage) {
						if($unassociatedProducts == 0) {
							if(Setting::GetValue('disable_google_checkout') == 'false') {
								?>

								<div style="text-align: center; margin: 10px 0 0 auto;">
									<p><em>- <strong>or</strong> -</em></p>
								</div>

								<?php
								$googleCheckout = new GoogleCheckout($cart);

								if($GLOBALS['GOOGLE_CHECKOUT_LIVE']) {
									$googleCheckout->continueShoppingUrl = 'complete.php?payment=google&cid=' . $session->Customer->ID;
									$googleCheckout->editCartUrl = 'cart.php';
								} else {
									$googleCheckout->continueShoppingUrl = 'complete.php?payment=google&cid=' . $session->Customer->ID;
									$googleCheckout->editCartUrl = 'cart.php';
								}

								$googleCheckout->merchantCalculationsUrl = 'ignition/services/google-checkout/responsehandler.php';
								echo "<p>Checkout with Google Wallet:</p>";
								echo $googleCheckout->getForm();

								if($session->Customer->AvailableDiscountReward > 0) {
									?>

									<div style="text-align: left; width: 100%; margin: 10px 0 0 auto;">
										<p><em><strong>Please Note:</strong> Your discount reward cannot be used in conjunction with Google Checkout at this time.</em></p>
									</div>

									<?php
								}
							}
						}
					}
				} else {
				  	?>

				  	<div style="width:100%">
						<div class="alert">
							<strong>Sorry...</strong><br />We do not currently have any shipping settings for your deliveries to <?php echo $cart->Location; ?>

							<?php
							echo '<a href="cartDeliveryChanger.php" title="Change Shipping Location">(Change Location)</a>';
							?>

							on one or more of the products in your Shopping Cart. 
							Please call us on <strong><?php echo $GLOBALS['COMPANY_PHONE']; ?></strong> and we will be happy to arrange shipping for you (Cart Ref. <?php echo $cart->ID; ?>).

							<?php
							if(count($cart->Errors) > 0) {
								echo '<br /><br /><strong>Reasons:</strong>';
								echo '<ul>';

								foreach($cart->Errors as $error) {
									echo sprintf('<li>%s</li>', $error);
								}

								echo '</ul>';
							}
							?>
						</div>
					</div>
				<?php
				}
				?>
			</div>

			<?php
			if($cart->TotalLines > 0) {
				if($session->Customer->Contact->IsTradeAccount == 'N') {
					$products = '';
				  	
				  	for($i = 0; $i < $cart->TotalLines; $i++) {
				  		$products .= sprintf('pc.Product_ID<>%d AND ', $cart->Line[$i]->Product->ID);
				  	}

				  	$products = substr($products, 0, -5);

				  	$data = new DataQuery(sprintf("SELECT * FROM product_cart AS pc INNER JOIN product AS p ON pc.Product_ID=p.Product_ID WHERE %s", mysql_real_escape_string($products)));
				  	if($data->TotalRows > 0) {
				  		$columns = 2;
						$index = 0;	
						?>

						<div style="width: 100%;">
				  		  <table border="0" cellspacing="0" class="catProducts" width="100%">
						    <tr>
									<th colspan="<?php echo $columns*2; ?>">Last Minute Shopping</th>
							  </tr>

							  <?php
								while($data->Row) {
									$productCart = new ProductCart($data->Row['Product_Cart_ID']);
									$product = new Product();

									if($product->Get($productCart->ProductID)) {
										if($index == 0) {
											echo '<tr>';
										}
										?>

									  <tr>
									    <td align="left" valign="top"><a href="product.php?pid=<?php echo $product->ID; ?>&amp;nm=<?php echo urlencode($product->MetaTitle); ?>" title="<?php echo $product->MetaTitle; ?>"><img src="<?php echo (empty($product->DefaultImage->Thumb->FileName) || !file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$product->DefaultImage->Thumb->FileName))?"images/image_coming_soon_3.jpg":"images/products/".$product->DefaultImage->Thumb->FileName; ?>" alt="<?php echo $product->Name; ?>" /></a></td>
										<td align="left" valign="top">
											<a href="product.php?pid=<?php echo $product->ID; ?>&amp;nm=<?php echo urlencode($product->MetaTitle); ?>" title="<?php echo $product->MetaTitle; ?>"><strong><?php echo $product->Name; ?></strong><br />
											<span class="smallGreyText">QuickFind #: <?php echo $product->ID; ?>, Part Number: <?php echo $product->SKU; ?></span></a><br /><br />
											&pound;<?php echo number_format($product->PriceCurrent, 2, '.', ','); ?><br />
											<span class="smallGreyText">
												<?php echo ($product->PriceCurrentIncTax != $product->PriceCurrent)?'&pound;' . number_format($product->PriceCurrentIncTax, 2, '.', ',') . ' inc. VAT':'No VAT'; ?>
											</span><br /><br />	
											<input type="button" name="buy" value="Add to Cart" class="submit" onclick="window.self.location.href='customise.php?action=customise&amp;quantity=1&amp;product=<?php echo $product->ID; ?>'" />
										</td>

										<?php
										$index++;

										if($index == $columns) {
											$index = 0;

											echo '</tr>';
										}
									}

									$data->Next();
								}
								?>

							</tr>
					  		</table>
						</div>
						
						<?php
					}
  					$data->Disconnect();
				}
		  	}
		  	?>

		  	<div class="clear"></div>

		  	<?php					
			if($session->IsLoggedIn) {
				$lastProducts = array();
				$products = array();

				$data = new DataQuery(sprintf("SELECT * FROM customer_product WHERE Customer_ID=%d", mysql_real_escape_string($session->Customer->ID)));
				while($data->Row) {
					$products[] = $data->Row;

					$data->Next();
				}
				$data->Disconnect();

				new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_order SELECT ol.Product_ID, MAX(o.Created_On) AS Last_Ordered_On FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE o.Customer_ID=%d GROUP BY ol.Product_ID", mysql_real_escape_string($session->Customer->ID)));
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
					<p>Add these to your cart if required, if not please checkout. For more of your bulbs <a href="bulbs.php">click here</a>.</p>

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
										<a href="product.php?pid=<?php echo $product->ID; ?>" title="Click to View <?php echo $product->Name; ?>"><strong><?php echo $product->Name; ?></strong></a><br />
										<span class="smallGreyText"><?php echo "Quickfind Code: " . $product->ID; ?></span>
									</td>
									<td align="right">&pound;<?php echo number_format($product->PriceCurrent, 2, '.', ','); ?></td>
									<td align="right"><input type="button" name="buy" value="Buy" class="submit" onclick="window.self.location.href='customise.php?action=customise&amp;quantity=1&amp;product=<?php echo $product->ID; ?>'" /></td>
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
</div>
</div>
<?php include('../lib/templates/back_wspl.php'); ?>
<?php include('../lib/templates/recent_wspl.php'); ?>  
<?php include("ui/footer.php");?>
<?php require_once('../lib/common/appFooter.php');