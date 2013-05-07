<?php
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ShippingCalculator.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ShippingClass.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Shipping.php");

class GlobalShippingCalculator {
	var $Classes;
	var $CountryID;
	var $RegionID;
	var $DefaultClass;
	
	function GlobalShippingCalculator($country, $region) {
		$this->CountryID = $country;
		$this->Classes = array();
		$this->RegionID = $region;
		$this->GetClasses();
	}
	
	function GetClasses() {
		$data = new DataQuery("select * from shipping_class");
		while ($data->Row) {
			$key = 'class_' . $data->Row['Shipping_Class_ID'];
			$this->Classes[$key] = new ShippingClass();
			$this->Classes[$key]->ID = $data->Row['Shipping_Class_ID'];
			$this->Classes[$key]->Name = $data->Row['Shipping_Class_Title'];
			$this->Classes[$key]->Description = $data->Row['Shipping_Class_Description'];
			$this->Classes[$key]->IsDefault = $data->Row['Is_Default'];
			if (strtoupper($this->Classes[$key]->IsDefault) == 'Y')
				$this->DefaultClass = $data->Row['Shipping_Class_ID'];
			$data->Next();
		}
		$data->Disconnect();
	}
	
	function GetProductTable(&$product) {
		// Setup some Temporary arrays
		$table = array();
		$html = '';
		
		$useClass = $product->ShippingClass->ID;
		if (empty($useClass))
			$useClass = $this->DefaultClass;
		
		$sql = sprintf("select s.*,
					  p.Postage_Title,
					  p.Postage_Days,
					  p.Postage_Description,
					  ga.Region_ID as maxRegion,
					  g.Geozone_Title
					from shipping as s
					inner join geozone_assoc as ga on s.Geozone_ID=ga.Geozone_ID
					and ga.Country_ID=%d and (ga.Region_ID=%d or ga.Region_ID=0)
					left join postage as p on s.Postage_ID=p.Postage_ID
					left join geozone as g on s.Geozone_ID=g.Geozone_ID
					where s.Shipping_Class_ID=%d", mysql_real_escape_string($this->CountryID), mysql_real_escape_string($this->RegionID), mysql_real_escape_string($useClass));
		
		$data = new DataQuery($sql);
		while ($data->Row) {
			$table[] = $data->Row;
			$data->Next();
		}
		$data->Disconnect();
	}
}
?>