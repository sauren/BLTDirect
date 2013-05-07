<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");

class ProductRelated {
	var $ID;
	var $Product;
	var $Parent;
	var $Type;
	var $IsRequired;
	var $IsActive;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Collection;

	function __construct($id=NULL){
		$this->Product = new Product();
		$this->Parent = new Product();

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("select * from product_related where Product_Related_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Product->ID = $data->Row['Product_ID'];
			$this->Parent->ID = $data->Row['Related_To_Product_ID'];
			$this->Type = $data->Row['Type'];
			$this->IsActive = $data->Row['Is_Active'];
			$this->IsRequired = $data->Row['Is_Required'];
			$this->IsActive = $data->Row['Is_Active'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			
			$data->Disconnect();
			return true;
		}
		
		$data->Disconnect();
		return false;
	}

	function Delete($id=NULL){
		if(!is_null($id)){
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("delete from product_related where Product_Related_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Add(){
		$data = new DataQuery(sprintf("insert into product_related (
										Product_ID,
										Related_To_Product_ID,
										Type,
										Is_Active,
										Is_Required,
										Created_On,
										Created_By,
										Modified_On,
										Modified_By) values (
										%d, %d, '%s', '%s', '%s', Now(), %d, Now(), %d)",
										mysql_real_escape_string($this->Product->ID),
										mysql_real_escape_string($this->Parent->ID),
										mysql_real_escape_string($this->Type),
										mysql_real_escape_string($this->IsActive),
										mysql_real_escape_string($this->IsRequired),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		return true;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("update product_related set Product_ID=%d,
										Type='%s',
										Is_Active='%s',
										Is_Required='%s',
										Modified_On=Now(),
										Modified_By=%d
										where Product_Related_ID=%d",
										mysql_real_escape_string($this->Product->ID),
										mysql_real_escape_string($this->Type),
										mysql_real_escape_string($this->IsActive),
										mysql_real_escape_string($this->IsRequired),
										mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
										mysql_real_escape_string($this->ID)));
		return true;
	}

	static function DeleteProduct($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from product_related where Related_To_Product_ID=%d", mysql_real_escape_string($id)));
	}
}