<?php
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TaxCalculator.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TaxClass.php");

class GlobalTaxCalculator {
	var $Classes;
	var $CountryID;
	var $RegionID;
	var $DefaultClass;
	
	function GlobalTaxCalculator($country, $region) {
		$this->CountryID = $country;
		$this->Classes = array();
		$this->RegionID = (is_numeric($region)) ? $region : 0;
		$this->GetClasses();
	}
	
	function GetClasses() {
		$getTaxClasses = new DataQuery("select * from tax_class");
		while ($getTaxClasses->Row) {
			$tid = 'class_' . $getTaxClasses->Row['Tax_Class_ID'];
			$this->Classes[$tid] = new TaxClass();
			$this->Classes[$tid]->ID = $getTaxClasses->Row['Tax_Class_ID'];
			$this->Classes[$tid]->Name = $getTaxClasses->Row['Tax_Class_Title'];
			$this->Classes[$tid]->Description = $getTaxClasses->Row['Tax_Class_Description'];
			$this->Classes[$tid]->IsDefault = $getTaxClasses->Row['Is_Default'];
			
			if(strtoupper($getTaxClasses->Row['Is_Default']) == 'Y') {
				$this->DefaultClass = $getTaxClasses->Row['Tax_Class_ID'];
			}
				
			$this->Classes[$tid]->Calculator = new TaxCalculator(0, $this->CountryID, $this->RegionID, $getTaxClasses->Row['Tax_Class_ID']);
			
			$getTaxClasses->Next();
		}
		$getTaxClasses->Disconnect();
	}
	
	function GetTax($amount, $class=null) {
		if (empty($class)) {
			$class = $this->DefaultClass;
		}
		$tid = 'class_' . $class;
		if (array_key_exists($tid, $this->Classes)) {
			return $this->Classes[$tid]->Calculator->Calculate($amount);
		} else {
			return 0;
		}
	}
}