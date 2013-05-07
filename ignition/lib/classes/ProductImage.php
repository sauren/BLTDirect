<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Image.php");

class ProductImage{
	var $ID;
	var $ParentID;
	var $SpecificationValueID;
	var $Thumb;
	var $Large;
	var $Name;
	var $Description;
	var $IsActive;
	var $IsDefault;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function ProductImage($id=NULL){
		$this->Thumb = new Image;
		$this->Thumb->OnConflict = "makeunique";
		$this->Thumb->SetMinDimensions($GLOBALS['PRODUCT_THUMB_MIN_WIDTH'], $GLOBALS['PRODUCT_THUMB_MIN_HEIGHT']);
		$this->Thumb->SetMaxDimensions($GLOBALS['PRODUCT_THUMB_MAX_WIDTH'], $GLOBALS['PRODUCT_THUMB_MAX_HEIGHT']);
		$this->Thumb->SetDirectory($GLOBALS['PRODUCT_IMAGES_DIR_FS']);

		$this->Large = new Image;
		$this->Large->OnConflict = "makeunique";
		$this->Large->SetMinDimensions($GLOBALS['PRODUCT_IMG_MIN_WIDTH'], $GLOBALS['PRODUCT_IMG_MIN_HEIGHT']);
		$this->Large->SetMaxDimensions($GLOBALS['PRODUCT_IMG_MAX_WIDTH'], $GLOBALS['PRODUCT_IMG_MAX_HEIGHT']);
		$this->Large->SetDirectory($GLOBALS['PRODUCT_IMAGES_DIR_FS']);

		$this->IsActive = 'Y';
		$this->IsDefault = 'N';

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
		
		$data = new DataQuery(sprintf("select * from product_images where Product_Image_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->ParentID = $data->Row['Product_ID'];
			$this->SpecificationValueID = $data->Row['Specification_Value_ID'];
			$this->Thumb->SetName($data->Row['Image_Thumb']);
			$this->Thumb->Width = $data->Row['Image_Thumb_Width'];
			$this->Thumb->Height = $data->Row['Image_Thumb_Height'];
			$this->Large->SetName($data->Row['Image_Src']);
			$this->Large->Width = $data->Row['Image_Src_Width'];
			$this->Large->Height = $data->Row['Image_Src_Height'];
			$this->IsDefault = $data->Row['Is_Primary'];
			$this->IsActive = $data->Row['Is_Active'];
			$this->Name = $data->Row['Image_Title'];
			$this->Description = $data->Row['Image_Description'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$update = false;

			if(($this->Thumb->Width == 0) || ($this->Thumb->Height == 0)) {
				$this->Thumb->GetDimensions();

				$update = true;
			}

			if(($this->Large->Width == 0) || ($this->Large->Height == 0)) {
				$this->Large->GetDimensions();

				$update = true;
			}

			if($update) {
				$this->Update();
			}

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Delete($id=NULL) {
		if(!is_null($id)) $this->ID = $id;

		if(empty($this->Large->FileName) && empty($this->Thumb->FileName)){
			$this->Get();
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("delete from product_images where Product_Image_ID=%d", mysql_real_escape_string($this->ID)));

		if(!empty($this->Thumb->FileName) && $this->Thumb->Exists()){
			if(!$this->InUse($this->Thumb->FileName)) $this->Thumb->Delete();
		}
		if(!empty($this->Large->FileName) && $this->Large->Exists()){
			if(!$this->InUse($this->Large->FileName)) $this->Large->Delete();
		}
	}

	function Add($thumbField=NULL, $largeField=NULL){
		if(!is_null($largeField) && isset($_FILES[$largeField]) && !empty($_FILES[$largeField]['name'])){
			if(!$this->Large->Upload($largeField)){
				return false;
			} else {
				if(!$this->Large->CheckDimensions()){
					$this->Large->Resize();
				}
			}
		}

		if(!is_null($largeField) && $largeField == $thumbField){
			$tempFileName = $this->Large->Name . "_thumb." . $this->Large->Extension;
			$this->Large->Copy($this->Thumb->Directory, $tempFileName);
			$this->Thumb->SetName($tempFileName);
			$this->Thumb->Width = $this->Large->Width;
			$this->Thumb->Height = $this->Large->Height;

			if(!$this->Thumb->CheckDimensions()){
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

		if($this->IsDefault == 'Y'){
			$this->ClearDefault();
		}

		$data = new DataQuery(sprintf("insert into product_images (
								Product_ID,
								Specification_Value_ID,
								Is_Active,
								Is_Primary,
								Image_Thumb,
								Image_Thumb_Width,
								Image_Thumb_Height,
								Image_Src,
								Image_Src_Width,
								Image_Src_Height,
								Image_Title,
								Image_Description,
								Created_On,
								Created_By,
								Modified_On,
								Modified_By
								) values (%d, %d, '%s', '%s', '%s', %d, %d, '%s', %d, %d, '%s', '%s', Now(), %d, Now(), %d)",
								mysql_real_escape_string($this->ParentID),
								mysql_real_escape_string($this->SpecificationValueID),
								mysql_real_escape_string($this->IsActive),
								mysql_real_escape_string($this->IsDefault),
								mysql_real_escape_string($this->Thumb->FileName),
								mysql_real_escape_string($this->Thumb->Width),
								mysql_real_escape_string($this->Thumb->Height),
								mysql_real_escape_string($this->Large->FileName),
								mysql_real_escape_string($this->Large->Width),
								mysql_real_escape_string($this->Large->Height),
								mysql_real_escape_string($this->Name),
								mysql_real_escape_string($this->Description),
								mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
								mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;

		return true;
	}

	function Update($thumbField=NULL, $largeField=NULL){
		$oldLarge = new Image($this->Large->FileName, $this->Large->Directory);
		$oldThumb = new Image($this->Thumb->FileName, $this->Thumb->Directory);

		if(!is_null($largeField) && isset($_FILES[$largeField]) && !empty($_FILES[$largeField]['name'])){
			if(!$this->Large->Upload($largeField)){
				return false;
			} else {
				if(!$this->Large->CheckDimensions()){
					$this->Large->Resize();
				}
				$oldLarge->Delete();
			}
		}

		if(!is_null($largeField) && $largeField == $thumbField){
			$tempFileName = $this->Large->Name . "_thumb." . $this->Large->Extension;
			$this->Large->Copy($this->Thumb->Directory, $tempFileName);
			$this->Thumb->SetName($tempFileName);
			$this->Thumb->Width = $this->Large->Width;
			$this->Thumb->Height = $this->Large->Height;

			if(!$this->Thumb->CheckDimensions()){
				$this->Thumb->Resize();
			}
			$oldThumb->Delete();
		} else {
			if(!is_null($thumbField) && isset($_FILES[$thumbField]) && !empty($_FILES[$thumbField]['name'])){
				if(!$this->Thumb->Upload($thumbField)){
					return false;
				} else {
					if(!$this->Thumb->CheckDimensions()){
						$this->Thumb->Resize();
					}
					$oldThumb->Delete();
				}
			}
		}

		if($this->IsDefault == 'Y'){
			$this->ClearDefault();
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("update product_images set
								Product_ID=%d,
								Specification_Value_ID=%d,
								Is_Active='%s',
								Is_Primary='%s',
								Image_Thumb='%s',
								Image_Thumb_Width=%d,
								Image_Thumb_Height=%d,
								Image_Src='%s',
								Image_Src_Width=%d,
								Image_Src_Height=%d,
								Image_Title='%s',
								Image_Description='%s',
								Modified_On=Now(),
								Modified_By=%d
								where Product_Image_ID=%d",
								mysql_real_escape_string($this->ParentID),
								mysql_real_escape_string($this->SpecificationValueID),
								mysql_real_escape_string($this->IsActive),
								mysql_real_escape_string($this->IsDefault),
								mysql_real_escape_string($this->Thumb->FileName),
								mysql_real_escape_string($this->Thumb->Width),
								mysql_real_escape_string($this->Thumb->Height),
								mysql_real_escape_string($this->Large->FileName),
								mysql_real_escape_string($this->Large->Width),
								mysql_real_escape_string($this->Large->Height),
								mysql_real_escape_string($this->Name),
								mysql_real_escape_string($this->Description),
								mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
								mysql_real_escape_string($this->ID)));

		return true;
	}

	function ClearDefault(){
		new DataQuery(sprintf("update product_images set Is_Primary='N' where Product_ID=%d", mysql_real_escape_string($this->ParentID)));
	}

	function InUse($str){
		$data = new DataQuery(sprintf("select * from product_images where Image_Thumb='%s' or Image_Src='%s'", mysql_real_escape_string($str), mysql_real_escape_string($str)));
		$returnValue = ($data->TotalRows > 0)?true:false;
		$data->Disconnect();

		return $returnValue;
	}
}
?>