<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class TradeBanding {
	private static $cache;
	private static $categories;
	private static $products;
	
	private static function GetCategories(array $ids) {
		$categories = array();
		$return = $ids;
		if(!is_array($ids) || empty($ids)){
			return array();
		}
		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID IN (%s)", mysql_real_escape_string(implode(', ', $ids))));
		while($data->Row) {
			$categories[] = $data->Row['Category_ID'];
			
			$data->Next();
		}
		$data->Disconnect();
		
		if(!empty($categories)) {
			$return = array_merge($return, self::GetCategories($categories));
		}
		
		return $return;
	}
	
	public static function GetMarkup($cost, $productId) {
		if(is_null(self::$categories)) {
			self::$categories = array();	
			
			$data = new DataQuery("SELECT categoryId, markup FROM trade_banding_category");
			while($data->Row) {
				if(!isset(self::$categories[$data->Row['markup']])) {
					self::$categories[$data->Row['markup']] = array();
				}
				
				self::$categories[$data->Row['markup']] = array_merge(self::GetCategories(array($data->Row['categoryId'])), self::$categories[$data->Row['markup']]);
				
				$data->Next();
			}
			$data->Disconnect();
		}
		
		if(is_null(self::$cache)) {
			self::$cache = array();	
			
			$data = new DataQuery("SELECT * FROM trade_banding ORDER BY markup DESC");
			while($data->Row) {
				self::$cache[] = $data->Row;
				
				$data->Next();
			}
			$data->Disconnect();
		}
		
		if(is_null(self::$products)) {
			self::$products = array();		
		}
		
		$markup = 0;
		
		if(!isset(self::$products[$productId])) {
			self::$products[$productId] = -1;
			
			$data = new DataQuery(sprintf("SELECT Category_ID FROM product_in_categories WHERE Product_ID=%d", mysql_real_escape_string($productId)));
			while($data->Row) {
				foreach(self::$categories as $categoryMarkup=>$categoryData) {
					foreach($categoryData as $categoryId) {
						if($categoryId == $data->Row['Category_ID']) {
							self::$products[$productId] = $categoryMarkup;
							break;
						}
					}
				}
				
				$data->Next();
			}
			$data->Disconnect();
		}
		
		if(self::$products[$productId] == -1) {
			foreach(self::$cache as $cache) {
				if(($cost >= $cache['costFrom']) && ($cost < $cache['costTo'])) {
					$markup = $cache['markup'];
					break;
				}
			}
		} else {
			$markup = self::$products[$productId];
		}
		
		return $markup;
	}
	
	public $id;
	public $costFrom;
	public $costTo;
	public $markup;
	
	public function __construct($id = null) {
		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM trade_banding WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO trade_banding (costFrom, costTo, markup) VALUES (%f, %f, %f)", mysql_real_escape_string($this->costFrom), mysql_real_escape_string($this->costTo), mysql_real_escape_string($this->markup)));
		
		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE trade_banding SET costFrom=%f, costTo=%f, markup=%f WHERE id=%d", mysql_real_escape_string($this->costFrom), mysql_real_escape_string($this->costTo), mysql_real_escape_string($this->markup), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM trade_banding WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}