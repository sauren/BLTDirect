<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	class DiscountProduct{
		var $ID;
		var $DiscountID;
		var $ProductID;
		var $CreatedOn;
		var $CreatedBy;
		var $ModifiedOn;
		var $ModifiedBy;
		
		function DiscountProduct($id=NULL){
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
			
			$sql = sprintf("select * from discount_product where Discount_Product_ID=%d", mysql_real_escape_string($this->ID));
			$data = new DataQuery($sql);
			$this->DiscountID = $data->Row['Discount_Schema_ID'];
			$this->ProductID = $data->Row['Product_ID'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$data->Disconnect();
		}
		
		function Add(){
			$sql = sprintf("insert into discount_product (Discount_Schema_ID, Product_ID, Created_On, Created_By, Modified_On, Modified_By) values (%d, %d, Now(), %d, Now(), %d)", 
								mysql_real_escape_string($this->DiscountID),
								mysql_real_escape_string($this->ProductID),
								mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
								mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));
								
			$data = new DataQuery($sql);
			$this->ID = $data->InsertID;
		}
		
		function Update(){

			if(!is_numeric($this->ID)){
				return false;
			}
			$sql = sprintf("update discount_product set Discount_Schema_ID=%d, Product_ID=%d, Modified_On=Now(), Modified_By=%d where Discount_Product_ID=%d", 
								mysql_real_escape_string($this->DiscountID),
								mysql_real_escape_string($this->ProductID),
								mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
								mysql_real_escape_string($this->ID));
								
			$data = new DataQuery($sql);
		}
		
		function Delete($id=NULL){
			if(!is_null($id)) $this->ID = $id;

			if(!is_numeric($this->ID)){
				return false;
			}
			
			$sql = sprintf("delete from discount_product where Discount_Product_ID=%d", mysql_real_escape_string($this->ID));
			$data = new DataQuery($sql);
		}

		static function DeleteBySchema($id, $product_id) {

			if(!is_numeric($this->ID)){
				return false;
			}
			new DataQuery(sprintf("delete from discount_product where Discount_Schema_ID=%d and Product_ID=%d", mysql_real_escape_string($id), mysql_real_escape_string($product_id)));
		}
	}
?>