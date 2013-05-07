<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

class SearchKeyword {
	public $id;
	public $term;

	public function __construct($id = null) {
		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM search_keyword WHERE id=%d", mysql_real_escape_string($this->id)));
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

	public function getByTerm($term = null) {
		if(!is_null($term)) {
			$this->term = $term;
		}

		$data = new DataQuery(sprintf("SELECT id FROM search_keyword WHERE term LIKE '%s'", mysql_real_escape_string($this->term)));
		if($data->TotalRows > 0) {
			$this->get($data->Row['id']);

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO search_keyword (term) VALUES ('%s')", mysql_real_escape_string($this->term)));

		$this->id = $data->InsertID;
	}

	public function update() {
		new DataQuery(sprintf("UPDATE search_keyword SET term='%s' WHERE id=%d", mysql_real_escape_string($this->term), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		new DataQuery(sprintf("DELETE FROM search_keyword WHERE id=%d", mysql_real_escape_string($this->id)));
		new DataQuery(sprintf("DELETE FROM search_keyword_category WHERE id=%d", mysql_real_escape_string($this->id)));
		new DataQuery(sprintf("DELETE FROM search_keyword_product WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}