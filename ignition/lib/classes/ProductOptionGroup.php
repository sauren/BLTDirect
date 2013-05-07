<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductOption.php");
	
	class ProductOptionGroup{
		var $ID;
		var $Name;
		var $Description;
		var $Type;
		var $IsActive;
		var $ProductID;
		
		var $CreatedOn;
		var $CreatedBy;
		var $ModifiedOn;
		var $ModifiedBy;
		
		var $Item;
		
		function ProductOptionGroup($id=NULL){
			if(!is_null($id)){
				$this->ID = $id;
				$this->Get($id);
			}
			$this->Item = array();
		}
		
		function Get($id=NULL){
			if(!is_null($id)){
				$this->ID = $id;
			}

			if(!is_numeric($this->ID)){
				return false;
			}
			$data = new DataQuery(sprintf("select * from product_option_groups where Product_Option_Group_ID=%d", mysql_real_escape_string($this->ID)));
			$this->Name = $data->Row['Group_Title'];
			$this->Description = $data->Row['Group_Description'];
			$this->Type = $data->Row['Group_Type'];
			$this->ProductID = $data->Row['Product_ID'];
			$this->IsActive = $data->Row['Is_Active'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$data->Disconnect();
			return true;
		}
		
		function Add(){
			$data = new DataQuery(sprintf("insert into product_option_groups (
									Product_ID, Group_Title, Group_Description, Group_Type, Is_Active, Created_On, Created_By, Modified_On, Modified_By) 
									values (%d, '%s', '%s', '%s','%s', Now(), %d, Now(), %d)",
									mysql_real_escape_string($this->ProductID),
									mysql_real_escape_string($this->Name),
									mysql_real_escape_string($this->Description),
									mysql_real_escape_string($this->Type),
									mysql_real_escape_string($this->IsActive),
									mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
									mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
			return true;
		}
		
		function Delete($id=NULL){
			if(!is_null($id)){
				$this->ID = $id;
			}

			if(!is_numeric($this->ID)){
				return false;
			}
			$data = new DataQuery(sprintf("delete from product_option_groups where Product_Option_Group_ID=%d", mysql_real_escape_string($this->ID)));
			ProductOption::Deletegroup($this->ID);
			return true;
		}
		
		function Update(){

		if(!is_numeric($this->ID)){
			return false;
		}
			$data = new DataQuery(sprintf("update product_option_groups set
									Group_Title='%s', 
									Group_Description='%s',
									Group_Type='%s',
									Is_Active='%s',
									Modified_On=Now(),
									Modified_By=%d
									where Product_Option_Group_ID=%d",
									mysql_real_escape_string($this->Name),
									mysql_real_escape_string($this->Description),
									mysql_real_escape_string($this->Type),
									mysql_real_escape_string($this->IsActive),
									mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
									mysql_real_escape_string($this->ID)));
			return true;
		}
		
		function GetOptions(){

		if(!is_numeric($this->ID)){
			return false;
		}
			$sql = sprintf("select * from product_options where Is_Active='Y' and Product_Option_Group_ID=%d", mysql_real_escape_string($this->ID));
			$options = new DataQuery($sql);
			
			while($options->Row){
				$tempOption = new ProductOption;
				$tempOption->ID = $options->Row['Product_Option_ID'];
				$tempOption->Name = $options->Row['Option_Title'];
				$tempOption->ParentGroupID = $options->Row['Product_Option_Group_ID'];
				$tempOption->UseProductID = $options->Row['Use_Existing_Product_ID'];
				$tempOption->Price = $options->Row['Option_Price'];
				$tempOption->IsActive = $options->Row['Is_Active'];
				$tempOption->IsSelected = $options->Row['Is_Selected'];
				$tempOption->Quantity = $options->Row['Option_Quantity'];
				$this->Item[] = $tempOption;
				$options->Next();
			}
			
			$options->Disconnect();
		}

		static function DeleteProduct($id){

		if(!is_numeric($id)){
			return false;
		}
			new DataQuery(sprintf("delete from product_option_groups where Product_ID=%d", mysql_real_escape_string($id)));
		}
	}
?>