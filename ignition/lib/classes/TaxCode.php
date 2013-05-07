<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Tax.php');

class TaxCode {
	var $ID;
	var $IntegrationReference;
	var $Description;
	var $Rate;
	var $IsDefault;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function __construct($id=NULL) {
		$this->IsDefault = 'N';

		if(isset($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM tax_code WHERE Tax_Code_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->Row) {
			$this->IntegrationReference = $data->Row['Integration_Reference'];
			$this->Description = $data->Row['Description'];
			$this->Rate = $data->Row['Rate'];
			$this->IsDefault = $data->Row['Is_Default'];
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

	function GetByIntegrationReference($integrationReference=NULL) {
		if(!is_null($integrationReference)) {
			$this->IntegrationReference = $integrationReference;
		}

		$data = new DataQuery(sprintf("SELECT Tax_Code_ID FROM tax_code WHERE Integration_Reference='%s'", mysql_real_escape_string($this->IntegrationReference)));
		if($data->Row) {
			$return = $this->Get($data->Row['Tax_Code_ID']);

			$data->Disconnect();
			return $return;
		}
		$data->Disconnect();
		return false;
	}

	function Add(){
		if($this->IsDefault == 'Y') {
			$this->ClearDefault();
		}

		$data = new DataQuery(sprintf("INSERT INTO tax_code (Integration_Reference, Description, Rate, Is_Default, Created_On, Created_By, Modified_On, Modified_By) values ('%s', '%s', %f, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->IntegrationReference), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->Rate), mysql_real_escape_string($this->IsDefault), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update(){
		if($this->IsDefault == 'Y') {
			$this->ClearDefault();
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("UPDATE tax_code SET Integration_Reference='%s', Description='%s', Rate=%f, Is_Default='%s', Modified_On=NOW(), Modified_By=%d WHERE Tax_Code_ID=%d", mysql_real_escape_string($this->IntegrationReference), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->Rate), mysql_real_escape_string($this->IsDefault), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function ClearDefault() {
		new DataQuery(sprintf("UPDATE tax_code SET Is_Default='N'"));
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM tax_code WHERE Tax_Code_ID=%d", mysql_real_escape_string($this->ID)));
		Tax::TaxCodeChange($this->ID);
	}
}