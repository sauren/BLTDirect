<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class IntegrationSage {
	var $ID;
	var $DataFeed;
	var $Type;
	var $IsSynchronised;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function __construct($id = NULL) {
		$this->Type = 'E';
		$this->IsSynchronised = 'N';

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM integration_sage WHERE Integration_Sage_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->DataFeed = $data->Row['Data_Feed'];
			$this->Type = $data->Row['Type'];
			$this->IsSynchronised = $data->Row['Is_Synchronised'];
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
		$data = new DataQuery(sprintf("INSERT INTO integration_sage (Data_Feed, Type, Is_Synchronised, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->DataFeed), mysql_real_escape_string($this->Type), mysql_real_escape_string($this->IsSynchronised), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE integration_sage SET Data_Feed='%s', Type='%s', Is_Synchronised='%s', Modified_On=NOW(), Modified_By=%d WHERE Integration_Sage_ID=%d", mysql_real_escape_string($this->DataFeed), mysql_real_escape_string($this->Type), mysql_real_escape_string($this->IsSynchronised), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM integration_sage WHERE Integration_Sage_ID=%d", mysql_real_escape_string($this->ID)));
	}
}