<?php
require_once('../../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/SupplierProduct.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/WarehouseStock.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ProductPrice.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Manufacturer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerSession.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/GlobalTaxCalculator.php');

class Products {
	var $Title;
	var $Price;
	var $Quantity;

	function Products() {
		$this->Title = array();
		$this->Price = array();
		$this->Quantity = array();
		$this->IntegrationID = array();
	}
}

$session = new CustomerSession();
$session->Start();

global $cart;
global $globalTaxCalculator;

$cart = new Cart($session);

if(!$cart->Exists()) {
	$cart->Add();	
}

if(!empty($session->Customer->ID)){
	$session->Customer->Get();
	$session->Customer->Contact->Get();

	$cart->BillingCountry->ID = $session->Customer->Contact->Person->Address->Country->ID;
	$cart->BillingRegion->ID = $session->Customer->Contact->Person->Address->Region->ID;
}

if($cart->Customer->ID != $session->Customer->ID){
	$cart->Customer->ID = $session->Customer->ID;
	$cart->Update();
}

$globalTaxCountry = (!empty($cart->ShippingCountry->ID)) ? $cart->ShippingCountry->ID : $GLOBALS['SYSTEM_COUNTRY'];
$globalTaxRegion = (!empty($cart->ShippingCountry->ID)) ? $cart->ShippingRegion->ID : $GLOBALS['SYSTEM_REGION'];
$globalTaxCalculator = new GlobalTaxCalculator($globalTaxCountry, $globalTaxRegion);

$cart->Calculate();

if(isset($_REQUEST['shopping'])) {
	$products = unserialize(base64_decode($_REQUEST['shopping']));

	if(is_object($products)) {
		for($i=0;$i<count($products->Title);$i++) {
			if($products->Quantity[$i] > 0) {
				if(isset($products->IntegrationID) && ($products->IntegrationID[$i] > 0)) {
					$cart->AddLine($products->IntegrationID[$i], $products->Quantity[$i]);
				} else {
					$line = new CartLine();
					$line->CartID = $cart->ID;
					$line->IsAssociative = 'Y';
					$line->Quantity = $products->Quantity[$i];

					$data = new DataQuery(sprintf("SELECT Product_ID FROM product WHERE Associative_Product_Title LIKE '%s' LIMIT 0, 1", addslashes(stripslashes($products->Title[$i]))));
					if($data->TotalRows > 0) {
						$line->Product->Get($data->Row['Product_ID']);

						if($products->Price[$i] < $line->Product->PriceCurrent) {
							$discount = number_format(100 - (($products->Price[$i] / $line->Product->PriceCurrent) * 100), 2, '.', '');

							$line->DiscountInformation = sprintf('azxcustom:%s', $discount);
							$line->Discount = ($line->Product->PriceCurrent / 100) * $discount;
						}
					} else {
						$line->Product->ID = 0;
						$line->Discount = -$products->Price[$i];
						$line->AssociativeProductTitle = $products->Title[$i];
					}
					$data->Disconnect();

					$line->Add();
				}
			}
		}

		$cart->Prefix = 'L';
		$cart->Update();
	}
}

redirect(sprintf("Location: ../../cart.php?transferred=true"));