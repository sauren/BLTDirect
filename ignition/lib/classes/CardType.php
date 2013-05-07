<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	
	class CardType{
		var $ID;
		var $Name;
		var $Reference;
		
		function CardType($id=NULL){
			if(!is_null($id)){
				$this->ID = $id;
				$this->Get();
			}
		}
		
		function Get($id=NULL){
			if(!is_null($id)) $this->ID = $id;
			if(!is_numeric($this->ID)){
				return false;
			}
			$data = new DataQuery(sprintf("select * from card_type where Card_Type_ID=%d", mysql_real_escape_string($this->ID)));
			$this->Name = $data->Row['Card_Type'];
			$this->Reference = $data->Row['Reference'];
			$data->Disconnect();
		}
		
		function getByReference($ref){
			$data = new DataQuery(sprintf("select * from card_type where Reference='%s'", mysql_real_escape_string($ref)));
			$data->Disconnect();
			if($data->TotalRows > 0) {
				$this->ID = $data->Row['Card_Type_ID'];
				$this->Name = $data->Row['Card_Type'];
				$this->Reference = $data->Row['Reference'];
				return true;
			}
			return false;
		}
	}
?>