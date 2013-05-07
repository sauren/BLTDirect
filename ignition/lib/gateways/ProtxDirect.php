<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Payment.php");

class PaymentProcessor {
	var $IsTestMode;
	var	$ProtocolVersion = "2.22";
	var $PurchaseURL;
	var $CallbackURL;
	var $AuthoriseURL;
	var $RefundURL;
	var $RepeatURL;
	var $CancelURL;
	var $Vendor;
	var $Currency;
	var $ExternalIPAddress;
	var $InternalIPAddress;
	var $ClientIPAddress;
	var $TargetURL;
	var $Payment;
	var $URL;

	var $Data;
	var $Response;
	var $TransactionType; // TxType (AUTHENTICATE, AUTHORISE, REPEAT, REFUND, or CANCEL)
	var $TransactionReference; // VendorTxCode
	var $Amount; // Amount
	var $Description; // Description (100)

	var $BillingAddress; // Optional, up to 200 characters
	var $BillingPostcode; // Optional, up to 10 characters
	var $DeliveryAddress; // Optional, up to 200 characters
	var $DeliveryPostcode; // Optional, up to 10 characters
	var $ContactNumber; // Optional, up to 20 characters
	var $ContactFax; // Optional, up to 20 characters
	var $CustomerEMail; // Optional, up to 255 characters
	var $Basket; // Optional, up to 7500 characters. See the protocol documentation for details of the basket format.
	var $GiftAidPayment; // Optional, this can be 0 if it is not a gift aid payment, 1 if the customer has agreed to donate the tax.
	var $ApplyAVSCV2; // Optional, this can be 0, 1, 2 or 3. This field allows you to fine tune AVS/CV2 checks, see the protocol documentation for details.
	var $Apply3DSecure; // Optiona, this can be 0, 1, 2 or 3. This field allows you to fine tune the 3D Secure checks at a transaction level, see the protocol documentation for details.
	var $Secure3DStatus; // Optional, up to 15 characters. 3D secure status.
	var $CustomerName; // Optional, the name of customer to whom the goods are ordered, not necessarily the same as the card holder.
	var $AccountType; // Optional, tell the VSP System which merchant account to use for this transaction in situations where more than one type of merchant account is set up, see the protocol documentation for details.

	var $PAReq; // Required, 3D Auth callback page.
	var $PARes; // Required, 3D Auth callback page.
	var $MD; // Required, 3D Auth callback page.

	var $CardHolder;
	var $CardNumber;
	var $StartDate;
	var $ExpiryDate;
	var $CardType;

	var $IssueNumber;
	var $CV2;

	public $Error;
	public $IsConnectivityError;

	public function __construct($vendor, $testMode = 'N'){
		$this->Vendor = $vendor;
		$this->IsTestMode = $testMode;
		$this->Data = array();
		$this->Currency = 'GBP';
		$this->Payment = new Payment();
		$this->SetUrls();

		$this->Reset();
	}

	public function Reset() {
		$this->Error = array();
		$this->IsConnectivityError = false;
	}

	function PreAuthorise(){
		$this->TargetURL = $this->PurchaseURL;
		$this->TransactionType = 'AUTHENTICATE';
		return $this->Execute();
	}

	function Authorise(&$oldPayment){
		$this->TargetURL = $this->AuthoriseURL;
		$this->TransactionType = 'AUTHORISE';
		if(!empty($this->ApplyAVSCV2)) $this->Data['ApplyAVSCV2'] = $this->ApplyAVSCV2;
		$this->Data['RelatedVPSTxId'] = $oldPayment->Reference;		// Original VPSTxID of transaction
		$this->Data['RelatedVendorTxCode'] = $oldPayment->ID;			// Original VendorTxCode
		$this->Data['RelatedSecurityKey'] = $oldPayment->SecurityKey;	// Original Security Key
		return $this->Execute();
	}

	function ChargeCard(){
		$this->TargetURL = $this->PurchaseURL;
		$this->TransactionType = 'PAYMENT';
		if(!empty($this->ApplyAVSCV2)) $this->Data['ApplyAVSCV2'] = $this->ApplyAVSCV2;
		return $this->Execute();
	}

	function Callback(&$oldPayment){
		$this->TargetURL = $this->CallbackURL;
		$this->TransactionType = '3DAUTH';
		if(!empty($this->PARes)) $this->Data['PARes'] = $this->PARes;
		$success = $this->Execute();

		if($success) {
			$oldPayment->Reference = $this->Payment->Reference;
			$oldPayment->SecurityKey = $this->Payment->SecurityKey;
			$oldPayment->Update();
		}

		return $success;
	}

	function Cancel(&$oldPayment) {
		$this->TargetURL = $this->CancelURL;
		$this->TransactionType = 'CANCEL';

		$this->Data['VPSTxId'] = $oldPayment->Reference;		// Original VPSTxID of order
		$this->Data['SecurityKey'] = $oldPayment->SecurityKey;	// Original Security Key
		$this->Data['VendorTxCode'] = $oldPayment->ID;			// Original TransactionTxCode of authentication

		return $this->Execute();
	}

	function Repeat(&$oldPayment){
		$this->TargetURL = $this->RepeatURL;
		$this->TransactionType = 'REPEAT';

		$this->Data['RelatedVPSTxId'] = $oldPayment->Reference;			// Original VPSTxID of order
		$this->Data['RelatedVendorTxCode'] = $oldPayment->ID;			// Original VendorTxCode
		$this->Data['RelatedSecurityKey'] = $oldPayment->SecurityKey;	// Original Security Key
		$this->Data['RelatedTxAuthNo'] = $oldPayment->AuthorisationNumber; // Original Transaction Auth. number

		return $this->Execute();
	}

	function RefundCard(&$oldPayment){
		$this->TargetURL = $this->RefundURL;
		$this->TransactionType = 'REFUND';

		$this->Data['RelatedVPSTxId'] = $oldPayment->Reference;			// Original VPSTxID of order
		$this->Data['RelatedVendorTxCode'] = $oldPayment->ID;			// Original VendorTxCode
		$this->Data['RelatedSecurityKey'] = $oldPayment->SecurityKey;	// Original Security Key
		$this->Data['RelatedTxAuthNo'] = $oldPayment->AuthorisationNumber; // Original Transaction Auth. number

		return $this->Execute();
	}

	function Execute() {
		$this->Reset();

		$this->Payment->Type = $this->TransactionType;
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

		switch(strtoupper($this->TransactionType)) {
			case '3DAUTH':
			case 'AUTHENTICATE':
				if($this->Payment->Status != 'INVALID'){
					$this->Payment->Secure3DStatus = $this->Response["3DSecureStatus"];

					if ($this->Response['Status'] == 'OK') {
						if ($this->Response['3DSecureStatus'] == 'OK') {
							$this->Payment->AuthorisationNumber = $this->Response["TxAuthNo"];
						}
						$this->Payment->CAVV = $this->Response["CAVV"];
					}
				}
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
			$this->PurchaseURL="https://test.sagepay.com/Simulator/VSPDirectGateway.asp";
			$this->CallbackURL="https://test.sagepay.com/Simulator/VSPDirectCallback.asp";
			$this->AuthoriseURL="https://test.sagepay.com/Simulator/VSPServerGateway.asp?Service=VendorAuthroiseTx";
			$this->RefundURL="https://test.sagepay.com/Simulator/VSPServerGateway.asp?Service=VendorRefundTx";
			$this->RepeatURL="https://test.sagepay.com/Simulator/VSPServerGateway.asp?Service=VendorRepeatTx";
			$this->CancelURL="https://test.sagepay.com/Simulator/VSPServerGateway.asp?service=VendorCancelTx";
		} elseif(strtoupper($this->IsTestMode) == 'Y') {
			$this->PurchaseURL="https://test.sagepay.com/gateway/service/vspdirect-register.vsp";
			$this->CallbackURL="https://test.sagepay.com/gateway/service/direct3dcallback.vsp";
			$this->AuthoriseURL="https://test.sagepay.com/gateway/service/authorise.vsp";
			$this->RefundURL="https://test.sagepay.com/gateway/service/refund.vsp";
			$this->RepeatURL="https://test.sagepay.com/gateway/service/repeat.vsp";
			$this->CancelURL="https://test.sagepay.com/gateway/service/cancel.vsp";
		} else {
			$this->PurchaseURL="https://live.sagepay.com/gateway/service/vspdirect-register.vsp";
			$this->CallbackURL="https://live.sagepay.com/gateway/service/direct3dcallback.vsp";
			$this->AuthoriseURL="https://live.sagepay.com/gateway/service/authorise.vsp";
			$this->RefundURL="https://live.sagepay.com/gateway/service/refund.vsp";
			$this->RepeatURL="https://live.sagepay.com/gateway/service/repeat.vsp";
			$this->CancelURL="https://live.sagepay.com/gateway/service/cancel.vsp";
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
		$this->Data['VPSProtocol'] = $this->ProtocolVersion;
		$this->Data['TxType'] = $this->TransactionType;
		$this->Data['Vendor'] = $this->Vendor;

		switch($this->TransactionType) {
			case 'CANCEL':
				break;
			default:
				$this->Data['VendorTxCode'] = $this->TransactionReference;
				$this->Data['Amount'] = number_format($this->Amount, 2, '.', '');
				$this->Data['Description'] = $this->Description;
				$this->Data['Currency'] = $this->Currency;

				if(!empty($this->ContactNumber)) $this->Data['ContactNumber'] = $this->ContactNumber;
				if(!empty($this->ContactFax)) $this->Data['ContactFax'] = $this->ContactFax;
				if(!empty($this->CustomerEMail)) $this->Data['CustomerEMail'] = $this->CustomerEMail;
				if(!empty($this->Basket)) $this->Data['Basket'] = $this->Basket;
				if(!empty($this->GiftAidPayment)) $this->Data['GiftAidPayment'] = $this->GiftAidPayment;
				if(!empty($this->Apply3DSecure)) $this->Data['Apply3DSecure'] = $this->Apply3DSecure;
				if($this->IssueNumber == 0 || !empty($this->IssueNumber)) $this->Data['IssueNumber'] = $this->IssueNumber;
				if(!empty($this->CV2)) $this->Data['CV2'] = $this->CV2;
				if(!empty($this->ClientIPAddress)) $this->Data['ClientIPAddress'] = $this->ClientIPAddress;
				if(!empty($this->AccountType)) $this->Data['AccountType'] = $this->AccountType;
				if(!empty($this->CustomerName)) $this->Data['CustomerName'] = $this->CustomerName;
				if(!empty($this->MD)) $this->Data['MD'] = $this->MD;
				if(!empty($this->PAReq)) $this->Data['PAReq'] = $this->PAReq;

				$this->Data['CardHolder'] = $this->CardHolder;
				$this->Data['CardNumber'] = $this->CardNumber;
				$this->Data['StartDate'] = $this->StartDate;
				$this->Data['ExpiryDate'] = $this->ExpiryDate;
				$this->Data['CardType'] = $this->CardType;
				$this->Data['BillingAddress'] = $this->BillingAddress;
				$this->Data['BillingPostCode'] = preg_replace('/([^\w\s]|_)/','',$this->BillingPostcode);
				$this->Data['DeliveryAddress'] = $this->DeliveryAddress;
				$this->Data['DeliveryPostCode'] = $this->DeliveryPostcode;

				break;
		}
	}
}