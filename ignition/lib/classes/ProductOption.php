<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	
	class ProductOption{
		var $ID;
		var $Name;
		var $ParentGroupID;
		var $UseProductID;
		var $Price;
		var $IsActive;
		var $IsSelected;
		var $Quantity;
		
		var $CreatedOn;
		var $CreatedBy;
		var $ModifiedOn;
		var $ModifiedBy;
		
		function ProductOption($id=NULL){
			if(!is_null($id)){
				$this->ID = $id;
				$this->Get($id);
			}
		}
		
		function Get($id=NULL){
			if(!is_null($id)){
				$this->ID = $id;
			}
			if(!is_numeric($this->ID)){
				return false;
			}
			$data = new DataQuery(sprintf("select * from product_options where Product_Option_ID=%d", mysql_real_escape_string($this->ID)));
			$this->Name = $data->Row['Option_Title'];
			$this->ParentGroupID = $data->Row['Product_Option_Group_ID'];
			$this->UseProductID = $data->Row['Use_Existing_Product_ID'];
			$this->Price = $data->Row['Option_Price'];
			$this->IsActive = $data->Row['Is_Active'];
			$this->IsSelected = $data->Row['Is_Selected'];
			$this->Quantity = $data->Row['Option_Quantity'];
			
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$data->Disconnect();
			return true;
		}
		
		function Delete($id=NULL){
			if(!is_null($id)){
				$this->ID = $id;
			}
			if(!is_numeric($this->ID)){
				return false;
			}
			$data = new DataQuery(sprintf("delete from product_options where Product_Option_ID=%d", mysql_real_escape_string($this->ID)));
			return true;
		}
		
		function Add(){
			$data = new DataQuery(sprintf("insert into product_options (
										Option_Title,
										Product_Option_Group_ID,
										Use_Existing_Product_ID,
										Option_Price,
										Is_Active,
										Is_Selected,
										Option_Quantity,
										Created_On,
										Created_By,
										Modified_On,
										Modified_By) values ('%s', %d, %d, %f, '%s', '%s', %d, Now(), %d, Now(), %d)",
										mysql_real_escape_string($this->Name),
										mysql_real_escape_string($this->ParentGroupID),
										mysql_real_escape_string($this->UseProductID),
										mysql_real_escape_string($this->Price),
										mysql_real_escape_string($this->IsActive),
										mysql_real_escape_string($this->IsSelected),
										mysql_real_escape_string($this->Quantity),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
			$this->ID = $data->InsertID;
			return true;
		}
		
		function Update(){
			if(!is_numeric($this->ID)){
				return false;
			}
			$data = new DataQuery(sprintf("update product_options set
										Option_Title='%s',
										Product_Option_Group_ID=%d,
										Use_Existing_Product_ID=%d,
										Option_Price=%f,
										Is_Active='%s',
										Is_Selected='%s',
										Option_Quantity=%d,
										Created_On=Now(),
										Created_By=%d,
										Modified_On=Now(),
										Modified_By=%d
										where Product_Option_ID=%d",
										mysql_real_escape_string($this->Name),
										mysql_real_escape_string($this->ParentGroupID),
										mysql_real_escape_string($this->UseProductID),
										mysql_real_escape_string($this->Price),
										mysql_real_escape_string($this->IsActive),
										mysql_real_escape_string($this->IsSelected),
										mysql_real_escape_string($this->Quantity),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($this->ID)));
			return true;
		}

		static function DeleteProduct($id){
			if(!is_numeric($id)){
				return false;
			}
			new DataQuery(sprintf("delete from product_options where Use_Existing_Product_ID=%d", mysql_real_escape_string($id)));
		}

		static function DeleteOption($id){
			if(!is_numeric($id)){
				return false;
			}
			new DataQuery(sprintf("delete from product_options where Product_Option_Group_ID=%d", mysql_real_escape_string($id)));
		}
	}
?>
