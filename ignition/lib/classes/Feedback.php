<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	class Feedback{
		var $ID;
		var $Name;
		var $Description;
		var $CreatedOn;
		var $CreatedBy;
		var $ModifiedOn;
		var $ModifiedBy;

		function Feedback($id=NULL){
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
			$sql = sprintf("select * from feedback where Feedback_ID=%d", mysql_real_escape_string($this->ID));
			$data = new DataQuery($sql);

			if($data->TotalRows > 0) {
				$this->Name = $data->Row['Title'];
				$this->Description = $data->Row['Description'];
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
			$sql = sprintf("insert into feedback (Title, Description, Created_On, Created_By, Modified_On, Modified_By) values ('%s', '%s', Now(), %d, Now(), %d)",
				mysql_real_escape_string($this->Name),
				mysql_real_escape_string($this->Description),
				mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
				mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])
				);
			$data = new DataQuery($sql);
			$this->ID = $data->InsertID;
		}

		function Update(){
			if(!is_numeric($this->ID)){
				return false;
			}
			$sql = sprintf("update feedback set Title='%s', Description='%s', Modified_On=Now(), Modified_By=%d where Feedback_ID=%d",
				mysql_real_escape_string($this->Name),
				mysql_real_escape_string($this->Description),
				mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
				mysql_real_escape_string($this->ID)
				);
			$data = new DataQuery($sql);
		}

		function Delete($id=NULL){
			if(!is_null($id)) $this->ID = $id;
			if(!is_numeric($this->ID)){
				return false;
			}
			$sql = sprintf("delete from feedback where Feedback_ID=%d", mysql_real_escape_string($this->ID));
			$data = new DataQuery($sql);
		}
	}
?>