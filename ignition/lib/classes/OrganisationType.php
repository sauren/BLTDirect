<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class OrganisationType{
	var $ID;
	var $Name;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	
	function OrganisationType($id=NULL){
		if(!is_null($id) && is_numeric($id)){
			$this->ID = $id;
			$this->Get();
		}
	}
	
	function Get($id=NULL){
		if(!is_null($id) && is_numeric($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("select * from organisation_type where Org_Type_ID=%d", mysql_real_escape_string($this->ID)));
		$this->Name = $data->Row['Org_Type'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];
		$data->Disconnect();
		return true;
	}
	
	function Delete($id=NULL){
		if(!is_null($id) && is_numeric($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("delete from organisation_type where Org_Type_ID=%d", mysql_real_escape_string($this->ID)));
	}
	
	function Add(){
		$data = new DataQuery(sprintf("insert into organisation_type (
										Org_Type,
										Created_On,
										Created_By,
										Modified_On,
										Modified_By
										) values ('%s', Now(), %d, Now(), %d)", 
										mysql_real_escape_string($this->Name),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
	}
	
	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("update organisation_type set
										Org_Type='%s',
										Modified_On=Now(),
										Modified_By=%d
										where Org_Type_ID=%d", 
										mysql_real_escape_string($this->Name),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($this->ID)));
	}
}