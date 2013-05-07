<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Country.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Region.php");

class Address{
	var $ID;
	var $Line1;
	var $Line2;
	var $Line3;
	var $Line4;
	var $Line5;
	var $City;
	var $Region;
	var $Country;
	var $Zip;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $IntegrationID;

	function Address($id=NULL){
		$this->Region = new Region();
		
		$this->Country = new Country();
		$this->Country->ID = $GLOBALS['SYSTEM_COUNTRY'];

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
		if(!is_numeric($this->ID)){
			return false;
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) { $this->ID = $id; }
		if(!is_numeric($this->ID)) return false;

		$data = new DataQuery(sprintf("select
										a.*,
										c.Country, c.Address_Format_ID, c.ISO_Code_2, c.Allow_Sales,
										r.Region_Name, r.Region_Code
										from address as a
										left join countries as c on a.Country_ID=c.Country_ID
										left join regions as r on a.Region_ID=r.Region_ID
										where a.Address_ID=%d", mysql_real_escape_string($this->ID)));

		if($data->TotalRows > 0) {
			$this->Line1 = $data->Row['Address_Line_1'];
			$this->Line2 = $data->Row['Address_Line_2'];
			$this->Line3 = $data->Row['Address_Line_3'];
			$this->City = $data->Row['City'];

			if(empty($data->Row['Region_ID'])){
				$this->Region->ID = $data->Row['Region_ID'];
				$this->Region->Name = $data->Row['Region_Name'];
			} else {
				$this->Region->ID = $data->Row["Region_ID"];
				$this->Region->Name = $data->Row["Region_Name"];
				$this->Region->CountryID = $data->Row["Country_ID"];
				$this->Region->Code = $data->Row["Region_Code"];
			}

			$this->Country->ID = $data->Row['Country_ID'];
			$this->Country->Name = $data->Row['Country'];
			$this->Country->AddressFormat->Get($data->Row['Address_Format_ID']);
			$this->Country->ISOCode2 = $data->Row['ISO_Code_2'];
			$this->Country->AllowSales = $data->Row['Allow_Sales'];
			$this->Zip = $data->Row['Zip'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)) return false;
		$data = new DataQuery(sprintf("delete from address where Address_ID=%d", mysql_real_escape_string($this->ID)));
		return true;
	}

	function Add($connection = null){
		$regionName = ($this->Region->ID == 0) ? $this->Region->Name : NULL;

		if(empty($this->Region->ID) && !empty($this->Region->Name)){
			$this->Region->ID = $this->Region->GetIDFromString($this->Region->Name);
		}
		if(empty($this->Country->ID) && !empty($this->Country->Name)){
			$this->Country->ID = $this->Country->GetIDFromString($this->Country->Name);
		}

		$data = new DataQuery(sprintf("insert into address (
										Address_Line_1,
										Address_Line_2,
										Address_Line_3,
										City,
										Region_ID,
										Region_Name,
										Country_ID,
										Zip,
										Zip_Search,
										Created_On,
										Created_By,
										Modified_On,
										Modified_By
										) values ('%s', '%s', '%s', '%s', %d, '%s', %d, '%s', '%s', Now(), %d, Now(), %d)",
										mysql_real_escape_string(stripslashes($this->Line1)),
										mysql_real_escape_string(stripslashes($this->Line2)),
										mysql_real_escape_string(stripslashes($this->Line3)),
										mysql_real_escape_string(stripslashes($this->City)),
										mysql_real_escape_string($this->Region->ID),
										mysql_real_escape_string(stripslashes($regionName)),
										mysql_real_escape_string($this->Country->ID),
										mysql_real_escape_string(stripslashes($this->Zip)),
										mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Zip)),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])), $connection);
		$this->ID = $data->InsertID;
		$data->Disconnect();

		return true;
	}

	function Update(){
		if(!is_numeric($this->ID)) return false;
		$check = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM address WHERE Address_ID=%d", mysql_real_escape_string($this->ID)));

		if($check->Row['count'] == 0) {
			$this->Add();
			return true;
		} else {
			$regionName = ($this->Region->ID == 0)? $this->Region->Name : NULL;
			$data = new DataQuery(sprintf("update address set
											Address_Line_1='%s',
											Address_Line_2='%s',
											Address_Line_3='%s',
											City='%s',
											Region_ID=%d,
											Region_Name='%s',
											Country_ID=%d,
											Zip='%s',
											Zip_Search='%s',
											Modified_On=Now(),
											Modified_By=%d
											where Address_ID=%d",
											mysql_real_escape_string(stripslashes($this->Line1)),
											mysql_real_escape_string(stripslashes($this->Line2)),
											mysql_real_escape_string(stripslashes($this->Line3)),
											mysql_real_escape_string(stripslashes($this->City)),
											mysql_real_escape_string($this->Region->ID),
											mysql_real_escape_string(stripslashes($regionName)),
											mysql_real_escape_string($this->Country->ID),
											mysql_real_escape_string(stripslashes($this->Zip)),
											mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $this->Zip)),
											mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
											mysql_real_escape_string($this->ID)));
			return true;
		}
	}

	function GetLongString($sep = '<br />') {
		return $this->GetFormatted($sep, 'long');
	}

	function GetShortString($sep = ', ') {
		return $this->GetFormatted($sep, 'short');
	}

	function GetFormatted($sep, $type='long'){
		$tempStr = ($type == "long") ? $this->Country->AddressFormat->Long : $this->Country->AddressFormat->Short;
		$streets = '';

		if(!empty($this->Line1)) $streets .= $this->Line1;
		if(!empty($this->Line2)) $streets .= $sep . $this->Line2;
		if(!empty($this->Line3)) $streets .= $sep . $this->Line3;

		$patterns = array(
							"/\[sep\]/",
							"/\[streets\]/",
							"/\[city\]/",
							"/\[region\]/",
							"/\[country\]/",
							"/\[zip\]/",
							"/\[countrycode\]/",
							"/\[regioncode\]/");
		$replacements = array(
							$sep,
							$streets,
							$this->City,
							$this->Region->Name,
							$this->Country->Name,
							$this->Zip,
							$this->Country->ISOCode2,
							$this->Region->Code);

		$tempStr = preg_replace($patterns, $replacements, $tempStr);

		while(stripos($tempStr, $sep.$sep) !== false) {
			$tempStr = str_replace($sep.$sep, $sep, $tempStr);
		}

		return stripslashes($tempStr);
	}
}