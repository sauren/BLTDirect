<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class BreadCrumb{
	var $Table;
	var $ParentField;
	var $ChildField;
	var $TitleField;
	var $LinkCode;
	var $Separator;
	var $Crumbs;
	var $Sql;
	var $Text;

	function BreadCrumb(){
		$this->Separator = "/";
		$this->Table = "product_categories";
		$this->ParentField = "Category_Parent_ID";
		$this->ChildField = "Category_ID";
		$this->TitleField = "Category_Title";
		$this->LinkCode = "./products.php?cat=%s";
		$this->Crumbs = array();
		$this->Sql = "select %s, %s, %s from %s where %s=";
	}

	function Get($id, $inclusive=false){
		$this->BuildSql();
		$this->GetNode($id);
		$this->BuildText($inclusive);
	}

	function BuildSql(){
		$this->Sql = sprintf($this->Sql,
							$this->ParentField,
							$this->ChildField,
							$this->TitleField,
							$this->Table,
							$this->ChildField);
	}

	function GetNode($id){
		$tempArr = array();
		$data = new DataQuery($this->Sql . $id);
		if($data->TotalRows == 1){
			$tempArr['id'] = $data->Row[$this->ChildField];
			$tempArr['title'] = $data->Row[$this->TitleField];
			$this->Crumbs[] = $tempArr;
			$parent = $data->Row[$this->ParentField];
		}
		$data->Disconnect();
		if(isset($parent) && $parent != 0){
			$this->GetNode($parent);
		}
	}

	function BuildText($inclusive){
		$endNode = ($inclusive)?0:1;
		for($i=count($this->Crumbs)-1; $i>=$endNode; $i--){
			$tempLink = sprintf($this->LinkCode, $this->Crumbs[$i]['id']);
			$this->Text .= " " . $this->Separator . " <a href=\"" . $tempLink . "\">" . htmlspecialchars($this->Crumbs[$i]['title']) . "</a>";
		}
	}
}
?>