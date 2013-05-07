<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Order.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Invoice.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/PaymentGateway.php");

class Payment{
	var $ID;
	var $Type;
	var $Status;
	var $StatusDetail;
	var $Reference;
	var $SecurityKey;
	var $AuthorisationNumber;
	var $AVSCV2;
	var $AddressResult;
	var $AddressStatus;
	var $PostcodeResult;
	var $CV2Result;
	var $Secure3DStatus;
	var $CAVV;
	var $Gateway;
	var $Amount;
	var $PayerStatus;
	var $CardType;
	var $Last4Digits;
	var $Order;
	var $Invoice;
	var $PaidOn;
	var $IsMoto;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function Payment($id=NULL){
		$this->Order = new Order;
		$this->Gateway = new PaymentGateway;
		$this->Invoice = new Invoice;
		$this->PaidOn = '0000-00-00 00:00:00';
		$this->IsMoto = 'N';

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)) return false;
		$sql = sprintf("select * from payment where Payment_ID=%d", mysql_real_escape_string($this->ID));

		$data = new DataQuery($sql);

		$this->Reference = $data->Row['Reference'];
		$this->Type = $data->Row['Transaction_Type'];
		$this->Gateway->ID = $data->Row['Gateway_ID'];
		$this->Status = $data->Row['Status'];
		$this->StatusDetail = $data->Row['Status_Detail'];
		$this->SecurityKey = $data->Row['Security_Key'];
		$this->AuthorisationNumber = $data->Row['Authorisation_Number'];
		$this->AVSCV2 = $data->Row['AVSCV2'];
		$this->AddressResult = $data->Row['Address_Result'];
		$this->AddressStatus = $data->Row['Address_Status'];
		$this->PostcodeResult = $data->Row['Postcode_Result'];
		$this->CV2Result = $data->Row['CV2_Result'];
		$this->Amount = $data->Row['Amount'];
		$this->PayerStatus = $data->Row['Payer_Status'];
		$this->CardType = $data->Row['Card_Type'];
		$this->Last4Digits = $data->Row['Last_4_Digits'];
		$this->Order->ID = $data->Row['Order_ID'];
		$this->Invoice->ID = $data->Row['Invoice_ID'];
		$this->PaidOn = $data->Row['Paid_On'];
		$this->IsMoto = $data->Row['Is_Moto'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];
		$this->Secure3DStatus = $data->Row['3D_Secure_Status'];
		$this->CAVV = $data->Row['CAVV'];

		$data->Disconnect();
	}

	function Add(){
		$sql = sprintf("insert into payment (Reference, Transaction_Type, Status, Status_Detail, Security_Key, Authorisation_Number, AVSCV2, Address_Result, Address_Status, Postcode_Result, CV2_Result, 3D_Secure_Status, CAVV, Gateway_ID, Amount, Payer_Status, Card_Type, Last_4_Digits, Order_ID, Invoice_ID, Paid_On, Is_Moto, Created_On, Created_By, Modified_On, Modified_By) values ('%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s','%s', '%s','%s', %d, %f, '%s', '%s', '%s', %d, %d, '%s', '%s', Now(), %d, Now(), %d)",
		mysql_real_escape_string($this->Reference),
		mysql_real_escape_string($this->Type),
		mysql_real_escape_string($this->Status),
		mysql_real_escape_string($this->StatusDetail),
		mysql_real_escape_string($this->SecurityKey),
		mysql_real_escape_string($this->AuthorisationNumber),
		mysql_real_escape_string($this->AVSCV2),
		mysql_real_escape_string($this->AddressResult),
		mysql_real_escape_string($this->AddressStatus),
		mysql_real_escape_string($this->PostcodeResult),
		mysql_real_escape_string($this->CV2Result),
		mysql_real_escape_string($this->Secure3DStatus),
		mysql_real_escape_string($this->CAVV),
		mysql_real_escape_string($this->Gateway->ID),
		mysql_real_escape_string($this->Amount),
		mysql_real_escape_string($this->PayerStatus),
		mysql_real_escape_string($this->CardType),
		mysql_real_escape_string($this->Last4Digits),
		mysql_real_escape_string($this->Order->ID),
		mysql_real_escape_string($this->Invoice->ID),
		mysql_real_escape_string($this->PaidOn),
		mysql_real_escape_string($this->IsMoto),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));

		$data = new DataQuery($sql);
		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf("update payment set Reference='%s', Transaction_Type='%s', Status='%s', Status_Detail='%s', Security_Key='%s', Authorisation_Number=%d, AVSCV2='%s', Address_Result='%s', Address_Status='%s', Postcode_Result='%s', CV2_Result='%s', 3D_Secure_Status='%s', CAVV='%s', Gateway_ID=%d, Amount=%f, Payer_Status='%s', Card_Type='%s', Last_4_Digits='%s', Order_ID=%d, Invoice_ID=%d, Paid_On='%s', Is_Moto='%s', Modified_On=Now(), Modified_By=%d where Payment_ID=%d",
		mysql_real_escape_string($this->Reference),
		mysql_real_escape_string($this->Type),
		mysql_real_escape_string($this->Status),
		mysql_real_escape_string($this->StatusDetail),
		mysql_real_escape_string($this->SecurityKey),
		mysql_real_escape_string($this->AuthorisationNumber),
		mysql_real_escape_string($this->AVSCV2),
		mysql_real_escape_string($this->AddressResult),
		mysql_real_escape_string($this->AddressStatus),
		mysql_real_escape_string($this->PostcodeResult),
		mysql_real_escape_string($this->CV2Result),
		mysql_real_escape_string($this->Secure3DStatus),
		mysql_real_escape_string($this->CAVV),
		mysql_real_escape_string($this->Gateway->ID),
		mysql_real_escape_string($this->Amount),
		mysql_real_escape_string($this->PayerStatus),
		mysql_real_escape_string($this->CardType),
		mysql_real_escape_string($this->Last4Digits),
		mysql_real_escape_string($this->Order->ID),
		mysql_real_escape_string($this->Invoice->ID),
		mysql_real_escape_string($this->PaidOn),
		mysql_real_escape_string($this->IsMoto),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->ID));

		$data = new DataQuery($sql);
		return true;
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;

		if(!is_numeric($this->ID)) return false;
		$sql = sprintf("delete from payment where Payment_ID=%d", $this->ID);

		$data = new DataQuery($sql);

		return true;
	}
	
	function getSecurityKey($strVendorTxCode, $strVPSTxId){
		$sql = "SELECT Security_Key FROM payment where Payment_ID='" . mysql_real_escape_string($strVendorTxCode) . "' and Reference='" . mysql_real_escape_string($strVPSTxId) . "'";
		$data = new DataQuery($sql);
		$data->Disconnect();
		return $data->Row['Security_Key'];
	}
	
	function updateOrderCardDetails(){
		$cardNo = '****' . $this->Last4Digits;
		$this->Order->Card->SetNumber($cardNo);
		$this->Order->Card->Type->getByReference($this->CardType);
		$sql = sprintf("UPDATE orders SET Card_Payment_Method=%d, Card_Type='%s', Card_Number='%s' where Order_ID=%d", 
					mysql_real_escape_string($this->Order->Card->Type->ID),
					mysql_real_escape_string($this->Order->Card->Type->Name),
					mysql_real_escape_string($this->Order->Card->Number),
					mysql_real_escape_string($this->Order->ID));
		$data = new DataQuery($sql);
		return true;

	}
}