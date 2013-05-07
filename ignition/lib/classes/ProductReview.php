<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

class ProductReview {
	var $ID;
	var $Product;
	var $Customer;
	var $Title;
	var $Review;
	var $Rating;
	var $IsApproved;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function ProductReview($id=NULL){
		$this->Product = new Product();
		$this->Customer = new Customer();
		$this->IsApproved = 'N';

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

		$data = new DataQuery(sprintf("SELECT * FROM product_review WHERE Product_Review_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Product->ID = $data->Row['Product_ID'];
			$this->Customer->ID = $data->Row['Customer_ID'];
			$this->Title = $data->Row['Title'];
			$this->Review = $data->Row['Review'];
			$this->Rating = $data->Row['Rating'];
			$this->IsApproved = $data->Row['Is_Approved'];
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
		$data = new DataQuery(sprintf("INSERT INTO product_review (Product_ID, Customer_ID, Title, Review, Rating, Is_Approved, Created_On, Created_By, Modified_On, Modified_By) values (%d, %d, '%s', '%s', %f, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Customer->ID), mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Review), mysql_real_escape_string($this->Rating), mysql_real_escape_string($this->IsApproved), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE product_review SET Product_ID=%d, Customer_ID=%d, Title='%s', Review='%s', Rating=%f, Is_Approved='%s', Modified_On=NOW(), Modified_By=%d WHERE Product_Review_ID=%d", mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Customer->ID), mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Review), mysql_real_escape_string($this->Rating), mysql_real_escape_string($this->IsApproved), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_review WHERE Product_Review_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function Approve(){
		$this->IsApproved = 'Y';
		$this->Update();
	}

	static function DeleteCustomer($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM product_review WHERE Customer_ID=%s", mysql_real_escape_string($id)));
	}
}