<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Order Summary</title>
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
	<!-- InstanceBeginEditable name="head" -->
	<script type="text/javascript">
	function changeDelivery(obj){
		var url = "<?php echo $_SERVER['PHP_SELF']; ?>?changePostage=" + obj;
		window.location.href = url;
	}
	</script>
	<!-- InstanceEndEditable -->
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
			<h1>Summary</h1>
			<p>Please confirm the below details including your delivery option before proceeding to payment.</p>

			<?php
			if($cart->FoundPostage) {
				echo $cart->PostageMessages();
			}
			?>

			<table style="border:0px;" cellspacing="0" cellpadding="0">
				<tr>
					<td>
						<table cellpadding="0" cellspacing="0" style="border:0px;" class="invoiceAddresses">
							<tr>
								<td valign="top" class="billing"><p>
									<strong>Billing Address:</strong><br />
									<?php echo $billing->GetFullName();  ?>
									<br />
									<?php echo $billing->Address->GetFormatted('<br />');  ?></p>

								</td>
								<td valign="top" class="shipping"><p>
									<strong>Shipping Address:</strong><br />
									<?php echo $shipping->GetFullName();  ?>
									<br />

									<?php
									echo $shipping->Address->GetFormatted('<br />');
									?></p>


								</td>
							</tr>
							<tr>
								<td class="billing change"><form action="checkout.php" method="post">
									<input type="hidden" name="action" value="editBilling" />
									<input type="hidden" name="contact" value="" />
									<input type="hidden" name="type" value="billing" />
									<input type="submit" name="Change" value="Change" class="greySubmit" />
									</form></td>
								<td class="shipping change"><form action="checkout.php" method="post">
									<input type="hidden" name="action" value="change" />
									<input type="submit" name="Change" value="Change" class="greySubmit" />
									</form></td>
							</tr>
						</table>
					</td>
					<td style="padding-left:15px;">

						<?php
						if(!$form->Valid){
							echo $form->GetError();
							echo "<br>";
						}

						echo $form->Open();
						echo $form->GetHtml('confirm');
						echo $form->GetHtml('action');

						if(count($cart->Line) > 0){
							if(!empty($cart->Coupon->ID)){
								$cart->Coupon->Get();
								echo '<table cellspacing="0" class="cartCoupon"><tr><td><img src="/images/discount_1.gif" border="0" />';
								echo '</td><td><strong>';
								echo $cart->Coupon->Name . '</strong> (Ref: ' . strtoupper($cart->Coupon->Reference) .' )<br />';
								echo $cart->Coupon->Description . '<br />';
								echo sprintf('<span class="smallGreyText">Only one coupon may be added per order. You may use this coupon %d times till expiry. ', $cart->Coupon->UsageLimit);
								echo $cart->Coupon->GetExpiryString();
								echo '</span>';
								echo '<br /><br /><a href="summary.php?action=removeCoupon&confirm=true&shipTo=' . $_REQUEST['shipTo'] . '">Click Here to remove this coupon from your order<a/>';
								echo '</td></tr></table>';
							} else {
								echo $form->GetLabel('coupon') . '<br />';
								echo $form->GetHtml('coupon');
								echo '<input name="addcoupon" type="submit" class="greySubmit" value="add coupon" />';
							}
						}

			?>

					</td>
				</tr>
			</table>
			<br />
			<p><strong>Optional:</strong><br />It may be helpful for you to enter the locations of these lamps for your property/project.<br />This information will then be saved to your customer profile for future ease of reordering.</p>

			<table cellspacing="0" class="catProducts">
				<tr>
					<th>Qty</th>
					<th>Product</th>
					<th>New Location <span style="font-weight: normal;">(Optional)</span></th>
					<th>Quickfind</th>
					<th style="text-align: right;">Price</th>
					<th style="text-align: right;">Your Price</th>
					<th style="text-align: right;">Line Total</th>
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
					<td><?php echo $cart->Line[$i]->Quantity; ?>x</td>
					<td>
					<?php
						if($cart->Line[$i]->Product->ID == 0) {
							echo $cart->Line[$i]->AssociativeProductTitle;
						} else {
							echo $cart->Line[$i]->Product->Name;
						}
					?>
					</td>
					<td>
					<?php
						if($cart->Line[$i]->Product->ID > 0) {
							echo $form->GetHTML('location_'.$cart->Line[$i]->ID);
						} else {
							echo '&nbsp;';
						}
					?>
					</td>
					<td>
					<?php
						if($cart->Line[$i]->Product->ID > 0) {
							echo $cart->Line[$i]->Product->PublicID();
						} else {
							echo '-';
						}
					?>
					</td>
					<td align="right">&pound;<?php echo number_format($cart->Line[$i]->Price, 2, '.', ','); ?></td>
					<?php
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
					<td colspan="7" align="center">Your Shopping Cart is Empty</td>
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
	                    <td style="color: #f00;">&pound;<?php echo number_format($session->Customer->AvailableDiscountReward, 2, '.', ','); ?></td>
	                    <td style="color: #f00;">Discount Reward<br /><span class="smallGreyText" style="color: #f00;">Your reward must be used in conjunction with our payment system.<br />The Google Checkout will not support your discount reward at this time.</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
	                    <td style="color: #f00;" align="right">-&pound;<?php echo number_format($discount, 2, '.', ','); ?></td>
						<td style="color: #f00;" align="right">-&pound;<?php echo number_format($discount, 2, '.', ','); ?></td>
						<td style="color: #f00;" align="right">-&pound;<?php echo number_format($discount, 2, '.', ','); ?></td>
					</tr>

			<?php
			}
			?>
				<tr>
					<td colspan="6" align="right">Sub Total:</td>
					<td align="right">&pound;<?php echo number_format($subTotal, 2, '.', ','); ?></td>
				</tr>
			</table>
			<br />

			<table style="width:100%; border:0px;" cellpadding="0" cellspacing="0">
				<tr>
					<td valign="top">
						<?php echo ($cart->ShippingMultiplier > 1) ? '<span class="alert">' : ''; ?>
						<p style="margin: 0; padding: 5px;">
							Cart Weight: <span style="<?php echo ($cart->ShippingMultiplier > 1) ? 'font-weight: bold' : ''; ?>"><?php echo $cart->Weight; ?>Kg</span><br />
							<span class="smallGreyText">(Approx.)</span>
						</p>
						<?php echo ($cart->ShippingMultiplier > 1) ? '</span>' : ''; ?>
					</td>
					<td valign="top" align="right">
						<table style="width:100%; border:0px;" cellpadding="0" cellspacing="0">
							<tr>
								<td valign="top" align="right">
								<?php if($cart->TotalLines > 0){
									if($cart->Warning) {
										for($i=0; $i<count($cart->Warnings); $i++){ ?>
											<div style="text-align: left;">
												<p class="alert"><?php echo $cart->Warnings[$i]; ?></p>
											</div>
											<br />
										<?php }
									}
									if(!$cart->Error){ ?>
										<table style="border:0px;" cellpadding="5" cellspacing="0" class="catProducts">
											<tr>
												<th colspan="2">Tax &amp; Shipping</th>
											</tr>
											<tr>
												<td>Delivery Option:</td>
												<td align="right"><?php echo $cart->PostageOptions; ?></td>
											</tr>
											<tr>
												<td>
													Shipping to:<br /><strong><?php echo $cart->Location; ?></strong>
												</td>
												<td align="right">
													<?php if($cart->FoundPostage){
														echo ($cart->ShippingTotal == 0) ? 'FREE' : '&pound;' . number_format($cart->ShippingTotal, 2, '.', ',');
													} else {
														echo 'Select Postage Option';
													} ?>
												</td>
											</tr>
											<?php if($cart->ShippingMultiplier > 1){ ?>
												<tr>
													<td style="background-color: #ffc;" valign="top">
														Shipping Breakdown<br /><br />
														<?php for($i=0; $i<count($cart->ShippingLine); $i++) {
															echo sprintf('<span style="font-size: 9px; color: #333;">%d x %skg @ &pound;%s</span><br />', $cart->ShippingLine[$i]->Quantity, $cart->ShippingLine[$i]->Weight, number_format($cart->ShippingLine[$i]->Charge, 2, '.', ','));
														} ?>
													</td>
													<td style="background-color: #ffc;" valign="top" align="right">
														&nbsp;<br /><br />
														<?php for($i=0; $i<count($cart->ShippingLine); $i++) {
															echo sprintf('<span style="font-size: 9px; color: #333;">&pound;%s</span><br />', number_format($cart->ShippingLine[$i]->Charge * $cart->ShippingLine[$i]->Quantity, 2, '.', ','));
														} ?>
													</td>
												</tr>
											<?php } ?>
											<?php if($cart->BillingCountry->ID != $GLOBALS['SYSTEM_COUNTRY']){ ?>
												<tr>
													<td>Tax Exemption Code:<br /><span style="font-size: 9px; color: #999;">Subject to security checks</span></td>
													<td align="right"><?php echo $form->GetHTML('taxexemptcode'); ?> <input type="submit" class="greySubmit" name="updatetax" value="Update" /></td>
												</tr>
											<?php } ?>
											<?php if($session->Customer->AvailableDiscountReward > 0){ ?>
												<tr>
													<td>Pre Tax Total:</td>
													<td align="right">&pound;<?php echo number_format($subTotal+$cart->ShippingTotal, 2, ".", ","); ?></td>
												</tr>
												<tr>
													<td>VAT:</td>
													<td align="right">&pound;<?php echo number_format($taxTotal, 2, ".", ","); ?></td>
												</tr>
												<tr>
													<td>Total:</td>
													<td align="right">&pound;<?php echo number_format($subTotal+$cart->ShippingTotal+$taxTotal, 2, ".", ","); ?></td>
												</tr>
											<?php } else { ?>
												<tr>
													<td>Pre Tax Total:</td>
													<td align="right">&pound;<?php echo number_format($cart->Total-$cart->TaxTotal, 2, ".", ","); ?></td>
												</tr>
												<tr>
													<td>VAT:</td>
													<td align="right">&pound;<?php echo number_format($cart->TaxTotal, 2, ".", ","); ?></td>
												</tr>
												<tr>
													<td>Total:</td>
													<td align="right">&pound;<?php echo number_format($cart->Total, 2, ".", ","); ?></td>
												</tr>
											<?php } ?>
											<?php if($cart->Discount > 0){ ?>
												<tr>
													<td><strong><span style="color: #993333;">Total Saving:</span></strong></td>
													<td align="right"><i>&pound;<?php echo number_format($cart->Discount, 2, ".", ","); ?></i></td>
												</tr>
											<?php } ?>
										</table>
										<?php if($cart->FoundPostage){ ?>
											<br />
											<?php
												if(!$form->Valid){
													echo $form->GetError();
													echo "<br />";
												}
												$form->OnSubmit('disableSubmit();');

												echo $form->Open();
												echo $form->GetHtml('action');
												echo $form->GetHtml('confirm');

												$displayRadio = false; 
											?>
											<?php if($cart->Customer->IsCreditActive == 'Y'){?>
												<?php if(strtoupper($cart->Customer->IsCreditActive) == 'Y' && $cart->Customer->CreditRemaining > 0 && $cart->Customer->CreditRemaining >= $cart->Total){ ?>
													<table style="border:0px;" cellpadding="5" cellspacing="0" class="catProducts">
														<tr>
															<th colspan="2">Credit Acount</th>
														</tr>
														<tr>
															<td colspan="2"><?php echo $form->GetHTML('isOnAccount', 1); ?><strong><?php echo $form->GetLabel('isOnAccount', 1); ?></strong></td>
														</tr>
														<tr>
															<td align="right" width="50%">Charge My Credit Account for:</td>
															<td><strong>&pound;<?php echo number_format($cart->Total, 2, '.', ','); ?></strong></td>
														</tr>
														<tr>
															<td align="right" width="50%">My Monthly Credit Allowance:</td>
															<td>&pound;<?php echo number_format($cart->Customer->CreditLimit, 2, '.', ','); ?></td>
														</tr>
														<tr>
															<td align="right" width="50%">Remaining Credit Before Spend:</td>
															<td>&pound;<?php echo number_format($cart->Customer->CreditRemaining, 2, '.', ','); ?></td>
														</tr>
														<tr>
															<td align="right" width="50%">Remaining Credit After Spend:</td>
															<td>&pound;<?php echo number_format($cart->Customer->CreditRemaining-$cart->Total, 2, '.', ','); ?></td>
														</tr>
														<tr>
															<td align="right" width="50%">My Credit Terms:</td>
															<td><?php echo $cart->Customer->CreditPeriod; ?> Days</td>
														</tr>
														<tr></tr>
													</table>
													<br/>
												<?php } elseif(strtoupper($cart->Customer->IsCreditActive) == 'Y' && ($cart->Customer->CreditRemaining <= 0 || $cart->Customer->CreditRemaining < $cart->Total)){ ?>
													<table style="border:0px;" cellpadding="5" cellspacing="0" class="catProducts">
														<tr>
															<td colspan="2"><strong>Credit Account Customer</strong></td>
														</tr>
														<tr>
															<td colspan="2"><span class="alert"><img src="ignition/images/icon_alert_2.gif" align="absmiddle" />
															Your Credit Account has insufficient funds remaining this month to purchase on credit (See Details Below). You may continue with purchase via Credit/Debit Card.</span></td>
														</tr>
														<tr>
															<td align="right" width="50%">Charge My Credit Account for:</td>
															<td><strong>&pound;<?php echo number_format($cart->Total, 2, '.', ','); ?></strong></td>
														</tr>
														<tr>
															<td align="right" width="50%">My Monthly Credit Allowance:</td>
															<td>&pound;<?php echo number_format($cart->Customer->CreditLimit, 2, '.', ','); ?></td>
														</tr>
														<tr>
															<td align="right" width="50%">Remaining Credit Before Spend:</td>
															<td>&pound;<?php echo number_format($cart->Customer->CreditRemaining, 2, '.', ','); ?></td>
														</tr>
														<tr>
															<td align="right" width="50%">Remaining Credit After Spend:</td>
															<td>&pound;<?php echo number_format($cart->Customer->CreditRemaining-$cart->Total, 2, '.', ','); ?></td>
														</tr>
														<tr>
															<td align="right" width="50%">My Credit Terms:</td>
															<td><?php echo$cart->Customer->CreditPeriod; ?> Days</td>
														</tr>
													</table>
													<br />
												<?php } ?>
								  <table style="border:0px;" cellpadding="5" cellspacing="0" class="catProducts">
													<tr>
														<th colspan="2">Credit / Debit Card</th>
													</tr>
													<tr>
														<td colspan="2"><?php echo $form->GetHTML('isOnAccount', 2); ?><strong><?php echo $form->GetLabel('isOnAccount', 2); ?></strong></td>
													</tr>
													<tr>
														<td align="right" width="50%">Charge My Credit Card for:</td>
														<td><strong>&pound;<?php echo number_format($cart->Total, 2, '.', ','); ?></strong></td>
													</tr>
												</table>
											<?php } ?>
											<p>
												<br />
												</p>
											<form method="post" action="<?php echo $_SERVER['PHP_SELF']; //payment.php"?>">
												<input type="hidden" name="shipTo" value="<?php echo $_REQUEST['shipTo']; ?>" />
													<?php
														if(!empty($cart->QuoteID)){
															echo '<input type="submit"  class="greySubmit" name="action" value="Update Quote" />&nbsp;';
														} else {
															echo sprintf('<input type="submit" class="greySubmit" name="action" value="Save as Quote" %s />&nbsp;', ($unassociatedProducts > 0) ? 'disabled="disabled" title="Sorry, you cannot save this shopping cart as a quote as it contains bltdirect.co.uk products."' : '');
														}

													 	if($cart->Customer->IsCreditActive == 'Y'){
															echo '<input type="submit" class="submit" name="action" value="Continue" />';
														} else {
															echo '<input type="submit" class="submit" name="action" value="Pay by Card" />';
														}
													?>
								  </form>
											</p>
										<?php } ?>
									<?php } else { ?>
										<table class="error">
											<tr>
												<td>
													<strong>Sorry..</strong><br />
													Unfortunately we do not currently have any shipping prices for the your selected shipping destination.
													Your custom is important to us. If you would like to continue with your order please call us on <strong><?php echo $GLOBALS['COMPANY_PHONE']; ?></strong>
													and we will be happy to arrange shipping and complete you order.

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
												</td>
											</tr>
										</table>
									<?php } ?>
								<?php } ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<br />

			<?php
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
			?>

			<?php
			echo $form->Close();
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