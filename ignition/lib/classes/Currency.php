<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class Currency{
	var $ID;
	var $Name;
	var $Code;
	var $SymbolLeft;
	var $SymbolRight;
	var $DecimalPoint;
	var $ThousandsPoint;
	var $DecimalPlaces;
	var $Value;
	var $Created;
	var $CreatedBy;
	var $Modified;
	var $ModifiedBy;
	
	function Currency($id=NULL){
		if(isset($id)){
			$this->ID = $id;
			$this->Get();
		}
	}
	
	function Get(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$currency = new DataQuery(sprintf("select * from currencies where Currency_ID=%d", mysql_real_escape_string($this->ID)));
		$this->Name = $currency->Row['Currency'];
		$this->Code = $currency->Row['Code'];
		$this->SymbolLeft = $currency->Row['Symbol_Left'];
		$this->SymbolRight = $currency->Row['Symbol_Right'];
		$this->DecimalPoint = $currency->Row['Decimal_Point'];
		$this->ThousandsPoint = $currency->Row['Thousands_Point'];
		$this->DecimalPlaces = $currency->Row['Decimal_Places'];
		$this->Value = $currency->Row['Value'];
		
		$this->Created = $currency->Row['Created_On'];
		$this->CreatedBy = $currency->Row['Created_By'];
		$this->Modified = $currency->Row['Modified_On'];
		$this->ModifiedBy = $currency->Row['Modified_By'];	
		$currency->Disconnect();
		$currency = NULL;
	}
	
	function Add(){
		$currency = new DataQuery(sprintf("insert into currencies (Currency, Code, Symbol_Left, Symbol_Right, 
																	Decimal_Point, Thousands_Point, Decimal_Places, 
																	Value, Created_On, Created_By, Modified_On, Modified_By) 
																	values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', %f, Now(), %d, Now(), %d)", 
																	mysql_real_escape_string($this->Name),
																	mysql_real_escape_string($this->Code),
																	mysql_real_escape_string($this->SymbolLeft),
																	mysql_real_escape_string($this->SymbolRight),
																	mysql_real_escape_string($this->DecimalPoint),
																	mysql_real_escape_string($this->ThousandsPoint),
																	mysql_real_escape_string($this->DecimalPlaces),
																	mysql_real_escape_string($this->Value),
																	mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
																	mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $currency->InsertId;
		return true;
	}
	
	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$currency = new DataQuery(sprintf("update currencies set Currency='%s', 
																	Code='%s', 
																	Symbol_Left='%s', 
																	Symbol_Right='%s', 
																	Decimal_Point='%s', 
																	Thousands_Point='%s', 
																	Decimal_Places='%s', 
																	Value=%f, 
																	Modified_On=Now(), 
																	Modified_By=%d 
																	where Currency_ID=%d", 
																	mysql_real_escape_string($this->Name),
																	mysql_real_escape_string($this->Code),
																	mysql_real_escape_string($this->SymbolLeft),
																	mysql_real_escape_string($this->SymbolRight),
																	mysql_real_escape_string($this->DecimalPoint),
																	mysql_real_escape_string($this->ThousandsPoint),
																	mysql_real_escape_string($this->DecimalPlaces),
																	mysql_real_escape_string($this->Value),
																	mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
																	mysql_real_escape_string($this->ID)));
		return true;
	}
	
	function Remove(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$currency = new DataQuery(sprintf("delete from currencies where Currency_ID=%d", mysql_real_escape_string($this->ID)));
		return true;
	}
	
	function Format($number){
		return $this->SymbolLeft . number_format($number, $this->DecimalPlaces, $this->DecimalPoint, $this->ThousandsPoint);
	}
}