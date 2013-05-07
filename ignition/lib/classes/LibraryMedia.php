<?php
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Image.php');

class LibraryMedia {
	var $ID;
	var $Title;
	var $Thumb;
	var $Src;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function LibraryMedia($id = null) {
		$this->Thumb = new Image;
		$this->Thumb->OnConflict = "makeunique";
		$this->Thumb->SetMinDimensions($GLOBALS['MEDIA_THUMB_MIN_WIDTH'], $GLOBALS['MEDIA_THUMB_MIN_HEIGHT']);
		$this->Thumb->SetMaxDimensions($GLOBALS['MEDIA_THUMB_MAX_WIDTH'], $GLOBALS['MEDIA_THUMB_MAX_HEIGHT']);
		$this->Thumb->SetDirectory($GLOBALS['MEDIA_DIR_FS']);

		$this->Src = new Image;
		$this->Src->OnConflict = "makeunique";
		$this->Src->SetDirectory($GLOBALS['MEDIA_DIR_FS']);

		if(!is_null($id))
		{
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

		$data = new DataQuery(sprintf("SELECT * FROM library_media WHERE Media_ID=%d", mysql_real_escape_string($this->ID)));

		if($data->TotalRows > 0)
		{
			$this->Title = $data->Row['Title'];
			$this->Thumb->FileName = $data->Row['Thumb'];
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

	function Add($thumbField=NULL, $largeField=NULL) {
		if(!is_null($largeField) && isset($_FILES[$largeField]) && !empty($_FILES[$largeField]['name'])){
			if(!$this->Src->Upload($largeField)){
				return false;
			}
		}

		if(!is_null($largeField) && $largeField == $thumbField) {
			$tempFileName = $this->Src->Name . "_thumb." . $this->Src->Extension;

			$this->Src->Copy($this->Thumb->Directory, $tempFileName);
			$this->Thumb->SetName($tempFileName);
			$this->Thumb->Width = $this->Src->Width;
			$this->Thumb->Height = $this->Src->Height;

			if(!$this->Thumb->CheckDimensions()) {
				$this->Thumb->Resize();
			}
		} else {
			if(!is_null($thumbField) && isset($_FILES[$thumbField]) && !empty($_FILES[$thumbField]['name'])){
				if(!$this->Thumb->Upload($thumbField)){
					return false;
				} else {
					if(!$this->Thumb->CheckDimensions()){
						$this->Thumb->Resize();
					}
				}
			}

		}

		$data = new DataQuery(sprintf("INSERT INTO library_media (Title, Thumb, SRC, Created_On, Created_By, Modified_On, Modified_By) VALUES ('%s', '%s', '%s', Now(), %d, Now(), %d)", mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Thumb->FileName), mysql_real_escape_string($this->Src->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();

		return true;
	}

	function Update($thumbField=NULL, $largeField=NULL) {
		$oldLarge = new Image($this->Src->FileName, $this->Src->Directory);
		$oldThumb = new Image($this->Thumb->FileName, $this->Thumb->Directory);

		if(!is_null($largeField) && isset($_FILES[$largeField]) && !empty($_FILES[$largeField]['name'])){
			if(!$this->Src->Upload($largeField)){
				return false;
			} else {
				$oldLarge->Delete();
			}
		}

		if(!((!is_null($largeField) && isset($_FILES[$largeField]) && !empty($_FILES[$largeField]['name'])) && (!is_null($thumbField) && isset($_FILES[$thumbField]) && !empty($_FILES[$thumbField]['name'])) && $largeField == $thumbField)) {
			if(!is_null($thumbField) && isset($_FILES[$thumbField]) && !empty($_FILES[$thumbField]['name'])){
				if(!$this->Thumb->Upload($thumbField)){
					return false;
				} else {
					$oldThumb->Delete();

					if(!$this->Thumb->CheckDimensions()){
						$this->Thumb->Resize();
					}
				}
			}

		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("UPDATE library_media SET Title='%s', Thumb='%s', SRC='%s', Modified_On=Now(), Modified_By=%d WHERE Media_ID=%d", mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Thumb->FileName), mysql_real_escape_string($this->Src->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();

		return true;
	}

	function Remove($id = null){
		if(!is_null($id))
			$this->ID = $id;

		if(empty($this->Thumb->FileName) && empty($this->Src->FileName)) {
			$this->Get();
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM library_media WHERE Media_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();

		if(!empty($this->Thumb->FileName) && $this->Thumb->Exists()) {
			$this->Thumb->Delete();
		}
		if(!empty($this->Src->FileName) && $this->Src->Exists()) {
			$this->Src->Delete();
		}

		return true;
	}
}
?>