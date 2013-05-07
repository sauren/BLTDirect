<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Postage.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/QuoteLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountCustomer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProductLocation.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TelePrompt.php');

$session->Secure(2);

$minimumMarkup = Setting::GetValue('minimum_markup_percent');
$minimumMarkupThreshold = Setting::GetValue('minimum_markup_alternative_threshold');
$minimumMarkupAlternative = Setting::GetValue('minimum_markup_alternative_percent');

$cart = new Cart($session, true);

if(empty($cart->Customer->ID)) {
	redirect('Location: order_checkout.php');
}

$cart->Calculate();
$cart->Customer->Get();
$cart->Customer->Contact->Get();
$cart->Customer->Contact->Person->Get();

$locations = array();

$data = new DataQuery(sprintf("SELECT * FROM customer_location WHERE CustomerID=%d", mysql_real_escape_string($cart->Customer->ID)));
while($data->Row) {
	$locations[strtolower(trim($data->Row['Name']))] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF'], 'post', 'summaryForm');
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('coupon', 'Coupon Code', 'text', '', 'alpha_numeric', 1, 15, false);
$form->AddField('taxexemptcode', 'Tax Exempt Code', 'text', $cart->TaxExemptCode, 'paragraph', 0, 20, false);
$form->AddField('freeText', 'Free Text', 'text', $cart->FreeText, 'paragraph', 0, 255, false, 'style="width:100%;"');
$form->AddField('freeTextValue', 'Free Text Value', 'text', (isset($cart->FreeTextValue) ? $cart->FreeTextValue : '0.00'), 'float', 0, 11, false, 'size="10"');

for($i=0; $i < count($cart->Line); $i++) {
	$form->AddField(sprintf('location_%d', $cart->Line[$i]->ID), 'Product Location', 'text', '', 'anything', 1, 120, false);
}

if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'addproduct')) {
	$cart->AddLine($_REQUEST['pid']);
}

$shipping = NULL;
$billing = &$cart->Customer->Contact->Person;

if(isset($_REQUEST['shipTo']) && !empty($_REQUEST['shipTo'])){
	$cart->ShipTo = $_REQUEST['shipTo'];
	$cart->Update();
}

if(empty($cart->ShipTo)){
	redirect("Location: order_shipping.php");
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

if(isset($_POST['proceed'])) {
	$cart->Prefix = 'T';
	$cart->Update();

	if($_POST['proceed'] == "Order"){
		for($i=0; $i < count($cart->Line); $i++){
			$cp = new CustomerProduct();
			$cp->Product = $cart->Line[$i]->Product;
			$cp->Customer = $cart->Customer;

			if(!$cp->Exists()) {
				$cp->Add();
			}

			$value = $form->GetValue(sprintf('location_%d', $cart->Line[$i]->ID));

			if(!empty($value)) {
				if(isset($locations[strtolower(trim($value))])) {
					$data = new DataQuery(sprintf("SELECT * FROM customer_product_location WHERE CustomerLocationID=%d AND CustomerProductID=%d", $locations[strtolower(trim($value))]['CustomerLocationID'], mysql_real_escape_string($cp->ID)));
					if($data->TotalRows == 0) {
						$productLocation = new CustomerProductLocation();
						$productLocation->Product->ID = $cp->ID;
						$productLocation->Location->ID = $locations[strtolower(trim($value))]['CustomerLocationID'];
						$productLocation->Add();
					}
					$data->Disconnect();
				} else {
					$customerLocation = new CustomerLocation();
					$customerLocation->Customer->ID = $cp->Customer->ID;
					$customerLocation->Name = $value;
					$customerLocation->Add();

					$productLocation = new CustomerProductLocation();
					$productLocation->Product->ID = $cp->ID;
					$productLocation->Location->ID = $customerLocation->ID;
					$productLocation->Add();
				}
			}
		}

        redirect("Location: order_takePayment.php");
    } elseif($_POST['proceed'] == "Quote"){
    	for($i=0; $i < count($cart->Line); $i++){
			$cp = new CustomerProduct();
			$cp->Product = $cart->Line[$i]->Product;
			$cp->Customer = $cart->Customer;

    		if(!$cp->Exists()) {
				$cp->Add();
			}

			$value = $form->GetValue(sprintf('location_%d', $cart->Line[$i]->ID));

			if(!empty($value)) {
				if(isset($locations[strtolower(trim($value))])) {
					$data = new DataQuery(sprintf("SELECT * FROM customer_product_location WHERE CustomerLocationID=%d AND CustomerProductID=%d", $locations[strtolower(trim($value))]['CustomerLocationID'], mysql_real_escape_string($cp->ID)));
					if($data->TotalRows == 0) {
						$productLocation = new CustomerProductLocation();
						$productLocation->Product->ID = $cp->ID;
						$productLocation->Location->ID = $locations[strtolower(trim($value))]['CustomerLocationID'];
						$productLocation->Add();
					}
					$data->Disconnect();
				} else {
					$customerLocation = new CustomerLocation();
					$customerLocation->Customer->ID = $cp->Customer->ID;
					$customerLocation->Name = $value;
					$customerLocation->Add();

					$productLocation = new CustomerProductLocation();
					$productLocation->Product->ID = $cp->ID;
					$productLocation->Location->ID = $customerLocation->ID;
					$productLocation->Add();
				}
			}
		}

        $quote = new Quote();
		$quote->Prefix = 'T';
		$quote->GenerateFromCart($cart);
		$quote->SendEmail();
		
        redirectTo('quote_details.php?quoteid=' . $quote->ID);
	}
}

if(strtolower($cart->ShipTo) == 'billing'){
	$shipping = &$cart->Customer->Contact->Person;
} else {
	$shipping = new CustomerContact($cart->ShipTo);
}

if(($cart->ShippingCountry->ID != $shipping->Address->Country->ID) ||
   ($cart->ShippingRegion->ID  != $shipping->Address->Region->ID)){
		$cart->ShippingCountry->ID = $shipping->Address->Country->ID;
		$cart->ShippingRegion->ID = $shipping->Address->Region->ID;
		$cart->Update();
		$cart->Reset();
		$cart->Calculate();
}

switch(strtolower($action)){
	case 'remove':
		remove();
		break;
	case 'removecoupon':
		removeCoupon();
		break;
}

if(isset($_REQUEST['changePostage']) && is_numeric($_REQUEST['changePostage']) && $_REQUEST['changePostage'] > 0){
	$cart->Postage = $_REQUEST['changePostage'];
	$cart->Update();
	redirect("Location: order_summary.php");
}

function remove(){
	if(isset($_REQUEST['line']) && is_numeric($_REQUEST['line'])){
		$line = new CartLine;
		$line->Remove($_REQUEST['line']);
		
		redirect("Location: order_summary.php");
	}
}

function removeCoupon(){
	global $cart;
	
	if(isset($_REQUEST['confirm'])) {
		$cart->Coupon->ID = 0;
		$cart->Update();
		
		redirect("Location:order_summary.php");
	}
}

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

		$cart->TaxExemptCode = $form->GetValue('taxexemptcode');

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

		redirect("Location: order_summary.php");
	}
}

$page = new Page('Create A New Order Manually', '');
$page->Display('header');
?>

<script language="javascript" type="text/javascript">
function changeDelivery(obj){
	var url = "<?php echo $_SERVER['PHP_SELF']; ?>?changePostage=" + obj;
	window.location.href = url;
}

function confirmRemove(id){
    if(confirm('Are you sure you would like to remove this product from your cart?')) {
        window.location.href = 'order_summary.php?action=remove&confirm=true&line=' + id;
    }
}
</script>

<table width="100%" border="0">
  <tr>
    <td width="300" valign="top"><?php include('./order_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top">
    
    	<?php
	    $prompt = new TelePrompt();
		$prompt->Output('ordersummary');
		
		echo $prompt->Body;
		?>
		
		<strong>Order Summary</strong>
			<p>Before proceeding to the payment stage of the checkout please confirm the billing details, shipping details and your delivery option.</p>
			<?php
				if(!$form->Valid){
					echo $form->GetError();
					echo "<br>";
				}
			?>

			<table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
				<tr>
					<td valign="top" class="billing"><p>
						<strong>Organisation/Individual:</strong><br />
						<?php echo $billing->GetFullName();  ?>
						<br />
						<?php echo $billing->Address->GetFormatted('<br />');  ?></p>
					</td>
					<td valign="top" class="shipping"><p>
						<strong>Shipping Address:</strong><br />
						<?php echo $shipping->GetFullName();  ?>
						<br />
						<?php echo $shipping->Address->GetFormatted('<br />');  ?></p>
					</td>
				</tr>
				<tr>
					<td class="billing change"><form action="order_shipping.php" method="post">
						<input type="hidden" name="action" value="change" />
						<input type="submit" name="Change" value="Change" class="btn" />
						</form></td>
					<td class="shipping change"><form action="order_shipping.php" method="post">
						<input type="hidden" name="action" value="change" />
						<input type="submit" name="Change" value="Change" class="btn" />
						</form></td>
				</tr>
			</table>
			<br />
			 <?php
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
						echo '<br /><br /><a href="order_summary.php?action=removeCoupon&confirm=true">Click Here to remove this coupon from your order<a/>';
						echo '</td></tr></table>';
					} else {
						echo '<strong>'.$form->GetLabel('coupon') . '</strong><br />';
						echo $form->GetHtml('coupon');
			 	}
			 }
			 ?>

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
				$cartIds = '';
				$subTotal = 0;

				for($i=0; $i < count($cart->Line); $i++){
					?>

				<tr>
					<td><a href="javascript:confirmRemove(<?php echo $cart->Line[$i]->ID; ?>);" onmouseover="MM_displayStatusMsg('Remove <?php echo $cart->Line[$i]->Product->Name; ?>');return document.MM_returnValue"  onmouseout="MM_displayStatusMsg('');return document.MM_returnValue"><img src="images/icon_trash_1.gif" alt="Remove <?php echo $cart->Line[$i]->Product->Name; ?>" width="16" height="16" border="0" /></a></td>
                    <td><?php echo $form->GetHTML('qty_' . $cart->Line[$i]->ID); ?></td>
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
						?><br />
						Location: <?php echo $form->GetHTML('location_'.$cart->Line[$i]->ID); ?>
					</td>
					<td align="right">&pound;<?php echo number_format($cart->Line[$i]->Price, 2, '.', ','); ?></td>
		            <td align="right">&pound;<?php echo number_format($cart->Line[$i]->Discount / $cart->Line[$i]->Quantity, 2, '.', ','); ?></td>
		            <td align="right">&pound;<?php echo number_format($cart->Line[$i]->Price - ($cart->Line[$i]->Discount / $cart->Line[$i]->Quantity), 2, '.', ','); ?></td>
		            <td align="right">&pound;<?php echo number_format($cart->Line[$i]->Total - $cart->Line[$i]->Discount, 2, '.', ','); ?></td>
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
					<td colspan="7" align="center">The Shopping Cart is Empty</td>
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
					<td colspan="5">Cart Weight: <?php echo $cart->Weight; ?>Kg. <span class="smallGreyText">(Approx.)</span></td>
					<td align="right">Sub Total:</td>
					<td align="right">&pound;<?php echo number_format($cart->Total, 2, '.', ','); ?></td>
				</tr>
			</table>

			<br />
			<table border="0" width="100%" cellpadding="0" cellspacing="0">
				<tr>
				  <td width="150" valign="top">
				  <?php if($cart->TotalLines > 0){ ?>
				  <p><input name="action" type="submit" class="btn" id="action" value="update" /></p>
				  <?php } ?>
			      <p>&nbsp;</p></td>
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
								<th>Tax Exemption: </th>
							    <th width="20">&nbsp;</th>
							    <th colspan="2">Tax &amp; Shipping</th>
						    </tr>
							<tr>
							  <td rowspan="7" valign="top"><p>If you have a VAT/Tax exemption code please enter it below:</p>
							      <p>Tax Exemption Code:<br />
							         <?php echo $form->GetHTML('taxexemptcode'); ?>
</p>
						        <p>&nbsp; </p></td>
								<td width="20" rowspan="7">&nbsp;</td>
								<td>Delivery Option:</td>
								<td align="right">
									<?php echo $cart->PostageOptions; ?>								</td>
							</tr>
							<tr>
							    <td>Shipping:</td>
								<td align="right">
										<?php
															if($cart->FoundPostage && $cart->IsCustomShipping=='N'){
																echo '&pound;' . number_format($cart->ShippingTotal, 2, ".", ",");
															} else {
																echo "Select Postage Option";
															}
														?></td>
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
							?>

							<tr>
							    <td>Discount:</td>
								<td align="right">-&pound;<?php echo number_format($cart->Discount, 2, ".", ","); ?></td>
							</tr>
							<tr>
							    <td>VAT:</td>
								<td align="right">&pound;<?php echo number_format($cart->TaxTotal, 2, ".", ","); ?></td>
							</tr>
							<tr>
							    <td>Total:</td>
								<td align="right">&pound;<?php echo number_format($cart->Total, 2, ".", ","); ?></td>
							</tr>
						</table>


					<?php
						if($cart->FoundPostage || $cart->IsCustomShipping == 'Y'){
							?>
								<br />
								<input type="hidden" name="shipTo" value="<?php echo $_REQUEST['shipTo']; ?>" />

								<input type="submit" class="btn" name="proceed" value="Quote" />
								<input type="submit" class="btn" name="proceed" value="Order" />

					<?php
							}
						} else {
					?>
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
					<?php 	}
					}
					?>
				</tr>
			</table>
			<br />

			<?php echo $form->Close(); ?>

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
									<a href="order_product.php?pid=<?php echo $product->ID; ?>" title="Click to View <?php echo $product->Name; ?>"><strong><?php echo $product->Name; ?></strong></a><br />
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
			?>

	</td>
  </tr>
</table>

<script language="javascript" type="text/javascript">
var discountSlider = new Slider(document.getElementById("discount-slider"), document.getElementById("discount-slider-input"));
discountSlider.setMaximum(100);
discountSlider.setValue(0);
discountSlider.recalculate();

var markupSlider = new Slider(document.getElementById("markup-slider"), document.getElementById("markup-slider-input"));
markupSlider.setMaximum(1000);
markupSlider.setMinimum(0);
markupSlider.setValue(0);
markupSlider.recalculate();

var discountInput = document.getElementById("discount-input");

discountInput.onchange = function () {
	discountSlider.setValue(parseInt(this.value));
}

var markupInput = document.getElementById("markup-input");

markupInput.onchange = function () {
	markupSlider.setValue(parseInt(this.value));
}

var form = document.getElementById("summaryForm");
var element = null;
var costs = new Array();
var prices = new Array();

discountSlider.onchange = function () {
	discountInput.value = discountSlider.getValue();

	if (typeof window.onchange == "function") {
		window.onchange();
	}

	for(var i = 0; i < form.elements.length; i++) {
		element = form.elements[i];

		switch(element.type) {
			case 'text':
				if(element.name.length >= 9) {
					if(element.name.substr(0, 9) == 'discount_') {

						element.value = discountSlider.getValue();

						verifyDiscount(element.name.substr(9, element.name.length));
					}
				}
				break;
		}
	}
}

markupSlider.onchange = function () {
	var newPrice = 0;

	markupInput.value = markupSlider.getValue();

	if (typeof window.onchange == "function") {
		window.onchange();
	}

	for(var i = 0; i < form.elements.length; i++) {
		element = form.elements[i];

		switch(element.type) {
			case 'text':
				if(element.name.length >= 9) {
					if(element.name.substr(0, 9) == 'discount_') {
						if(costs[element.name.substr(9, element.name.length)] > 0) {
							newPrice = (costs[element.name.substr(9, element.name.length)] / 100) * (markupSlider.getValue() + 100);

							discount = 100 - (newPrice / prices[element.name.substr(9, element.name.length)]) * 100;
							discount = (discount < 0) ? 0 : discount;
							discount = (discount > 100) ? 100 : discount;

							element.value = discount;

							verifyDiscount(element.name.substr(9, element.name.length));
						}
					}
				}
				break;
		}
	}
}

for(var i = 0; i < form.elements.length; i++) {
	element = form.elements[i];

	switch(element.type) {
		case 'hidden':
			if(element.name.length >= 5) {
				if(element.name.substr(0, 5) == 'cost_') {
					costs[element.name.substr(5, element.name.length)] = element.value;
				} else if(element.name.substr(0, 6) == 'price_') {
					prices[element.name.substr(6, element.name.length)] = element.value;
				}
			}
			break;
	}
}

resizeSliders = function() {
	discountSlider.recalculate();
	markupSlider.recalculate();
}

window.onresize = resizeSliders();
</script>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>