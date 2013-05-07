<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ArticleCategory.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ArticleDownload.php");

class Article{
	var $ID;
	var $Name;
	var $Description;
	var $IsActive;
	var $MetaTitle;
	var $MetaKeywords;
	var $MetaDescription;
	var $Category;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Download;
	
	function Article($id=NULL){
		$this->Category = new ArticleCategory;
		$this->Download = array();
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
		
		$data = new DataQuery(sprintf("select * from article where Article_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Article_Title'];
			$this->Category->ID = $data->Row['Article_Category_ID'];
			$this->Description = $data->Row['Article_Description'];
			$this->IsActive = $data->Row['Is_Active'];
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
		$data = new DataQuery(sprintf("insert into article (Article_Category_ID, Article_Title, Article_Description, Is_Active, Meta_Title,  Meta_Keywords, Meta_Description, Created_On, Created_By, Modified_On, Modified_By) values (%d, '%s', '%s', '%s', '%s', '%s', '%s', Now(), %d, Now(), %d)",
			mysql_real_escape_string($this->Category->ID),
			mysql_real_escape_string($this->Name),
			mysql_real_escape_string($this->Description),
			mysql_real_escape_string($this->IsActive),
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

		new DataQuery(sprintf("update article set Article_Category_ID=%d, Article_Title='%s', Article_Description='%s', Is_Active='%s', Meta_Title='%s',  Meta_Keywords='%s', Meta_Description='%s', Modified_On=Now(), Modified_By=%d where Article_ID=%d",
			mysql_real_escape_string($this->Category->ID),
			mysql_real_escape_string($this->Name),
			mysql_real_escape_string($this->Description),
			mysql_real_escape_string($this->IsActive),
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
		
		new DataQuery(sprintf("delete from article where Article_ID=%d", mysql_real_escape_string($this->ID)));
	}
	
	function GetDownloads(){

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("select * from article_download where Article_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$download = new ArticleDownload;
			$download->ID = $data->Row['Article_Download_ID'];
			$download->Article->ID = $data->Row['Article_ID'];
			$download->File->SetName($data->Row['File_Name']);
			$download->Name = $data->Row['Title'];
			$download->CreatedOn = $data->Row['Created_On'];
			$download->CreatedBy = $data->Row['Created_By'];
			$download->ModifiedOn = $data->Row['Modified_On'];
			$download->ModifiedBy = $data->Row['Modified_By'];
			$this->Download[] = $download;
			$data->Next();
		}
		$data->Disconnect();
	}
	
	function DeleteDownloads(){
	}
}
?>