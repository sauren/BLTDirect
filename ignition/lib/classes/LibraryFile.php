<?php
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/IFile.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/LibraryFileDirectory.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/LibraryFileType.php');

class LibraryFile {
	var $ID;
	var $FileType;
	var $FileDirectory;
	var $Title;
	var $Description;
	var $Src;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function __construct($id = null) {
		$this->FileType = new LibraryFileType();
		$this->FileDirectory = new LibraryFileDirectory();
		
		$this->Src = new IFile();
		$this->Src->OnConflict = 'makeunique';
		$this->Src->SetDirectory($GLOBALS['FILE_DIR_FS']);
		$this->Src->Extensions = '';
		$this->Src->SizeLimit = 16000;

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null) {
		if(!is_null($id))
			$this->ID = $id;

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM library_file WHERE File_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->FileType->ID = $data->Row['File_Type_ID'];
			$this->FileDirectory->ID = $data->Row['File_Directory_ID'];
			$this->Title = $data->Row['Title'];
			$this->Description = $data->Row['Description'];
			$this->Src->FileName = $data->Row['SRC'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add($fileField=NULL) {
		if(!is_null($fileField) && isset($_FILES[$fileField]) && !empty($_FILES[$fileField]['name'])){
			if(!$this->Src->Upload($fileField)){
				return false;
			}
		}

		$data = new DataQuery(sprintf("INSERT INTO library_file (File_Type_ID, File_Directory_ID, Title, Description, SRC, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, %d, '%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->FileType->ID), mysql_real_escape_string($this->FileDirectory->ID), mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->Src->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		
		$this->ID = $data->InsertID;

		return true;
	}

	function Update($fileField=NULL) {
		$oldFile = new IFile($this->Src->FileName, $this->Src->Directory);

		if(!is_null($fileField) && isset($_FILES[$fileField]) && !empty($_FILES[$fileField]['name'])){
			if(!$this->Src->Upload($fileField)){
				return false;
			} else {
				$oldFile->Delete();
			}
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("UPDATE library_file SET File_Type_ID=%d, File_Directory_ID=%d, Title='%s', Description='%s', SRC='%s', Modified_On=NOW(), Modified_By=%d WHERE File_ID=%d", mysql_real_escape_string($this->FileType->ID), mysql_real_escape_string($this->FileDirectory->ID), mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->Src->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));

		return true;
	}

	function Remove($id = null){
		if(!is_null($id))
			$this->ID = $id;

		if(empty($this->Src->FileName)) {
			$this->Get();
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM library_file WHERE File_ID=%d", mysql_real_escape_string($this->ID)));

		if(!empty($this->Src->FileName) && $this->Src->Exists()) {
			$this->Src->Delete();
		}

		return true;
	}
}