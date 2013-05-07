<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/IFile.php");

class EnquiryLineDocument {
	var $ID;
	var $EnquiryLineID;
	var $File;
	var $IsPublic;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function EnquiryLineDocument($id=NULL){
		$this->IsPublic = 'Y';

		$this->File = new IFile();
		$this->File->OnConflict = "makeunique";
		$this->File->Extensions = "";
		$this->File->SetDirectory($GLOBALS['ENQUIRY_DOCUMENT_DIR_FS']);

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

		$data = new DataQuery(sprintf("SELECT * FROM enquiry_line_document WHERE Enquiry_Line_Document_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->Row) {
			$this->EnquiryLineID = $data->Row['Enquiry_Line_ID'];
			$this->File->FileName = stripslashes($data->Row['File_Name']);
			$this->IsPublic = $data->Row['Is_Public'];
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

		$data = new DataQuery(sprintf("INSERT INTO enquiry_line_document (Enquiry_Line_ID, File_Name, Is_Public, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->EnquiryLineID), mysql_real_escape_string(stripslashes($this->File->FileName)), mysql_real_escape_string($this->IsPublic), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;

		return true;
	}

	function Update($file=NULL){
		if(!is_null($file) && isset($_FILES[$file]) && !empty($_FILES[$file]['name'])){
			$oldFile = new IFile($this->File->FileName, $this->File->Directory);

			if(!$this->File->Upload($file)){
				return false;
			} else {
				$oldFile->Delete();
			}
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("UPDATE enquiry_line_document SET Enquiry_Line_ID=%d, File_Name='%s', Is_Public='%s', Modified_On=NOW(), Modified_By=%d WHERE Enquiry_Line_Document_ID=%d", mysql_real_escape_string($this->EnquiryLineID), mysql_real_escape_string(stripslashes($this->File->FileName)), mysql_real_escape_string($this->IsPublic), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();

		return true;
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get($this->ID);
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM enquiry_line_document WHERE Enquiry_Line_Document_ID=%d", mysql_real_escape_string($this->ID)));
		
		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM enquiry_line_document WHERE File_Name='%s' AND Enquiry_Line_Document_ID<>%d", mysql_real_escape_string(stripslashes($this->File->FileName)), mysql_real_escape_string($this->ID)));
		if($data->Row['Count'] == 0) {
			if(!empty($this->File->FileName) && $this->File->Exists()) {
				$this->File->Delete();
			}
		}
		$data->Disconnect();
	}
}