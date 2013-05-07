<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/SearchKeyword.php');

class SearchKeywordProduct {
	public $id;
	public $searchKeyword;
	public $product;

	public function __construct($id = null) {
		$this->searchKeyword = new SearchKeyword();
		$this->product = new Product();

		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM search_keyword_product WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}

			$this->searchKeyword->id = $data->Row['searchKeywordId'];
			$this->product->id = $data->Row['productId'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO search_keyword_product (searchKeywordId, productId) VALUES (%d, %d)", mysql_real_escape_string($this->searchKeyword->id), mysql_real_escape_string($this->product->id)));

		$this->id = $data->InsertID;
	}

	public function update() {
		new DataQuery(sprintf("UPDATE search_keyword_product SET searchKeywordId=%d, productId=%d WHERE id=%d", mysql_real_escape_string($this->searchKeyword->id), mysql_real_escape_string($this->product->id), $this->id));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		new DataQuery(sprintf("DELETE FROM search_keyword_product WHERE id=%d", $this->id));
	}
}