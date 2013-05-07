<?php
require_once('../classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerSession.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountCollection.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/GlobalTaxCalculator.php');

$session = new CustomerSession;
$session->Start();

$globalTaxCountry = $GLOBALS['SYSTEM_COUNTRY'];
$globalTaxRegion = $GLOBALS['SYSTEM_REGION'];

if(!empty($cart->BillingCountry->ID)){
	$globalTaxCountry = $cart->ShippingCountry->ID;
	$globalTaxRegion = $cart->ShippingRegion->ID;
}

global $globalTaxCalculator;

$globalTaxCalculator = new GlobalTaxCalculator($globalTaxCountry, $globalTaxRegion);

$dicountCollection = new DiscountCollection();
$dicountCollection->Get($session->Customer);

$order = new Order($_REQUEST['orderid']);
$order->GetLines();

echo "LineID,Name,Quantity,Price\n";

foreach($order->Line as $line){
	if($line->Product->Get()) {
		if($line->Product->Discontinued == 'N') {
			$price = $line->Product->PriceCurrent;
			$shownCustomPrice = false;

			if(count($dicountCollection->Line) > 0){
				list($tempLineTotal, $discountName) = $dicountCollection->DiscountProduct($line->Product, 1);

				if($tempLineTotal < $line->Product->PriceCurrent)  {
					$shownCustomPrice = true;

					$price = $tempLineTotal;
				}
			}

		    echo sprintf("%s,%s,%s,%s\n", $line->ID, $line->Product->Name, $line->Quantity, $price);
		} else {
			echo sprintf("%s,%s,%s,%s\n", $line->ID, 'Discontinued.', 0, '0.00');
		}
	} else {
		echo sprintf("%s,%s,%s,%s\n", $line->ID, 'No longer exists.', 0, '0.00');
	}
}

$GLOBALS['DBCONNECTION']->Close();