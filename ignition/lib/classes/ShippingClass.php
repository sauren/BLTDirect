<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Shipping.php');

class ShippingClass{
	public $ID;
	public $Name;
	public $Description;
	public $IsDefault;
	public $UnavailableDescription;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;
	public $Option;

	function ShippingClass($id=NULL) {
		$this->Option = array();

		if(!empty($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!empty($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("select * from shipping_class where Shipping_Class_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Shipping_Class_Title'];
			$this->Description = $data->Row['Shipping_Class_Description'];
			$this->IsDefault = $data->Row['Is_Default'];
			$this->UnavailableDescription = $data->Row['Unavailable_Description'];
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
		$data = new DataQuery(sprintf("insert into shipping_class (
										Shipping_Class_Title,
										Shipping_Class_Description,
										Is_Default,
										Unavailable_Description,
										Created_On,
										Created_By,
										Modified_On,
										Modified_By) values (
										'%s', '%s', '%s', '%s', Now(), %d, Now(), %d)",
										mysql_real_escape_string($this->Name),
										mysql_real_escape_string($this->Description),
										mysql_real_escape_string($this->IsDefault),
										mysql_real_escape_string($this->UnavailableDescription),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
	}

	function Update(){
		if(strtoupper($this->IsDefault) == 'Y'){
			$reset = new DataQuery("update shipping_class set Is_Default='N'");
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("update shipping_class set
										Shipping_Class_Title='%s',
										Shipping_Class_Description='%s',
										Is_Default='%s',
										Unavailable_Description='%s',
										Modified_On=Now(),
										Modified_By=%d
										where Shipping_Class_ID=%d",
										mysql_real_escape_string($this->Name),
										mysql_real_escape_string($this->Description),
										mysql_real_escape_string($this->IsDefault),
										mysql_real_escape_string($this->UnavailableDescription),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($this->ID)));
	}

	function Delete($id = NULL){
		if(!is_null($id) && !empty($id)) $this->ID=$id;
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("delete from shipping_class where Shipping_Class_ID=%d", mysql_real_escape_string($this->ID)));
		Shipping::DeleteClass($this->ID);
	}

	function Exists($name=NULL){
		if(!empty($name)) $this->Name = $name;
		$found = false;
		$data = new DataQuery(sprintf("select * from shipping_class where Shipping_Class_Title='%s'", mysql_real_escape_string($this->Name)));
		if($data->TotalRows > 0){
			$this->ID = $data->Row['Shipping_Class_ID'];
			$this->Description = $data->Row['Shipping_Class_Description'];
			$found = true;
		}
		$data->Disconnect();
		return $found;
	}
}
?>