<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	class DiscountCustomer{
		var $ID;
		var $DiscountID;
		var $CustomerID;
		var $CreatedOn;
		var $CreatedBy;
		var $ModifiedOn;
		var $ModifiedBy;
		
		function DiscountCustomer($id=NULL){
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
			$sql = sprintf("select * from discount_customer where Discount_Customer_ID=%d", mysql_real_escape_string($this->ID));
			$data = new DataQuery($sql);
			$this->DiscountID = $data->Row['Discount_Schema_ID'];
			$this->CustomerID = $data->Row['Customer_ID'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$data->Disconnect();
		}
		
		function Add(){
			$sql = sprintf("insert into discount_customer (Discount_Schema_ID, Customer_ID, Created_On, Created_By, Modified_On, Modified_By) values (%d, %d, Now(), %d, Now(), %d)", 
								mysql_real_escape_string($this->DiscountID),
								mysql_real_escape_string($this->CustomerID),
								mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
								mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));
								
			$data = new DataQuery($sql);
			$this->ID = $data->InsertID;
		}
		
		function Update(){

			if(!is_numeric($this->ID)){
				return false;
			}
			$sql = sprintf("update discount_customer set Discount_Schema_ID=%d, Customer_ID=%d, Modified_On=Now(), Modified_By=%d where Discount_Product_ID=%d", 
								mysql_real_escape_string($this->DiscountID),
								mysql_real_escape_string($this->CustomerID),
								mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
								mysql_real_escape_string($this->ID));
								
			$data = new DataQuery($sql);
		}
		
		function Delete($id=NULL){
			if(!is_null($id)) $this->ID = $id;

			if(!is_numeric($this->ID)){
				return false;
			}
			
			$sql = sprintf("delete from discount_customer where Discount_Customer_ID=%d", mysql_real_escape_string($this->ID));
			$data = new DataQuery($sql);
		}
		
		function Exists(){
			$sql = sprintf("select * from discount_customer where Discount_Schema_ID=%d and Customer_ID=%d", mysql_real_escape_string($this->DiscountID), mysql_real_escape_string($this->CustomerID));
			$data = new DataQuery($sql);
			$data->Disconnect();
			if($data->TotalRows > 0){
				return true;
			} else {
				return false;
			}
		}

		static function DeleteContact($id){

			if(!is_numeric($id)){
				return false;
			}
			new DataQuery(sprintf("DELETE FROM discount_customer WHERE Customer_ID=%d", mysql_real_escape_string($id)));
		}
	}
?>