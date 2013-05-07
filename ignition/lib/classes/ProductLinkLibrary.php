<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Asset.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Image.php");

class ProductLinkLibrary {
	public $id;
	public $asset;
	public $name;
	public $url;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	public $image;

	public function __construct($id = null) {
		$this->asset = new Asset();
		
		$this->image = new Image();
		$this->image->SetMinDimensions($GLOBALS['PRODUCT_LINK_IMAGE_MIN_WIDTH'], $GLOBALS['PRODUCT_LINK_IMAGE_MIN_HEIGHT']);
		$this->image->SetMaxDimensions($GLOBALS['PRODUCT_LINK_IMAGE_MAX_WIDTH'], $GLOBALS['PRODUCT_LINK_IMAGE_MAX_HEIGHT']);
		$this->image->SetDirectory(sys_get_temp_dir());

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
		
		$data = new DataQuery(sprintf("SELECT * FROM product_link_library WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->asset->id = $data->Row['assetId'];
		
			$data->Disconnect();
			return true;	
		}
		
		$data->Disconnect();
		return false;
	}

	public function attach($file) {
		if(isset($_FILES[$file]) && !empty($_FILES[$file]['name'])) {
			if(file_exists($_FILES[$file]['tmp_name'])) {
				$this->image->SetName(basename($_FILES[$file]['tmp_name']));

				if(!$this->image->CheckDimensions()) {
					$this->image->Resize();
				}

				$this->asset->name = $_FILES[$file]['name'];
				$this->asset->data = file_get_contents($this->image->Directory.$this->image->FileName);
				$this->asset->add();

				return true;
			}
		}

		return false;
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO product_link_library (assetId, name, url, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->asset->id), mysql_real_escape_string($this->name), mysql_real_escape_string($this->url), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}

	public function update() {
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE product_link_library SET assetId=%d, name='%s', url='%s', modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->asset->id), mysql_real_escape_string($this->name), mysql_real_escape_string($this->url), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}
	
	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		
		if(empty($this->asset->id)) {
			$this->get();
		}
		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_link_library WHERE id=%d", mysql_real_escape_string($this->id)));
		
		$this->asset->clean();
	}
}