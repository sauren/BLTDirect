<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	
	class SubscriptionType{
		var $ID;
		var $Title;
		
		function SubscriptionType($id = NULL){
			if(!is_null($id)){
				$this->ID = $id;
				$this->Get();
				}
			
		}
		
		function Get($id = NULL){
			if(!is_null($id)) $this->ID = $id;

		if(!is_numeric($this->ID)){
			return false;
		}
			$data = new DataQuery(sprintf("SELECT * FROM news_subscription_type WHERE Type_ID = %d",mysql_real_escape_string($this->ID)));
			$this->Title = $data->Row['Title'];
		}
		
		function Add(){
			$sql = sprintf("INSERT INTO news_subscription_type (Title,
																Created_ON,
																Created_By,
																Modified_On,
																Modified_By)
											VALUES('%s',Now(),%d,Now(),%d)",	
																mysql_real_escape_string($this->Title),
																mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
																mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));
																
		
			$data = new DataQuery($sql);													
			$this->ID = $data->InsertID;
			$data->Disconnect();
			unset($data);
			if(empty($data->Error))
				return true;
			else
				return false;
		}
		
		function Update(){

			if(!is_numeric($this->ID)){
				return false;
			}
			$sql = sprintf("UPDATE news_subscription_type SET Title = '%s',
															  Modified_On = Now(),
															  Modified_BY = %d
															  WHERE Type_ID = %d",
															 mysql_real_escape_string($this->Title),
															 mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
															 mysql_real_escape_string($this->ID));
			
			$data = new DataQuery($sql);
			$data->Disconnect();
			unset($data);
			return true;
															
		}
		
		function Delete($id=NULL){
			if(!is_null($id)) $this->ID = $id;

			if(!is_numeric($this->ID)){
				return false;
			}
			$data = new DataQuery(sprintf("DELETE FROM news_subscription_type WHERE Type_ID = %d",mysql_real_escape_string($this->ID)));
			$data->Disconnect();
			unset($data);
		}
		
	}
?>