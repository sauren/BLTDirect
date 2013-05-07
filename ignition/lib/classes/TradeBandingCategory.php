<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class TradeBandingCategory {
	public $id;
	public $category;
	public $markup;
	
	public function __construct($id = null) {
		$this->category = new Category();
		
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

		$data = new DataQuery(sprintf("SELECT * FROM trade_banding_category WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->category->ID = $this->categoryId;
			
            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO trade_banding_category (categoryId, markup) VALUES (%d, %d)", mysql_real_escape_string($this->category->ID), mysql_real_escape_string($this->markup)));
		
		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE trade_banding_category SET categoryId=%d, markup=%d WHERE id=%d", mysql_real_escape_string($this->category->ID), mysql_real_escape_string($this->markup), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM trade_banding_category WHERE id=%d", mysql_real_escape_string($this->id)));
	}
}