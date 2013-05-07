<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');

class ProductDownload {
	public $id;
	public $productId;
	public $name;
	public $description;
	public $file;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	
	function __construct($id = null) {
		$this->file = new IFile();
		$this->file->OnConflict = 'makeunique';
		$this->file->SetDirectory($GLOBALS['PRODUCT_DOWNLOAD_DIR_FS']);
		$this->file->Extensions = '';
		
		if(!is_null($id)) {
			$this->get($id);
		}
	}
	
	function get($id = null) {
		$this->id = !is_null($id) ? $id : $this->id;

		if(!is_numeric($this->id)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM product_download WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				if(!is_object($this->$key)) {
					$this->$key = $value;
				}
			}
			
			$this->file->SetName($data->Row['file']);
			
			$data->Disconnect();
			return true;
		}
		
		$data->Disconnect();
		return false;
	}
	
	function add($fileField = null) {
		if(!is_null($fileField) && isset($_FILES[$fileField]) && !empty($_FILES[$fileField]['name'])) {
			if(!$this->file->Upload($fileField)){
				return false;
			} else {
				$product = new Product($this->productId);

				$this->file->Rename(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $product->Name)) . '.' . $this->file->Extension);
			}
		}

		$data = new DataQuery(sprintf("INSERT INTO product_download (productId, name, description, file, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, '%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->productId), mysql_real_escape_string($this->name), mysql_real_escape_string($this->description), mysql_real_escape_string($this->file->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->id = $data->InsertID;
		
		return true;
	}
	
	function update($fileField = null) {
		$oldFile = new IFile($this->file->FileName, $this->file->Directory);
		
		if(!is_null($fileField) && isset($_FILES[$fileField]) && !empty($_FILES[$fileField]['name'])) {
			if(!$this->file->Upload($fileField)){
				return false;
			} else {
				$product = new Product($this->productId);
				
				$this->file->Rename(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $product->Name)) . '.' . $this->file->Extension);
				
				$oldFile->Delete();
			}
		}


		if(!is_numeric($this->id)){
			return false;
		}
		
		new DataQuery(sprintf("UPDATE product_download SET productId=%d, name='%s', description='%s', file='%s', modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->productId), mysql_real_escape_string($this->name), mysql_real_escape_string($this->description), mysql_real_escape_string($this->file->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
		
		return true;
	}
	
	function delete($id = null) {
		$this->id = !is_null($id) ? $id : $this->id;
		
		if(empty($this->file->FileName)) {
			$this->get();
		}


		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_download WHERE id=%d", mysql_real_escape_string($this->id)));
		
		if(!empty($this->file->FileName) && $this->file->Exists()) {
			$this->file->Delete();
		}
	}
}