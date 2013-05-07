<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Language.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/AddressFormat.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Currency.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TaxCode.php");

class Country{
	var $ID;
	var $Name;
	var $AddressFormat;
	var $Language;
	var $ISOCode2;
	var $Currency;
	var $AllowSales;
	var $AllowCustomRegions;
	var $NominalCode;
	var $NominalCodeTaxFree;
	var $NominalCodeAccount;
	var $NominalCodeAccountTaxFree;
	var $ExemptTaxCode;
	var $Created;
	var $CreatedBy;
	var $Modified;
	var $ModifiedBy;

	function Country($id=NULL){
		$this->AddressFormat = new AddressFormat();
		$this->Language = new Language();
		$this->Currency = new Currency();
		$this->AllowSales = 'N';
		$this->AllowCustomRegions = 'N';
		$this->ExemptTaxCode = new TaxCode();

		if(isset($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM countries WHERE Country_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Country'];
			$this->AddressFormat->Get($data->Row['Address_Format_ID']);
			$this->Language->ID = $data->Row['Language_ID'];
			$this->ISOCode2 = $data->Row['ISO_Code_2'];
			$this->Currency->ID = $data->Row['Currency_ID'];
			$this->AllowSales = $data->Row['Allow_Sales'];
			$this->AllowCustomRegions = $data->Row['Allow_Custom_Regions'];
            $this->NominalCode = $data->Row['Nominal_Code'];
            $this->NominalCodeTaxFree = $data->Row['Nominal_Code_Tax_Free'];
            $this->NominalCodeAccount = $data->Row['Nominal_Code_Account'];
            $this->NominalCodeAccountTaxFree = $data->Row['Nominal_Code_Account_Tax_Free'];
			$this->ExemptTaxCode->ID = $data->Row['Exempt_Tax_Code_ID'];
			$this->Created = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->Modified = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE countries
												SET Country='%s',
												Address_Format_ID=%d,
											  	Language_ID=%d,
											   	ISO_Code_2='%s',
											    Currency_ID='%s',
											    Allow_Sales='%s',
											    Allow_Custom_Regions='%s',
											    Nominal_Code='%s',
											    Nominal_Code_Tax_Free='%s',
											    Nominal_Code_Account='%s',
											    Nominal_Code_Account_Tax_Free='%s',
											    Exempt_Tax_Code_ID=%d,
											    Modified_On=Now(),
											    Modified_By=%d
											    WHERE Country_ID=%d",
		mysql_real_escape_string($this->Name),
		mysql_real_escape_string($this->AddressFormat),
		mysql_real_escape_string($this->Language->ID),
		mysql_real_escape_string($this->ISOCode2),
		mysql_real_escape_string($this->Currency->ID),
		mysql_real_escape_string($this->AllowSales),
		mysql_real_escape_string($this->AllowCustomRegions),
		mysql_real_escape_string($this->NominalCode),
		mysql_real_escape_string($this->NominalCodeTaxFree),
		mysql_real_escape_string($this->NominalCodeAccount),
		mysql_real_escape_string($this->NominalCodeAccountTaxFree),
		mysql_real_escape_string($this->ExemptTaxCode->ID),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->ID)));
	}

	function Add(){
		$country = new DataQuery(sprintf("INSERT INTO countries
											(Country, Address_Format_ID, Language_ID,
											ISO_Code_2, Currency_ID, Allow_Sales, Allow_Custom_Regions, Nominal_Code, Nominal_Code_Tax_Free, Nominal_Code_Account, Nominal_Code_Account_Tax_Free, Exempt_Tax_Code_ID,
											Modified_On, Modified_By, Created_On,
											Created_By)
											VALUES ('%s', %d, %d, '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', %d, Now(), %d, Now(), %d)",
		mysql_real_escape_string($this->Name),
		mysql_real_escape_string($this->AddressFormat),
		mysql_real_escape_string($this->Language->ID),
		mysql_real_escape_string($this->ISOCode2),
		mysql_real_escape_string($this->Currency->ID),
		mysql_real_escape_string($this->AllowSales),
		mysql_real_escape_string($this->AllowCustomRegions),
        mysql_real_escape_string($this->NominalCode),
		mysql_real_escape_string($this->NominalCodeTaxFree),
		mysql_real_escape_string($this->NominalCodeAccount),
		mysql_real_escape_string($this->NominalCodeAccountTaxFree),
		mysql_real_escape_string($this->ExemptTaxCode->ID),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $country->InsertID;
	}


	function Remove(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM countries WHERE Country_ID=%d",  mysql_real_escape_string($this->ID)));
		
		return true;
	}

	function GetIDFromString($str){
		$region = new DataQuery(sprintf("SELECT * FROM countries WHERE Country='%s'", mysql_real_escape_string(trim($str))));
		$result = $region->Row["Country_ID"];
		$region->Disconnect();

		return $result;
	}

	function GetIDFromIsoCode2(){
		$data = new DataQuery(sprintf("select * from countries where ISO_Code_2 like '%s'", mysql_real_escape_string(trim($this->ISOCode2))));
		$this->ID = $data->Row["Country_ID"];
		$data->Disconnect();
		
		return $this->ID;
	}
}