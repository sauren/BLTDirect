<?php
require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/common/constants.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactProductTrade.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerSession.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountCollection.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/GlobalTaxCalculator.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EntityController.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TradeBanding.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'packages/MobileESP/mdetect.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignPhoneNumbers.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductReview.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/HolidayPromotion.php');

EntityController::start();

$session = new CustomerSession();
$session->Start();

$action = isset($_REQUEST['action']) ? strtolower($_REQUEST['action']) : null;

if($action == 'logout') {
	$session->Logout();
}

global $cart;
global $globalTaxCalculator;

$cart = new Cart($session);

if(!empty($session->Customer->ID)){
	$session->Customer->Get();
	$session->Customer->Contact->Get();

	$cart->BillingCountry->ID = $session->Customer->Contact->Person->Address->Country->ID;
	$cart->BillingRegion->ID = $session->Customer->Contact->Person->Address->Region->ID;
	
	$discountCollection = new DiscountCollection();
	$discountCollection->Get($session->Customer);
}

if($cart->Customer->ID != $session->Customer->ID){
	$cart->Customer->ID = $session->Customer->ID;
	$cart->ShipTo = '';
	$cart->Update();
}

$globalTaxCalculator = new GlobalTaxCalculator(!empty($cart->ShippingCountry->ID) ? $cart->ShippingCountry->ID : $GLOBALS['SYSTEM_COUNTRY'], !empty($cart->ShippingCountry->ID) ? $cart->ShippingRegion->ID : $GLOBALS['SYSTEM_REGION']);

$mobile = new uagent_info();
$mobileDetected = $mobile->DetectSmartphone();

$renderer = $mobileDetected ? 'mobile' : 'default';

$cart->MobileDetected = $mobileDetected;
$cart->Calculate();

$GLOBALS['Cache'] = array();

include('cacheBrochure.php');
include('cacheCategories.php');
include('cacheLogo.php');

if(!isset($ignoreHeader) || !$ignoreHeader){
	header("Content-Type: text/html; charset=UTF-8");
}

function replace($name)
{
	//$pos = strpos($name, 'https://www.bltdirect.com');

	//$pos1 = strpos($name, 'bltdirect');
	//if($pos !== false){
//		$a='test.bltdirect.com/wsplmobile';
//		$s=explode('com', $name);
//		$link=$a . "" . $s[1];
		return (str_replace("http://www.bltdirect.com", $GLOBALS['MOBILE_LINK'], $name));
	//}
	
}
//function replace1($name)
//{
//	$pos1 = strpos($name, 'http://www.bltdirect.com');
//	
//	if($pos1 !== false){
//		return (str_replace("http://www.bltdirect.com", "http://test.bltdirect.com/wsplmobile", $name));
//	}
//
//}
//function replace2($name)
//{
//	$pos2 = strpos($name, 'http://bltdirect.com');
//	
//	if($pos2 !== false){
//		return (str_replace("http://bltdirect.com", "http://test.bltdirect.com/wsplmobile", $name));
//	}
//}
//	$searchArray = array("www.bltdirect.com", "https://");
//	$replaceArray = array("test.bltdirect.com/wsplmobile", "");
//	return (str_replace($searchArray, $replaceArray, $name));}
ob_start("replace");
//ob_start("replace1");
//ob_start("replace2");