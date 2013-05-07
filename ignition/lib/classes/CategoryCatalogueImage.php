<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Image.php');

class CategoryCatalogueImage {
	var $ID;
	var $CategoryID;
	var $Title;
	var $Thumb;
	var $Large;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function CategoryCatalogueImage($id = null) {
		$this->Thumb = new Image();
		$this->Thumb->OnConflict = 'makeunique';
		$this->Thumb->SetMinDimensions($GLOBALS['CATEGORY_CATALOGUE_THUMB_MIN_WIDTH'], $GLOBALS['CATEGORY_CATALOGUE_THUMB_MIN_HEIGHT']);
		$this->Thumb->SetMaxDimensions($GLOBALS['CATEGORY_CATALOGUE_THUMB_MAX_WIDTH'], $GLOBALS['CATEGORY_CATALOGUE_THUMB_MAX_HEIGHT']);
		$this->Thumb->SetDirectory($GLOBALS['CATEGORY_CATALOGUE_THUMB_DIR_FS']);

		$this->Large = new Image();
		$this->Large->OnConflict = 'makeunique';
		$this->Large->SetDirectory($GLOBALS['CATEGORY_CATALOGUE_IMAGE_DIR_FS']);

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM category_catalogue_image WHERE Category_Catalogue_Image_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->CategoryID = $data->Row['Category_ID'];
			$this->Title = $data->Row['Title'];
			$this->Thumb->FileName = $data->Row['Thumb_File_Name'];
			$this->Large->FileName = $data->Row['Large_File_Name'];
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

	function Add($imageField = null) {
		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])){
			if(!$this->Large->Upload($imageField)){
				return false;
			} else {
				$tempFileName = sprintf('%s_thumb.%s', $this->Large->Name, $this->Large->Extension);

				$this->Large->Copy($this->Thumb->Directory, $tempFileName);

				$this->Thumb->SetName($tempFileName);
				$this->Thumb->Width = $this->Large->Width;
				$this->Thumb->Height = $this->Large->Height;

				if(!$this->Thumb->CheckDimensions()) {
					$this->Thumb->Resize();
				}
			}
		}

		$data = new DataQuery(sprintf("INSERT INTO category_catalogue_image (Category_ID, Title, Thumb_File_Name, Large_File_Name, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', '%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->CategoryID), mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Thumb->FileName), mysql_real_escape_string($this->Large->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;

		return true;
	}

	function Update($imageField = null) {
		$oldThumb = new Image($this->Thumb->FileName, $this->Thumb->Directory);
		$oldLarge = new Image($this->Large->FileName, $this->Large->Directory);

		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])){
			if(!$this->Large->Upload($imageField)){
				return false;
			} else {
				$tempFileName = sprintf('%s_thumb.%s', $this->Large->Name, $this->Large->Extension);

				$this->Large->Copy($this->Thumb->Directory, $tempFileName);

				$this->Thumb->SetName($tempFileName);
				$this->Thumb->Width = $this->Large->Width;
				$this->Thumb->Height = $this->Large->Height;

				if(!$this->Thumb->CheckDimensions()){
					$this->Thumb->Resize();
				}
			}
		}

		if(!is_null($imageField) && isset($_FILES[$imageField]) && !empty($_FILES[$imageField]['name'])){
			if($oldThumb->FileName != $this->Thumb->FileName) {
				$oldThumb->Delete();
			}

			if($oldLarge->FileName != $this->Large->FileName) {
				$oldLarge->Delete();
			}
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("UPDATE category_catalogue_image SET Category_ID=%d, Title='%s', Thumb_File_Name='%s', Large_File_Name='%s', Modified_On=NOW(), Modified_By=%d WHERE Category_Catalogue_Image_ID=%d", mysql_real_escape_string($this->CategoryID), mysql_real_escape_string($this->Title), mysql_real_escape_string($this->Thumb->FileName), mysql_real_escape_string($this->Large->FileName), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));

		return true;
	}

	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(empty($this->Thumb->FileName) && empty($this->Large->FileName)) {
			$this->Get();
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM category_catalogue_image WHERE Category_Catalogue_Image_ID=%d", mysql_real_escape_string($this->ID)));
		//new DataQuery(sprintf("UPDATE catalogue_section_category SET Category_Catalogue_Image_ID=0 WHERE Category_Catalogue_Image_ID=%d", $this->ID));

		if(!empty($this->Thumb->FileName) && $this->Thumb->Exists()) {
			$this->Thumb->Delete();
		}

		if(!empty($this->Large->FileName) && $this->Large->Exists()) {
			$this->Large->Delete();
		}
	}
}
?>