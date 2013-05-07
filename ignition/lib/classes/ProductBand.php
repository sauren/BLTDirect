<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

class ProductBand{
	var $ID;
	var $Reference;
	var $Name;
	var $Description;
	
	// Tracking
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	
	function ProductBand($id=NULL){
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
		
		$sql = sprintf("select * from product_band where Product_Band_ID=%d", mysql_real_escape_string($this->ID));
		
		$data = new DataQuery($sql);
		if($data->TotalRows > 0) {
			$this->Reference = $data->Row['Band_Ref'];
			$this->Name = $data->Row['Band_Title'];
			$this->Description = $data->Row['Band_Description'];

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
	
	function Add(){
		$sql = sprintf("insert into product_band (Band_Ref, Band_Title, Band_Description, Created_On, Created_By, Modified_On, Modified_By) values ('%s', '%s', '%s', Now(), %d, Now(), %d)", 
		mysql_real_escape_string($this->Reference),
		mysql_real_escape_string($this->Name),
		mysql_real_escape_string($this->Description),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])
		);

		$data = new DataQuery($sql);
		$this->ID = $data->InsertID;
	}
	
	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf("update product_band set Band_Ref='%s', Band_Title='%s', Band_Description='%s', Modified_On=Now(), Modified_By=%d where Product_Band_ID=%d", 
		mysql_real_escape_string($this->Reference),
		mysql_real_escape_string($this->Name),
		mysql_real_escape_string($this->Description),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->ID)
		);
		
		$data = new DataQuery($sql);
	}
	
	function Delete($id=NULL){
		if(!is_null($id)) $this->ID = $id;
		if(!is_numeric($this->ID)){
			return false;
		}
		
		// reset all products with this id
		Product::Reset($this->ID);
		
		// now delete this id from band list
		$sql = sprintf("delete from product_band where Product_Band_ID=%d", mysql_real_escape_string($this->ID));
		$data = new DataQuery($sql);
	}
			
	function GetByReference($reference=NULL){
		if(!is_null($reference)) $this->Reference = $reference;
		
		$sql = sprintf("select * from product_band where Band_Ref LIKE '%s'", $this->Reference);
		$data = new DataQuery($sql);
		
		if($data->TotalRows > 0){
			$this->ID = $data->Row['Product_Band_ID'];
			$this->Name = $data->Row['Band_Title'];
			$this->Description = $data->Row['Band_Description'];
			// Tracking
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$data->Disconnect();
			return true;
		} else {
			$this->Errors[] = sprintf('Unable to find matching Product Band Reference %s.', strtoupper($this->Reference));
			$data->Disconnect();
			return false;
		}
	}
			
	function Exists($reference=NULL){
		if(!is_null($reference)) $this->Reference = $reference;
		
		$sql = sprintf("select Product_Band_ID from product_band where Band_Ref like '%s'", mysql_real_escape_string($this->Reference));
		$data = new DataQuery($sql);
		$data->Disconnect();
		if($data->TotalRows > 0){
			return true;
		} else {
			return false;
		}
	}
	
	function AddCategory($cat){
		$sql = sprintf("select * from product_in_categories where Category_ID=%d", mysql_real_escape_string($cat));
		$data = new DataQuery($sql);
		while($data->Row){
			// update product manually
			Product::UpdateProductBand($this->ID, $data->Row['Product_ID']);
			$data->Next();
		}
		$data->Disconnect();
	}
	
	function ResetCategory($cat){
		$sql = sprintf("select * from product_in_categories where Category_ID=%d", mysql_real_escape_string($cat));
		$data = new DataQuery($sql);
		while($data->Row){
			Product::ResetCategory($data->Row['Product_ID']);
			$data->Next();
		}
		$data->Disconnect();
	}
	
	function AddProduct($pid){
		Product::AddProductBand($this->ID, $pid);
	}
	
	function ResetProduct($pid){
		Product::ResetProduct($pid);
	}
}
?>