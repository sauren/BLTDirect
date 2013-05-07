<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Country.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Region.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Geozone.php");

class GeozoneAssoc {
	var $ID;
	var $Parent;
	var $Country;
	var $Region;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function GeozoneAssoc($id=NULL){
		$this->Country = new Country;
		$this->Region = new Region;
		$this->Parent = new Geozone;

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("delete from geozone_assoc where Geozone_Assoc_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("select * from geozone_assoc where Geozone_Assoc_ID=%d", mysql_real_escape_string($this->ID)));
		$this->Parent->ID = $data->Row['Geozone_ID'];
		$this->Country->ID = $data->Row['Country_ID'];
		$this->Region->ID = $data->Row['Region_ID'];
		$this->CreatedOn  = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];
		$data->Disconnect();
		return true;
	}

	function Add(){
		$data = new DataQuery(sprintf("insert into geozone_assoc (
										Geozone_ID,
										Country_ID,
										Region_ID,
										Created_On,
										Created_By,
										Modified_On,
										Modified_By
										) values (%d, %d, %d, Now(), %d, Now(), %d)",
										mysql_real_escape_string($this->Parent->ID),
										mysql_real_escape_string($this->Country->ID),
										mysql_real_escape_string($this->Region->ID),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("update geozone_assoc set
										Country_ID=%d,
										Region_ID=%d,
										Modified_On=Now(),
										Modified_By=%d
										where Geozone_Assoc_ID=%d",
										mysql_real_escape_string($this->Country->ID),
										mysql_real_escape_string($this->Region->ID),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($this->ID)));
	}
}
?>