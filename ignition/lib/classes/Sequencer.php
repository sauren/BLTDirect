<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

class Sequencer
{
	var $ID;
	var $IDName;
	var $ParentID;
	var $ParentIDName;
	var $Sequence;
	var $Order;
	var $Table;
	var $Redirect;
	var $IsActive;

	function Sequencer($table){
		$this->IsActive = false;

		switch($table){
			case 'document':
				$this->Table = $table;
				$this->IDName = 'ID';
				$this->ID = $_REQUEST['id'];
				$this->ParentIDName = 'Parent_ID';
				$this->ParentID = $_REQUEST['pid'];
				$this->Redirect = sprintf("Location: %s?id=%d&%s", $_SERVER['PHP_SELF'], $this->ParentID, $_REQUEST['tableid'].'_Current='.$_REQUEST['current']);
				break;
			case 'product_categories':
				$this->Table = $table;
				$this->IDName = 'Category_ID';
				$this->ParentIDName = 'Category_Parent_ID';
				$this->Order = 'Category_Order';
				$this->ID = $_REQUEST['node'];
				$this->Redirect = 'Location: product_categories.php?cat='.$this->ID;

				if(!is_numeric($this->ID)){
					return false;
				}
				$data2 = new DataQuery(sprintf("SELECT Category_Parent_ID FROM product_categories WHERE Category_ID=%d", mysql_real_escape_string($this->ID)));
				if($data2->Row['Category_Parent_ID'] > 0) {
					$sql = "SELECT p1.{$this->ParentIDName}, p2.{$this->Order}
                    FROM {$this->Table} AS p1
                    INNER JOIN {$this->Table} AS p2
                    ON p1.{$this->ParentIDName} = p2.{$this->IDName}
                    WHERE p1.{$this->IDName} = {$this->ID}";
					$data = new DataQuery($sql);
					if($data->TotalRows > 0) {
						$this->ParentID = $data->Row[$this->ParentIDName];
						$this->Sequence = $data->Row[$this->Order];
						$this->IsActive = true;
					}
				} else {
					$this->ParentID = 0;
					$this->Sequence = 'Sequence';
					$this->IsActive = true;
				}
				$data2->Disconnect();

				break;
		}
	}
	
	function MoveUp() {
		if($this->IsActive) {
			$lastSequence = 0;
			$lastID = 0;

			$sql = sprintf("SELECT {$this->IDName}, {$this->Sequence}
                                      FROM {$this->Table}
                                      WHERE {$this->ParentIDName}=%d
                                      ORDER BY {$this->Sequence} ASC",
			mysql_real_escape_string($this->ParentID));
			$data = new DataQuery($sql);
			while($data->Row) {
				if(($data->Row[$this->IDName] == $this->ID) && ($lastID != 0)){
					if(($lastSequence != 0) && ($data->Row[$this->Sequence] != 0)) {
						new DataQuery(sprintf("UPDATE {$this->Table}
                                          SET {$this->Sequence}=%d
                                          WHERE {$this->IDName}=%d",
						mysql_real_escape_string($lastSequence),
						mysql_real_escape_string($data->Row[$this->IDName])));
						new DataQuery(sprintf("UPDATE {$this->Table}
                                          SET {$this->Sequence}=%d
                                          WHERE {$this->IDName}=%d",
						mysql_real_escape_string($data->Row[$this->Sequence]),
						mysql_real_escape_string($lastID)));
					}
					break;
				}

				$lastSequence = $data->Row[$this->Sequence];
				$lastID = $data->Row[$this->IDName];

				$data->Next();
			}
			$data->Disconnect();
		}

		redirect($this->Redirect);
	}

	function MoveDown() {
		if($this->IsActive) {
			$lastSequence = 0;
			$lastID = 0;

			$sql = sprintf("SELECT {$this->IDName}, {$this->Sequence}
                                      FROM {$this->Table}
                                      WHERE {$this->ParentIDName}=%d
                                      ORDER BY {$this->Sequence} ASC",
			mysql_real_escape_string($this->ParentID));
			$data = new DataQuery($sql);
			while($data->Row) {
				if($data->Row[$this->IDName] == $this->ID){
					$lastID = $data->Row[$this->IDName];
					$lastSequence = $data->Row[$this->Sequence];
					$data->Next();
					if(($data->Row[$this->Sequence] != 0) && ($lastSequence != 0)) {
						new DataQuery(sprintf("UPDATE {$this->Table} SET {$this->Sequence}=%d
                                          WHERE {$this->IDName}=%d",
						mysql_real_escape_string($data->Row[$this->Sequence]),
						mysql_real_escape_string($lastID)));
						new DataQuery(sprintf("UPDATE {$this->Table} SET {$this->Sequence}=%d
                                          WHERE {$this->IDName}=%d",
						mysql_real_escape_string($lastSequence), mysql_real_escape_string($data->Row[$this->IDName])));
					}
					break;
				}
				$data->Next();
			}
			$data->Disconnect();
		}

		redirect($this->Redirect);
	}
}
?>