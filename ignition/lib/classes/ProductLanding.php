<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductLandingProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');

class ProductLanding {
	public $id;
	public $directoryId;
	public $name;
	public $description;
	public $specGroup;
	public $specValue;
	public $imageReference;
	public $category;
	public $hideFilter;
	public $popularAlignment;
	public $popularImageSize;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	public $product;
	public $productsFetched;
	
	public function __construct($id = null) {
		$this->specGroup = new ProductSpecGroup();
		$this->specValue = new ProductSpecValue();
		$this->category = new Category();
		$this->hideFilter = 'N';
		$this->popularAlignment = 'Left';
		$this->popularImageSize = 1.00;
		$this->product = array();
		$this->productsFetched = false;

		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->id)) return false;

		$data = new DataQuery(sprintf("SELECT * FROM product_landing WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->specGroup->ID = $data->Row['specGroupId'];
			$this->specValue->ID = $data->Row['specValueId'];
			$this->category->ID = $data->Row['categoryId'];

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function getProducts() {
		$this->product = array();
		$this->productsFetched = true;
		if(!is_numeric($this->id)) return false;
		
		$data = new DataQuery(sprintf("SELECT id FROM product_landing_product WHERE landingId=%d", mysql_real_escape_string($this->id)));
		while($data->Row) {
			$this->product[] = new ProductLandingProduct($data->Row['id']);	
		
			$data->Next();	
		}
		$data->Disconnect();
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO product_landing (directoryId, name, description, specGroupId, specValueId, categoryId, imageReference, hideFilter, popularAlignment, popularImageSize, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, '%s', '%s', %d, %d, %d, '%s', '%s', '%s', %f, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->directoryId), mysql_real_escape_string($this->name), mysql_real_escape_string($this->description), mysql_real_escape_string($this->specGroup->ID), mysql_real_escape_string($this->specValue->ID), mysql_real_escape_string($this->category->ID), mysql_real_escape_string($this->imageReference), mysql_real_escape_string($this->hideFilter), mysql_real_escape_string($this->popularAlignment), mysql_real_escape_string($this->popularImageSize), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE product_landing SET directoryId=%d, name='%s', description='%s', specGroupId=%d, specValueId=%d, categoryId=%d, imageReference='%s', hideFilter='%s', popularAlignment='%s', popularImageSize=%f, modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->directoryId), mysql_real_escape_string($this->name), mysql_real_escape_string($this->description), mysql_real_escape_string($this->specGroup->ID), mysql_real_escape_string($this->specValue->ID), mysql_real_escape_string($this->category->ID), mysql_real_escape_string($this->imageReference), mysql_real_escape_string($this->hideFilter), mysql_real_escape_string($this->popularAlignment), mysql_real_escape_string($this->popularImageSize), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_landing WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}