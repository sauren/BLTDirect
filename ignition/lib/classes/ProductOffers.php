<?php
	
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	class ProductOffer {
	
	public $id;
	public $ProductID;
	public $priceOffer;
	public $offerStart;
	public $offerEnd;
	public $isTaxIncluded;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;

	public function __construct($id=NULL) {
		$this->isTaxIncluded = 'N';

		if(!is_null($id)) {
			$this->id = $id;
			$this->Get();
		}
	}

	public function Get($id=NULL){
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->id)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM product_offers WHERE Product_Offer_ID=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			$this->ProductID = $data->Row['Product_ID'];
			$this->priceOffer = $data->Row['Price_Offer'];
			$this->offerStart = $data->Row['Offer_Start_On'];
			$this->offerEnd = $data->Row['Offer_End_On'];
			$this->isTaxIncluded = $data->Row['Is_Tax_Included'];
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

	public function Delete($id=NULL){
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_offers WHERE Product_Offer_ID=%d", mysql_real_escape_string($this->id)));
	}

	public function Add(){
		$data = new DataQuery(sprintf("INSERT INTO product_offers (Product_ID, Price_Offer, Is_Tax_Included, Offer_Start_On, Offer_End_On, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %f, '%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->ProductID), mysql_real_escape_string($this->priceOffer), mysql_real_escape_string($this->isTaxIncluded), mysql_real_escape_string($this->offerStart), mysql_real_escape_string($this->offerEnd), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->id = $data->InsertID;
	}

	public function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE product_offers SET Product_ID=%d, Price_Offer=%f, Is_Tax_Included='%s', Offer_Start_On='%s', Offer_End_On = '%s', Modified_On=NOW(), Modifed_By=%d WHERE Product_Offer_ID=%d", mysql_real_escape_string($this->ProductID), mysql_real_escape_string($this->priceOffer), mysql_real_escape_string($this->isTaxIncluded), mysql_real_escape_string($this->offerStart), mysql_real_escape_string($this->offerEnd), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	static function DeleteProduct($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from product_offers where Product_ID=%d", mysql_real_escape_string($id)));
	}
}

?>