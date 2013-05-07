<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

class ProductPrice {
	var $ID;
	var $ProductID;
	var $PriceOurs;
	var $PriceRRP;
	var $Quantity;
	var $IsTaxIncluded;
	var $PriceStartsOn;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function ProductPrice($id=null){
		$this->PriceOurs = 0;
		$this->PriceRRP = 0;
		$this->IsTaxIncluded = 'N';
		$this->Quantity = 0;

		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	function Get($id=null){
		if(!is_null($id)) $this->ID = $id;
		$sql = "SELECT * FROM product_prices
                WHERE Product_Price_ID = {$this->ID}";
		$data = new DataQuery($sql);
		if($data->TotalRows > 0){
			$this->ProductID = $data->Row['Product_ID'];
			$this->PriceOurs = $data->Row['Price_Base_Our'];
			$this->PriceRRP = $data->Row['Price_Base_RRP'];
			$this->IsTaxIncluded = $data->Row['Is_Tax_Included'];
			$this->PriceStartsOn = $data->Row['Price_Starts_On'];
			$this->Quantity = $data->Row['Quantity'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			return true;
		} else return false;
	}

	function GetProductPrice(){
		# Get latest price for specified product
		$sql = sprintf("SELECT * FROM product_prices
                WHERE Product_ID = {$this->ProductID}
                AND Price_Starts_On < '%s'
                ORDER BY Price_Starts_On DESC",
		date('Y-m-d H:i:s'));
		$data = new DataQuery($sql);
		if($data->TotalRows > 0){
			$this->PriceOurs = $data->Row['Price_Base_Our'];
			$this->PriceRRP = $data->Row['Price_Base_RRP'];
			$this->IsTaxIncluded = $data->Row['Is_Tax_Included'];
			$this->PriceStartsOn = $data->Row['Price_Starts_On'];
			$this->Quantity = $data->Row['Quantity'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			return true;
		} else return false;
	}

	function Add($connection = null){
		$sql = sprintf("INSERT INTO product_prices
                (Product_ID, Price_Base_Our, Price_Base_RRP,
                Is_Tax_Included, Price_Starts_On, Quantity,
                Created_On, Created_By, Modified_On, Modified_By) VALUES
                (%d, %f, %f, '%s', '%s', '%s', NOW(), %d, NOW(), %d)",
		mysql_real_escape_string($this->ProductID),
		mysql_real_escape_string($this->PriceOurs),
		mysql_real_escape_string($this->PriceRRP),
		mysql_real_escape_string($this->IsTaxIncluded),
		mysql_real_escape_string($this->PriceStartsOn),
		mysql_real_escape_string($this->Quantity),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));

		$data = new DataQuery($sql, $connection);
		$this->ID = $data->InsertID;

		return true;
	}

	function Update($id=null, $connection = null){
		if(!is_null($id)) $this->ID = $id;
		$sql = sprintf("UPDATE product_prices SET
                        Price_Base_Our = {$this->PriceOurs},
                        Price_Base_RRP = {$this->PriceRRP},
                        Is_Tax_Included = '{$this->IsTaxIncluded}',
                        Price_Starts_On = '{$this->PriceStartsOn}',
                        Quantity = {$this->Quantity},
                        Modified_On=NOW(),
                        Modified_By = %d
                        WHERE Product_ID = {$this->ProductID}
                        AND Product_Price_ID={$this->ID}",
		$GLOBALS['SESSION_USER_ID']);

		$data = new DataQuery($sql, $connection);
		$data->Disconnect();
	}

	static function DeleteProduct($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from product_prices where Product_ID=%d", mysql_real_escape_string($id)));
	}
}
?>
