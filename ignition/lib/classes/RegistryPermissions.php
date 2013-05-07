<?php

require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class RegistryPermissions {
	
	public $id;
	public $access;
	public $permission;
	public $registry;
	public $createdOn;
	public $createdBy;
	public $modifiedBy;
	public $modifiedOn;

	public function __construct($id = null){
		if(!is_null($id)){
			$this->id = $id;
			$this->get();
		}
	}

	public function get($id = null){
		if(!is_null($id)){
			$this->id = $id;
		}
		
		if(!is_numeric($this->id)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM registry_permissions WHERE Registry_Permission_ID =%d", mysql_real_escape_string($this->id)));
		
		if($data->TotalRows > 0) {
			$this->access = $data->Row['Access_ID'];
			$this->permission = $data->Row['Permission_ID'];
			$this->registry = $data->Row['Registry_ID'];
			$this->createdOn = $data->Row['Created_On'];
			$this->createdBy = $data->Row['Created_By'];
			$this->modifiedOn = $data->Row['Modified_On'];
			$this->modifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}
		$data->Disconnect();
		return false;
	}

	public function add(){
		$data = new DataQuery(sprintf("INSERT INTO registry_permissions(Access_ID, Permission_ID, Registry_ID, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->access), mysql_real_escape_string($this->permission), mysql_real_escape_string($this->registry), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->id = $data->InsertID;
	}

	public function update(){

		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE registry_permissions SET Access_ID = %d, Permission_ID = %d, Registry_ID = %d, Modified_On = NOW(), Modified_By = %d WHERE Registry_Permission_ID = %d", mysql_real_escape_string($this->access), mysql_real_escape_string($this->permission), mysql_real_escape_string($this->registry), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null){
		if(!is_null($id)){
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM registry_permissions WHERE Registry_Permission_ID=%d", mysql_real_escape_string($this->id)));
	}

	public function deleteByRegistryAccess($registry = null, $access = null){
		if(!is_null($registry)){
			$this->registry = $registry;
		}

		if(!is_null($access)){
			$this->access = $access;
		}

		new DataQuery(sprintf("DELETE FROM registry_permissions WHERE Registry_ID=%d AND Access_ID=%d", mysql_real_escape_string($this->registry), mysql_real_escape_string($this->access)));
	}
}
