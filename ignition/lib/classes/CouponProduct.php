<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	class CouponProduct{
		var $ID;
		var $CouponID;
		var $ProductID;
		var $CreatedOn;
		var $CreatedBy;
		var $ModifiedOn;
		var $ModifiedBy;
		
		function CouponProduct($id=NULL){
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
			
			$sql = sprintf("select * from coupon_product where Coupon_Product_ID=%d", mysql_real_escape_string($this->ID));
			$data = new DataQuery($sql);
			$this->CouponID = $data->Row['Coupon_ID'];
			$this->ProductID = $data->Row['Product_ID'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$data->Disconnect();
		}
		
		function Add(){
			$sql = sprintf("insert into coupon_product (Coupon_ID, Product_ID, Created_On, Created_By, Modified_On, Modified_By) values (%d, %d, Now(), %d, Now(), %d)", 
								mysql_real_escape_string($this->CouponID),
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
			$sql = sprintf("update coupon_product set Coupon_ID=%d, Product_ID=%d, Modified_On=Now(), Modified_By=%d where Coupon_Product_ID=%d", 
								mysql_real_escape_string($this->CouponID),
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
			
			$sql = sprintf("delete from coupon_product where Coupon_Product_ID=%d", mysql_real_escape_string($this->ID));
			$data = new DataQuery($sql);
		}
	}
?>