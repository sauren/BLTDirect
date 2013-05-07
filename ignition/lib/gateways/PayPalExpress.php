<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Payment.php");

class PaymentProcessor {
	var $Version;
	var $IsTestMode;
	var $Password;
	var $User;
	var $Signature;
	var $Data;
	var $Error;
	var $Response;
	var $TargetURL;
	var $SetExpressCheckoutURL;
	var $Token;
	var $Amount;
	var $ReturnURL;
	var $CancelURL;
	var $Email;
	var $Description;
	var $TrackingNumber;
	var $HeaderImage;
	var $isOnAccount;
	var $resArray;
	var $Payment;

	function PaymentProcessor($vendorName = '', $isTestMode = 'N'){
		$this->Version = '3.0';
		$this->IsTestMode = $isTestMode;
		$this->Data = array();
		$this->Payment = new Payment();
		$this->Error = array();
		$this->User = $GLOBALS['PAYPAL_USERNAME'];
		$this->Password = $GLOBALS['PAYPAL_PASSWORD'];
		$this->Signature = $GLOBALS['PAYPAL_SIGNATURE'];
        $this->SetUrls();
	}

	function SetExpressCheckout($paymentAmount, $isOnAccount){
		$this->Amount = $paymentAmount;
		$this->isOnAccount = $isOnAccount;

		$this->Data['METHOD'] = 'SetExpressCheckout';
		$this->Data['RETURNURL'] = $this->ReturnURL;
		$this->Data['CANCELURL'] = $this->CancelURL;

		return $this->Execute();
	}

	function Execute() {
		$this->PrepareData();
		$this->RequestPost();

		return $this->FilterResponse();
	}

	function FilterResponse(){
		$split = explode(" ", $this->Response["Status"]);
		$baseStatus = array_shift($split);

		switch($baseStatus) {
			case 'OK':
				return true;
				break;
			case 'NOTAUTHED':
				$this->Error[] = 'Sorry, your credit card details did not authorise successfully. Please check that your name, credit card number, card verification number, expiry date, start date and issue number are correct where applicable. It is important that your billing address is the same address that appears on your credit card statement.';
				$this->Error[] = $this->Response["StatusDetail"];
				return false;
				break;

			case 'REJECTED':
				$this->Error[] = 'Sorry, your credit card details were rejected. Please check that your name, credit card number, card verification number, expiry date, start date and issue number are correct where applicable. It is important that your billing address is the same address that appears on your credit card statement.';
				$this->Error[] = $this->Response["StatusDetail"];
				return false;
				break;

			case 'FAIL':
				$this->Error[] = 'Sorry, we were unable to connect to the authorisation server. Please try again later.';
				$this->Error[] = $this->Response["StatusDetail"];
				return false;
				break;

			default:
				$this->Error[] = 'Sorry, an error occured whilst contacting the authorisation server.';
				$this->Error[] = $this->Response["StatusDetail"];
				return false;
				break;
		}
	}

	function FormatData(){
		$output = "";

		foreach($this->Data as $key => $value){
			$output .= "&" . $key . "=". urlencode($value);
		}

		$output = substr($output,1);

		return $output;
	}

	function RequestPost(){
		@set_time_limit(90);

		$url = $this->TargetURL;
		$data = $this->FormatData();
		$output = array();
		$curlSession = curl_init();

		curl_setopt($curlSession, CURLOPT_URL, $url);
		curl_setopt($curlSession, CURLOPT_HEADER, 0);
		curl_setopt($curlSession, CURLOPT_POST, 1);
		curl_setopt($curlSession, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curlSession, CURLOPT_TIMEOUT,60);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 1);

		$response = curl_exec($curlSession);

		if(curl_error($curlSession)){
			$output['Status'] = "FAIL";
			$output['StatusDetail'] = curl_error($curlSession);
		} else {
			$intial = 0;

			while(strlen($response)){
				$keypos = strpos($response,'=');
				$valuepos = strpos($response,'&') ? strpos($response,'&') : strlen($response);

				$keyval = substr($response,$intial,$keypos);
				$valval = substr($response,$keypos+1,$valuepos-$keypos-1);

				$output[urldecode($keyval)] = urldecode( $valval);
				$response=substr($response,$valuepos+1,strlen($response));
			}
		}

		$this->Response = $output;

		curl_close($curlSession);

		return $output;
	}

	function SetUrls(){
		if(strtoupper($this->IsTestMode) == 'Y'){
			$this->SetExpressCheckoutURL = "https://api-3t.sandbox.paypal.com/nvp";
		} else {
			$this->SetExpressCheckoutURL = "https://api-3t.paypal.com/nvp";
		}
	}

	function PrepareData(){
		$this->Data['CURRENCYCODE'] = 'GBP';
		$this->Data['LOCALECODE'] = 'GB';
		$this->Data['VERSION'] = $this->Version;
		$this->Data['PWD'] = $this->Password;
		$this->Data['USER'] = $this->User;
		$this->Data['SIGNATURE'] = $this->Signature;

		if(!empty($this->Email)) $this->Data['EMAIL'] = $this->Email;
		if(!empty($this->Description)) $this->Data['DESC'] = $this->Description;
		if(!empty($this->TrackingNumber)) $this->Data['INVNUM'] = $this->TrackingNumber;
		if(!empty($this->HeaderImage)) $this->Data['HDRIMG'] = $this->HeaderImage;
	}

	function LogPaypalPayments($transactionType){

	}

	function PayUsingPaypal($orderId){
		$ack = "FAILURE";

		$data = new DataQuery(sprintf("SELECT * FROM orders WHERE Order_ID=%d", mysql_real_escape_string($orderId)));
		if($data->TotalRows > 0){
			$token =urlencode($data->Row['Token']);
			$paymentAmount =urlencode($this->Amount);
			$payerID = urlencode($data->Row['Payer_ID']);
			$serverName = urlencode($_SERVER['SERVER_NAME']);
			$paymentAction = 'Order';
			$currencyCode = 'GBP';
			$nvpstr='&TOKEN='.$token.'&PAYERID='.$payerID.'&PAYMENTACTION='.$paymentAction.'&CURRENCYCODE='.$currencyCode.'&AMT='.$paymentAmount.'&IPADDRESS='.$serverName ;

			/* Make the call to PayPal to finalize payment
			If an error occured, show the resulting errors
			*/
			$resArray=$this->hash_call("DoExpressCheckoutPayment",$nvpstr);
			$_SESSION['reshash']=$resArray;
			/* Display the API response back to the browser.
			If the response from PayPal was a success, display the response parameters'
			If the response was an error, display the errors received using APIError.php.
			*/
			$ack = strtoupper($resArray["ACK"]);
		}
		$data->Disconnect();

		$this->Payment->Type="PAYMENT";
		$this->Payment->Reference=$resArray['TRANSACTIONID'];
		$this->Payment->Amount=$this->Amount;
		$this->Payment->Status=$ack;
		$this->Payment->StatusDetail=$paymentAction;
		$this->Payment->Add();

		if($ack != "SUCCESS")
		return false;
		else
		return true;
	}

	function AuthorizeUsingPaypal($orderId){
		$ack = "FAILURE";
		//$sql = sprintf("SELECT Payment_Transaction_ID FROM orders WHERE Order_ID=%d",$orderId);
		$sql = sprintf("SELECT MAX(Payment_ID), Reference FROM payment WHERE Order_ID=%d and Transaction_Type='PAYMENT' and Status='SUCCESS' GROUP BY Transaction_Type", mysql_real_escape_string($orderId));
		$data = new DataQuery($sql);
		if($data->TotalRows > 0){


			$transactionId =urlencode($data->Row['Reference']);
			$paymentAmount =urlencode ($this->Amount);
			$currencyCode = 'GBP';
			$nvpstr='&TRANSACTIONID='.$transactionId.'&AMT='.$paymentAmount.'&CURRENCYCODE='.$currencyCode;

			/* Make the call to PayPal to authorize payment
			If an error occured, show the resulting errors
			*/
			$resArray=$this->hash_call("DoAuthorization",$nvpstr);
			$_SESSION['reshash']=$resArray;
			/* Display the API response back to the browser.
			If the response from PayPal was a success, display the response parameters'
			If the response was an error, display the errors received using APIError.php.
			*/
			$ack = strtoupper($resArray["ACK"]);
		}
		$data->Disconnect();

		$this->Payment->Type="AUTHORIZE";
		$this->Payment->Reference=$resArray['TRANSACTIONID'];
		$this->Payment->Amount=$this->Amount;
		$this->Payment->Status=$ack;
		$this->Payment->Add();

		if($ack != "SUCCESS")
			return false;
		else
			return true;
	}

	function CaptureUsingPaypal($orderId){
		$ack = "FAILURE";

		$sql = sprintf("SELECT MAX(Payment_ID), Reference FROM payment WHERE Order_ID=%d and Transaction_Type='PAYMENT' and Status='SUCCESS' GROUP BY Transaction_Type", mysql_real_escape_string($orderId));
		$data = new DataQuery($sql);
		if($data->TotalRows > 0){
			$authorizationId =urlencode($data->Row['Reference']);
			$paymentAmount =urlencode ($this->Amount);
			$currencyCode = 'GBP';
			$completeType = "Complete"; //since we are making complete captures not partial captures.
			$nvpstr='&AUTHORIZATIONID='.$authorizationId.'&AMT='.$paymentAmount.'&COMPLETETYPE='.$completeType.'&CURRENCYCODE='.$currencyCode;

			/* Make the call to PayPal to capture payment
			If an error occured, show the resulting errors
			*/
			$resArray=$this->hash_call("DoCapture",$nvpstr);
			$_SESSION['reshash']=$resArray;
			/* Display the API response back to the browser.
			If the response from PayPal was a success, display the response parameters'
			If the response was an error, display the errors received using APIError.php.
			*/
			$ack = strtoupper($resArray["ACK"]);
		}
		$data->Disconnect();
		$this->Payment->Type="CAPTURE";
		$this->Payment->Reference=$resArray['TRANSACTIONID'];
		$this->Payment->Amount=$this->Amount;
		$this->Payment->Status=$ack;
		$this->Payment->StatusDetail=$completeType;
		$this->Payment->Add();

		if($ack != "SUCCESS")
			return false;
		else
			return true;
	}

	function RefundUsingPaypal($orderId,$refundType="Full"){
		$ack = "FAILURE";

		//$sql = sprintf("SELECT Capture_Transaction_ID,Total FROM orders WHERE Order_ID=%d",$orderId);
		$sql = sprintf("SELECT max(Payment_ID), Reference FROM payment WHERE Order_ID=%d and Transaction_Type='CAPTURE' and Status='SUCCESS' GROUP BY Transaction_Type", mysql_real_escape_string($orderId));
		$data = new DataQuery($sql);
		if($data->TotalRows > 0){

			$captureTransactionId =urlencode($data->Row['Reference']);
			//refundAmount can be either the full order total or a part of it
			//$refundAmount = urlencode($data->Row['Total']);
			$refundAmount = $this->Amount;
			/*If RefundType is set to Full, you must not set Amount; however,
			if RefundType is Partial, you must set Amount
			*/
			if($refundType == "Full")
			$nvpstr='&TRANSACTIONID='.$captureTransactionId.'&REFUNDTYPE='.$refundType;
			else if($refundType == "Partial"){
				$currencyCode = 'GBP';
				$nvpstr='&TRANSACTIONID='.$captureTransactionId.'&REFUNDTYPE='.$refundType.'&AMT='.$refundAmount.'&CURRENCYCODE='.$currencyCode;
			}

			/* Make the call to PayPal to refund payment
			If an error occured, show the resulting errors
			*/
			$resArray=$this->hash_call("RefundTransaction",$nvpstr);
			$_SESSION['reshash']=$resArray;
			/* Display the API response back to the browser.
			If the response from PayPal was a success, display the response parameters'
			If the response was an error, display the errors received using APIError.php.
			*/
			$ack = strtoupper($resArray["ACK"]);
		}
		$data->Disconnect();
		$this->Payment->Type="REFUND";
		$this->Payment->Reference=$resArray['REFUNDTRANSACTIONID'];
		$this->Payment->Amount=$this->Amount;
		$this->Payment->Status=$ack;
		$this->Payment->StatusDetail=$refundType;
		$this->Payment->Add();

		if($ack != "SUCCESS")
		return false;
		else
		return true;
	}

	function hash_call($methodName,$nvpStr) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$this->SetExpressCheckoutURL);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1);

		$nvpreq="METHOD=".urlencode($methodName)."&VERSION=".urlencode($this->Version)."&PWD=".urlencode($this->Password)."&USER=".urlencode($this->User)."&SIGNATURE=".urlencode($this->Signature).$nvpStr;

		curl_setopt($ch,CURLOPT_POSTFIELDS,$nvpreq);

		$response = curl_exec($ch);

		//convrting NVPResponse to an Associative Array
		$nvpResArray=$this->deformatNVP($response);
		$nvpReqArray=$this->deformatNVP($nvpreq);
		$_SESSION['nvpReqArray']=$nvpReqArray;

		if (curl_errno($ch)) {
			$_SESSION['curl_error_no']=curl_errno($ch) ;
			$_SESSION['curl_error_msg']=curl_error($ch);
		} else {
			curl_close($ch);
		}

		return $nvpResArray;
	}

	function deformatNVP($nvpstr) {
		$intial = 0;
		$nvpArray = array();

		while(strlen($nvpstr)){
			$keypos = strpos($nvpstr,'=');
			$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

			$keyval = substr($nvpstr,$intial,$keypos);
			$valval = substr($nvpstr,$keypos+1,$valuepos-$keypos-1);

			$nvpArray[urldecode($keyval)] = urldecode( $valval);
			$nvpstr = substr($nvpstr,$valuepos+1,strlen($nvpstr));
		}

		return $nvpArray;
	}
}
?>