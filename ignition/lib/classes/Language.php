<?php
	/*
		Class:		Language.php
		Version:	1.0
		Product:	Ignition
		Author:		Geoff Willings
		
		Copyright (c) Deveus Software, 2004
		
		Notes:
	*/
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	
	class Language{
		var $ID;
		var $Name;
		var $Code;
		
		// Flags
		// We will ignore the flags for now
		var $Flag_Ignition;
		var $Flag_Public;
		
		// Generic Variables
		var $CreatedOn;
		var $CreatedBy;
		var $ModifiedOn;
		var $ModifiedBy;		
				
		function Language($id=NULL){
			if(isset($id)){
				$this->ID = $id;
				$this->Get();
			}
		}
		
		function Get(){
			if(!is_numeric($this->ID)){
				return false;
			}
			$lang = new DataQuery(sprintf("select * from languages where Language_ID=%d", mysql_real_escape_string($this->ID)));
			$this->Name = $lang->Row["Language"];
			$this->Code = $lang->Row["Code"];
			$this->Flag_Ignition = $lang->Row["Flag_Ignition"];
			$this->Flag_Public = $lang->Row["Flag_Public"];
			$this->CreatedOn = $lang->Row["Created_On"];
			$this->CreatedBy = $lang->Row["Created_By"];
			$this->ModifiedOn = $lang->Row["Modified_On"];
			$this->ModifiedBy = $lang->Row["Modified_By"];		
			$lang->Disconnect();
			return true;
		}
		
		function Add(){
			$lang = new DataQuery(sprintf("insert into languages (Language, Code, Flag_Ignition, Flag_Public, Modified_By, Modified_On, Created_On, Created_By) values ('%s', '%s', '%s', '%s', Now(), %d, Now(), %d)",
									mysql_real_escape_string($this->Name),
									mysql_real_escape_string($this->Code),
									mysql_real_escape_string($this->Flag_Ignition),
									mysql_real_escape_string($this->Flag_Public),
									mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
									mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
			return true;
		}
		
		function Remove($id=NULL){
			if(!is_numeric($this->ID)){
				return false;
			}
			if(!is_null($id) && is_numeric($id)) $this->ID = $id;
			$lang = new DataQuery(sprintf("delete from languages where Language_ID=%d",
									mysql_real_escape_string($this->ID)));
			return true;
		}
		
		function Update(){
			if(!is_numeric($this->ID)){
				return false;
			}
			$lang = new DataQuery(sprintf("update languages set Language='%s', Code='%s', Flag_Ignition='%s', Flag_Public='%s', Modified_By=%d, Modified_On=Now() where Language_ID=%d",
									mysql_real_escape_string($this->Name),
									mysql_real_escape_string($this->Code),
									mysql_real_escape_string($this->Flag_Ignition),
									mysql_real_escape_string($this->Flag_Public),
									mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
									mysql_real_escape_string($this->ID)));
			return true;
		}
	}
?>
