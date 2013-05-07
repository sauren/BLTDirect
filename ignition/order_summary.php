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
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProForma.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProFormaLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountCustomer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProductLocation.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

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

if($action == 'addcustom') {
	$line = new CartLine();
	$line->Quantity = 1;
	$line->CartID = $cart->ID;
	$line->Total = $line->Price * $line->Quantity;
	$line->Tax = $cart->CalculateCustomTax($line->Total);
	$line->Add();

	redirect(sprintf("Location: ?action=view"));

} elseif($action == 'addcatalogue') {
	$line = new CartLine();
	$line->Quantity = 1;
	$line->CartID = $cart->ID;
	$line->Product->Name = 'BLT Direct Catalogue';
	$line->Total = $line->Price * $line->Quantity;
	$line->Tax = $cart->CalculateCustomTax($line->Total);
	$line->Add();

	redirect(sprintf("Location: ?action=view"));
}

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
$form->AddField('schema', 'Apply Discount Schema', 'select', 0, 'numeric_unsigned', 1, 11, false);
$form->AddOption('schema', '0', 'Select Schema');

for($i=0; $i < count($cart->Line); $i++) {
	$cart->Line[$i]->Product->GetDownloads();
	
	$form->AddField(sprintf('location_%d', $cart->Line[$i]->ID), 'Product Location', 'text', '', 'anything', 1, 120, false);
	
	if(!empty($cart->Line[$i]->Product->Download)) {
		$form->AddField(sprintf('downloads_%d', $cart->Line[$i]->ID), 'Attach Spec Sheets', 'checkbox', $cart->Line[$i]->IncludeDownloads, 'boolean', 1, 11, false);	
	}
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
	if($_REQUEST['shipping'] == 'custom'){
		$cart->IsCustomShipping = 'Y';
		$cart->Update();
	} elseif($_REQUEST['shipping'] == 'standard'){
		$cart->IsCustomShipping = 'N';
		$cart->Update();
		$cart->Calculate();
	}
}

if(isset($_REQUEST['proceed'])) {
	$cart->Prefix = 'T';
	$cart->Update();

	if(isset($_REQUEST['accountManager']) && ($_REQUEST['accountManager'] == 'Y')) {
		$cart->Customer->Contact->AccountManager->ID = $GLOBALS['SESSION_USER_ID'];
		$cart->Customer->Contact->Update();
		$cart->Customer->Contact->UpdateAccountManager();
	}

	if(isset($_REQUEST['status']) && ($_REQUEST['status'] > 0)) {
		$cart->Customer->Contact->Status->ID = $_REQUEST['status'];
		$cart->Customer->Contact->Update();
	}

	if(isset($_REQUEST['sendCatalogue']) && ($_REQUEST['sendCatalogue'] == 'Y')) {
		$cart->Customer->Contact->IsCatalogueRequested = 'Y';
		$cart->Customer->Contact->Update();
	}

	$_SESSION['CartCollection'] = (isset($_REQUEST['isCollection']) && ($_REQUEST['isCollection'] == 'Y')) ? 'Y' : 'N';

	if($_REQUEST['proceed'] == "order"){
		for($i=0; $i < count($cart->Line); $i++){
			if($cart->Line[$i]->Product->ID > 0) {
				$cp = new CustomerProduct();
				$cp->Product = $cart->Line[$i]->Product;
				$cp->Customer = $cart->Customer;
				$cp->Add();

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
		}

        redirect("Location: order_takePayment.php");
        
    } elseif($_REQUEST['proceed'] == "quote") {
    	for($i=0; $i < count($cart->Line); $i++){
    		if($cart->Line[$i]->Product->ID > 0) {
				$cp = new CustomerProduct();
				$cp->Product = $cart->Line[$i]->Product;
				$cp->Customer = $cart->Customer;
				$cp->Add();

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
		}

        $quote = new Quote();
		$quote->Prefix = 'T';
		$quote->GenerateFromCart($cart);
		$quote->SendEmail();

        redirectTo('quote_details.php?quoteid=' . $quote->ID);
        
     } elseif($_REQUEST['proceed'] == "pro forma"){
    	for($i=0; $i < count($cart->Line); $i++){
    		if($cart->Line[$i]->Product->ID > 0) {
				$cp = new CustomerProduct();
				$cp->Product = $cart->Line[$i]->Product;
				$cp->Customer = $cart->Customer;
				$cp->Add();

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
		}

        $proforma = new ProForma();
        $proforma->Prefix = 'T';
		$proforma->GenerateFromCart($cart);
		$proforma->SendEmail();
		
		redirectTo('proforma_details.php?proformaid=' . $proforma->ID);

    } elseif($_REQUEST['proceed'] == "sample"){
    	for($i=0; $i < count($cart->Line); $i++){
    		if($cart->Line[$i]->Product->ID > 0) {
				$cp = new CustomerProduct();
				$cp->Product = $cart->Line[$i]->Product;
				$cp->Customer = $cart->Customer;
				$cp->Add();

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
		}

		$order = new Order();
		$order->Sample = 'Y';
		$order->Prefix = 'T';
		$order->GenerateFromCart($cart);

		$order->SendSampleEmail();

		$orderNum = new Cipher($order->ID);
		$orderNum->Encrypt();
		$o = base64_encode($orderNum->Value);

		redirect(sprintf("Location: order_complete.php?o=%s", $o));
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

$countSchemas = 0;

$getSchema = new DataQuery("SELECT Discount_Schema_ID, Discount_Title FROM discount_schema WHERE Discount_Ref LIKE 'DIS-%'");
while($getSchema->Row){
	$form->AddOption('schema', $getSchema->Row['Discount_Schema_ID'], $getSchema->Row['Discount_Title']);
	$countSchemas++;

	$getSchema->Next();
}
$getSchema->Disconnect();

for($i=0; $i < count($cart->Line); $i++){
	$form->AddField('qty_' . $cart->Line[$i]->ID, 'Quantity of ' . (($cart->Line[$i]->IsAssociative == 'N') || ($cart->Line[$i]->Product->ID > 0)) ? $cart->Line[$i]->Product->Name : $cart->Line[$i]->AssociativeProductTitle, 'text', $cart->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="3"');

    $discountVal = '';

	if(!empty($cart->Line[$i]->DiscountInformation)) {
		$discountCustom = explode(':', $cart->Line[$i]->DiscountInformation);

		if(trim($discountCustom[0]) == 'azxcustom') {
			$discountVal = $discountCustom[1];
		}
	}

	if($cart->Line[$i]->Product->DiscountLimit != '' && $discountVal > $cart->Line[$i]->Product->DiscountLimit){
		$discountVal = $cart->Line[$i]->Product->DiscountLimit;
	}

    $form->AddField('freeofcharge_'.$cart->Line[$i]->ID,'Free of Charge','checkbox',$cart->Line[$i]->FreeOfCharge,'boolean',1,1,false);
	$form->AddField('discount_'.$cart->Line[$i]->ID, 'Discount for '. (($cart->Line[$i]->IsAssociative == 'N') || ($cart->Line[$i]->Product->ID > 0)) ? $cart->Line[$i]->Product->Name : $cart->Line[$i]->AssociativeProductTitle, 'text', $discountVal, 'float', 0, 11, false, sprintf('size="1" onkeyup="checkDiscount(%d);"', $cart->Line[$i]->ID));
	$form->AddField('handling_'.$cart->Line[$i]->ID, 'Handling for '. (($cart->Line[$i]->IsAssociative == 'N') || ($cart->Line[$i]->Product->ID > 0)) ? $cart->Line[$i]->Product->Name : $cart->Line[$i]->AssociativeProductTitle, 'text', $cart->Line[$i]->HandlingCharge, 'float', 1, 11, false, 'size="1"');

	$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d ORDER BY Preferred_Supplier ASC, Cost DESC LIMIT 0, 1", mysql_real_escape_string($cart->Line[$i]->Product->ID)));
	$cost = ($data->TotalRows > 0) ? $data->Row['Cost'] : 0;
	$data->Disconnect();

	$form->AddField('cost_'.$cart->Line[$i]->ID, 'Cost price for '. $cart->Line[$i]->Product->Name, 'hidden', $cost, 'float', 1, 11, false);
	
	if(($cart->Line[$i]->IsAssociative == 'N') && ($cart->Line[$i]->Product->ID == 0)) {
		$form->AddField('name_' . $cart->Line[$i]->ID, 'Name for ' . $cart->Line[$i]->Product->Name, 'textarea', $cart->Line[$i]->Product->Name, 'paragraph', 1, 100, true, 'style="font-family: arial, sans-serif;"');
		$form->AddField('price_' . $cart->Line[$i]->ID, 'Price for ' . $cart->Line[$i]->Product->Name, 'text', $cart->Line[$i]->Price, 'float', 1, 11, true, 'size="5"');
	} else {
		$form->AddField('price_'.$cart->Line[$i]->ID, 'Price for '. $cart->Line[$i]->Product->Name, 'hidden', $cart->Line[$i]->Price, 'float', 1, 11, false);
	}
}

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
	if($form->Validate()) {
		for($i=0; $i < count($cart->Line); $i++){
			$discountVal = $form->GetValue('discount_'.$cart->Line[$i]->ID);
			if(strlen($discountVal) > 0) {
				if(($discountVal > 100) || ($discountVal < 0)) {
					$form->AddError('Discount for '.(($cart->Line[$i]->IsAssociative == 'N') || ($cart->Line[$i]->Product->ID > 0)) ? $cart->Line[$i]->Product->Name : $cart->Line[$i]->AssociativeProductTitle.' must be in the range of 0-100%.');
				}
			}
		}
	}

	if($form->Valid) {
		$quantitiesUpdated = false;
		
		for($i=0; $i < count($cart->Line); $i++){
			if(is_numeric($form->GetValue('qty_' . $cart->Line[$i]->ID)) && ($cart->Line[$i]->Quantity != $form->GetValue('qty_' . $cart->Line[$i]->ID)) && $form->GetValue('qty_' . $cart->Line[$i]->ID) > 0) {
				 $cart->Line[$i]->Quantity = $form->GetValue('qty_' . $cart->Line[$i]->ID);
				 $quantitiesUpdated = true;
			}
			
			if(!empty($cart->Line[$i]->Product->Download)) {
				$cart->Line[$i]->IncludeDownloads = $form->GetValue(sprintf('downloads_%d', $cart->Line[$i]->ID));
			}

			$cart->Line[$i]->FreeOfCharge = $form->GetValue('freeofcharge_'.$cart->Line[$i]->ID);
			$cart->Line[$i]->HandlingCharge = $form->GetValue('handling_'.$cart->Line[$i]->ID);

			$discountVal = $form->GetValue('discount_'.$cart->Line[$i]->ID);

			if(strlen($discountVal) > 0) {
				$cart->Line[$i]->DiscountInformation = 'azxcustom:'.$discountVal;
			} else {
				$cart->Line[$i]->DiscountInformation = '';
			}

			if(($cart->Line[$i]->IsAssociative == 'N') && ($cart->Line[$i]->Product->ID == 0)) {
				$cart->Line[$i]->Product->Name = $form->GetValue('name_' . $cart->Line[$i]->ID);
				$cart->Line[$i]->Price = $form->GetValue('price_' . $cart->Line[$i]->ID);
			}

			$cart->Line[$i]->Update();
		}

		if(isset($_REQUEST['setShipping'])){
			$quantitiesUpdated = true;
			$cart->ShippingTotal = $_REQUEST['setShipping'];
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

		if($form->GetValue('schema') > 0) {
			$discount = new DiscountCustomer();
			$discount->CustomerID = $leadCustomerID;
			$discount->DiscountID = $form->GetValue('schema');

			if(!$discount->Exists()) {
				$discount->Add();
			}
		}

		$cart->Calculate();
		$cart->Update();

		redirect("Location: order_summary.php");
	}
} elseif($action == 'removeschema') {
	if(isset($_REQUEST['schema'])) {
		if($_REQUEST['schema'] > 0) {
			$data = new DataQuery(sprintf("DELETE FROM discount_customer WHERE Customer_ID=%d AND Discount_Schema_ID=%d", mysql_real_escape_string($leadCustomerID), mysql_real_escape_string($_REQUEST['schema'])));
			$data->Disconnect();
		}
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

$markup = array();

for($i=0; $i < count($cart->Line); $i++) {
	$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d ORDER BY Preferred_Supplier ASC, Cost DESC LIMIT 0, 1", mysql_real_escape_string($cart->Line[$i]->Product->ID)));
	$cost = ($data->TotalRows > 0) ? $data->Row['Cost'] : 0;
	$data->Disconnect();

	$markup[] = $cart->Line[$i]->ID;
	$markup[] = $cost;
	$markup[] = $cart->Line[$i]->Price;
	if($cart->Line[$i]->Product->DiscountLimit != '' && ($cart->Line[$i]->Product->DiscountLimit >= 0 && $cart->Line[$i]->Product->DiscountLimit <= 100)){
		$markup[] = $cart->Line[$i]->Product->DiscountLimit;
	} else {
		$markup[] = 100;
	}
}

$script = sprintf('<script language="javascript" type="text/javascript">
	var markup = new Array(%s);
	var minMarkup = %s;
	var discountTimeout = new Array();

	var checkDiscount = function(id) {
		for(var i=0; i<discountTimeout.length; i=i+2) {
			if(discountTimeout[i] == id) {
				clearTimeout(discountTimeout[i+1]);

				discountTimeout.splice(i, 2);
				break;
			}
		}

		var tempTimeout = setTimeout(function() {
			verifyDiscount(id);
		}, 500);

		discountTimeout[discountTimeout.length] = id;
		discountTimeout[discountTimeout.length] = tempTimeout;
	}

	var verifyDiscount = function(id) {
		var discount = document.getElementById(\'discount_\' + id);
		var value = 0;
		var minimumPrice = 0;
		var maximumDiscount = 0;

		if(discount) {
			value = parseFloat(discount.value);
			markupValue = 0;
			maximumDiscount = 0;

			if(!isNaN(value)) {
				for(var i=0; i<markup.length; i=i+4) {
					if(markup[i] == id) {
						minimumPrice = markup[i+1] * ((100 + minMarkup) / 100);
						maximumDiscount = (minimumPrice > markup[i+2]) ? 0 : (((minimumPrice * -1) / markup[i+2]) + 1) * 100;
						if(markup[i+3] && maximumDiscount > markup[i+3]){
							maximumDiscount = markup[i+3];
						}

						maximumDiscount = maximumDiscount.toString();

						if(value > maximumDiscount) {
							discount.value = maximumDiscount;
						}

						break;
					}
				}
			}
		}
	}
	</script>', implode(', ', $markup), ($cart->SubTotal > $minimumMarkupThreshold) ? $minimumMarkupAlternative : $minimumMarkup);

$page = new Page('Create A New Order Manually', '');
$page->AddToHead('<link type="text/css" rel="StyleSheet" href="css/slider/luna/luna.css" />');
$page->AddToHead('<link type="text/css" rel="StyleSheet" href="css/Slider.css" />');
$page->AddToHead('<script type="text/javascript" src="js/slider/range.js"></script>');
$page->AddToHead('<script type="text/javascript" src="js/slider/timer.js"></script>');
$page->AddToHead('<script type="text/javascript" src="js/slider/slider.js"></script>');
$page->AddToHead($script);
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

function populateDiscount(lineId, discountAmount) {
	var e = document.getElementById('discount_' + lineId);
	if(e) {
		e.value = discountAmount;
	}
}
</script>

<table width="100%" border="0">
  <tr>
    <td width="300" valign="top"><?php include('./order_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top">
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
						<input type="hidden" name="action" value="editBilling" />
						<input type="hidden" name="contact" value="" />
						<input type="hidden" name="type" value="billing" />
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

				for($i=0; $i < count($cart->Line); $i++){
					echo $form->GetHTML('cost_'.$cart->Line[$i]->ID);

					if(($cart->Line[$i]->IsAssociative == 'Y') || ($cart->Line[$i]->Product->ID > 0)) {
						echo $form->GetHTML('price_'.$cart->Line[$i]->ID);
					}
				}

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

			 if($countSchemas > 0) {
			 	?>
				<br /><br />

				<table cellpadding="0" cellspacing="0" border="0" width="100%">
					<tr>
						<td width="30%">
							<?php
							echo '<strong>'.$form->GetLabel('schema') . '</strong><br />';
				 			echo $form->GetHtml('schema') . '<br /><br />';
				 			?>
			 			</td>
			 			<td>
			 				<?php
			 				$data = new DataQuery(sprintf("SELECT ds.Discount_Title, ds.Discount_Schema_ID FROM discount_schema AS ds INNER JOIN discount_customer AS dc ON dc.Discount_Schema_ID=ds.Discount_Schema_ID WHERE dc.Customer_ID=%d AND ds.Discount_Ref LIKE 'DIS-%%'", mysql_real_escape_string($leadCustomerID)));
			 				while($data->Row) {
			 					?>

				 				<a href="<?php print $_SERVER['PHP_SELF']; ?>?action=removeschema&schema=<?php print $data->Row['Discount_Schema_ID']; ?>"><img border="0" align="absmiddle" src="images/aztector_6.gif" alt="Remove Schema" /></a> <?php print $data->Row['Discount_Title']; ?>

				 				<?php
			 					$data->Next();
			 				}
			 				$data->Disconnect();
			 				?>
			 			</td>
			 		</tr>
			 	</table>

			 	<?php
			}
			?>

			<table cellpadding="0" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="30%">
						<strong>Apply Global Discount</strong><br />
						<input id="discount-input" maxlength="11" size="1" />%
					</td>
					<td>
						&nbsp;<br />
						<div class="slider" id="discount-slider">
							<input class="slider-input" id="discount-slider-input" />
						</div>
					</td>
				</tr>
			</table><br />

			<table cellpadding="0" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="30%">
						<strong>Apply Cost Price Markup</strong><br />
						<input id="markup-input" maxlength="11" size="1" />%
					</td>
					<td>
						&nbsp;<br />
						<div class="slider" id="markup-slider">
							<input class="markup-input" id="markup-slider-input" />
						</div>
					</td>
				</tr>
			</table><br />

			<table cellspacing="0" class="catProducts">
				<tr>
					<th>&nbsp;</th>
					<th>Qty</th>
					<th>Product</th>
					<th>Spec Sheets</th>
					<th>Discount</th>
					<th>Handling</th>
					<th>Max Discount</th>
					<th>Previous Discount</th>
					<th style="text-align: right;">Cost</th>
					<th style="text-align: right;">Price</th>
					<th style="text-align: right;">Discount</th>
					<th style="text-align: right;">Your Price</th>
					<th style="text-align: center;">Free of Charge</th>
					<th style="text-align: right;">Line Total</th>
				</tr>
			<?php
				$cartIds = '';
				$subTotal = 0;

				for($i=0; $i < count($cart->Line); $i++){
					$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d ORDER BY Preferred_Supplier ASC, Cost DESC LIMIT 0, 1", mysql_real_escape_string($cart->Line[$i]->Product->ID)));
					$cost = ($data->TotalRows > 0) ? $data->Row['Cost'] : 0;
					$data->Disconnect();
					?>

				<tr>
					<td><a href="javascript:confirmRemove(<?php echo $cart->Line[$i]->ID; ?>);" onmouseover="MM_displayStatusMsg('Remove <?php echo (($cart->Line[$i]->IsAssociative == 'N') || ($cart->Line[$i]->Product->ID > 0)) ? $cart->Line[$i]->Product->Name : $cart->Line[$i]->AssociativeProductTitle; ?>');return document.MM_returnValue"  onmouseout="MM_displayStatusMsg('');return document.MM_returnValue"><img src="images/icon_trash_1.gif" alt="Remove <?php echo (($cart->Line[$i]->IsAssociative == 'N') || ($cart->Line[$i]->Product->ID > 0)) ? $cart->Line[$i]->Product->Name : $cart->Line[$i]->AssociativeProductTitle; ?>" width="16" height="16" border="0" /></a></td>
                    <td><?php echo $form->GetHTML('qty_' . $cart->Line[$i]->ID); ?></td>
					<td>
						<?php
						if(($cart->Line[$i]->IsAssociative == 'N') || ($cart->Line[$i]->Product->ID > 0)) {
							if($cart->Line[$i]->Product->ID > 0) {
								?>
								<a href="order_product.php?pid=<?php echo $cart->Line[$i]->Product->ID;?>"><?php echo $cart->Line[$i]->Product->Name; ?></a><br />
								<span class="smallGreyText">Quickfind: <?php echo $cart->Line[$i]->Product->ID; ?></span>

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

								<br />
								Location: <?php echo $form->GetHTML('location_'.$cart->Line[$i]->ID); ?>

								<?php
							} else {
								echo $form->GetHTML('name_' . $cart->Line[$i]->ID);
							}
						} else {
							echo $cart->Line[$i]->AssociativeProductTitle;
						}
						?>
					</td>
					<td>
						<?php
						if(!empty($cart->Line[$i]->Product->Download)) {
							echo $form->GetHTML(sprintf('downloads_%d', $cart->Line[$i]->ID), 'Attach Spec Sheets', 'checkbox', 'Y', 'boolean', 1, 11, false);	
						}
						?>
					</td>
					<td nowrap="nowrap">
						<?php
						if($cart->Line[$i]->Product->ID > 0) {
							echo $form->GetHTML('discount_'.$cart->Line[$i]->ID);
							echo '%';
						}
						?>
					</td>
					<td nowrap="nowrap">
						<?php echo $form->GetHTML('handling_'.$cart->Line[$i]->ID); ?>%
					</td>
					<td nowrap="nowrap">
						<?php
						$minimumPrice = $cost * ((100 + (($cart->SubTotal > $minimumMarkupThreshold) ? $minimumMarkupAlternative : $minimumMarkup)) / 100);
						if($cart->Line[$i]->Price > 0){
							if($minimumPrice > $cart->Line[$i]->Price){
								$maximumDiscount = 0;
							} else {
								$maximumDiscount = ((($minimumPrice * -1) / $cart->Line[$i]->Price) + 1) * 100;
							}
						} else {
							$maxmiumDiscount = 0;
						}
						if($cart->Line[$i]->Product->DiscountLimit != '' &&$cart->Line[$i]->Product->DiscountLimit < $maximumDiscount){
							$maximumDiscount = $cart->Line[$i]->Product->DiscountLimit;
						}

						echo sprintf('%s%%', number_format(floor($maximumDiscount), 0, '.', ''));
						?>
		            </td>
					<td nowrap="nowrap">
						<?php
						$data = new DataQuery(sprintf("SELECT ol.Discount_Information FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE o.Customer_ID=%d AND ol.Product_ID=%d AND Discount_Information REGEXP '^azxcustom:[0-9]*' ORDER BY o.Created_On DESC LIMIT 0, 1", mysql_real_escape_string($cart->Customer->ID), mysql_real_escape_string($cart->Line[$i]->Product->ID)));
						if($data->TotalRows > 0) {
							$items = explode(':', $data->Row['Discount_Information']);

							if((count($items) >= 2) && is_numeric($items[1])) {
								echo sprintf('<a href="javascript:populateDiscount(%d, \'%s\');"><img border="0" src="images/aztector_1.gif" align="absmiddle" height="16" width="16" alt="Use Discount" /></a> %s%%', $cart->Line[$i]->ID, number_format($items[1], 0, '.', ''), number_format($items[1], 0, '.', ''));
							}
						}
						$data->Disconnect();
						?>&nbsp;
					</td>
					<td nowrap="nowrap" align="right">
						&pound;
						<?php
						$data = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d ORDER BY Preferred_Supplier ASC, Cost DESC LIMIT 0, 1", mysql_real_escape_string($cart->Line[$i]->Product->ID)));
						$cost = ($data->TotalRows > 0) ? $data->Row['Cost'] : 0;
						$data->Disconnect();

						echo number_format($cost, 2, '.', ',');
						?>
					</td>
					<td nowrap="nowrap" align="right">
						<?php
						if(($cart->Line[$i]->IsAssociative == 'Y') || ($cart->Line[$i]->Product->ID > 0)) {
							echo '&pound;' . number_format($cart->Line[$i]->Price, 2, '.', ',');
						} else {
							echo '&pound;' . $form->GetHTML('price_' . $cart->Line[$i]->ID);
						}
						?>
					</td>
		            <td nowrap="nowrap" align="right">&pound;<?php echo number_format($cart->Line[$i]->Discount / $cart->Line[$i]->Quantity, 2, '.', ','); ?></td>
		            <td nowrap="nowrap" align="right">&pound;<?php echo number_format($cart->Line[$i]->Price - ($cart->Line[$i]->Discount / $cart->Line[$i]->Quantity), 2, '.', ','); ?></td>
					<td nowrap="nowrap" align="center"><?php echo $form->GetHTML('freeofcharge_'.$cart->Line[$i]->ID); ?></td>
		            <td nowrap="nowrap" align="right">&pound;<?php echo number_format($cart->Line[$i]->Total - $cart->Line[$i]->Discount, 2, '.', ','); ?></td>
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
					<td colspan="12" align="center">The Shopping Cart is Empty</td>
				</tr>
			<?php
				}
				?>

				<tr>
					<td colspan="12">Cart Weight: <?php echo $cart->Weight; ?>Kg. <span class="smallGreyText">(Approx.)</span></td>
					<td align="right">Sub Total:</td>
					<td align="right">&pound;<?php echo number_format($cart->SubTotal, 2, '.', ','); ?></td>
				</tr>
			</table>

			<br />
			<table border="0" width="100%" cellpadding="0" cellspacing="0">
				<tr>
				  <td width="50%" valign="top">
						<input type="submit" name="action" class="btn" value="update" />
						<input type="button" name="addcustom" value="add custom" class="btn" onclick="window.location.href='?action=addcustom';" />
						<input type="button" name="addcatalogue" value="add catalogue" class="btn" onclick="window.location.href='?action=addcatalogue';" />			      
			      </td>
				  <td width="50%" valign="top">
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
							  <td rowspan="7" valign="top">
							  	<p>If you have a VAT/Tax exemption code please enter it below:</p>
								<p>Tax Exemption Code:<br /><?php echo $form->GetHTML('taxexemptcode'); ?></p>
								</td>
								<td width="20" rowspan="7">&nbsp;</td>
								<td>Delivery Option:</td>
								<td align="right"><?php echo $cart->PostageOptions; ?></td>
							</tr>
							<tr>
							    <td>Shipping:
                                    <?php
							if($cart->IsCustomShipping == 'N'){
						?>
                                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?shipping=custom">(customise)</a>
                                    <?php } else { ?>
                                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?shipping=standard">(standardise)</a>
                                    <?php } ?></td>
								<td align="right">
										<?php
															if($cart->IsCustomShipping=='Y'){
									?>
									&pound;<input type="text" name="setShipping" value="<?php echo number_format($cart->ShippingTotal, 2, ".", ",");  ?>" size="10" />
									<?php
															}elseif($cart->FoundPostage && $cart->IsCustomShipping=='N'){
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
								<div style="text-align: left;">
									<strong>Account Manager</strong><br />
									<?php
									if($cart->Customer->Contact->AccountManager->ID > 0) {
										$user = new User($cart->Customer->Contact->AccountManager->ID);

										echo trim(sprintf('%s %s<br /><br />', $user->Person->Name, $user->Person->LastName));
									} else {
										echo '<input type="checkbox" name="accountManager" value="Y" id="accountManager" /> (Click to become the current account manager)<br /><br />';
									}
									?>

									<strong>Contact Status</strong><br />
									<select name="status">
										<option value="0"></option>

										<?php
										$data = new DataQuery(sprintf("SELECT * FROM contact_status ORDER BY Name ASC"));
										while($data->Row) {
											echo sprintf('<option value="%d" %s>%s</option>', $data->Row['Contact_Status_ID'], ($cart->Customer->Contact->Status->ID == $data->Row['Contact_Status_ID']) ? 'selected="selected"' : '', $data->Row['Name']);

											$data->Next();
										}
										$data->Disconnect();
										?>

									</select><br /><br />
									
									<strong>Send Catalogue</strong><br />
									<input type="checkbox" name="sendCatalogue" value="Y" />
									<?php 
									if($cart->Customer->Contact->CatalogueSentOn != '0000-00-00 00:00:00'){
									    echo "&nbsp;Catalogue last sent on " . cDatetime($cart->Customer->Contact->CatalogueSentOn, 'shortdate');
									}else{
									    echo "&nbsp;Catalogue has never been sent";
									}
									?><br /><br />

									<strong>Is Collection</strong><br />
									<input type="checkbox" name="isCollection" value="Y" />
								</div>
								<br />

								<input type="hidden" name="shipTo" value="<?php echo $_REQUEST['shipTo']; ?>" />

								<input type="submit" class="btn" name="proceed" value="sample" />
								<input type="submit" class="btn" name="proceed" value="pro forma" />
								<input type="submit" class="btn" name="proceed" value="quote" />
								<input type="submit" class="btn" name="proceed" value="order" />

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
								<td align="right"><input type="button" name="buy" value="Buy" class="submit" onclick="window.self.location.href='customise.php?action=customise&quantity=1&product=<?php echo $product->ID; ?>'" /></td>
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