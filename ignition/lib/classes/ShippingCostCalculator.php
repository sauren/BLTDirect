<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ShippingCostCalculator {
	var $Country;
	var $Region;
	var $Weight;
	var $OrderAmount;
	var $PostageID;
	var $Total;
	var $Surcharge;
	var $DeliveryCharge;
	var $HighestWeightThreshold;
	var $HighestPerKiloCharge;
	var $HasErrors;

	function __construct($country=NULL, $region=NULL, $orderAmount=NULL, $weight=NULL, $postageId = 0) {
		if(($country == $GLOBALS['SYSTEM_COUNTRY']) && ($region == 0)) {
			$region = $GLOBALS['SYSTEM_REGION'];
		}

		$this->Country = (!is_null($country)) ? $country : $GLOBALS['SYSTEM_COUNTRY'];
		$this->Region = (!is_null($region)) ? $region: $GLOBALS['SYSTEM_REGION'];
		$this->OrderAmount = (!is_null($orderAmount))?$orderAmount:0;
		$this->Weight = (!is_null($weight))?$weight:0;
		$this->PostageID = $postageId;

		$this->Total = 0;
		$this->Surcharge = 0;
		$this->DeliveryCharge = 0;
		$this->HighestWeightThreshold = 0;
		$this->HighestPerKiloCharge = 0;
		$this->HasErrors = false;
	}

	function Add($qty, $classId) {
		$data = new DataQuery(sprintf("SELECT MAX(ss.Over_Order_Amount) AS maxAmount, MAX(ga.Region_ID) AS maxRegion FROM shipping_cost AS ss INNER JOIN geozone_assoc AS ga ON ga.Geozone_ID=ss.Geozone_ID WHERE (ss.Shipping_Class_ID=0 OR ss.Shipping_Class_ID=%d) AND ga.Country_ID=%d AND (ga.Region_ID=%d OR ga.Region_ID=0) AND ss.Over_Order_Amount<=%f AND ss.Weight_Threshold<=%f AND Postage_ID=%d", mysql_real_escape_string($classId), mysql_real_escape_string($this->Country), mysql_real_escape_string($this->Region), mysql_real_escape_string($this->OrderAmount), mysql_real_escape_string($this->Weight), mysql_real_escape_string($this->PostageID)));
		if($data->TotalRows > 0) {
			$data2 = new DataQuery(sprintf("SELECT ss.Weight_Threshold, ss.Per_Item, ss.Per_Delivery, ss.Per_Additional_Kilo FROM shipping_cost AS ss INNER JOIN geozone_assoc AS ga ON ga.Geozone_ID=ss.Geozone_ID WHERE (ss.Shipping_Class_ID=0 OR ss.Shipping_Class_ID=%d) AND ga.Country_ID=%d AND ga.Region_ID=%d AND ss.Over_Order_Amount=%f AND ss.Weight_Threshold<=%f AND ss.Postage_ID=%d ORDER BY ss.Weight_Threshold DESC LIMIT 0, 1", mysql_real_escape_string($classId), mysql_real_escape_string($this->Country), mysql_real_escape_string($data->Row['maxRegion']), mysql_real_escape_string($data->Row['maxAmount']), mysql_real_escape_string($this->Weight), mysql_real_escape_string($this->PostageID)));
			if($data2->TotalRows > 0) {
				$this->Surcharge += $qty * $data2->Row['Per_Item'];

				if($this->DeliveryCharge < $data2->Row['Per_Delivery']) {
					$this->DeliveryCharge = $data2->Row['Per_Delivery'];
				}

				if($this->HighestWeightThreshold < $data2->Row['Weight_Threshold']) {
					$this->HighestWeightThreshold = $data2->Row['Weight_Threshold'];
				}

				if($this->HighestPerKiloCharge < $data2->Row['Per_Additional_Kilo']) {
					$this->HighestPerKiloCharge = $data2->Row['Per_Additional_Kilo'];
				}
			} else {
				$this->HasErrors = true;
			}
		} else {
			$this->HasErrors = true;
		}
		$data->Disconnect();
	}

	function GetTotal(){
		$this->Surcharge += ceil($this->Weight - $this->HighestWeightThreshold) * $this->HighestPerKiloCharge;
		$this->Total = $this->DeliveryCharge + $this->Surcharge;

		return $this->Total;
	}
}