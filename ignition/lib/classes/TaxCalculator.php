<?php
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class TaxCalculator {
	var $TaxRate;
	var $TaxCode;
	var $Amount;
	var $Tax;
	var $CountryID;
	var $RegionID;
	var $ClassID;
	
	function __construct($amount = NULL, $country = NULL, $region = NULL, $class = NULL, $connection = null) {
		$this->TaxRate = 0;
		$this->Amount = $amount;
		$this->CountryID = $country;
		$this->RegionID = $region;
		$this->ClassID = $class;
		
		if(!is_null($this->CountryID) && !is_null($this->RegionID) && !is_null($this->ClassID)) {
			$this->TaxRate = $this->GetRate($connection);
			
			if(!is_null($this->Amount)) {
				$this->Calculate();
			}
		}
	}
	
	function Calculate($amount = NULL) {
		if (!is_null($amount)) {
			$this->Amount = $amount;
		}
		
		$tempRate = (100 + $this->TaxRate) / 100;
		$this->Tax = ($this->Amount * $tempRate) - $this->Amount;
		
		return $this->Tax;
	}
	
	function GetRate($connection = null) {
		$this->TaxRate = 0;
		
		$tax = new DataQuery(sprintf("SELECT t.Tax_Rate, tc.Integration_Reference FROM tax AS t LEFT JOIN tax_code AS tc ON tc.Tax_Code_ID=t.Tax_Code_ID INNER JOIN geozone_assoc AS ga ON ga.Geozone_ID=t.Geozone_ID WHERE ga.Country_ID=%d AND (ga.Region_ID=0 OR ga.Region_ID=%d) AND t.Tax_Class_ID=%d ORDER BY t.Tax_Rate ASC LIMIT 0, 1", mysql_real_escape_string($this->CountryID), mysql_real_escape_string($this->RegionID), mysql_real_escape_string($this->ClassID)), $connection);
		if ($tax->TotalRows > 0) {
			$this->TaxRate = $tax->Row['Tax_Rate'];
			$this->TaxCode = $tax->Row['Integration_Reference'];
		}
		$tax->Disconnect();
		
		return $this->TaxRate;
	}
	
	function GetTax($amount) {
		return $this->Calculate($amount);
	}
}