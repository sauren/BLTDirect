<?php
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/XmlBuilder.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');

class GoogleCheckout {
	var $type; // xml or html
	var $cart;
	var $currency; // GBP
	var $merchantId;
	var $merchantKey;
	var $xmlns = 'http://checkout.google.com/schema/2';
	var $continueShoppingUrl;
	var $editCartUrl;
	var $defaultTaxRate;
	var $merchantCalculationsUrl;

	function GoogleCheckout(&$cart) {
		$this->cart = &$cart;
		$this->currency = 'GBP';

		$this->defaultTaxRate = $GLOBALS['GOOGLE_CHECKOUT_DEFAULT_TAX'];
		$this->merchantId = ($GLOBALS['GOOGLE_CHECKOUT_LIVE']) ? $GLOBALS['GOOGLE_CHECKOUT_LIVE_MERCHANT_ID'] : $GLOBALS['GOOGLE_CHECKOUT_SANDBOX_MERCHANT_ID'];
		$this->merchantKey = ($GLOBALS['GOOGLE_CHECKOUT_LIVE']) ? $GLOBALS['GOOGLE_CHECKOUT_LIVE_MERCHANT_KEY'] : $GLOBALS['GOOGLE_CHECKOUT_SANDBOX_MERCHANT_KEY'];
	}

	function getCartXml() {
		$attributes = array();
		$attributes['xmlns'] = $this->xmlns;
		$xmlRoot = new XmlElement('checkout-shopping-cart', null, $attributes);
		$xmlCart = new XmlElement('shopping-cart');
		$xmlFlow = new XmlElement('checkout-flow-support');
		$xmlMerchantFlow = new XmlElement('merchant-checkout-flow-support');

		$defaultTax = - 1;

		$xmlItems = new XmlElement('items');
		foreach ($this->cart->Line as $index => $line) {
			$xmlItem = new XmlElement('item');
			$xmlItem->AddChild('merchant-item-id', null, null, $line->Product->ID);
			$xmlItem->AddChild('item-name', null, null, $line->Product->Name);
			$xmlItem->AddChild('item-description', null, null, $line->Product->Blurb);
			$attributes = array();
			$attributes['currency'] = $this->currency;
			$xmlItem->AddChild('unit-price', null, $attributes, $line->Price - ($line->Discount / $line->Quantity));
			$xmlItem->AddChild('quantity', null, null, $line->Quantity);

			if ($line->Product->TaxClass->ID == 0) {
				if ($defaultTax == - 1) {
					$data = new DataQuery(sprintf("SELECT Tax_Class_ID FROM tax_class WHERE Is_Default='Y'"));
					if ($data->TotalRows > 0) {
						$defaultTax = $data->Row['Tax_Class_ID'];
					} else {
						$defaultTax = 0;
					}
					$data->Disconnect();
				}

				$line->Product->TaxClass->ID = $defaultTax;
			}

			$xmlItem->AddChild('tax-table-selector', null, null, $line->Product->TaxClass->ID);
			$xmlItems->AddChildElement($xmlItem);
		}
		$xmlCart->AddChildElement($xmlItems);

		$xmlPrivateData = new XmlElement('merchant-private-data');
		$xmlPrivateData->AddChild('order-note', null, null, 'Google Checkout Order.');
		$xmlPrivateData->AddChild('session-data', null, null, session_id());
		$xmlPrivateData->AddChild('order-coupon', null, null, $this->cart->Coupon->ID);
		$xmlPrivateData->AddChild('customer-id', null, null, $this->cart->Customer->ID);
		$xmlCart->AddChildElement($xmlPrivateData);

		$xmlShippingMethods = new XmlElement('shipping-methods');
		foreach ($this->cart->ShippingCalculator->AvailablePostage as $index => $postage) {
			$attributes = array();
			$attributes['name'] = $postage->Name;
			$xmlShipping = new XmlElement('merchant-calculated-shipping', null, $attributes);
			$attributes = array();
			$attributes['currency'] = $this->currency;
			$xmlShipping->AddChild('price', null, $attributes, number_format($postage->Total, 2));
			$xmlShippingMethods->AddChildElement($xmlShipping);
		}
		$xmlMerchantFlow->AddChildElement($xmlShippingMethods);

		$xmlMerchantFlow->AddChild('continue-shopping-url', null, null, $this->continueShoppingUrl);

		$roundingPolicy = new XmlElement('rounding-policy');
		$roundingPolicy->AddChild('mode', null, null, 'HALF_UP');
		$roundingPolicy->AddChild('rule', null, null, 'PER_LINE');
		$xmlMerchantFlow->AddChildElement($roundingPolicy);

		$xmlMerchantFlow->AddChild('edit-cart-url', null, null, $this->editCartUrl);

		$xmlMerchantCalculations = new XmlElement('merchant-calculations');
		$xmlMerchantCalculations->AddChild('merchant-calculations-url', null, null, $this->merchantCalculationsUrl);
		$xmlMerchantCalculations->AddChild('accept-merchant-coupons', null, null, 'false');
		$xmlMerchantCalculations->AddChild('accept-gift-certificates', null, null, 'false');
		$xmlMerchantFlow->AddChildElement($xmlMerchantCalculations);

		$attributes = array();
		$attributes['merchant-calculated'] = 'true';
		$xmlTaxTables = new XmlElement('tax-tables', null, $attributes);

		$xmlDefaultTaxTable = new XmlElement('default-tax-table');
		$xmltaxRules = new XmlElement('tax-rules');
		$xmlDefaultTaxRule = new XmlElement('default-tax-rule');
		$xmlDefaultTaxRule->AddChild('shipping-taxed', null, null, 'true');
		$xmlDefaultTaxRule->AddChild('rate', null, null, $this->defaultTaxRate);
		$xmlTaxArea = new XmlElement('tax-area');
		$xmlPostalArea = new XmlElement('postal-area');
		$xmlPostalArea->AddChild('country-code', null, null, 'GB');
		$xmlTaxArea->AddChildElement($xmlPostalArea);
		$xmlDefaultTaxRule->AddChildElement($xmlTaxArea);
		$xmltaxRules->AddChildElement($xmlDefaultTaxRule);
		$xmlDefaultTaxTable->AddChildElement($xmltaxRules);
		$xmlTaxTables->AddChildElement($xmlDefaultTaxTable);

		$xmlAlternativeTaxTables = new XmlElement('alternate-tax-tables');

		$geozoneData = array();
		$geozoneQuery = new DataQuery('SELECT c.Country, c.ISO_CODE_2, r.Region_Name, r.Region_Code, g.Geozone_ID FROM geozone_assoc g inner join countries as c on g.Country_ID=c.Country_ID left join regions as r on g.Region_ID=r.Region_ID');
		while ($geozoneQuery->Row) {
			// create geozone entry if it doesn't already exist
			if (! array_key_exists($geozoneQuery->Row['Geozone_ID'], $geozoneData))
				$geozoneData[$geozoneQuery->Row['Geozone_ID']] = array();
				// create country entry if it doesn't already exist
			if (! array_key_exists($geozoneQuery->Row['ISO_CODE_2'], $geozoneData[$geozoneQuery->Row['Geozone_ID']]))
				$geozoneData[$geozoneQuery->Row['Geozone_ID']][$geozoneQuery->Row['ISO_CODE_2']] = array();

			if ($geozoneQuery->Row['ISO_CODE_2'] == 'US') {
				if (! in_array($geozoneQuery->Row['Region_Code'], $geozoneData[$geozoneQuery->Row['Geozone_ID']][$geozoneQuery->Row['ISO_CODE_2']])) {
					$geozoneData[$geozoneQuery->Row['Geozone_ID']][$geozoneQuery->Row['ISO_CODE_2']][] = $geozoneQuery->Row['Region_Code'];
				}
			}
			$geozoneQuery->Next();
		}
		$geozoneQuery->Disconnect();

		$sql = 'select * from tax_class';
		$data = new DataQuery($sql);
		while ($data->Row) {
			$attributes = array();
			$attributes['standalone'] = 'true';
			$attributes['name'] = $data->Row['Tax_Class_ID'];
			$xmlAltTaxTable = new XmlElement('alternate-tax-table', null, $attributes);
			$xmlAltTaxRules = new XmlElement('alternate-tax-rules');

			$sqlRule = 'select t.* from tax as t where Tax_Class_ID=' . $data->Row['Tax_Class_ID'];
			$ruleData = new DataQuery($sqlRule);
			while ($ruleData->Row) {

				// go through each country except US in the geozone entries
				if(isset($geozoneData[$ruleData->Row['Geozone_ID']])) {
					foreach ($geozoneData[$ruleData->Row['Geozone_ID']] as $index => $zoneData) {
						if ($index == 'US') {
							if (count($zoneData) == 1 && empty($zoneData[0])) {
								$xmlAltTaxRule = new XmlElement('alternate-tax-rule');
								$xmlAltTaxRule->AddChild('rate', null, null, ($ruleData->Row['Tax_Rate'] > 0) ? ($ruleData->Row['Tax_Rate'] / 100) : '0.00');
								$xmlTaxArea = new XmlElement('tax-area');
								$attributes = array();
								$attributes['country-area'] = 'ALL';
								$xmlTaxArea->AddChild('us-country-area', null, $attributes);
								$xmlAltTaxRule->AddChildElement($xmlTaxArea);
								$xmlAltTaxRules->AddChildElement($xmlAltTaxRule);

							} else {
								for ($i = 0; $i < count($zoneData); $i++) {
									$xmlAltTaxRule = new XmlElement('alternate-tax-rule');
									$xmlAltTaxRule->AddChild('rate', null, null, ($ruleData->Row['Tax_Rate'] > 0) ? ($ruleData->Row['Tax_Rate'] / 100) : '0.00');
									$xmlTaxArea = new XmlElement('tax-area');
									$xmlZone = new XmlElement('us-state-area');
									$xmlZone->AddChild('state', null, null, $zoneData[$i]);
									$xmlTaxArea->AddChildElement($xmlZone);
									$xmlAltTaxRule->AddChildElement($xmlTaxArea);
									$xmlAltTaxRules->AddChildElement($xmlAltTaxRule);
								}
							}
						} else {
							$xmlAltTaxRule = new XmlElement('alternate-tax-rule');
							$xmlAltTaxRule->AddChild('rate', null, null, ($ruleData->Row['Tax_Rate'] > 0) ? ($ruleData->Row['Tax_Rate'] / 100) : '0.00');
							$xmlTaxArea = new XmlElement('tax-area');
							$xmlZone = new XmlElement('postal-area');
							$xmlZone->AddChild('country-code', null, null, $index);
							$xmlTaxArea->AddChildElement($xmlZone);
							$xmlAltTaxRule->AddChildElement($xmlTaxArea);
							$xmlAltTaxRules->AddChildElement($xmlAltTaxRule);
						}
					}
				}

				$ruleData->Next();
			}
			$ruleData->Disconnect();

			$xmlAltTaxTable->AddChildElement($xmlAltTaxRules);
			$xmlAlternativeTaxTables->AddChildElement($xmlAltTaxTable);
			$data->Next();
		}
		$data->Disconnect();

		$xmlTaxTables->AddChildElement($xmlAlternativeTaxTables);

		$xmlMerchantFlow->AddChildElement($xmlTaxTables);
		$xmlFlow->AddChildElement($xmlMerchantFlow);
		$xmlRoot->AddChildElement($xmlCart);
		$xmlRoot->AddChildElement($xmlFlow);

		return $xmlRoot->ToString();
	}

	function encryptCartXml($data) {
		$key = $this->merchantKey;
		$blocksize = 64;
		$hashfunc = 'sha1';
		if (strlen($key) > $blocksize) {
			$key = pack('H*', $hashfunc($key));
		}
		$key = str_pad($key, $blocksize, chr(0x00));
		$ipad = str_repeat(chr(0x36), $blocksize);
		$opad = str_repeat(chr(0x5c), $blocksize);
		$hmac = pack('H*', $hashfunc(($key ^ $opad) . pack('H*', $hashfunc(($key ^ $ipad) . $data))));
		return $hmac;
	}

	function getForm() {
		$xml = $this->getCartXml();
		$signature = base64_encode($this->encryptCartXml($xml));

		$str = sprintf('<form method="post" action="https://%s/api/checkout/v2/checkout/Merchant/%s">', ($GLOBALS['GOOGLE_CHECKOUT_LIVE']) ? 'checkout.google.com' : 'sandbox.google.com/checkout', $this->merchantId);
		$str .= sprintf('<input type="hidden" name="cart" value="%s" />', base64_encode($xml));
		$str .= sprintf('<input type="hidden" name="signature" value="%s" />', $signature);
		$str .= sprintf('<input type="image" name="Google Checkout" alt="Fast checkout through Google" src="https://%s/buttons/checkout.gif?merchant_id=%s&w=168&h=44&style=white&variant=text&loc=en_GB" height="44" width="168" />', ($GLOBALS['GOOGLE_CHECKOUT_LIVE']) ? 'checkout.google.com' : 'sandbox.google.com/checkout', $this->merchantId);
		$str .= sprintf('</form>');

		return $str;
	}
}