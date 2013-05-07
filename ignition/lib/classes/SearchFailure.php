<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

class SearchFailure {
	public $id;
	public $term;
	public $frequency;
	public $date;

	public function __construct($id = null) {
		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM search_failure WHERE id=%d", mysql_real_escape_string($this->id)));
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

	public function getByTerm($term = null, $date = null) {
		if(!is_null($term)) {
			$this->term = $term;
		}

		if(!is_null($date)) {
			$this->date = $date;
		}

		$this->term = trim($this->term);

		$data = new DataQuery(sprintf("SELECT id FROM search_failure WHERE term LIKE '%s' AND `date`='%s'", mysql_real_escape_string($this->term), mysql_real_escape_string($this->date)));
		if($data->TotalRows > 0) {
			$this->get($data->Row['id']);

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO search_failure (term, frequency, `date`) VALUES ('%s', %d, NOW())", mysql_real_escape_string($this->term), mysql_real_escape_string($this->frequency)));

		$this->id = $data->InsertID;
	}

	public function update() {
		new DataQuery(sprintf("UPDATE search_failure SET term='%s', frequency=%d, `date`='%s' WHERE id=%d", mysql_real_escape_string($this->term), mysql_real_escape_string($this->frequency), mysql_real_escape_string($this->date), mysql_real_escape_string($this->id)));
	}

	public function delete() {
		new DataQuery(sprintf("DELETE FROM search_failure WHERE id=%d", mysql_real_escape_string($this->id)));
	}

	public function increment() {
		$this->frequency++;
		$this->update();
	}
}