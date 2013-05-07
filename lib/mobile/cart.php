<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/mobile.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Shopping Cart</title>
	<!-- InstanceEndEditable -->
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/>
	<link rel="stylesheet" type="text/css" href="/css/lightbulbs.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="/css/lightbulbs_print.css" media="print" />
	<link rel="stylesheet" type="text/css" href="/css/Navigation.css" />
	<link rel="stylesheet" type="text/css" href="/css/Menu.css" />
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'Y') {
		?>
		<link rel="stylesheet" type="text/css" href="/css/Trade.css" />
        <?php
	}
	?>
    
	<link rel="shortcut icon" href="/favicon.ico" />
	<script language="javascript" type="text/javascript" src="/js/generic.js"></script>
	<script language="javascript" type="text/javascript" src="/js/evance_api.js"></script>
	<script language="javascript" type="text/javascript" src="/js/mootools.js"></script>
	<script language="javascript" type="text/javascript" src="/js/evance.js"></script>
	<script language="javascript" type="text/javascript" src="/js/bltdirect.js"></script>
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'N') {
		?>
		<script language="javascript" type="text/javascript" src="/js/bltdirect/template.js"></script>
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
	<link rel="stylesheet" type="text/css" href="/css/MobileSplash.css" />
    <link rel="stylesheet" type="text/css" href="/css/new.css" />
   	<link rel="stylesheet" type="text/css" href="/css/mobile/new.css" />
	<!-- InstanceBeginEditable name="head" -->
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

		function resize() {
			var ele = null;
			var frameWidth;
			var frameHeight;

			if (self.innerWidth)
			{
				frameWidth = self.innerWidth;
				frameHeight = self.innerHeight;
			}
			else if (document.documentElement && document.documentElement.clientWidth)
			{
				frameWidth = document.documentElement.clientWidth;
				frameHeight = document.documentElement.clientHeight;
			}
			else if (document.body)
			{
				frameWidth = document.body.clientWidth;
				frameHeight = document.body.clientHeight;
			}
			else return;

			ele = document.getElementById('LastMinuteShopping');
			if(ele) {
				if(frameWidth<890) {
					ele.style.display = 'none';
				} else {
					ele.style.display = 'block';
				}
			}
		}

		var resizeController = new (function(){
			Interface.addListener(this);

			this.load = function(){
				resize();

				self.onresize = function() {
					resize();
				}

				document.body.onresize = function (){
					resize();
				}
			}
		});
	</script>
	<meta name="keywords" content="light bulbs, light bulb, lightbulbs, lightbulb, lamps, fluorescent, tubes, osram, energy saving, sylvania, philips, ge, halogen, low energy, metal halide, candle, dichroic, gu10, projector, blt direct" />
	<meta name="description" content="We specialise in supplying lamps, light bulbs and fluorescent tubes, Our stocks include Osram,GE, Sylvania, Omicron, Pro lite, Crompton, Ushio and Philips light bulbs, " />
	<!-- InstanceEndEditable -->
</head>
<body>

	<a name="top"></a>

    <div id="Page">
        <div id="PageContent">
            <div class="right rightIcon">
            	<a href="http://www.bltdirect.com/" title="Light Bulbs, Lamps and Tubes Direct"><img src="../../images/logo_125.png" alt="Light Bulbs, Lamps and Tubes Direct" /></a><br />
            	<?php echo Setting::GetValue('telephone_sales_hotline'); ?>
            </div>
            
            <!-- InstanceBeginEditable name="pageContent" -->
           	<h1>Your Shopping Cart</h1>
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
					<p>For Maestro and other cards not listed or if you have problems placing your order, please call our sales hotline on 01473 716418 and quote the following cart reference: <?php echo $cart->ID; ?></p>
				</div>
				<div class="clear"></div>
			</div>
			
			<?php
			if(isset($_REQUEST['postage']) && ($_REQUEST['postage'] == 'missing')) {
				$bubble = new Bubble('', '<strong>Postage Missing</strong> - Please select a postage option before checking out.');

				echo '<div class="bubblePostage">';
				echo $bubble->GetHTML();
				echo '</div>';
				echo '<br />';
			}

			$data = new DataQuery(sprintf("SELECT ga.Geozone_ID FROM geozone_assoc AS ga LEFT JOIN countries AS c ON ga.Country_ID=c.Country_ID OR ga.Country_ID=0 LEFT JOIN regions AS r ON ga.Region_ID=r.Region_ID OR ga.Region_ID=0 WHERE c.Country_ID=%d AND r.Region_ID=%d AND (ga.Geozone_ID=5 OR ga.Geozone_ID=6 OR ga.Geozone_ID=21)", mysql_real_escape_string($cart->ShippingCountry->ID), mysql_real_escape_string($cart->ShippingRegion->ID)));
			if($data->TotalRows > 0) {
				$bubble = new Bubble('Free Deliveries', 'Northern Ireland, Scottish Highlands and Isles, Isle of Man, and the Channel Islands only qualify for free shipping on light bulb orders where the consignment weight is under 2kgs and the order value is over &pound;40.00 (ex. VAT). Please note there is a delivery charge for control gear, fluorescent tubes and light fittings on orders under &pound;40.00 (ex. VAT).');
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

		    if($cart->FoundPostage) {
		        echo $cart->PostageMessages();
		    }

			if(!$form->Valid){
				echo $form->GetError();
				echo '<br />';
			}

			echo $form->Open();
			echo $form->GetHTML('confirm');
            ?>

			<p>
				<?php
				if(!empty($cart->Coupon->ID)) {
					$cart->Coupon->Get();

					echo '<table cellspacing="0" class="cartCoupon"><tr><td><img src="/images/discount_1.gif" alt="Discount" />';
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

					<table border="0" cellpadding="5" cellspacing="0" class="catProducts">
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

			<table cellspacing="0" class="catProducts">
				<tr>
					<th valign="top">&nbsp;</th>
					<th valign="top">Qty</th>
					<th valign="top">Product</th>
					<th valign="top" style="text-align: right;">Price</th>
					<?php
					if($session->IsLoggedIn) {
						?>
						<th valign="top" style="text-align: right;">Your Price</th>
						<?php
					}
					?>
					<th valign="top" style="text-align: right;">Line Total</th>
				</tr>

			<?php
			$cartIds = '';
			$subTotal = 0;

			for($i=0; $i < count($cart->Line); $i++){
				if($cart->Line[$i]->Product->ID > 0) {
					$itemTotal = (($cart->Line[$i]->Price-($cart->Line[$i]->Discount/$cart->Line[$i]->Quantity))*$cart->Line[$i]->Quantity);
				} else {
					$itemTotal = $cart->Line[$i]->Price * $cart->Line[$i]->Quantity;
				}

				$subTotal += $itemTotal;
				?>
				<tr>
                    <td><a href="javascript:confirmRemove(<?php echo $cart->Line[$i]->ID; ?>);"><img src="images/icon_trash_1.gif" alt="Remove <?php echo $cart->Line[$i]->Product->Name; ?>" width="16" height="16" /></a></td>
                    <td><?php echo $form->GetHTML('qty_' . $cart->Line[$i]->ID); ?></td>
					<td>
						<?php
						if($cart->Line[$i]->Product->ID == 0) {
							?><strong><?php echo $cart->Line[$i]->AssociativeProductTitle; ?></strong><br /><?php
						} else {
							?><a href="product.php?pid=<?php echo $cart->Line[$i]->Product->PublicID();?>" title="Click to View <?php echo $cart->Line[$i]->Product->Name; ?>"><strong><?php echo $cart->Line[$i]->Product->Name; ?></strong></a><br /><?php
						}

						if($cart->Line[$i]->Product->ID > 0) {
							if(!empty($cart->Line[$i]->Discount)){
								$discountVal = explode(':', $cart->Line[$i]->DiscountInformation);
								if(trim($discountVal[0]) == 'azxcustom') {
									$showDiscount = 'Custom Discount';
								} else {
									$showDiscount = $cart->Line[$i]->DiscountInformation;
								}
								?>

								<span class="smallGreyText">

								<?php
								if(!empty($showDiscount)) {
									echo sprintf("%s (&pound;%s Discount)", $showDiscount, number_format($cart->Line[$i]->Discount, 2, '.',','));
								} else {
									echo sprintf("%s (&pound;%s Discount)", $cart->Line[$i]->DiscountInformation, number_format($cart->Line[$i]->Discount, 2, '.',','));
								}
								?>

								</span><br />

								<?php
							}
							?>

							<span class="smallGreyText"><?php
							echo "Quickfind Code: " . $cart->Line[$i]->Product->PublicID();
							?> </span>
						<?php
						}
						?>
					</td>
					<td align="right">&pound;<?php echo number_format($cart->Line[$i]->Price, 2, '.', ','); ?></td>
					<?php
					if($session->IsLoggedIn) {
						if($cart->Line[$i]->Product->ID > 0) {
							if($cart->Line[$i]->Price == ($cart->Line[$i]->Price-($cart->Line[$i]->Discount/$cart->Line[$i]->Quantity))) {
								?>
								<td align="right">-</td>
								<?php
							} else {
								?>
								<td align="right">&pound;<?php echo number_format(($cart->Line[$i]->Price-($cart->Line[$i]->Discount/$cart->Line[$i]->Quantity)), 2, '.', ','); ?></td>
								<?php
							}
						} else {
							?>
							<td align="right">-</td>
							<?php
						}
					}
					?>
					<td align="right">&pound;<?php echo number_format($itemTotal, 2, '.', ','); ?></td>
				</tr>
				<?php
				$cartIds .= sprintf('cp.Product_ID<>%d AND ', $cart->Line[$i]->Product->ID);
			}

			if(strlen($cartIds) > 0) {
				$cartIds = sprintf(" AND (%s)", substr($cartIds, 0, -5));
			}

			if(count($cart->Line) == 0){
			?>
				<tr>
					<td colspan="<?php print ($session->IsLoggedIn) ? '6' : '5' ; ?>" align="center">Your Shopping Cart is Empty</td>
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
					<td colspan="<?php print ($session->IsLoggedIn) ? '4' : '3' ; ?>"><img src="images/icon_trash_1.gif" width="16" height="16" alt="Remove" /> = Remove</td>
					<td align="right">Sub Total: </td>
					<td align="right">&pound;<?php echo number_format($subTotal, 2, '.', ','); ?></td>
				</tr>
			</table><br />

			<p><input name="action" type="submit" class="greySubmit" id="action" value="update" /></p>

			<?php
			echo $form->Close();
			?>

			<table border="0" width="100%" cellpadding="0" cellspacing="0">
				<tr>
				  <td width="50%" valign="top">
					<?php echo ($cart->ShippingMultiplier > 1) ? '<span class="alert">' : ''; ?>

						<p style="margin: 0; padding: 5px;">
							Cart Weight: <span style="<?php echo ($cart->ShippingMultiplier > 1) ? 'font-weight: bold' : ''; ?>"><?php echo $cart->Weight; ?>Kg</span><br />
							<span class="smallGreyText">(Approx.)</span>
						</p>

					<?php echo ($cart->ShippingMultiplier > 1) ? '</span>' : ''; ?>

			       </td>
				  <td width="50%" valign="top" align="right">
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
							  <td><strong style="color: #f00;">Select</strong> Delivery Option:</td>
								<td align="right">
									<?php echo $cart->PostageOptions; ?>
								</td>
							</tr>
							<tr>
								<td>
									Shipping to:<br />
									<strong><?php echo $cart->Location; ?></strong><br />

									<?php
									if($session->IsLoggedIn) {
										echo '<a href="/checkout.php" title="Change Shipping Location">(Change Location)</a>';
									} else {
										echo '<a href="/cartDeliveryChanger.php" title="Change Shipping Location">(Change Location)</a>';
									}
									?>
								</td>
								<td align="right">
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

							if($session->Customer->AvailableDiscountReward > 0){
								?>

								<tr>
									<td>Pre Tax Total:</td>
									<td align="right">&pound;<?php echo number_format($subTotal+$cart->ShippingTotal, 2, ".", ","); ?></td>
								</tr>
								<tr>
									<td>VAT @ 20.0%:</td>
									<td align="right">&pound;<?php echo number_format($taxTotal, 2, ".", ","); ?></td>
								</tr>
								<tr>
									<td>Total:</td>
									<td align="right">&pound;<?php echo number_format($subTotal+$cart->ShippingTotal+$taxTotal, 2, ".", ","); ?></td>
								</tr>

								<?php
							} else {
								?>

								<tr>
									<td>Pre Tax Total:</td>
									<td align="right">&pound;<?php echo number_format($cart->Total-$cart->TaxTotal, 2, ".", ","); ?></td>
								</tr>
								<tr>
									<td>VAT @ 20.0%:</td>
									<td align="right">&pound;<?php echo number_format($cart->TaxTotal, 2, ".", ","); ?></td>
								</tr>
								<tr>
									<td>Total:</td>
									<td align="right">&pound;<?php echo number_format($cart->Total, 2, ".", ","); ?></td>
								</tr>

								<?php
							}
							?>

							<?php
							if($cart->Discount > 0){
								?>
							<tr>
								<td><strong><span style="color: #993333;">Total Saving:</span></strong></td>
								<td align="right"><i>&pound;<?php echo number_format($cart->Discount, 2, ".", ","); ?></i></td>
							</tr>
							<?php
							}
				  			?>

						</table>

						<table border="0" cellpadding="5" cellspacing="0">
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
						</table><br />

						<?php
						echo $form->Open();
						echo $form->GetHTML('confirm');
						?>

						<input type="button" name="continue" class="greySubmit" value="Continue Shopping" onclick="window.location.href = 'finder.php';" />
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

									<div style="text-align: right; margin: 10px 0 0 auto;">
										<p><em>- <strong>or</strong> -</em></p>
									</div>

									<?php
									$googleCheckout = new GoogleCheckout($cart);

									if($GLOBALS['GOOGLE_CHECKOUT_LIVE']) {
										$googleCheckout->continueShoppingUrl = 'https://www.bltdirect.com/complete.php?payment=google&cid=' . $session->Customer->ID;
										$googleCheckout->editCartUrl = 'https://www.bltdirect.com/cart.php';
									} else {
										$googleCheckout->continueShoppingUrl = 'http://dev.bltdirect.com/complete.php?payment=google&cid=' . $session->Customer->ID;
										$googleCheckout->editCartUrl = 'http://dev.bltdirect.com/cart.php';
									}

									$googleCheckout->merchantCalculationsUrl = 'https://www.bltdirect.com/ignition/services/google-checkout/responsehandler.php';

									echo $googleCheckout->getForm();

									if($session->Customer->AvailableDiscountReward > 0) {
										?>

										<div style="text-align: left; width: 300px; margin: 10px 0 0 auto;">
											<p><em><strong>Please Note:</strong> Your discount reward cannot be used in conjunction with Google Checkout at this time.</em></p>
										</div>

										<?php
									}
								}
							}
						}
				  } else {
				  	?>

				  	<div style="text-align: left;">
						<div class="alert">
							<strong>Sorry...</strong><br />We do not currently have any shipping settings for your deliveries to <?php echo $cart->Location; ?>

							<?php
							echo '<a href="/cartDeliveryChanger.php" title="Change Shipping Location">(Change Location)</a>';
							?>

							on one or more of the products in your Shopping Cart. Please call us on <strong><?php echo $GLOBALS['COMPANY_PHONE']; ?></strong> and we will be happy to arrange shipping for you.

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

				  if($count > 0) {
							?>

									</td>
								</tr>
							</table>

							<?php
				  }
				  }
					?>
					</td>
				</tr>
			</table>

			<?php include('lib/templates/back.php'); ?>
				
			<!-- InstanceEndEditable -->
            
            <div class="clear"></div>
        </div>
    </div>

	<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-1618935-2']);
  _gaq.push(['_setDomainName', 'bltdirect.com']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
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
<img src="http://stats1.saletrack.co.uk/scripts/stinit.asp?cid=256336&rf=JavaScript%20Disabled%20Browser" width="0" height="0" />
</noscript>
-->


<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>