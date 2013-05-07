<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class Manufacturer{
	var $ID;
	var $Name;
	var $URL;
	var $IsDataProjector;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function Manufacturer($id=NULL){
		$this->IsDataProjector = 'N';
		
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM manufacturer WHERE Manufacturer_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Manufacturer_Name'];
			$this->URL = $data->Row['Manufacturer_URL'];
			$this->IsDataProjector = $data->Row['IsDataProjector'];
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

	function Add(){
		$data = new DataQuery(sprintf("insert into manufacturer (
														Manufacturer_Name,
														Manufacturer_URL,
														IsDataProjector,
														Created_On,
														Created_By,
														Modified_On,
														Modified_By)
														values ('%s', '%s', '%s', Now(), %d, Now(), %d)",
		mysql_real_escape_string($this->Name),
		mysql_real_escape_string($this->URL),
		mysql_real_escape_string($this->IsDataProjector),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();

		return true;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("update manufacturer set
														Manufacturer_Name='%s',
														Manufacturer_URL='%s',
														IsDataProjector='%s',
														Modified_On=Now(),
														Modified_By=%d
														where Manufacturer_ID=%d",
		mysql_real_escape_string($this->Name),
		mysql_real_escape_string($this->URL),
		mysql_real_escape_string($this->IsDataProjector),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->ID)));
		$data->Disconnect();

		return true;
	}

	function Remove(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("delete from manufacturer where Manufacturer_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();

		return true;
	}

	function Exists($name=NULL){
		if(!is_null($name)) $this->Name = $name;
		if(!is_numeric($this->ID)){
			return false;
		}
		$chkManufacturer = new DataQuery(sprintf("select * from manufacturer where Manufacturer_Name='%s'", mysql_real_escape_string($this->Name)));
		$chkManufacturer->Disconnect();
		if ($chkManufacturer->TotalRows > 0){
			$this->ID = $chkManufacturer->Row["Manufacturer_ID"];
			$chkManufacturer->Disconnect();
			$chkManufacturer = NULL;
			return true;
		} else {
			$chkManufacturer->Disconnect();
			return false;
		}
	}
}