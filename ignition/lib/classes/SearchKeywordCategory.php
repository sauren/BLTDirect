<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Category.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/SearchKeyword.php');

class SearchKeywordCategory {
	public $id;
	public $searchKeyword;
	public $category;

	public function __construct($id = null) {
		$this->searchKeyword = new SearchKeyword();
		$this->category = new Category();

		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM search_keyword_category WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}

			$this->searchKeyword->id = $data->Row['searchKeywordId'];
			$this->category->id = $data->Row['categoryId'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO search_keyword_category (searchKeywordId, categoryId) VALUES (%d, %d)", mysql_real_escape_string($this->searchKeyword->id), mysql_real_escape_string($this->category->id)));

		$this->id = $data->InsertID;
	}

	public function update() {
		new DataQuery(sprintf("UPDATE search_keyword_category SET searchKeywordId=%d, categoryId=%d WHERE id=%d", mysql_real_escape_string($this->searchKeyword->id), mysql_real_escape_string($this->category->id), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		new DataQuery(sprintf("DELETE FROM search_keyword_category WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}