<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/XmlBuilder.php');

class GoogleMerchantCalculation{
	var $AvailableShipping;
	var $xmlns = 'http://checkout.google.com/schema/2';
	var $currency;

	function GoogleMerchantCalculation(){
		$this->currency = 'GBP';
		$this->AvailableShipping = array();
	}

	function AddShipping($shippingAvailable, $addressId){
		$this->AvailableShipping[$addressId] = $shippingAvailable;
	}

	function GetResults() {
		$attributes = array();
		$attributes['xmlns'] = $this->xmlns;
		$xmlRoot = new XmlElement('merchant-calculation-results', null, $attributes);
		$xmlResults = new XmlElement('results');

		foreach($this->AvailableShipping as $key=>$shippingArray){
			foreach ($shippingArray as $shipping){
				$attributes = array();
				$attributes['shipping-name'] = $shipping->Name;
				$attributes['address-id'] = $key;
				$xmlResult = new XmlElement('result', null, $attributes);

				$attributes = array();
				$attributes['currency'] = $this->currency;
				$xmlRate = new XmlElement('shipping-rate', null, $attributes, number_format($shipping->Total, 2, '.', ''));
				$xmlShippable = new XmlElement('shippable', null, null, 'true');
				$xmlTax = new XmlElement('total-tax', null, $attributes, number_format($shipping->OrderTax, 2, '.', ''));
				$xmlCodes = new XmlElement('merchant-code-results');

				$xmlResult->AddChildElement($xmlRate);
				$xmlResult->AddChildElement($xmlShippable);
				$xmlResult->AddChildElement($xmlTax);
				$xmlResult->AddChildElement($xmlCodes);

				$xmlResults->AddChildElement($xmlResult);
			}
		}

		$xmlRoot->AddChildElement($xmlResults);

		return $xmlRoot->ToString();
	}
}