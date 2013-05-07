<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Postage.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/FindReplace.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Country.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ShippingClass.php");

class ShippingCalculator{
	var $Country;
	var $Region;
	var $Errors;
	var $Warnings;
	var $Weight;
	var $OrderAmount;
	var $Tax;
	var $DeliveryCharge;
	var $Total;
	var $Surcharge;
	var $Error;
	var $Warning;
	var $Location;
	var $Postage;
	var $AvailablePostage;
	var $AllPostage;
	var $HighestPostageCount;
	var $HighestWeightThreshold;
	var $HighestPerKiloCharge;
	var $Geozone;
	var $FoundPostage;
	var $Products;
	var $IsWeightRestricted;
	var $Multiplier;
	var $Line;

	function ShippingCalculator($country=NULL, $region=NULL, $orderAmount=NULL, $weight=NULL, $postage=NULL){
		$this->Products = array();

		if(($country == $GLOBALS['SYSTEM_COUNTRY']) && ($region == 0)) {
			$region = $GLOBALS['SYSTEM_REGION'];
		}

		$this->Country = (!is_null($country)) ? $country : $GLOBALS['SYSTEM_COUNTRY'];
		$this->Region = (!is_null($region)) ? $region: $GLOBALS['SYSTEM_REGION'];

		$this->Weight = (!is_null($weight))?$weight:0;
		$this->OrderAmount = (!is_null($orderAmount))?$orderAmount:0;
		$this->Postage = $postage;
		$this->Errors = array();
		$this->Warnings = array();
		$this->AvailablePostage = array();
		$this->AllPostage = array();
		$this->HighestPostageCount=0;
		$this->Error = false;
		$this->Warning = false;
		$this->Check();
		$this->Total = 0;
		$this->Tax = 0;
		$this->Surcharge = 0;
		$this->DeliveryCharge = 0;
		$this->HighestPostageCount = 0;
		$this->HighestWeightThreshold = 0;
		$this->HighestPerKiloCharge = 0;
		$this->FoundPostage = false;
		$this->IsWeightRestricted = false;
		$this->Multiplier = 1;
		$this->Line = array();
	}

	function Add($qty, $class) {
		$this->Line[] = array('qty' => $qty, 'class' => $class);

		/*
		SQL For Shipping Calculation
		Demo information:
		* Cart weight = 2kg
		* Cart total = 19.92

		1. Getting the correct weight threshold from the database
		select max(Weight_Threshold) from shipping where Weight_Threshold < 2

		2. Getting the correct order threshold from the database
		select max(Over_Order_Amount) from shipping where Over_Order_Amount < 19.92

		3. Getting info from the shipping table
		select * from shipping

		4. The basic Shipping information we need
		select s.Shipping_ID, s.Geozone_ID, s.Postage_ID, s.Per_Item, s.Per_Delivery, s.Per_Additional_Kilo from shipping as s
		where s.Shipping_Class_ID=6

		5. How do we get the right Geozone?
		*/

		$c1 = new DataQuery(sprintf("select s.Postage_ID, max(s.Over_Order_Amount) as maxAmount, max(ga.Region_ID) as maxRegion from shipping as s
								inner join geozone_assoc as ga on
								ga.Geozone_ID=s.Geozone_ID
								where
								s.Shipping_Class_ID=%d and
								ga.Country_ID=%d and
								(ga.Region_ID=%d or ga.Region_ID=0) and
								s.Over_Order_Amount <= %f and
								s.Weight_Threshold <= %f
								group by Postage_ID",
		mysql_real_escape_string($class),
		mysql_real_escape_string($this->Country),
		mysql_real_escape_string($this->Region),
		mysql_real_escape_string($this->OrderAmount),
		mysql_real_escape_string($this->Weight)));
		if($c1->TotalRows > 0){
			while($c1->Row){
				$c2 = new DataQuery(sprintf("select  s.Geozone_ID, s.Weight_Threshold, s.Per_Item, s.Per_Delivery, s.Per_Additional_Kilo from shipping as s
											inner join geozone_assoc as ga on
											ga.Geozone_ID=s.Geozone_ID
											where
											s.Shipping_Class_ID=%d and
											ga.Country_ID=%d and
											ga.Region_ID=%d and
											s.Over_Order_Amount = %f and
											s.Weight_Threshold <= %f and
											s.Postage_ID=%d
											order by s.Weight_Threshold desc
											limit 1",
				mysql_real_escape_string($class),
				mysql_real_escape_string($this->Country),
				mysql_real_escape_string($c1->Row['maxRegion']),
				mysql_real_escape_string($c1->Row['maxAmount']),
				mysql_real_escape_string($this->Weight),
				mysql_real_escape_string($c1->Row['Postage_ID'])));

				if($c1->Row['Postage_ID'] == $this->Postage){
					$this->FoundPostage = true;

					$this->Surcharge += $qty * $c2->Row['Per_Item'];
					if($this->DeliveryCharge < $c2->Row['Per_Delivery']) $this->DeliveryCharge = $c2->Row['Per_Delivery'];
					if($this->HighestWeightThreshold < $c2->Row['Weight_Threshold']) $this->HighestWeightThreshold = $c2->Row['Weight_Threshold'];
					if($this->HighestPerKiloCharge < $c2->Row['Per_Additional_Kilo']) $this->HighestPerKiloCharge = $c2->Row['Per_Additional_Kilo'];
				}

				// calculate available postages

				// Add postage to array
				if(!array_key_exists($c1->Row['Postage_ID'], $this->AllPostage)){
					$this->AllPostage[$c1->Row['Postage_ID']] = new Postage();
					$this->AllPostage[$c1->Row['Postage_ID']]->ID = $c1->Row['Postage_ID'];
					$this->AllPostage[$c1->Row['Postage_ID']]->Count = 0;
				}
				$this->AllPostage[$c1->Row['Postage_ID']]->Surcharge += $qty * $c2->Row['Per_Item'];
				if($this->AllPostage[$c1->Row['Postage_ID']]->DeliveryCharge < $c2->Row['Per_Delivery']) $this->AllPostage[$c1->Row['Postage_ID']]->DeliveryCharge = $c2->Row['Per_Delivery'];
				if($this->AllPostage[$c1->Row['Postage_ID']]->HighestWeightThreshold < $c2->Row['Weight_Threshold']) $this->AllPostage[$c1->Row['Postage_ID']]->HighestWeightThreshold = $c2->Row['Weight_Threshold'];
				if($this->AllPostage[$c1->Row['Postage_ID']]->HighestPerKiloCharge < $c2->Row['Per_Additional_Kilo']) $this->AllPostage[$c1->Row['Postage_ID']]->HighestPerKiloCharge = $c2->Row['Per_Additional_Kilo'];

				$this->AllPostage[$c1->Row['Postage_ID']]->Count += 1;
				if($this->AllPostage[$c1->Row['Postage_ID']]->Count > $this->HighestPostageCount) $this->HighestPostageCount = $this->AllPostage[$c1->Row['Postage_ID']]->Count;

				$this->Geozone = $c2->Row['Geozone_ID'];
				$c2->Disconnect();
				$c1->Next();
			}
		} else {
			$shippingClass = new ShippingClass($class);

			if(!empty($shippingClass->UnavailableDescription)) {
				$this->AddError($shippingClass->UnavailableDescription);
			}
		}
		$c1->Disconnect();
	}

	function Check(){
		if($this->Country <= 0) $this->AddError('Invalid Country Setting');
		if($this->Region < 0) $this->AddError('Invalid Region Setting');
	}

	function AddError($str){
		$this->Errors[] = $str;
		$this->Error = true;
	}

	function GetTotal(){
		$this->Surcharge += ceil($this->Weight - $this->HighestWeightThreshold) * $this->HighestPerKiloCharge;
		$this->Total = $this->DeliveryCharge + $this->Surcharge;
		return $this->Total;
	}

	function GetOptionAmount(){

	}

	function GetOptions($checkDates = false) {
		$tempHtml = "";
		foreach($this->AllPostage as $key => $postage){
			if($postage->Count < $this->HighestPostageCount) unset($this->AllPostage[$key]);
		}
		$now = getDatetime();
		$dateSplit = explode(' ', $now);
		$today = $dateSplit[0];
		$tempHtml .= sprintf('<select style="width:100%%;" name="deliveryOption" onChange="changeDelivery(this.value);">', $_SERVER['PHP_SELF']);
		$tempHtml .= '<option value="">Select Postage</option>';

		foreach($this->AllPostage as $key=>$postage){
			$postage->Get();

			$day = date('N');
			$hour = date('H');
			$minute = date('i');

			$postageStartHour = substr($postage->StartTime, 0, 2);
			$postageStartMinute = substr($postage->StartTime, 3, 2);
			$postageEndHour = substr($postage->EndTime, 0, 2);
			$postageEndMinute = substr($postage->EndTime, 3, 2);

			$showPostage = false;

			if(!$checkDates) {
				$showPostage = true;
			} else {
				if(($postage->StartDay == 0) || ($postage->EndDay == 0)) {
					$showPostage = true;
				} elseif(($postage->StartDay == $postage->EndDay) && ($day == $postage->StartDay) && ((($hour == $postageStartHour) && ($minute >= $postageStartMinute)) || ($hour > $postageStartHour)) && ((($hour == $postageEndHour) && ($minute < $postageEndMinute)) || ($hour < $postageEndHour))) {
					$showPostage = true;
				} else {
					if(($postage->StartDay < $postage->EndDay) && (($day > $postage->StartDay) || (($day == $postage->StartDay) && ((($hour == $postageStartHour) && ($minute >= $postageStartMinute)) || ($hour > $postageStartHour)))) && (($day < $postage->EndDay) || (($day == $postage->EndDay) && ((($hour == $postageEndHour) && ($minute < $postageEndMinute)) || ($hour < $postageEndHour))))) {
						$showPostage = true;
					} elseif(($postage->StartDay > $postage->EndDay) && ((($day > $postage->StartDay) || (($day == $postage->StartDay) && ((($hour == $postageStartHour) && ($minute >= $postageStartMinute)) || ($hour > $postageStartHour)))) || (($day < $postage->EndDay) || (($day == $postage->EndDay) && ((($hour == $postageEndHour) && ($minute < $postageEndMinute)) || ($hour < $postageEndHour)))))) {
						$showPostage = true;
					}
				}
			}

			if($showPostage) {
				$postageAmount = 0;

				$shippingCalculator = new ShippingCalculator($this->Country, $this->Region, $this->OrderAmount, $this->Weight, $postage->ID);

				for($i=0; $i < count($this->Line); $i++){
					$shippingCalculator->Add($this->Line[$i]['qty'], $this->Line[$i]['class']);
				}

				$data = new DataQuery(sprintf("SELECT sl.Weight FROM shipping_limit AS sl INNER JOIN geozone AS g ON g.Geozone_ID=sl.Geozone_ID INNER JOIN geozone_assoc AS ga ON ga.Geozone_ID=g.Geozone_ID AND ((ga.Country_ID=%d AND ga.Region_ID=%d) OR (ga.Country_ID=%d AND ga.Region_ID=0) OR (ga.Country_ID=0)) WHERE sl.Weight<%f AND sl.Postage_ID=%d ORDER BY sl.Weight ASC LIMIT 0, 1", mysql_real_escape_string($this->Country), mysql_real_escape_string($this->Region), mysql_real_escape_string($this->Country), mysql_real_escape_string($this->Weight), mysql_real_escape_string($postage->ID)));
				if($data->TotalRows > 0) {
					$quantity = floor($this->Weight / $data->Row['Weight']);

					if($quantity >= 1) {
						$shippingCalculator2 = new ShippingCalculator($this->Country, $this->Region, $this->OrderAmount, $data->Row['Weight'], $postage->ID);

						for($i=0; $i < count($this->Line); $i++){
							$shippingCalculator2->Add($this->Line[$i]['qty'], $this->Line[$i]['class']);
						}

						$postageAmount += $shippingCalculator2->GetTotal() * $quantity;
					}

					$weight = $this->Weight - ($data->Row['Weight'] * $quantity);

					if($weight > 0) {
						$shippingCalculator2 = new ShippingCalculator($this->Country, $this->Region, $this->OrderAmount, $weight, $postage->ID);

						for($i=0; $i < count($this->Line); $i++){
							$shippingCalculator2->Add($this->Line[$i]['qty'], $this->Line[$i]['class']);
						}

						$postageAmount += $shippingCalculator2->GetTotal();
					}
				} else {
					$postageAmount += $shippingCalculator->GetTotal();
				}
				$data->Disconnect();

				$workingDay=0;
				switch(strtoupper(date('D'))){
					case 'SUN':
						$workingDay = 1;
						break;
					case 'SAT':
						$workingDay = 2;
						break;
					default:
						$workingDay = 0;
						break;
				}
				$postage->Days += $workingDay;
				$day = sprintf("+ %s day", $postage->Days);
				$time = date('d/m/y', strtotime($day));
				$selected = ($this->Postage == $postage->ID)?'selected="selected"':'';
				$tempHtml .= sprintf('<option value="%s" %s>%s (%s)</option>', $postage->ID, $selected, $postage->Name, ($postageAmount > 0) ? sprintf('&pound;%s', number_format($postageAmount, 2, '.', ',')) : 'FREE');

				$postage->Surcharge += ceil($this->Weight - $postage->HighestWeightThreshold) * $postage->HighestPerKiloCharge;
				$postage->Total = $postage->DeliveryCharge + $postage->Surcharge;

				$this->AvailablePostage[] = $postage;
			}
		}

		$tempHtml .= '</select>';
		
		if(count($this->AvailablePostage) == 0) {
			$this->Error = true;
		}
		
		return $tempHtml;
	}

	function GetLimitations() {
		$data = new DataQuery(sprintf("SELECT sl.Weight, sl.Message, sl.Is_Shipping_Prevented FROM shipping_limit AS sl INNER JOIN geozone AS g ON g.Geozone_ID=sl.Geozone_ID INNER JOIN geozone_assoc AS ga ON ga.Geozone_ID=g.Geozone_ID AND ((ga.Country_ID=%d AND ga.Region_ID=%d) OR (ga.Country_ID=%d AND ga.Region_ID=0) OR (ga.Country_ID=0)) WHERE sl.Weight<%f AND sl.Postage_ID=%d ORDER BY sl.Weight ASC LIMIT 0, 1", mysql_real_escape_string($this->Country), mysql_real_escape_string($this->Region), mysql_real_escape_string($this->Country), mysql_real_escape_string($this->Weight), mysql_real_escape_string($this->Postage)));
		if($data->TotalRows > 0) {
			$this->Multiplier = ceil($this->Weight / $data->Row['Weight']);

			$this->IsWeightRestricted = true;
			$this->Warning = true;

			if(!empty($data->Row['Message'])) {
				$country = new Country($this->Country);

				$findReplace = new FindReplace();
				$findReplace->Add('/\[WEIGHT\]/', sprintf('%sKg', $this->Weight));
				$findReplace->Add('/\[COUNTRY\]/', $country->Name);

				$this->Warnings[] = $findReplace->Execute($data->Row['Message']);
			}

			if($data->Row['Is_Shipping_Prevented'] == 'Y') {
				$this->AddError(sprintf('Weight limit of %sKg prevents shipping to the chosen destination with the selected postage options.', number_format($data->Row['Weight'], 2, '.', '')));
			}
		}
		$data->Disconnect();
	}
}
?>