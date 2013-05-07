<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");

class ProductComponent {
	var $ID;
	var $Product;
	var $Parent;
	var $IsActive;
	var $Quantity;
	
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	var $Collection;
	
	function __construct($id=NULL){
		$this->Product = new Product;
		$this->Parent = new Product;
		
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}
	
	function Get($id=NULL){
		if(!is_null($id)){
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("select * from product_components where Product_Component_ID=%d", mysql_real_escape_string($this->ID)));
		$this->Product->ID = $data->Row['Product_ID'];
		$this->Parent->ID = $data->Row['Component_Of_Product_ID'];
		$this->IsActive = $data->Row['Is_Active'];
		$this->Quantity = $data->Row['Component_Quantity'];
		
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];
		$data->Disconnect();
		return true;
	}

	function GetByProduct($id=NULL){
		if(!is_null($id)){
			$this->Product->ID = $id;
		}

		if(!is_numeric($this->Product->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("select * from product_components where Product_ID=%d", mysql_real_escape_string($this->Product->ID)));
		$this->ID = $data->Row['Product_Component_ID'];
		$this->Parent->ID = $data->Row['Component_Of_Product_ID'];
		$this->IsActive = $data->Row['Is_Active'];
		$this->Quantity = $data->Row['Component_Quantity'];
		
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
		$data = new DataQuery(sprintf("delete from product_components where Product_Component_ID=%d", mysql_real_escape_string($this->ID)));
		return true;
	}
	
	function Add(){
		$data = new DataQuery(sprintf("insert into product_components (
										Product_ID, 
										Component_Of_Product_ID, 
										Is_Active,
										Component_Quantity,
										Created_On,
										Created_By,
										Modified_On,
										Modified_By) values (
										%d, %d, '%s', %d, Now(), %d, Now(), %d)",
										mysql_real_escape_string($this->Product->ID),
										mysql_real_escape_string($this->Parent->ID),
										mysql_real_escape_string($this->IsActive),
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
		$data = new DataQuery(sprintf("update product_components set Product_ID=%d, 
										Is_Active='%s',
										Component_Quantity=%d,
										Modified_On=Now(),
										Modified_By=%d
										where Product_Component_ID=%d", 
										mysql_real_escape_string($this->Product->ID),
										mysql_real_escape_string($this->IsActive),
										mysql_real_escape_string($this->Quantity),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($this->ID)));
		return true;
	}

	static function DeleteProduct($id){

		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM product_components WHERE Component_Of_Product_ID=%d", mysql_real_escape_string($id)));
	}
}