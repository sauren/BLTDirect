<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class OrganisationIndustry{
	var $ID;
	var $Name;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	
	function OrganisationIndustry($id=NULL){
		if(!is_null($id) && is_numeric($id)){
			$this->ID = $id;
			$this->Get();
		}
	}
	
	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("select * from organisation_industry where Industry_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Industry_Name'];
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
		if(!is_null($id) && is_numeric($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("delete from organisation_industry where Industry_ID=%d", mysql_real_escape_string($this->ID)));
		unset($data);
		return true;
	}
	
	function Add(){
		$data = new DataQuery(sprintf("insert into organisation_industry (
										Industry_Name,
										Created_On,
										Created_By,
										Modified_On,
										Modified_By
										) values ('%s', Now(), %d, Now(), %d)", 
										mysql_real_escape_string($this->Name),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		unset($data);
		return true;
	}
	
	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("update organisation_industry set
										Industry_Name='%s',
										Modified_On=Now(),
										Modified_By=%d
										where Industry_ID=%d", 
										mysql_real_escape_string($this->Name),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($this->ID)));
		unset($data);
		return true;
	}
}
?>