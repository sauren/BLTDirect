<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Tax.php");

class TaxClass{
	var $ID;
	var $Name;
	var $Description;
	var $IsDefault;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Calculator;

	function TaxClass($id=NULL){
		if(isset($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id) && is_numeric($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("select * from tax_class where Tax_Class_ID=%d", mysql_real_escape_string($this->ID)));
		$this->Name = $data->Row['Tax_Class_Title'];
		$this->Description = $data->Row['Tax_Class_Description'];
		$this->IsDefault = $data->Row['Is_Default'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];
		$data->Disconnect();
		$data = NULL;
	}

	function GetDefault(){
		$sql = "SELECT * FROM tax_class WHERE Is_Default = 'Y'";
		$data = new DataQuery($sql);
		if($data->TotalRows > 0){
			$this->ID = $data->Row['Tax_Class_ID'];
			$this->Name = $data->Row['Tax_Class_Title'];
			$this->Description = $data->Row['Tax_Class_Description'];
			$this->IsDefault = $data->Row['Is_Default'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$data->Disconnect();
		}
	}

	function Add(){
		$data = new DataQuery(sprintf("insert into tax_class (
									Tax_Class_Title,
									Tax_Class_Description,
									Is_Default,
									Created_On,
									Created_By,
									Modified_On,
									Modified_By) values ('%s','%s','%s', Now(), %d, Now(), %d)",
		mysql_real_escape_string($this->Name),
		mysql_real_escape_string($this->Description),
		mysql_real_escape_string($this->IsDefault),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		return true;
	}

	function Update(){
		if(strtoupper($this->IsDefault) == 'Y'){
			$reset = new DataQuery("update tax_class set Is_Default='N'");
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("update tax_class set
									Tax_Class_Title='%s',
									Tax_Class_Description='%s',
									Is_Default='%s',
									Modified_On=Now(),
									Modified_By=%d
									where Tax_Class_ID=%d",
		mysql_real_escape_string($this->Name),
		mysql_real_escape_string($this->Description),
		mysql_real_escape_string($this->IsDefault),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->ID)));
		return true;
	}

	function Exists($name=NULL){
		if(!empty($name)) $this->Name = $name;
		$found = false;
		$data = new DataQuery(sprintf("select * from tax_class where Tax_Class_Title='%s'", mysql_real_escape_string($this->Name)));
		if($data->TotalRows > 0){
			$this->ID = $data->Row['Tax_Class_ID'];
			$this->Description = $data->Row['Tax_Class_Description'];
			$found = true;
		}
		$data->Disconnect();
		unset($data);
		return $found;
	}

	function Remove($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		// Remove Class
		$sql = sprintf("delete from tax_class where Tax_Class_ID=%d", mysql_real_escape_string($this->ID));
		$data = new DataQuery($sql);
		unset($data);

		// Remove Associated Tax Settings
		$data = new DataQuery(sprintf("select Tax_ID from tax where Tax_Class_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$tempTax = new Tax;
			$tempTax->Remove($data->Row['Tax_ID']);
			$data->Next();
		}
		$data->Disconnect();
	}
}
?>