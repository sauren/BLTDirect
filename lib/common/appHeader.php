<?php
session_start();
$mode=$_REQUEST['mode'];
if($mode!=''){
$_SESSION['mode']=$mode;
}
require_once('ignition/lib/classes/ApplicationHeader.php');
require_once 'Mobile_Detect.php';

$detect = new Mobile_Detect;
$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
$scriptVersion = $detect->getScriptVersion();
$check = $detect->isMobile();
$file=$_SERVER['REQUEST_URI'];
$link=ltrim($GLOBALS['MOBILE_LINK'], "/");
$link1=$link . $file;
if($check){
if(!isset($_SESSION['mode'])){
header("location:$link1");
}}
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