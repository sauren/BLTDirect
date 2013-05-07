<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	class ArticleCategory{
		var $ID;
		var $Name;
		var $MetaTitle;
		var $MetaDescription;
		var $MetaKeywords;
		var $IsActive;
		var $CreatedOn;
		var $CreatedBy;
		var $ModifiedOn;
		var $ModifiedBy;
		
		function ArticleCategory($id=NULL){
			if(!is_null($id)){
				$this->ID = $id;
				$this->Get();
			}
		}
		
		function Get($id=NULL){
			if(!is_null($id)) $this->ID = $id;
			if(!is_numeric($this->ID)){
				return false;
			}
			$sql = sprintf("select * from article_category where Article_Category_ID=%d", mysql_real_escape_string($this->ID));
			$data = new DataQuery($sql);
			$this->Name = $data->Row['Category_Title'];
			$this->MetaTitle = $data->Row['Meta_Title'];
			$this->MetaKeywords = $data->Row['Meta_Keywords'];
			$this->MetaDescription = $data->Row['Meta_Description'];
			$this->IsActive = $data->Row['Is_Active'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];	
			$data->Disconnect();	
		}
		
		function Add(){
			$sql = sprintf("insert into article_category (Category_Title, Meta_Title, Meta_Keywords, Meta_Description, Is_Active, Created_On, Created_By, Modified_On, Modified_By) values ('%s', '%s', '%s', '%s', '%s', Now(), %d, Now(), %d)",
				mysql_real_escape_string($this->Name),
				mysql_real_escape_string($this->MetaTitle),
				mysql_real_escape_string($this->MetaKeywords),
				mysql_real_escape_string($this->MetaDescription),
				mysql_real_escape_string($this->IsActive),
				mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
				mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));
			$data = new DataQuery($sql);
			$this->ID = $data->InsertID;
		}
		
		function Update(){
			if(!is_numeric($this->ID)){
				return false;
			}
			$sql = sprintf("update article_category  set Category_Title='%s', Meta_Title='%s', Meta_Keywords='%s', Meta_Description='%s', Is_Active='%s', Modified_On=Now(), Modified_By=%d where Article_Category_ID=%d",
				mysql_real_escape_string($this->Name),
				mysql_real_escape_string($this->MetaTitle),
				mysql_real_escape_string($this->MetaKeywords),
				mysql_real_escape_string($this->MetaDescription),
				mysql_real_escape_string($this->IsActive),
				mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
				mysql_real_escape_string($this->ID));
			$data = new DataQuery($sql);
		}
		
		function Delete($id=NULL){
			if(!is_null($id)) $this->ID = $id;
			$sql = sprintf("delete from article_category where Article_Category_ID=%d", mysql_real_escape_string($this->ID));
			$data = new DataQuery($sql);
		}
	}
?>