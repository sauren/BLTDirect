<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductOptionGroup.php");
	
	class ProductOptionGroupCollection{
		var $Group;
		
		function ProductOptionGroupCollection($id){
			$this->Group = array();
			if(!empty($id)) $this->Get($id);
		}
		
		function Get($id){
			if(!is_numeric($id)){
				return false;
			}
			$sql = "select * from product_option_groups where Product_ID=%d and Is_Active='Y' order by Group_Type desc";
			$groups = new DataQuery(sprintf($sql, mysql_real_escape_string($id)));
			
			while($groups->Row){
				$tempGroup = new ProductOptionGroup;
				$tempGroup->ID = $groups->Row['Product_Option_Group_ID'];
				$tempGroup->Name = $groups->Row['Group_Title'];
				$tempGroup->Description = $groups->Row['Group_Description'];
				$tempGroup->Type = $groups->Row['Group_Type'];
				$tempGroup->ProductID = $groups->Row['Product_ID'];
				$tempGroup->GetOptions();
				$this->Group[] = $tempGroup;
				$groups->Next();
			}
			$groups->Disconnect();
		}
	}

?>
