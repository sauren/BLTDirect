<?php
	/*
		Class:		Person.php
		Version:	1.0
		Product:	Ignition
		Author:		Geoff Willings
		
		Copyright (c) Deveus Software, 2005
		
		Notes:
	*/
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	
	class Greeting{
		var $ID;
		var $Name;
		var $CreatedOn;
		var $CreatedBy;
		var $ModifiedOn;
		var $ModifiedBy;
		
		function Greeting($id=NULL){
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
			$data = new DataQuery(sprintf("select * from greeting where Greeting_ID=%d", mysql_real_escape_string($this->ID)));
			$this->Name = $data->Row['Greeting_Text'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$data->Disconnect();
			return true;
		}
		
		function Delete($id=NULL){
			if(!is_null($id)) $this->ID = $id;
			if(!is_numeric($this->ID)){
				return false;
			}
			$data = new DataQuery(sprintf("delete from greeting where Greeting_ID=%d", mysql_real_escape_string($this->ID)));
			return true;
		}
		
		function Add(){
			$data = new DataQuery(sprintf("insert into greeting (
											Greeting_Text,
											Created_On,
											Created_By,
											Modified_On,
											Modified_By
											) values ('%s', Now(), %d, Now(), %d)",
											mysql_real_escape_string($this->Name),
											mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
											mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
			$this->ID = $data->InsertID;
			return true;
		}
		
		function Update(){
			if(!is_numeric($this->ID)){
				return false;
			}
			$data = new DataQuery(sprintf("update greeting set
											Greeting_Text='%s',
											Modified_On=Now(),
											Modified_By=%d
											where Greeting_ID=%d", 
											mysql_real_escape_string($this->Name),
											mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
											mysql_real_escape_string($this->ID)));
			return true;
		}
	}
?>