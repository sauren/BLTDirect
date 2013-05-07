<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');

class WorkTaskArchive {
	public $id;
	public $workTaskId;
	public $name;
	public $file;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	
	public function __construct($id = null) {
		$this->file = new IFile();
		$this->file->Extensions = '';
		$this->file->OnConflict = 'makeunique';
		$this->file->SetDirectory($GLOBALS['WORKTASK_ARCHIVE_DOCUMENT_DIR_FS']);

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

		$data = new DataQuery(sprintf("SELECT * FROM work_task_archive WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				if($key != 'file') {
					$this->$key = $value;
				}
			}
			
			$this->file->FileName = $data->Row['file'];

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function add($fileField = null) {
		if(!is_null($fileField) && isset($_FILES[$fileField]) && !empty($_FILES[$fileField]['name'])){
			if(!$this->file->Upload($fileField)){
				return false;
			}
		}
		
		$data = new DataQuery(sprintf("INSERT INTO work_task_archive (workTaskId, name, file, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, '%s', '%s', NOW(), %d, NOW(), %d)", $this->workTaskId, mysql_real_escape_string($this->name), mysql_real_escape_string($this->file->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
		
		return true;
	}
	
	public function update($fileField = null) {
		$oldFile = new IFile($this->file->FileName, $this->file->Directory);

		if(!is_null($fileField) && isset($_FILES[$fileField]) && !empty($_FILES[$fileField]['name'])) {
			if(!$this->file->Upload($fileField)) {
				return false;
			} else {
				$oldFile->Delete();
			}
		}

		if(!is_numeric($this->id)){
			return false;
		}
		
		new DataQuery(sprintf("UPDATE work_task_archive SET workTaskId=%d, name='%s', file='%s', modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->workTaskId), mysql_real_escape_string($this->name), mysql_real_escape_string($this->file->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
		
		return true;
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(empty($this->file->FileName)) {
			$this->get();
		}
		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM work_task_archive WHERE id=%d", mysql_real_escape_string($this->id)));
		
		if(!empty($this->file->FileName) && $this->file->Exists()) {
			$this->file->Delete();
		}
	}
}