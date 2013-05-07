<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartLine.php');

if(!isset($cart)){
	$cart = new Cart($session);
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'set', 'alpha', 3, 3);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('country', 'Country', 'select', $GLOBALS['SYSTEM_COUNTRY'], 'numeric_unsigned', 1, 11, true, 'onChange="propogateRegions(\'region\', this);"');
$country = new DataQuery(sprintf("select Country_ID, Country from countries where Allow_Sales='Y' order by Country"));
$form->AddOption('country', '0', 'Select Country');
$form->AddOption('country', '222', 'United Kingdom (inc. Scotland & N. Ireland)');

while($country->Row){
	$form->AddOption('country', $country->Row['Country_ID'], $country->Row['Country']);
	$country->Next();
}
$country->Disconnect();

 
//$disabled = (!param('region'))?'disabled="disabled"': '';

$form->AddField('region', 'Region', 'select', '0', 'numeric_unsigned', 1, 11, false);
$form->AddOption('region', '0', 'Select Region');


//if(param('region') && ($form->GetValue('country') > 0)) {
	$countryId = $form->GetValue('country');
	$region = new DataQuery(sprintf("select Region_ID, Region_Name from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($countryId)));

	while($region->Row){
		$form->AddOption('region', $region->Row['Region_ID'], $region->Row['Region_Name']);
		$region->Next();
	}
	$region->Disconnect();
//}

if(param('confirm')) {
	if($form->Validate()) {
		$cart->Postage = 0;
		$cart->ShipTo = '';
		$cart->ShippingCountry->ID = $form->GetValue('country');
		$cart->ShippingRegion->ID = $form->GetValue('region');
		$cart->Update();

		redirect("Location: cart.php");
	}
}

require_once('lib/' . $renderer . $_SERVER['PHP_SELF']);
require_once('lib/common/appFooter.php');