<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ProductInCategory {
	public $id;
	public $categoryId;
	public $productId;
	public $sequenceNumber;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;

	public function __construct($id = null){
		if(!is_null($id)){
			$this->id = $id;
			$this->get();
		}
	}

	public function get($id = null){
		if(!is_null($id)){
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM product_in_categories WHERE Products_In_Categories_ID =%d", mysql_real_escape_string($this->id)));

		if($data->TotalRows > 0) {
			$this->categoryId = $data->Row['Category_ID'];
			$this->productId = $data->Row['Product_ID'];
			$this->sequenceNumber = $data->Row['Sequence_Number'];
			$this->createdOn = $data->Row['Created_On'];
			$this->createdBy = $data->Row['Created_By'];
			$this->modifiedOn = $data->Row['Modified_On'];
			$this->modifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}
		$data->Disconnect();
		return false;
	}

	public function add(){
		$data = new DataQuery(sprintf("INSERT INTO product_in_categories (Category_ID, Product_ID, Sequence_Number, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->id), mysql_real_escape_string($this->categoryId), mysql_real_escape_string($this->productID), mysql_real_escape_string($this->sequenceNumber), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->id = $data->InsertID;
		$this->sequenceNumber = $this->id;
		$this->update();
	}

	public function update(){

		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE product_in_categories SET Category_ID = %d, Product_ID = %d, Sequence_Number = %d, Modified_By = %d, Modified_On = NOW() WHERE Products_In_Categories_ID = %d", mysql_real_escape_string($this->categoryId), mysql_real_escape_string($this->productId), mysql_real_escape_string($this->sequenceNumber), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null){
		if(!is_null($id)){
			$this->id = $id;
		}
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM product_in_categories WHERE Products_In_Categories_ID=%d", mysql_real_escape_string($this->id)));

	}

	static function DeleteProduct($cat, $id){

		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM product_in_categories WHERE Category_ID=%d AND Product_ID=%d", mysql_real_escape_string($cat), mysql_real_escape_string($id)));
	}

	static function DeleteProduct2($cat, $id){

		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM product_in_categories WHERE Category_ID=%d AND Product_ID=%d", mysql_real_escape_string($cat), mysql_real_escape_string($id)));
	}

	static function deleteProduct3($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM product_in_categories WHERE Product_ID=%d", mysql_real_escape_string($id)));
	}

	public function deleteByProductAndCategory($productId = null, $categoryId = null){
		if(!is_null($productId)){
			$this->productId = $productId;
		}

		if(!is_null($categoryId)){
			$this->categoryId = $categoryId;
		}

		new DataQuery(sprintf("DELETE FROM product_in_categories WHERE ProductId=%d AND Category_Id=%d", mysql_real_escape_string($this->productId), mysql_real_escape_string($this->categoryId)));
	}
}
?>