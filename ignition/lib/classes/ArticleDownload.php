<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Article.php");
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/IFile.php");

	class ArticleDownload{
		var $ID;
		var $Article;
		var $File;
		var $Name;
		var $CreatedOn;
		var $CreatedBy;
		var $ModifiedOn;
		var $ModifiedBy;

		function ArticleDownload($id=NULL){
			$this->Article = new Article;
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
			if(!is_null($id)) $this->ID = $id;
			if(!is_numeric($this->ID)){
				return false;
			}
			$sql = sprintf("select * from article_download where Article_Download_ID=%d", mysql_real_escape_string($this->ID));
			$data = new DataQuery($sql);
			$this->Article->ID = $data->Row['Article_ID'];
			$this->File->SetName($data->Row['File_Name']);
			$this->Name = $data->Row['Title'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$data->Disconnect();

		}

		function Add($file=NULL){
			// try uploading file first
			if(!is_null($file) && isset($_FILES[$file]) && !empty($_FILES[$file]['name'])){
				if(!$this->File->Upload($file)){
					return false;
				}
			}

			$sql = sprintf("insert into article_download (Article_ID, File_Name, Title, Created_On, Created_By, Modified_On, Modified_By) values (%d, '%s', '%s', Now(), %d, Now(), %d)",
				mysql_real_escape_string($this->Article->ID),
				mysql_real_escape_string($this->File->FileName),
				mysql_real_escape_string($this->Name),
				mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
				mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));

			$data = new DataQuery($sql);
			$this->ID = $data->InsertID;

			return true;
		}

		function Update($file=NULL){
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

			// update database entry
			$sql = sprintf("update article_download  set Article_ID=%d, File_Name='%s', Title='%s', Modified_On=Now(), Modified_By=%d where Article_Download_ID=%d",
				mysql_real_escape_string($this->Article->ID),
				mysql_real_escape_string($this->File->FileName),
				mysql_real_escape_string($this->Name),
				mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
				mysql_real_escape_string($this->ID));

			new DataQuery($sql);

			return true;
		}

		function Delete($id=NULL){
			if(!is_null($id)) $this->ID = $id;

			if(!is_numeric($this->ID)){
				return false;
			}

			// delete file from server
			if(empty($this->File->FileName)) $this->Get();
			if(!empty($this->File->FileName) && $this->File->Exists()){
				$this->File->Delete();
			}
			
			// delete from database
			$sql = sprintf("delete from article_download where Article_Download_ID=%d", mysql_real_escape_string($this->ID));
			$data = new DataQuery($sql);
		}
	}
?>