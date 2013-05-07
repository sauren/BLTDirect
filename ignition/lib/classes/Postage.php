<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class Postage{
	var $ID;
	var $Name;
	var $Description;
	var $Days;
	var $CuttOffTime;
	var $Message;
	var $StartDay;
	var $StartTime;
	var $EndDay;
	var $EndTime;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $OrderAmount;
	var $Tax;
	var $DeliveryCharge;
	var $Total;
	var $Surcharge;
	var $Count;
	var $HighestPostageCount;
	var $HighestWeightThreshold;
	var $HighestPerKiloCharge;
	var $OrderTax;

	function __construct($id=NULL) {
		$this->StartTime = '00:00';
		$this->EndTime = '00:00';
		$this->Total = 0;
		$this->Tax = 0;
		$this->Surcharge = 0;
		$this->DeliveryCharge = 0;
		$this->HighestPostageCount = 0;
		$this->HighestWeightThreshold = 0;
		$this->HighestPerKiloCharge = 0;

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO postage
									  (Postage_Title,
									  Postage_Description,
									  Postage_Start_Day,
									  Postage_Start_Time,
									  Postage_End_Day,
									  Postage_End_Time,
									  Postage_Days,
									  Cutt_Off_Time, Created_On,
									  Created_By, Modified_On,
									  Modified_By,Cut_Off_Message)
									  VALUES ('%s', '%s', %d, '%s', %d, '%s', %d, '%s',
									  Now(), %d, Now(), %d,'%s')",
									mysql_real_escape_string($this->Name),
									mysql_real_escape_string($this->Description),
									mysql_real_escape_string($this->StartDay),
									mysql_real_escape_string($this->StartTime),
									mysql_real_escape_string($this->EndDay),
									mysql_real_escape_string($this->EndTime),
									mysql_real_escape_string($this->Days),
									mysql_real_escape_string($this->CuttOffTime),
									mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
									mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
									mysql_real_escape_string($this->Message)));

		$this->ID = $data->InsertID;
	}

	function Update(){
		new DataQuery(sprintf("update postage set Postage_Title='%s', Postage_Description='%s', Postage_Start_Day=%d, Postage_Start_Time='%s', Postage_End_Day=%d, Postage_End_Time='%s', Postage_Days=%d, Cutt_Off_Time='%s', Modified_On=Now(), Modified_By=%d, Cut_Off_Message='%s'
											where Postage_ID=%d",
											mysql_real_escape_string($this->Name),
											mysql_real_escape_string($this->Description),
											mysql_real_escape_string($this->StartDay),
											mysql_real_escape_string($this->StartTime),
											mysql_real_escape_string($this->EndDay),
											mysql_real_escape_string($this->EndTime),
											mysql_real_escape_string($this->Days),
											mysql_real_escape_string($this->CuttOffTime),
											mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
											mysql_real_escape_string($this->Message),
											mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		
		new DataQuery(sprintf("delete from postage where Postage_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Get($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		
		$data =  new DataQuery(sprintf("SELECT * FROM postage
										WHERE Postage_ID=%d",
										mysql_real_escape_string($this->ID)));
		$this->Name = $data->Row['Postage_Title'];
		$this->Description = $data->Row['Postage_Description'];
		$this->StartDay = $data->Row['Postage_Start_Day'];
		$this->StartTime = $data->Row['Postage_Start_Time'];
		$this->EndDay = $data->Row['Postage_End_Day'];
		$this->EndTime = $data->Row['Postage_End_Time'];
		$this->Days = $data->Row['Postage_Days'];
		$this->CuttOffTime = $data->Row['Cutt_Off_Time'];
		$this->Message = $data->Row['Cut_Off_Message'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];
		$data->Disconnect();
		return true;
	}

	function GetByName($name){
		$sql = sprintf("select * from postage where Postage_Title like '%s'",  mysql_real_escape_string($name));
		$data = new DataQuery($sql);

		$returnValue = false;
		if($data->TotalRows > 0){
			$this->ID = $data->Row['Postage_ID'];
			$this->Name = $data->Row['Postage_Title'];
			$this->Description = $data->Row['Postage_Description'];
			$this->StartDay = $data->Row['Postage_Start_Day'];
			$this->StartTime = $data->Row['Postage_Start_Time'];
			$this->EndDay = $data->Row['Postage_End_Day'];
			$this->EndTime = $data->Row['Postage_End_Time'];
			$this->Days = $data->Row['Postage_Days'];
			$this->CuttOffTime = $data->Row['Cutt_Off_Time'];
			$this->Message = $data->Row['Cut_Off_Message'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$returnValue = true;
		}
		$data->Disconnect();
		return $returnValue;
	}
}
?>