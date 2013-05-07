<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	class Region{
		var $ID;
		var $Name;
		var $CountryID;
		var $Country;
		var $Code;

		var $CreatedOn;
		var $CreatedBy;
		var $ModifiedOn;
		var $ModifiedBy;

		function Region($id = NULL){
			if(!is_null($id) && is_numeric($id)){
				$this->ID = $id;
				$this->Get();
			}
		}

		function Add(){
			$region = new DataQuery(sprintf("INSERT INTO regions
											(Region_Name, Country_ID,
											Region_Code, Created_On,
											Created_By, Modified_On,
											Modified_By)
											VALUES ('%s', %d, '%s', Now(),
													%d, Now(), %d)",
											mysql_real_escape_string($this->Name),
											mysql_real_escape_string($this->CountryID),
											mysql_real_escape_string($this->Code),
											mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
											mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
			$this->ID = $region->InsertID;
			return true;
		}

		function Update(){
			if(!is_numeric($this->ID)){
			return false;
		}
			$region = new DataQuery(sprintf("UPDATE regions
											SET Region_Name='%s', Country_ID=%d,
											Region_Code='%s', Modified_On=Now(),
											Modified_By=%d where Region_ID=%d",
											mysql_real_escape_string($this->Name),
											mysql_real_escape_string($this->CountryID),
											mysql_real_escape_string($this->Code),
											mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
											mysql_real_escape_string($this->ID)));
			return true;
		}

		function Remove($id=NULL){
			if(!is_null($id) && is_numeric($id)) $this->ID = $id;
			if(!is_numeric($this->ID)){
			return false;
		}
			$region = new DataQuery(sprintf("DELETE FROM regions
											WHERE Region_ID=%d", mysql_real_escape_string($this->ID)));
			return true;
		}

		function Get($id=NULL){
			if(!is_null($id) && is_numeric($id)) $this->ID = $id;
			if(!is_numeric($this->ID)){
				return false;
			}
			$region = new DataQuery(sprintf("SELECT * FROM regions
											WHERE Region_ID=%d", mysql_real_escape_string($this->ID)));

			$this->ID = $region->Row["Region_ID"];
			$this->Name = $region->Row["Region_Name"];
			$this->CountryID = $region->Row["Country_ID"];
			$this->Code = $region->Row["Region_Code"];
			$this->CreatedOn = $region->Row["Created_On"];
			$this->CreatedBy = $region->Row["Created_By"];
			$this->ModifiedOn = $region->Row["Modified_On"];
			$this->ModifiedBy = $region->Row["Modified_By"];

			$region->Disconnect();
			return true;
		}

		function GetIDFromString($str=NULL){
			if(!is_null($str)) $this->Name = $str;
			$region = new DataQuery(sprintf("SELECT * FROM regions WHERE Region_Name LIKE '%s'", mysql_real_escape_string(stripslashes($this->Name))));
			if($region->TotalRows > 0) {
				$this->ID = $region->Row["Region_ID"];
				$this->Name = $region->Row["Region_Name"];
				$this->CountryID = $region->Row["Country_ID"];
				$this->Code = $region->Row["Region_Code"];
				$this->CreatedOn = $region->Row["Created_On"];
				$this->CreatedBy = $region->Row["Created_By"];
				$this->ModifiedOn = $region->Row["Modified_On"];
				$this->ModifiedBy = $region->Row["Modified_By"];
			} else {
				$this->ID = 0;
			}
			$region->Disconnect();

			return true;
		}
	}
?>