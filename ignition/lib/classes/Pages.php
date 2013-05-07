<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class Pages{
	var $ID;
	var $Name;
	var $Description;
	var $MetaTitle;
	var $MetaKeywords;
	var $MetaDescription;
	var $Category;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Banners;
	
	function Pages($id=NULL){
		$this->Banners = array();
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
		
		$data = new DataQuery(sprintf("select * from pages where Page_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Page_Title'];
			$this->Description = $data->Row['Page_Description'];
			$this->MetaTitle = $data->Row['Meta_Title'];
			$this->MetaKeywords = $data->Row['Meta_Keywords'];
			$this->MetaDescription = $data->Row['Meta_Description'];
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
	
	function Add(){
		$data = new DataQuery(sprintf("insert into pages (Page_Title, Page_Description, Meta_Title,  Meta_Keywords, Meta_Description, Created_On, Created_By, Modified_On, Modified_By) values (%d, '%s', '%s', '%s', '%s', '%s', Now(), %d, Now(), %d)",
			mysql_real_escape_string($this->Name),
			mysql_real_escape_string($this->Description),
			mysql_real_escape_string($this->MetaTitle),
			mysql_real_escape_string($this->MetaKeywords),
			mysql_real_escape_string($this->MetaDescription),
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));	
		$this->ID = $data->InsertID;
	}
	
	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("update pages set Page_Title='%s', Page_Description='%s', Meta_Title='%s',  Meta_Keywords='%s', Meta_Description='%s', Modified_On=Now(), Modified_By=%d where Page_ID=%d",
			mysql_real_escape_string($this->Name),
			mysql_real_escape_string($this->Description),
			mysql_real_escape_string($this->MetaTitle),
			mysql_real_escape_string($this->MetaKeywords),
			mysql_real_escape_string($this->MetaDescription),
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
			mysql_real_escape_string($this->ID)));
	}
	
	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		
		new DataQuery(sprintf("delete from pages where Page_ID=%d", mysql_real_escape_string($this->ID)));
	}
	
	function GetBanners(){
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("select * from pages_banners where Page_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$banner = new PagesBanners();
			$banner->ID = $data->Row['Page_Banner_ID'];
			$banner->PageID = $data->Row['Page_ID'];
			$banner->Title = $data->Row['Title'];
			$banner->File->SetName($data->Row['Image_Source']);
			$banner->Colour = $data->Row['Background_Colour'];
			$banner->StartOn = $data->Row['Start_On'];
			$banner->EndOn = $data->Row['End_On'];
			$banner->CreatedOn = $data->Row['Created_On'];
			$banner->CreatedBy = $data->Row['Created_By'];
			$banner->ModifiedOn = $data->Row['Modified_On'];
			$banner->ModifiedBy = $data->Row['Modified_By'];
			$this->Banners[] = $banner;
			$data->Next();
		}
		$data->Disconnect();
	}

	function GetViewableBanners(){
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("select *
from pages_banners
where Page_ID=%d and (
	NOW() BETWEEN Start_On AND End_On OR
	(Start_On = '0000-00-00' AND End_On = '0000-00-00') OR
	(Start_On < NOW() AND End_On = '0000-00-00') OR
	(Start_On = '0000-00-00' AND End_On > NOW())
)", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$banner = new PagesBanners();
			$banner->ID = $data->Row['Page_Banner_ID'];
			$banner->PageID = $data->Row['Page_ID'];
			$banner->Title = $data->Row['Title'];
			$banner->Link = $data->Row['Link'];
			$banner->File->SetName($data->Row['Image_Source']);
			$banner->Colour = $data->Row['Background_Colour'];
			$banner->StartOn = $data->Row['Start_On'];
			$banner->EndOn = $data->Row['End_On'];
			$banner->CreatedOn = $data->Row['Created_On'];
			$banner->CreatedBy = $data->Row['Created_By'];
			$banner->ModifiedOn = $data->Row['Modified_On'];
			$banner->ModifiedBy = $data->Row['Modified_By'];
			$this->Banners[] = $banner;
			$data->Next();
		}
		$data->Disconnect();
	}
}

class PagesBanners {
	var $ID;
	var $Title;
	var $File;
	var $Colour;
	var $Link;
	var $StartOn;
	var $EndOn;
	var $CreatedOn;
	var $createdBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $PageID;

	function PagesBanners($id=NULL){
		$this->File = new IFile;
		$this->File->OnConflict = "makeunique";
		$this->File->Extensions = "";
		$this->File->SetDirectory($GLOBALS['ARTICLE_DOWNLOAD_DIR_FS']);

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
		
		$data = new DataQuery(sprintf("select * from pages_banners where Page_Banner_ID=%d", mysql_real_escape_string($this->ID)));

		if($data->TotalRows > 0) {
			$this->Title = $data->Row['Title'];
			$this->File->SetName($data->Row['Image_Source']);
			$this->Colour = $data->Row['Background_Colour'];
			$this->Link = $data->Row['Link'];
			$this->StartOn = $data->Row['Start_On'];
			$this->EndOn = $data->Row['End_On'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$this->PageID = $data->Row['Page_ID'];
			
			$data->Disconnect();
			return true;
		}
		
		$data->Disconnect();
		return false;
	}

	function Add($file){
		if(!is_null($file) && isset($_FILES[$file]) && !empty($_FILES[$file]['name'])){
			if(!$this->File->Upload($file)){
				return false;
			}
		}

		$data = new DataQuery(sprintf("insert into pages_banners (Page_ID, Title, Image_Source, Background_Colour, Link, Start_On, End_On, Created_On, Created_By, Modified_On, Modified_By) values (%d, '%s', '%s', '%s', '%s', '%s', '%s', Now(), %d, Now(), %d)",
			mysql_real_escape_string($this->PageID),
			mysql_real_escape_string($this->Title),
			mysql_real_escape_string($this->File->FileName),
			mysql_real_escape_string($this->Colour),
			mysql_real_escape_string($this->Link),
			mysql_real_escape_string($this->StartOn),
			mysql_real_escape_string($this->EndOn),			
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));	
		$this->ID = $data->InsertID;

		return true;
	}

	function Update($file){
		$oldFile = new IFile($this->File->FileName, $this->File->Directory);
		// check for new file and delete old one if necessary
		if(!is_null($file) && isset($_FILES[$file]) && !empty($_FILES[$file]['name'])){
			if(!$this->File->Upload($file)){
				return false;
			} else {
				$oldFile->Delete();
			}
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("UPDATE pages_banners
			SET Page_ID=%d, Title='%s', Image_Source='%s', Background_Colour='%s', Link='%s', Start_On='%s', End_On='%s', Created_On=Now(), Created_By=%d, Modified_On=Now(), Modified_By=%d
			WHERE Page_Banner_ID=%d",
			mysql_real_escape_string($this->PageID),
			mysql_real_escape_string($this->Title),
			mysql_real_escape_string($this->File->FileName),
			mysql_real_escape_string($this->Colour),
			mysql_real_escape_string($this->Link),
			mysql_real_escape_string($this->StartOn),
			mysql_real_escape_string($this->EndOn),			
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
			mysql_real_escape_string($this->ID)));

		return true;
	}

	function Delete($id=NULL){
		if(!is_null($id)){
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		// delete file from server
		if(empty($this->File->FileName)){
			$this->Get();
		}
		if(!empty($this->File->FileName) && $this->File->Exists()){
			$this->File->Delete();
		}
		
		// delete from database
		$sql = new DataQuery(sprintf("delete from pages_banners where Page_Banner_ID=%d", mysql_real_escape_string($this->ID)));
	}
}
?>