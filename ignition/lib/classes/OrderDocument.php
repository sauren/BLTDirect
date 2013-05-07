<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/IFile.php");

class OrderDocument {
	public $id;
	public $orderId;
	public $type;
	public $name;
	public $file;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;

	function __construct($id = null) {
		$this->file = new IFile();
		$this->file->OnConflict = 'makeunique';
		$this->file->Extensions = '';
		$this->file->SetDirectory($GLOBALS['ORDER_DOCUMENT_DIR_FS']);

		if(!is_null($id)) {
			$this->get($id);
		}
	}

	function get($id = null) {
		$this->id = !is_null($id) ? $id : $this->id;

		if(!is_numeric($this->id)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT * FROM order_document WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->file->SetName($data->Row['fileName']);
			
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
			}
		}

		$data = new DataQuery(sprintf("INSERT INTO order_document (orderId, type, name, fileName, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, '%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->orderId), mysql_real_escape_string($this->type), mysql_real_escape_string($this->name), mysql_real_escape_string($this->file->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->id = $data->InsertID;
		
		return true;
	}
	
	function update($fileField = null) {
		$oldFile = new IFile($this->file->FileName, $this->file->Directory);
		
		if(!is_null($fileField) && isset($_FILES[$fileField]) && !empty($_FILES[$fileField]['name'])) {
			if(!$this->file->Upload($fileField)){
				return false;
			} else {
				$oldFile->Delete();
			}
		}

		if(!is_numeric($this->id)){
			return false;
		}
		
		new DataQuery(sprintf("UPDATE order_document SET orderId=%d, type='%s', name='%s', fileName='%s', modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->orderId), mysql_real_escape_string($this->type), mysql_real_escape_string($this->name), mysql_real_escape_string($this->file->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
		
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

		new DataQuery(sprintf("DELETE FROM order_document WHERE id=%d", mysql_real_escape_string($this->id)));
		
		if(!empty($this->file->FileName) && $this->file->Exists()) {
			$this->file->Delete();
		}
	}
}