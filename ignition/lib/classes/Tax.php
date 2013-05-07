<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Geozone.php');

class Tax{
	var $ID;
	var $Geozone;
	var $ClassID;
	var $Rate;
	var $CodeID;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function Tax($id=NULL){
		$this->Geozone = new Geozone();

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if (!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM tax WHERE Tax_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->ClassID = $data->Row['Tax_Class_ID'];
			$this->Geozone->ID = $data->Row['Geozone_ID'];
			$this->Rate = $data->Row['Tax_Rate'];
			$this->CodeID = $data->Row['Tax_Code_ID'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			if($this->Geozone->ID > 0) {
				$this->Geozone->Get();
			}

		$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$sql = sprintf("insert into tax (Tax_Class_ID, Geozone_ID, Tax_Rate, Tax_Code_ID, Created_On, Created_By, Modified_On, Modified_By)
						values (%d, %d, %f, %d, Now(), %d, Now(), %d)",
						mysql_real_escape_string($this->ClassID),
						mysql_real_escape_string($this->Geozone->ID),
						mysql_real_escape_string($this->Rate),
						mysql_real_escape_string($this->CodeID),
						mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
						mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));

		$data = new DataQuery($sql);

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf("update tax set Tax_Class_ID=%d, Geozone_ID=%d, Tax_Rate=%f, Tax_Code_ID=%d, Modified_On=Now(), Modified_By=%d where Tax_ID=%d",
						mysql_real_escape_string($this->ClassID),
						mysql_real_escape_string($this->Geozone->ID),
						mysql_real_escape_string($this->Rate),
						mysql_real_escape_string($this->CodeID),
						mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
						mysql_real_escape_string($this->ID));

		new DataQuery($sql);
	}

	function Remove($id = NULL) {
		if (!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM tax WHERE Tax_ID=%d", mysql_real_escape_string($this->ID)));
	}

	static function TaxCodeChange($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE tax SET Tax_Code_ID=0 WHERE Tax_Code_ID=%d", mysql_real_escape_string($id)));
	}
}
?>