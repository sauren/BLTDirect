<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BreadCrumb.php');

class CategoryBreadCrumb extends BreadCrumb {
	var $Table;
	var $ParentField;
	var $ChildField;
	var $TitleField;
	var $LinkCode;
	var $Separator;
	var $Crumbs;
	var $Sql;
	var $Text;

	function CategoryBreadCrumb() {
		parent::BreadCrumb();

		$this->Table = "product_categories";
		$this->ParentField = "Category_Parent_ID";
		$this->ChildField = "Category_ID";
		$this->TitleField = "Category_Title";
		$this->MetaField = "Meta_Title";
		$this->LinkCode = "/products.php?cat=%s%s";
		$this->Sql = "select %s, %s, %s, %s from %s where %s=";
	}

	function BuildSql(){
		$this->Sql = sprintf($this->Sql, $this->ParentField, $this->ChildField, $this->TitleField, $this->MetaField, $this->Table, $this->ChildField);
	}

	function GetNode($id){
		$tempArr = array();
		$data = new DataQuery($this->Sql . $id);
		if($data->TotalRows == 1){
			$tempArr['id'] = $data->Row[$this->ChildField];
			$tempArr['title'] = $data->Row[$this->TitleField];
			$tempArr['meta'] = $data->Row[$this->MetaField];

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
			$tempLink = sprintf($this->LinkCode, $this->Crumbs[$i]['id'], !empty($this->Crumbs[$i]['meta']) ? sprintf('&amp;nm=%s', urlencode($this->Crumbs[$i]['meta'])) : '');
			$this->Text .= " " . $this->Separator . " <a href=\"" . $tempLink . "\">" . htmlspecialchars($this->Crumbs[$i]['title']) . "</a>";
		}
	}
}
?>