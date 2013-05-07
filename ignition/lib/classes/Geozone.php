<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Shipping.php');

class Geozone {
	var $ID;
	var $Name;
	var $Description;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function Geozone($id=NULL){
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Add(){
		$data = new DataQuery(sprintf("insert into geozone (
										Geozone_Title,
										Geozone_Description,
										Created_On,
										Created_By,
										Modified_On,
										Modified_By
										) values ('%s', '%s', Now(), %d, Now(), %d)",
										mysql_real_escape_string($this->Name),
										mysql_real_escape_string($this->Description),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		return true;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("update geozone set
										Geozone_Title='%s',
										Geozone_Description='%s',
										Modified_On=Now(),
										Modified_By=%d
										where Geozone_ID=%d",
										mysql_real_escape_string($this->Name),
										mysql_real_escape_string($this->Description),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($this->ID)));
		return true;
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("delete from geozone where Geozone_ID=%d", mysql_real_escape_string($this->ID)));
		Shipping::DeleteGeozone($this->ID);
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("select * from geozone where Geozone_ID=%d", mysql_real_escape_string($this->ID)));
		$this->Name = $data->Row['Geozone_Title'];
		$this->Description = $data->Row['Geozone_Description'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];
		$data->Disconnect();
		return true;
	}
}
?>