<?php
	require_once($GLOBALS['DIR_WS_ADMIN'] . 'services/google-checkout/classes/GoogleLog.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Payment.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/XmlParser.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/XmlBuilder.php');

	class GoogleRequest{
		var $Url;
		var $Log;
		var $merchantId;
		var $xmlns = 'http://checkout.google.com/schema/2';
		var $merchantKey;

		var $_Data;
		var $_Root;
		var $_XmlParser;

		var $ErrorMessage;

		function GoogleRequest(){
			$this->merchantId = ($GLOBALS['GOOGLE_CHECKOUT_LIVE']) ? $GLOBALS['GOOGLE_CHECKOUT_LIVE_MERCHANT_ID'] : $GLOBALS['GOOGLE_CHECKOUT_SANDBOX_MERCHANT_ID'];
			$this->merchantKey = ($GLOBALS['GOOGLE_CHECKOUT_LIVE']) ? $GLOBALS['GOOGLE_CHECKOUT_LIVE_MERCHANT_KEY'] : $GLOBALS['GOOGLE_CHECKOUT_SANDBOX_MERCHANT_KEY'];
			$this->Url = sprintf('https://%s:%s@%s/cws/v2/Merchant/%s/request', $this->merchantId, $this->merchantKey, (($GLOBALS['GOOGLE_CHECKOUT_LIVE']) ? 'checkout.google.com' : 'sandbox.google.com/checkout'), $this->merchantId);
			$this->Log = new GoogleLog;
			$this->_Data = array();
		}

		function Error($message){
			$this->Log->LogError($message);
		}

		function ParseXml($xml=null){
			if(!is_null($xml) && !empty($xml)){
				$this->Log->LogResponse($xml);
				$this->_XmlParser = new XmlParser($xml);
		        $this->_Root = $this->_XmlParser->GetRoot();
		        $this->_Data = $this->_XmlParser->GetData();
		        return true;
			} else {
				return false;
			}
		}

		function chargeOrder($googleOrderId, $orderId, $amount){
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['google-order-number'] = $googleOrderId;
			$xml = new XmlElement('charge-order', null, $attributes);
			$attributes = array();
			$attributes['currency'] = 'GBP';
			$xmlAmount = new XmlElement('amount', null, $attributes, $amount);
			$xml->AddChildElement($xmlAmount);

			if($this->doRequest($xml)) {
				$payment = new Payment();
				$payment->Order->ID = $orderId;
				$payment->Type = 'PAYMENT';
				$payment->SecurityKey = 'GoogleCheckout';
				$payment->Reference = $this->_Data[$this->_Root]['serial-number'];
				$payment->Status = 'INITIATED';
				$payment->StatusDetail = 'Payment initiated through Google Checkout.';
				$payment->Amount = $amount;
				$payment->Add();

				return true;
			}

			return false;
		}

		function cancelOrder($googleOrderId, $reason = 'Unspecified', $comment = ''){
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['google-order-number'] = $googleOrderId;

			$xml = new XmlElement('cancel-order', null, $attributes);
			$xmlReason = new XmlElement('reason', null, null, $reason);
			$xml->AddChildElement($xmlReason);
			$xmlComment = new XmlElement('comment', null, null, $comment);
			$xml->AddChildElement($xmlComment);

			return $this->doRequest($xml);
		}

		function cancelItems($googleOrderId, $cancelItems, $reason = 'Unspecified', $comment = '', $sendMail = true){
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['google-order-number'] = $googleOrderId;

			$xml = new XmlElement('cancel-items', null, $attributes);
			$xmlReason = new XmlElement('reason', null, null, $reason);
			$xml->AddChildElement($xmlReason);
			$xmlComment = new XmlElement('comment', null, null, $comment);
			$xml->AddChildElement($xmlComment);

			$xmlList = new XmlElement('item-ids');

			foreach($cancelItems as $item) {
				$xmlMerchant = new XmlElement('merchant-item-id', null, null, $item);
				$xmlItem = new XmlElement('item-id');
				$xmlItem->AddChildElement($xmlMerchant);

				$xmlList->AddChildElement($xmlItem);
			}

			$xml->AddChildElement($xmlList);

			$xmlMail = new XmlElement('send-email', null, null, ($sendMail) ? 'true' : 'false');
			$xml->AddChildElement($xmlMail);

			return $this->doRequest($xml);
		}

		function refundOrder($googleOrderId, $amount, $reason = '', $comment = ''){
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['google-order-number'] = $googleOrderId;
			$xml = new XmlElement('refund-order', null, $attributes);
			$attributes = array();
			$attributes['currency'] = 'GBP';
			$xmlAmount = new XmlElement('amount', null, $attributes, $amount);
			$xml->AddChildElement($xmlAmount);
			$xmlReason = new XmlElement('reason', null, null, $reason);
			$xml->AddChildElement($xmlReason);
			$xmlComment = new XmlElement('comment', null, null, $comment);
			$xml->AddChildElement($xmlComment);

			return $this->doRequest($xml);
		}

		function addTrackingData($googleOrderId, $carrier = null, $reference = null) {
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['google-order-number'] = $googleOrderId;
			$xml = new XmlElement('add-tracking-data', null, $attributes);

			$xmlTracking = new XmlElement('tracking-data');
			$xmlTrackingCarrier = new XmlElement('carrier', null, null, $carrier);
			$xmlTracking->AddChildElement($xmlTrackingCarrier);
			$xmlTrackingCarrier = new XmlElement('tracking-number', null, null, $reference);
			$xmlTracking->AddChildElement($xmlTrackingCarrier);
			$xml->AddChildElement($xmlTracking);

			return $this->doRequest($xml);
		}

		function deliverOrder($googleOrderId, $carrier = null, $reference = null, $sendMail = true) {
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['google-order-number'] = $googleOrderId;
			$xml = new XmlElement('deliver-order', null, $attributes);

			$xmlTracking = new XmlElement('tracking-data');
			$xmlTrackingCarrier = new XmlElement('carrier', null, null, $carrier);
			$xmlTracking->AddChildElement($xmlTrackingCarrier);
			$xmlTrackingCarrier = new XmlElement('tracking-number', null, null, $reference);
			$xmlTracking->AddChildElement($xmlTrackingCarrier);
			$xml->AddChildElement($xmlTracking);

			$xmlMail = new XmlElement('send-email', null, null, ($sendMail) ? 'true' : 'false');
			$xml->AddChildElement($xmlMail);

			return $this->doRequest($xml);
		}

		function shipItems($googleOrderId, $shipItems, $carrier = null, $reference = null, $sendMail = true) {
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['google-order-number'] = $googleOrderId;
			$xml = new XmlElement('ship-items', null, $attributes);

			$xmlList = new XmlElement('item-shipping-information-list');

			foreach($shipItems as $item) {
				$xmlInfo = new XmlElement('item-shipping-information');

				$xmlMerchant = new XmlElement('merchant-item-id', null, null, $item);
				$xmlItem = new XmlElement('item-id');
				$xmlItem->AddChildElement($xmlMerchant);
				$xmlInfo->AddChildElement($xmlItem);

				$xmlTracking = new XmlElement('tracking-data');

				$xmlTrackingCarrier = new XmlElement('carrier', null, null, $carrier);
				$xmlTracking->AddChildElement($xmlTrackingCarrier);

				$xmlTrackingCarrier = new XmlElement('tracking-number', null, null, $reference);
				$xmlTracking->AddChildElement($xmlTrackingCarrier);

				$xmlTrackingList = new XmlElement('tracking-data-list');
				$xmlTrackingList->AddChildElement($xmlTracking);
				$xmlInfo->AddChildElement($xmlTrackingList);

				$xmlList->AddChildElement($xmlInfo);
			}

			$xml->AddChildElement($xmlList);

			$xmlMail = new XmlElement('send-email', null, null, ($sendMail) ? 'true' : 'false');
			$xml->AddChildElement($xmlMail);

			return $this->doRequest($xml);
		}

		function backorderItems($googleOrderId, $backorderItems, $sendMail = true){
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['google-order-number'] = $googleOrderId;
			$xml = new XmlElement('backorder-items', null, $attributes);

			$xmlList = new XmlElement('item-ids');

			foreach($backorderItems as $item) {
				$xmlMerchant = new XmlElement('merchant-item-id', null, null, $item);
				$xmlItem = new XmlElement('item-id');
				$xmlItem->AddChildElement($xmlMerchant);

				$xmlList->AddChildElement($xmlItem);
			}

			$xml->AddChildElement($xmlList);

			$xmlMail = new XmlElement('send-email', null, null, ($sendMail) ? 'true' : 'false');
			$xml->AddChildElement($xmlMail);

			return $this->doRequest($xml);
		}

		function addMerchantOrderNumber($googleOrderId, $orderId){
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['google-order-number'] = $googleOrderId;
			$xml = new XmlElement('add-merchant-order-number', null, $attributes);
			$xmlOrderNumber = new XmlElement('merchant-order-number', null, null, $orderId);
			$xml->AddChildElement($xmlOrderNumber);

			return $this->doRequest($xml);
		}

		function processOrder($googleOrderId) {
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['google-order-number'] = $googleOrderId;
			$xml = new XmlElement('process-order', null, $attributes);

			return $this->doRequest($xml);
		}

		function authoriseOrder($googleOrderId) {
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['google-order-number'] = $googleOrderId;
			$xml = new XmlElement('authorize-order', null, $attributes);

			return $this->doRequest($xml);
		}

		function archiveOrder($googleOrderId) {
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['google-order-number'] = $googleOrderId;
			$xml = new XmlElement('archive-order', null, $attributes);

			return $this->doRequest($xml);
		}

		function unarchiveOrder($googleOrderId) {
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['google-order-number'] = $googleOrderId;
			$xml = new XmlElement('unarchive-order', null, $attributes);

			return $this->doRequest($xml);
		}

		function sendBuyerMessage($googleOrderId, $message, $sendMail = true) {
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['google-order-number'] = $googleOrderId;
			$xml = new XmlElement('send-buyer-message', null, $attributes);

			$xmlMessage = new XmlElement('message', null, null, $message);
			$xml->AddChildElement($xmlMessage);

			$xmlMail = new XmlElement('send-email', null, null, ($sendMail) ? 'true' : 'false');
			$xml->AddChildElement($xmlMail);

			return $this->doRequest($xml);
		}

		function demoFailure($message = '') {
			$attributes = array();
			$attributes['xmlns'] = $this->xmlns;
			$attributes['message'] = $message;
			$xml = new XmlElement('demo-failure', null, $attributes);

			$this->doRequest($xml);
			return true;
		}

		function doRequest($xml){
			$data = '<?xml version="1.0" encoding="UTF-8"?>';
			$data .= "\n";
			$data .= $xml->ToString();

			$url = $this->Url;
			@set_time_limit(90);
			// Initialise output variable
			$output = array();

			$this->Log->LogRequest($data);

			// Open the cURL session
			$curlSession = curl_init();

			// Set the URL
			curl_setopt ($curlSession, CURLOPT_URL, $url);
			// No headers, please
			curl_setopt ($curlSession, CURLOPT_HEADER, 0);
			// It's a POST request
			curl_setopt ($curlSession, CURLOPT_POST, 1);
			// Set the fields for the POST
			curl_setopt ($curlSession, CURLOPT_POSTFIELDS, $data);
			// Return it direct, don't print it out
			curl_setopt($curlSession, CURLOPT_RETURNTRANSFER,1);
			// This connection will timeout in 60 seconds
			curl_setopt($curlSession, CURLOPT_TIMEOUT, 60);
			// The next two lines must be present for the kit to work with newer version of cURL
			// You should remove them if you have any problems in earluer version of cURL
			curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 1);

			//Send the request and store the response
			$response = curl_exec ($curlSession);

			// Check that a connection was made
			if (curl_error($curlSession)){
				// If it wasn't...
				$output['Status'] = "FAIL";
				$output['StatusDetail'] = curl_error($curlSession);
			}

			// Close the cURL session
			curl_close ($curlSession);

			if($this->ParseXml($response)){
				if(stristr($this->_Root, 'request-received')) {
					return true;
				}

				$this->ErrorMessage = $this->_Data[$this->_Root]['error-message']['VALUE'];
			} else {
				$this->Error('Unable to Parse Google Response XML.');
			}

			return false;
		}
	}
?>
