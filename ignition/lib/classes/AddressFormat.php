<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	
class AddressFormat{
	var $ID;
	var $Short;
	var $Long;

	function AddressFormat($id=NULL){
		$this->Short = $GLOBALS['SYSTEM_ADDRESS_FORMAT_SHORT'];
		$this->Long = $GLOBALS['SYSTEM_ADDRESS_FORMAT_LONG'];

		if(!is_null($id)){
			$this->Get($id);
		}
	}
		
	function Get($id=NULL){
		if(!is_null($id)){ $this->ID = $id; }
		if(!is_numeric($this->ID)) return false;

		$data = new DataQuery(sprintf("SELECT * FROM address_format WHERE Address_Format_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Short = $data->Row['Address_Summary'];
			$this->Long = $data->Row['Address_Format'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
}
?>
