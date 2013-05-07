<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/IFile.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/htmlMimeMail5.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');

class UserDocument {
	var $ID;
	var $UserID;
	var $Title;
	var $File;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function __construct($id=NULL) {
		$this->File = new IFile();
		$this->File->OnConflict = 'makeunique';
		$this->File->Extensions = '';
		$this->File->SetDirectory($GLOBALS['USER_DOCUMENT_DIR_FS']);

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM user_document WHERE User_Document_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->UserID = $data->Row['User_ID'];
			$this->Title = $data->Row['Title'];
			$this->File->FileName = $data->Row['File_Name'];
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

	function Add($file=NULL){
		if(!is_null($file) && isset($_FILES[$file]) && !empty($_FILES[$file]['name'])){
			if(!$this->File->Upload($file)){
				return false;
			}
		}

		$data = new DataQuery(sprintf("INSERT INTO user_document (User_ID, Title, File_Name, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->UserID), mysql_real_escape_string($this->Title), mysql_real_escape_string($this->File->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;

		return true;
	}

	function Update($file=NULL){
		$oldFile = new IFile($this->File->FileName, $this->File->Directory);

		if(!is_null($file) && isset($_FILES[$file]) && !empty($_FILES[$file]['name'])) {
			if(!$this->File->Upload($file)){
				return false;
			} else {
				$oldFile->Delete();
			}
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("UPDATE user_document SET Title='%s', File_Name='%s', Modified_On=NOW(), Modified_By=%d WHERE User_Document_ID=%d", mysql_real_escape_string($this->Title), mysql_real_escape_string($this->File->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));

		return true;
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(empty($this->File->FileName)) {
			$this->Get();
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM user_document WHERE User_Document_ID=%d", mysql_real_escape_string($this->ID)));

		if(!empty($this->File->FileName) && $this->File->Exists()) {
			$this->File->Delete();
		}
	}
}