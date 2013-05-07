<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Payment.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CustomerContact.php");

class PaymentProcessor {
	var $IsTestMode;
	var	$ProtocolVersion = "2.23";
	var $Gateway = null;
	
	var $Cart = null;
	var $Order = null;
	var $Payment;
	
	// URLS for transactions
	var $PurchaseURL;
	var $AuthoriseURL;
	var $RefundURL;
	var $RepeatURL;
	var $ReleaseURL;
	var $CancelURL;
	var $TargetURL;
	var $VoidURL;
	var $AbortURL;
	var $URL;

	var $Data = array();
	var $Response = array();
	
	// available transactions types are AUTHENTICATE, AUTHORISE, REPEAT, REFUND, or CANCEL
	
	var $TransactionReference; // VendorTxCode
	var $Secure3DStatus; // Optional, up to 15 characters. 3D secure status.

	public $Error;
	public $IsConnectivityError;

	public function __construct($vendor, $testMode = 'N'){
		$this->IsTestMode = $testMode;
		
		$this->Payment = new Payment();
		$this->SetUrls();
		$this->Reset();
		
		// set overloaded properies and defaults directly into Data
		$this->VPSProtocol = $this->ProtocolVersion;
		$this->Vendor = $vendor;
		$this->Currency = 'GBP';
		$this->AllowGiftAid = 0;
		$this->ApplyAVSCV2 = 0;
		$this->Apply3DSecure = 0;
		$this->Profile = 'NORMAL';
		$this->AccountType = 'E';
	}

	public function Reset() {
		$this->Error = array();
		$this->IsConnectivityError = false;
	}

	function PreAuthorise(){
		$this->TargetURL = $this->PurchaseURL;
		$this->setTransactionType('AUTHENTICATE');
		return $this->Execute();
	}

	function Authorise(&$oldPayment){
		$this->TargetURL = $this->AuthoriseURL;
		$this->setTransactionType('AUTHORISE');
		$this->Payment->IsMoto = $oldPayment->IsMoto;
		$this->Data['RelatedVPSTxId'] = $oldPayment->Reference;		// Original VPSTxID of transaction
		$this->Data['RelatedVendorTxCode'] = $oldPayment->ID;			// Original VendorTxCode
		$this->Data['RelatedSecurityKey'] = $oldPayment->SecurityKey;	// Original Security Key
		return $this->Execute();
	}

	function ChargeCard(){
		$this->TargetURL = $this->PurchaseURL;
		$this->setTransactionType('PAYMENT');
		return $this->Execute();
	}

	function Cancel(&$oldPayment) {
		$this->TargetURL = $this->CancelURL;
		$this->setTransactionType('CANCEL');
		$this->Payment->IsMoto = $oldPayment->IsMoto;
		$this->Data['VPSTxId'] = $oldPayment->Reference;		// Original VPSTxID of order
		$this->Data['SecurityKey'] = $oldPayment->SecurityKey;	// Original Security Key
		$this->Data['VendorTxCode'] = $oldPayment->ID;			// Original TransactionTxCode of authentication
		return $this->Execute();
	}

	function Repeat(&$oldPayment){
		$this->TargetURL = $this->RepeatURL;
		$this->setTransactionType('REPEAT');
		$this->Payment->IsMoto = $oldPayment->IsMoto;
		$this->Data['RelatedVPSTxId'] = $oldPayment->Reference;			// Original VPSTxID of order
		$this->Data['RelatedVendorTxCode'] = $oldPayment->ID;			// Original VendorTxCode
		$this->Data['RelatedSecurityKey'] = $oldPayment->SecurityKey;	// Original Security Key
		$this->Data['RelatedTxAuthNo'] = $oldPayment->AuthorisationNumber; // Original Transaction Auth. number
		return $this->Execute();
	}

	function RefundCard(&$oldPayment){
		$this->TargetURL = $this->RefundURL;
		$this->setTransactionType('REFUND');
		$this->Payment->IsMoto = $oldPayment->IsMoto;
		$this->Data['RelatedVPSTxId'] = $oldPayment->Reference;			// Original VPSTxID of order
		$this->Data['RelatedVendorTxCode'] = $oldPayment->ID;			// Original VendorTxCode
		$this->Data['RelatedSecurityKey'] = $oldPayment->SecurityKey;	// Original Security Key
		$this->Data['RelatedTxAuthNo'] = $oldPayment->AuthorisationNumber; // Original Transaction Auth. number
		return $this->Execute();
	}

	function Execute() {
		$this->Reset();

		$this->Payment->Type = $this->getTransactionType();
		$this->Payment->Amount = $this->Amount;
		$this->Payment->Add();

		$this->TransactionReference = $this->Payment->ID;

		$this->PrepareData();
		$this->RequestPost();

		$this->Payment->Status = strtoupper($this->Response['Status']);
		$this->Payment->StatusDetail = $this->Response['StatusDetail'];
		if (isset($this->Response['VPSTxId'])) {
			$this->Payment->Reference = $this->Response['VPSTxId'];
		}
		if (isset($this->Response['SecurityKey'])) {
			$this->Payment->SecurityKey = $this->Response['SecurityKey'];
		}

		switch(strtoupper($this->getTransactionType())) {
			case '3DAUTH':
			case 'AUTHENTICATE':
				break;
			case 'AUTHORISE':
			case 'PAYMENT':
				if ($this->Response['Status'] != '3DAUTH' && $this->Response['Status'] == 'OK') {
					$this->Payment->AuthorisationNumber = $this->Response["TxAuthNo"];
					$this->Payment->AVSCV2 = $this->Response["AVSCV2"];
					$this->Payment->AddressResult = $this->Response["AddressResult"];
					$this->Payment->PostCodeResult = $this->Response["PostCodeResult"];
					$this->Payment->CV2Result = $this->Response["CV2Result"];
				}
				break;
		}

		$this->Payment->Update();

		switch(strtoupper($this->Payment->Status)) {
			case 'ERROR':
			case 'FAIL':
				$this->IsConnectivityError = true;
				break;
		}

		switch(strtoupper($this->Payment->Status)) {
			case 'REGISTERED':
			case 'AUTHENTICATED':
			case 'OK':
			case '3DAUTH':
				return true;

			case 'NOTAUTHED':
				$this->Error[] = 'Sorry, your credit card details did not authorise successfully. Please check that your name, credit card number, card verification number, expiry date, start date and issue number are correct where applicable. It is important that your billing address is the same address that appears on your credit card statement.';
				$this->Error[] = $this->Response["StatusDetail"];
				return false;

			case 'REJECTED':
				$this->Error[] = 'Sorry, your credit card details were rejected. Please check that your name, credit card number, card verification number, expiry date, start date and issue number are correct where applicable. It is important that your billing address is the same address that appears on your credit card statement.';
				$this->Error[] = $this->Response["StatusDetail"];
				return false;

			default:
				$this->Error[] = 'Sorry, an error occured whilst contacting the authorisation server.';
				$this->Error[] = $this->Response["StatusDetail"];
				return false;
		}
	}

	function SetUrls() {
		if(strtoupper($this->IsTestMode) == 'S') {
			$this->AbortURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorAbortTx";
			$this->AuthoriseURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorAuthoriseTx";
			$this->CancelURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorCancelTx";
			$this->PurchaseURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorRegisterTx";
			$this->RefundURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorRefundTx";
			$this->ReleaseURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorReleaseTx";
			$this->RepeatURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorRepeatTx";
			$this->VoidURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorVoidTx";
		} elseif(strtoupper($this->IsTestMode) == 'Y') {
			$this->AbortURL ="https://test.sagepay.com/gateway/service/abort.vsp";
			$this->AuthoriseURL = "https://test.sagepay.com/gateway/service/authorise.vsp";
			$this->CancelURL="https://test.sagepay.com/gateway/service/cancel.vsp";
			$this->PurchaseURL="https://test.sagepay.com/gateway/service/vspserver-register.vsp";
			$this->RefundURL="https://test.sagepay.com/gateway/service/refund.vsp";
			$this->ReleaseURL="https://test.sagepay.com/gateway/service/abort.vsp";
			$this->RepeatURL="https://test.sagepay.com/gateway/service/repeat.vsp";
			$this->VoidURL="https://test.sagepay.com/gateway/service/void.vsp";
		} else {
			$this->AbortURL="https://live.sagepay.com/gateway/service/abort.vsp";
			$this->AuthoriseURL="https://live.sagepay.com/gateway/service/authorise.vsp";
			$this->CancelURL="https://live.sagepay.com/gateway/service/cancel.vsp";
			$this->PurchaseURL="https://live.sagepay.com/gateway/service/vspserver-register.vsp";
			$this->RefundURL="https://live.sagepay.com/gateway/service/refund.vsp";
			$this->ReleaseURL="https://live.sagepay.com/gateway/service/release.vsp";
			$this->RepeatURL="https://live.sagepay.com/gateway/service/repeat.vsp";
			$this->VoidURL="https://live.sagepay.com/gateway/service/void.vsp";
		}
	}

	function FormatData(){
		$output = '';
		foreach($this->Data as $key => $value){
			$output .= "&" . $key . "=". urlencode($value);
		}
		$output = substr($output,1);
		return $output;
	}

	function RequestPost(){
		$url = $this->TargetURL;
		$data = $this->FormatData();

		@set_time_limit(240);

		$output = array();

		$curlSession = curl_init();

		curl_setopt($curlSession, CURLOPT_URL, $url);
		curl_setopt($curlSession, CURLOPT_HEADER, 0);
		curl_setopt($curlSession, CURLOPT_POST, 1);
		curl_setopt($curlSession, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curlSession, CURLOPT_TIMEOUT, 180);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 1);

		//$response = explode(chr(10), curl_exec ($curlSession));
		$response = curl_exec($curlSession);
		$response = explode(chr(10), $response);

		if(curl_error($curlSession)) {
			$output['Status'] = 'FAIL';
			$output['StatusDetail'] = curl_error($curlSession);
		}

		curl_close($curlSession);

		for($i=0; $i<count($response); $i++){
			$splitAt = strpos($response[$i], '=');
			$output[trim(substr($response[$i], 0, $splitAt))] = trim(substr($response[$i], ($splitAt+1)));
		}

		if(!isset($output['Status']) || empty($output['Status'])) {
			$output['Status'] = 'FAIL';
			$output['StatusDetail'] = 'No response information was received.';
		}

		$this->Response = $output;

		return $output;
	}

	function PrepareData() {
		switch($this->getTransactionType()) {
			case 'CANCEL':
				break;
			default:
				$this->Data['VendorTxCode'] = $this->TransactionReference;
				break;
		}
	}
	
/*
	New Stuff from here on in
*/
	function __set($name, $value){
		$this->Data[$name] = $value;
	}
	
	function __get($name){
		if (array_key_exists($name, $this->Data)) {
            return $this->Data[$name];
        }
		return null;
	}
	
// Getting
	function getAuthenticateUrl($portal=''){
		$this->TargetURL = $this->PurchaseURL;
		$this->TxType = 'AUTHENTICATE';
		$this->Profile = 'LOW';
		$this->NotificationURL = ($GLOBALS['USE_SSL'] ? $GLOBALS['HTTPS_SERVER'] : $GLOBALS['HTTP_SERVER']) . $portal . 'paymentNotifications.php';
		$this->PrepareData();
		$this->Execute();		
		if(strtoupper($this->Response['Status']) == 'OK' && isset($this->Response['NextURL'])){
			return $this->Response['NextURL'];
		}
		return false;
	}
	
	function getPaymentStatus($paymentId){
		$payment = new Payment(id_param('VendorTxCode'));
		$strStatus = $payment->Status;
		//Work out what to tell the customer
		if (substr($strStatus,0,8)=="DECLINED"){
			$strReason="Your payment was declined by the bank.  This could be due to insufficient funds, or incorrect card details.";
		} elseif (substr($strStatus,0,9)=="MALFORMED" || substr($strStatus,0,7)=="INVALID") {
			$strReason="The Sage Pay Payment Gateway rejected some of the information provided without forwarding it to the bank.
			Please let us know about this error so we can determine the reason it was rejected.";
		} elseif (substr($strStatus,0,8)=="REJECTED") {
			$strReason="Your order did not meet our minimum fraud screening requirements.
			If you have questions about our fraud screening rules, or wish to discuss this, please contact us.";
		} elseif (substr($strStatus,5)=="ERROR") {
			$strReason="We could not process your order because our Payment Gateway service was experiencing difficulties.";
		 } else {
			$strReason="The transaction process failed.  We please contact us with the details below for us to investigate.";
		}
		return $strReason;
	}
	
	function notify($portal=''){
		$payment = new Payment();
		$payment->ID 			= param("VendorTxCode");
		$payment->Reference		= param("VPSTxId");
		$payment->SecurityKey	= '';
		$eoln 					= chr(13) . chr(10);
		$payment->SecurityKey 	= $payment->getSecurityKey($payment->ID, $payment->Reference);
		
		if (strlen($payment->SecurityKey)==0){
			/** We cannot find a record of this order in the database, so something isn't right **
			** To protect the customer, we should send back an INVALID response.  This will prevent **
			** the Sage Pay Server systems from settling any authorised transactions.  We will also send a **
			** RedirectURL that points to our orderFailure page, passing details of the error **
			** in the Query String so that the page knows how to respond to the customer **/
			header("Content-type: text/html");
			echo "Status=INVALID" . $eoln;
			echo "RedirectURL=" . ($GLOBALS['USE_SSL'] ? $GLOBALS['HTTPS_SERVER'] : $GLOBALS['HTTP_SERVER']) . $portal . "paymentFailed.php?reasonCode=001" . $eoln;
			echo "StatusDetail=Unable to find the transaction in our database." . $eoln;
			exit();
		}
		else
		{
			/** We've found the order in the database, so now we can validate the message **
			** First blank out our result variables **/
			$payment->Get();
			$payment->Order->Get();
			$payment->Status 				= param('Status', '');
			$payment->StatusDetail 			= param('StatusDetail', '');
			$payment->AuthorisationNumber	= param('TxAuthNo', '');
			$payment->AVSCV2				= param('AVSCV2', '');
			$payment->AddressResult			= param('AddressResult', '');
			$payment->AddressStatus			= param('AddressStatus', '');
			$payment->PostcodeResult		= param('PostCodeResult', '');
			$payment->CV2Result				= param('CV2Result', '');
			$payment->Secure3DStatus		= param('3DSecureStatus', '');
			$payment->CAVV					= param('CAVV', '');
			$payment->PayerStatus			= param('PayerStatus', '');
			$payment->CardType				= param('CardType', '');
			$payment->Last4Digits			= param('Last4Digits', '');
			
			/** Now get the VPSSignature value from the POST, and the StatusDetail in case we need it **/
			$VPSSignature 	= param('VPSSignature', '');
			$mySignature	= '';
			$giftAid		= param('GiftAid', '');
			
			/** Retrieve the other fields, from the POST if they are present **/
			
			/*
			
				Test:
				/paymentNotifications.php?Status=OK&StatusDetail=Test&VendorTxCode=352236&VPSTxId={4997870A-801A-1359-9CDD-AA8632D7C3EF}&
			*/
		
			/** Now we rebuilt the POST message, including our security key, and use the MD5 Hash **
			** component that is included to create our own signature to compare with **
			** the contents of the VPSSignature field in the POST.  Check the Sage Pay Server protocol **
			** if you need clarification on this process **/
			$message = $payment->Reference;
			$message .= $payment->ID;
			$message .= $payment->Status;
			$message .= $payment->AuthorisationNumber;
			$message .= $this->Vendor;
			$message .= $payment->AVSCV2;
			$message .= $payment->SecurityKey;
			$message .= $payment->AddressResult;
			$message .= $payment->PostcodeResult;
			$message .= $payment->CV2Result;
			$message .= $giftAid;
			$message .= $payment->Secure3DStatus;
			$message .= $payment->CAVV;
			$message .= $payment->AddressStatus;
			$message .= $payment->PayerStatus;
			$message .= $payment->CardType;
			$message .= $payment->Last4Digits;
		
			$mySignature=strtoupper(md5($message));
		
			/** We can now compare our MD5 Hash signature with that from Sage Pay Server **/
			if ($mySignature!==$VPSSignature)
			{
				/** If the signatures DON'T match, we should mark the order as tampered with, and **
				** send back a Status of INVALID and failure page RedirectURL **/
				$payment->Order->Status = 'Compromised';
				$payment->Order->Update();
		
				header("Content-type: text/plain");
				echo "Status=INVALID" . $eoln;
				echo "RedirectURL=" . ($GLOBALS['USE_SSL'] ? $GLOBALS['HTTPS_SERVER'] : $GLOBALS['HTTP_SERVER']) . $portal . "paymentFailed.php?reasonCode=002" . $eoln;
				echo "StatusDetail=Cannot match the MD5 Hash. Order might be tampered with." . $eoln;
				exit();
			}
			else
			{
				/** Great, the signatures DO match, so we can update the database and redirect the user appropriately **/
				if ($payment->Status=="OK"){
					$payment->StatusDetail="AUTHORISED - The transaction was successfully authorised with the bank.";
				} elseif ($payment->Status=="NOTAUTHED") {
					$payment->StatusDetail="DECLINED - The transaction was not authorised by the bank.";
				} elseif ($payment->Status=="ABORT"){
					$payment->StatusDetail="ABORTED - The customer clicked Cancel on the payment pages, or the transaction was timed out due to customer inactivity.";
				} elseif ($payment->Status=="REJECTED"){
					$payment->StatusDetail="REJECTED - The transaction was failed by your 3D-Secure or AVS/CV2 rule-bases.";
				} elseif ($payment->Status=="AUTHENTICATED"){
					$payment->StatusDetail="AUTHENTICATED - The transaction was successfully 3D-Secure Authenticated and can now be Authorised.";
				} elseif ($payment->Status=="REGISTERED"){
					$payment->StatusDetail="REGISTERED - The transaction was could not be 3D-Secure Authenticated, but has been registered to be Authorised.";
				} elseif ($payment->Status=="ERROR"){
					$payment->StatusDetail="ERROR - There was an error during the payment process.  The error details are: " . $payment->StatusDetail;
				} else {
					$payment->StatusDetail="UNKNOWN - An unknown status was returned from Sage Pay.  The Status was: " . $payment->Status .  ", with StatusDetail:" . $payment->StatusDetail;
				}				
				
				$payment->Update();
		
				/** New reply to Sage Pay Server to let the system know we've received the Notification POST **/
				header("Content-type: text/plain");
				
				/** Always send a Status of OK if we've read everything correctly.  Only INVALID for messages with a Status of ERROR **/
				if ($payment->Status=="ERROR")
					echo "Status=INVALID" . $eoln;
				else{
					echo "Status=OK" . $eoln; 
				}
						
				/** Now decide where to redirect the customer **/
				if ($payment->Status=="OK" || $payment->Status=="AUTHENTICATED" || $payment->Status=="REGISTERED"){
					/** If a transaction status is OK, AUTHENTICATED or REGISTERED, we should send the customer to the success page **/
					$redirectPage="paymentSuccessful.php?VendorTxCode=" . $payment->ID;
					
					if(strtoupper($payment->Order->Status) == 'INCOMPLETE' || strtoupper($payment->Order->Status) == 'UNAUTHENTICATED'){
						$payment->Order->confirmNewOrder();
					}
				} else {
					if(strtoupper($payment->Order->Status) == 'INCOMPLETE'){
						$payment->Order->unauthenticated();
					}
					/** The status indicates a failure of one state or another, so send the customer to orderFailed instead **/
					$redirectPage="paymentFailed.php?VendorTxCode=" . $payment->ID;
				}
				
				if(!empty($payment->CardType) && !empty($payment->Last4Digits)){
					$payment->updateOrderCardDetails();
				}
				
				/** Only use the Internal FQDN value during development.  In LIVE systems, always use the actual FQDN **/
				echo "RedirectURL=" . ($GLOBALS['USE_SSL'] ? $GLOBALS['HTTPS_SERVER'] : $GLOBALS['HTTP_SERVER']) . $portal . $redirectPage . $eoln;
								
				/** No need to send a StatusDetail, since we're happy with the POST **/
				exit();
			}
		}
	}
	
	function getTransactionType(){
		return $this->TxType;
	}
	

// Setting
	function setGateway(&$gateway){
		$this->Vendor = $gateway->VendorName;
		$this->IsTestMode = $gateway->IsTestMode;
		$this->Payment->Gateway->ID = $gateway->ID;
		$this->SetUrls();
	}
	
	function setCart(&$cart){
		$this->Cart = $cart;
		// $this->Basket // not really fussed as this is optional
		// set billing address (now required)
		$this->setCustomer($cart->Customer);
		$this->setBillingContact($cart->Customer->Contact->Person);
		// set delivery address (now required)
		$shipping = $cart->Customer->Contact->Person;
		if(strtolower($cart->ShipTo) != 'billing'){ $shipping = new CustomerContact($cart->ShipTo); }
		$this->setDeliveryContact($shipping);
	}
	
	function setOrder(&$order){
		$this->Order = $order;
		$this->Payment->Order->ID = $order->ID;
		// set customer
		$this->setCustomer($order->Customer);
		// first billing details
		$this->setBillingContact($order->Billing);
		// second delivery details
		$this->setDeliveryContact($order->Shipping);
	}
	
	function setAccountType($type){
		$this->AccountType = $type;
		$this->Payment->IsMoto = ($type == "M" ? "Y" : "N");
	}
	
	function setTransactionType($type){
		$this->TxType = $type;
	}
	
	function setAmount($amount, $currency='GBP'){
		$this->Amount = number_format($amount, 2, '.', '');
		$this->Currency = $currency;
	}
	
	function setDescription($description){
		$this->Description = $description;
	}
	
	function setCustomer($customer){
		if(empty($customer->Contact->ID)) $customer->Get();
		if(empty($customer->Contact->Person->ID)) {
			$customer->Contact->Get();
			$customer->Contact->Person->get();
		}
		$this->ContactNumber = $customer->Contact->Person->Phone1;
		$this->CustomerEMail = $customer->GetEmail();
		$this->ClientNumber = $customer->ID;
		// just in case
		$this->BillingSurname = truncate($customer->Contact->Person->LastName, 20, '');
		$this->BillingFirstnames = truncate($customer->Contact->Person->Name, 20, '');
	}
	
	function setBillingContact($person){
		if(!empty($person->LastName)) $this->BillingSurname = truncate($person->LastName, 20, '');
		if(!empty($person->Name)) $this->BillingFirstnames = truncate($person->Name, 20, '');
		$this->BillingPhone = truncate($person->Phone1, 20, '');
		$this->setBillingAddress($person->Address);
	}
	
	function setBillingAddress($address){
		if(!empty($address->Line1)){
			$this->BillingAddress1 = truncate($address->Line1, 100, '');
		} else {
			$this->BillingAddress1 = truncate($address->Line2, 100, '');
		}
		if(!empty($address->Line1) && !empty($address->Line2)){
			$this->BillingAddress2 = truncate($address->Line2, 100, '');
		} else if(empty($address->Line1) && !empty($address->Line3)){
			$this->BillingAddress2 = truncate($address->Line3, 100, '');
		}
		if(!empty($address->City)) $this->BillingCity = truncate($address->City, 40, '');
		if(empty($address->Country->ISOCode2)) $address->Country->Get();
		if(!empty($address->Country->ISOCode2)) $this->BillingCountry = $address->Country->ISOCode2;

		if (strtoupper($this->BillingCountry) == "US") {
			if (!$address->Region->Code) {
				$address->Region->Get();
			}
			$this->BillingState = $address->Region->Code;
		}

		if(!empty($address->Zip)){
			$this->BillingPostCode = truncate($address->Zip, 10, '');
		}else{
			$this->BillingPostCode = 0;
		}
	}
	
	function setDeliveryContact($person){
		$this->DeliverySurname = $person->LastName;
		$this->DeliveryFirstnames = $person->Name;
		$this->setDeliveryAddress($person->Address);
	}
	
	function setDeliveryAddress($address){
		if(!empty($address->Line1)){
			$this->DeliveryAddress1 = truncate($address->Line1, 100, '');
		} else {
			$this->DeliveryAddress1 = truncate($address->Line2, 100, '');
		}
		if(!empty($address->Line1) && !empty($address->Line2)){
			$this->DeliveryAddress2 = truncate($address->Line2, 100, '');
		} else if(empty($address->Line1) && !empty($address->Line3)){
			$this->DeliveryAddress2 = truncate($address->Line3, 100, '');
		}
		if(!empty($address->City)) $this->DeliveryCity = truncate($address->City, 40, '');
		if(empty($address->Country->ISOCode2)) $address->Country->Get();
		if(!empty($address->Country->ISOCode2)) $this->DeliveryCountry = $address->Country->ISOCode2;

		if (strtoupper($this->DeliveryCountry) == "US") {
			if (!$address->Region->Code) {
				$address->Region->Get();
			}

			$this->DeliveryState = $address->Region->Code;
		}
		
		if(!empty($address->Zip)){
			$this->DeliveryPostCode = truncate($address->Zip, 10, '');
		} else{
			$this->DeliveryPostCode = 0;
		}
	}
}
