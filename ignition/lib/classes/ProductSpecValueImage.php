<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Image.php");

class ProductSpecValueImage {
	public $id;
	public $valueId;
	public $image;
	public $reference;

	function __construct($id = null) {
		$this->image = new Image();
		$this->image->OnConflict = 'makeunique';
		$this->image->SetMinDimensions($GLOBALS['SPEC_IMAGE_MIN_WIDTH'], $GLOBALS['SPEC_IMAGE_MIN_HEIGHT']);
		$this->image->SetMaxDimensions($GLOBALS['SPEC_IMAGE_MAX_WIDTH'], $GLOBALS['SPEC_IMAGE_MAX_HEIGHT']);
		$this->image->SetDirectory($GLOBALS['SPEC_IMAGES_DIR_FS']);

		if(!is_null($id)) {
			$this->get($id);
		}
	}

	function get($id = null) {
		$this->id = !is_null($id) ? $id : $this->id;
		if(!is_numeric($this->id)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM product_specification_value_image WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->image->SetName($this->fileName);
			
			$data->Disconnect();
			return true;
		}
		
		$data->Disconnect();
		return false;
	}
	
	function add($imageField = null) {
		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])) {
			if(!$this->image->Upload($imageField)){
				return false;
			} else {
				if(!$this->image->CheckDimensions()) {
					$this->image->Resize();
				}
			}
		}

		$data = new DataQuery(sprintf("INSERT INTO product_specification_value_image (valueId, fileName, reference) VALUES (%d, '%s', '%s')", mysql_real_escape_string($this->valueId), mysql_real_escape_string($this->image->FileName), mysql_real_escape_string($this->reference)));
		
		$this->id = $data->InsertID;
		
		return true;
	}
	
	function update($imageField = null) {
		$oldImage = new IFile($this->image->FileName, $this->image->Directory);
		
		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])) {
			if(!$this->image->Upload($imageField)){
				return false;
			} else {
				if(!$this->image->CheckDimensions()){
					$this->image->Resize();
				}
				
				$oldImage->Delete();
			}
		}
		if(!is_numeric($this->id)){
			return false;
		}
		
		new DataQuery(sprintf("UPDATE product_specification_value_image SET valueId=%d, fileName='%s', reference='%s' WHERE id=%d", mysql_real_escape_string($this->valueId), mysql_real_escape_string($this->image->FileName), mysql_real_escape_string($this->reference), mysql_real_escape_string($this->id)));
		
		return true;
	}
	
	function delete($id = null) {
		$this->id = !is_null($id) ? $id : $this->id;
		
		if(empty($this->image->FileName)) {
			$this->get();
		}
		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_specification_value_image WHERE id=%d", mysql_real_escape_string($this->id)));
		
		if(!empty($this->image->FileName) && $this->image->Exists()) {
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM product_specification_value_image WHERE fileName LIKE '%s'", mysql_real_escape_string($this->image->FileName)));
			if($data->Row['count'] == 0) {
				$this->image->Delete();
			}
			$data->Disconnect();
		}
	}
}