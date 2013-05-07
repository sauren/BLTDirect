<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class PaymentGateway{
	var $ID;
	var $Name;
	var $Description;
	var $ClassFile;
	var $IsDefault;
	var $HasPreAuth;
	var $IsTestMode;
	var $VendorName;
	var $Currency;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function PaymentGateway($id=NULL){
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf("select * from payment_gateway where Gateway_ID=%d", mysql_real_escape_string($this->ID));
		$data = new DataQuery($sql);

		$this->Name = $data->Row['Name'];
		$this->Description = $data->Row['Description'];
		$this->ClassFile = $data->Row['Class_File'];
		$this->IsDefault = $data->Row['Is_Default'];
		$this->HasPreAuth = $data->Row['Has_Pre_Auth'];
		$this->IsTestMode = $data->Row['Is_Test_Mode'];
		$this->VendorName = $data->Row['Vendor_Name'];
		$this->Currency = $data->Row['Currency'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];

		$data->Disconnect();
	}

	function Add(){
		$this->ResetDefaults();
		$sql = sprintf("insert into payment_gateway (Name, Description, Class_File, Is_Default, Has_Pre_Auth, Is_Test_Mode, Vendor_Name, Currency, Created_On, Created_By, Modified_On, Modified_By) values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' Now(), %d, Now(), %d)",
		mysql_real_escape_string($this->Name),
		mysql_real_escape_string($this->Description),
		mysql_real_escape_string($this->ClassFile),
		mysql_real_escape_string($this->IsDefault),
		mysql_real_escape_string($this->HasPreAuth),
		mysql_real_escape_string($this->IsTestMode),
		mysql_real_escape_string($this->VendorName),
		mysql_real_escape_string($this->Currency),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));

		$data = new DataQuery($sql);
		$this->ID = $data->InsertID;
	}

	function ResetDefaults(){
		if($this->IsDefault == 'Y'){
			$sql = "update payment_gateway set Is_Default='N'";
			$data = new DataQuery($sql);
			unset($sql);
			unset($data);
		}
	}

	function Update(){
		$this->ResetDefaults();
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf("update payment_gateway set Name='%s', Description='%s', Class_File='%s', Is_Default='%s', Has_Pre_Auth='%s', Is_Test_Mode='%s', Vendor_Name='%s', Currency='%s', Modified_On=Now(), Modified_By=%d where Gateway_ID=%d",
		mysql_real_escape_string($this->Name),
		mysql_real_escape_string($this->Description),
		mysql_real_escape_string($this->ClassFile),
		mysql_real_escape_string($this->IsDefault),
		mysql_real_escape_string($this->HasPreAuth),
		mysql_real_escape_string($this->IsTestMode),
		mysql_real_escape_string($this->VendorName),
		mysql_real_escape_string($this->Currency),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->ID));

		$data = new DataQuery($sql);
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf("delete from payment_gateway where Gateway_ID=%d", mysql_real_escape_string($this->ID));
		$data = new DataQuery($sql);
	}

	function GetDefault(){
		$returnValue = false;
		$sql = "select * from payment_gateway where Is_Default='Y'";
		$data = new DataQuery($sql);
		if($data->TotalRows > 0){
			$this->ID = $data->Row['Gateway_ID'];
			$this->Name = $data->Row['Name'];
			$this->Description = $data->Row['Description'];
			$this->ClassFile = $data->Row['Class_File'];
			$this->IsDefault = $data->Row['Is_Default'];
			$this->HasPreAuth = $data->Row['Has_Pre_Auth'];
			$this->IsTestMode = $data->Row['Is_Test_Mode'];
			$this->VendorName = $data->Row['Vendor_Name'];
			$this->Currency = $data->Row['Currency'];

			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$returnValue = true;
		}
		
		$data->Disconnect();
		return $returnValue;
	}
}