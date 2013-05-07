<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class Asset {
	var $id;
	var $hash;
	var $name;
	var $data;
	var $size;

	public function __construct($id=NULL) {
		if(isset($id)){
			$this->id = $id;
			$this->get();
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM asset WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->Row) {
			$this->hash = $data->Row['hash'];
			$this->name = $data->Row['name'];
			$this->data = $data->Row['data'];
			$this->size = $data->Row['size'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function getMeta($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT hash, name, size FROM asset WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->Row) {
			$this->hash = $data->Row['hash'];
			$this->name = $data->Row['name'];
			$this->size = $data->Row['size'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function getByHash($hash = null) {
		if(!is_null($hash)) {
			$this->hash = $hash;
		}

		$data = new DataQuery(sprintf("SELECT id FROM asset WHERE hash='%s'", mysql_real_escape_string($this->hash)));
		if($data->Row) {
			$this->get($data->Row['id']);

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function getData() {
		if(empty($this->hash)) {
			$this->get();
		}
		
		$this->cache();
		
		return file_get_contents($this->getCacheFile());
	}
	
	public function getCacheFile() {
		return $GLOBALS['CACHE_DIR_FS'] . $this->hash;
	}
	
	public function cache() {
		if(empty($this->hash)) {
			$this->get();
		}
		
		$cache = $this->getCacheFile();
		
		if(!file_exists($cache)) {
			$fh = fopen($cache, 'w');
			
			if($fh) {
				fwrite($fh, $this->data);
				fclose($fh);	
			}
		} else {
			touch($cache);
		}
	}

	public function attach($file) {
		if(isset($_FILES[$file]) && !empty($_FILES[$file]['name'])) {
			if(file_exists($_FILES[$file]['tmp_name'])) {
				$this->name = $_FILES[$file]['name'];
				$this->data = file_get_contents($_FILES[$file]['tmp_name']);
				$this->add();
 			}
 		}
	}
	
	public function add() {
		if(!empty($this->data)) {
			$this->hash = sha1($this->data);
			
			$data = new DataQuery(sprintf("SELECT id FROM asset WHERE hash='%s'", mysql_real_escape_string($this->hash)));
			if($data->TotalRows > 0) {
				$this->id = $data->Row['id'];
			} else {
				$data2 = new DataQuery(sprintf("INSERT INTO asset (hash, name, data, size) VALUES ('%s', '%s', '%s', %d)", mysql_real_escape_string($this->hash), mysql_real_escape_string($this->name), mysql_real_escape_string($this->data), strlen($this->data)));

				$this->id = $data2->InsertID;
			}
			$data->Disconnect();
		}
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM asset WHERE id=%d", mysql_real_escape_string($this->id)));
	}
	
	public function clean($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}
		
		$tables = array();
		$tables['product_image_example_request'] = 'assetId';
		$tables['product_link_library'] = 'assetId';
		$tables['product_link'] = 'assetId';

		$linked = false;
		
		foreach($tables as $table=>$column) {
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM %s WHERE %s=%d", mysql_real_escape_string($table), mysql_real_escape_string($column), mysql_real_escape_string($this->id)));
			if($data->Row['count'] > 0) {
				$linked = true;
			}
			$data->Disconnect();
		}

		if(!$linked) {
			$this->delete();
		}
	}
}