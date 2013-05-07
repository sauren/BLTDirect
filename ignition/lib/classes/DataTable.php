<?php
require_once($GLOBALS['DIR_WS_ADMIN']. "lib/classes/DataQuery.php");
require_once($GLOBALS['DIR_WS_ADMIN']. "lib/common/generic.php");

class DataTable{
	var $Connection;
	var $SQL;
	var $TotalRowSQL;
	var $Fields;
	var $Inputs;
	var $Options;
	var $TableHTML;
	var $MaxRows;
	var $TableName;
	var $Table;
	var $OrderBy;
	var $Order;
	var $AscImage;
	var $DescImage;
	var $UseImage;
	var $TotalRows;
	var $TotalPages;
	var $CurrentPage;
	var $LimitStart;
	var $LimitEnd;
	var $NFields;
	var $NInputs;
	var $Links;
	var $ExtractVars;
	var $ExtractVarsLink;
	var $BackgroundCondition;
	var $LinkColumns;
	var $SessionKey;

	function DataTable($reqName, $connection = null) {
		$this->Connection = !is_null($connection) ? $connection : $GLOBALS['DBCONNECTION'];
		$this->BackgroundCondition = array();
		$this->ExtractVars = ",action,confirm";
		$this->ExtractVarsLink = "action,confirm,id";
		$this->TableName = $reqName;
		$this->TableHTML = "";
		$this->MaxRows = 25;
		$this->Order = "ASC";
		$this->CurrentPage = 1;
		$this->NFields = 3;
		$this->NInputs = 7;
		$this->Fields = array();
		$this->Inputs = array();
		$this->Links = array();
		$this->AscImage = "./images/icon_ordered_asc_1.gif";
		$this->DescImage = "./images/icon_ordered_desc_1.gif";
		$this->LinkColumns = 0;
		
		$recompile = array();
		
		foreach($_REQUEST as $key=>$value) {
			if(!preg_match(sprintf('/%s_/', $this->TableName), $key)) {
				$recompile[] = $key . (!empty($value) ? '=' . $value : '');	
			}
		}
		
		$uri = $_SERVER['REQUEST_URI'];
		
		if(($pos = strpos($_SERVER['REQUEST_URI'], '?')) !== false) {
			$uri = substr($uri, 0, $pos);	
		}
		
		$this->SessionKey = $uri;
		
		if(!empty($recompile)) {
			$this->SessionKey .= '?' . implode('&', $recompile);
		}

		if(!isset($_SESSION['DataTable'][$this->SessionKey][$this->TableName])) {
			$_SESSION['DataTable'][$this->SessionKey][$this->TableName] = array();
		}
	}
	
	function SetSession($name, $value) {
		$_SESSION['DataTable'][$this->SessionKey][$this->TableName][$name] = $value;
	}
	
	function GetSession($name) {
		if(isset($_SESSION['DataTable'][$this->SessionKey][$this->TableName][$name])) {
			return $_SESSION['DataTable'][$this->SessionKey][$this->TableName][$name];
		} else {
			switch($name)	 {
				case 'order':
					return $this->Order;
					break;
					
				case 'page':
					return $this->CurrentPage;
					break;
					
				case 'sort':
					if(empty($this->OrderBy)) {
						return $this->Fields[1];
					} else {
						return $this->OrderBy;
					}
					break;
			}
		}
		
		return null;
	}

	function AddBackgroundCondition($column, $value, $operator = '==', $light = '', $dark = '') {
		if((is_array($column) && is_array($value) && is_array($operator) && (count($column) == count($value)) && (count($value) == count($operator))) || (!is_array($column) && !is_array($value) && !is_array($operator))) {
			$this->BackgroundCondition[] = array('Column' => $column, 'Value' => $value, 'Operator' => $operator, 'Light' => $light, 'Dark' => $dark);
		}
	}

	function SetExtractVars($vars = array()) {
		if(!is_array($vars)) {
			$vars = array($vars);
		}

		$this->ExtractVars = sprintf(",%s", implode(",", $vars));
	}
	
	function SetExtractVarsLink($vars = array()) {
		if(!is_array($vars)) {
			$vars = array($vars);
		}

		$this->ExtractVarsLink = implode(",", $vars);
	}

	function SetSQL($reqSQL='Table 1') {
		$this->SQL = $reqSQL;
	}

	function AddField($dbField, $fieldName='', $halign='left'){
		$this->Fields[] = trim($dbField);
		$this->Fields[] = trim($fieldName);
		$this->Fields[] = trim($halign);
		return true;
	}

	function SetMaxRows($reqPageResults=25){
		if (isset($_GET[sprintf("%s_maxRows", $this->TableName)])) {
			$this->MaxRows = $_GET[sprintf("%s_maxRows", $this->TableName)];
		} else {
			$this->MaxRows = $reqPageResults;
		}
	}

	function SetOrderBy($reqOrder){
		$this->OrderBy = trim($reqOrder);
	}
	
	function SetOrder($reqOrder){
		$this->Order = trim($reqOrder);
	}

	function DisplayTable(){
		$this->TableHTML .= $this->GetTableHeader();
		$this->GetTableBody();
		$this->TableHTML .= "</table>";
		echo $this->TableHTML;
		$this->TableHTML = NULL;
	}

	function DisplayRecordInfo(){
		echo sprintf("Page %d of %d (Records %s to %s of %s)", $this->CurrentPage, $this->TotalPages, ($this->LimitStart+1), min($this->LimitStart + $this->MaxRows, $this->TotalRows), $this->TotalRows);
		echo "<br /><br>";
	}

	function DisplayNavigation(){
		// first we need to find the start and finish values
		$start = 1;
		$end = ($this->TotalPages < 10)?$this->TotalPages:10;

		if(($this->CurrentPage - 4) > 2){
			$start = $this->CurrentPage - 4;
			if(($start + 9) > $this->TotalPages){
				$end = $this->TotalPages;
				$start = (($end-9)>0)?$end - 9:1;
			} else {
				$end = $start + 9;
			}
		}

		echo "<table style=\"width:100%; border:0px;\" class=\"dataNav_1\"><tr><td style=\"white-space:nowrap;\"><img src=\"images/icon_pages_1.gif\" width=\"14\" height=\"15\" alt=\"Navigation\" />";
		echo sprintf(" %s of %s </td>", $this->CurrentPage, $this->TotalPages);
		if($this->CurrentPage > 1){
			$newLink = htmlentities(sprintf("%s?%s_Current=%s%s",$_SERVER['SCRIPT_NAME'], $this->TableName, 1, extractVars(sprintf("%s_Current%s", $this->TableName, $this->ExtractVars))));
			echo sprintf("<td class=\"navCell_1 alignCenter\"><a href=\"%s\" width=\"70\">First</a></td>", $newLink);
		} else {
			echo "<td class=\"navCell_1\"><span class=\"fade\">First</span></td>";
		}
		if($this->CurrentPage > 1){
			$newLink = htmlentities(sprintf("%s?%s_Current=%s%s", $_SERVER['SCRIPT_NAME'], $this->TableName, ($this->CurrentPage-1), extractVars(sprintf("%s_Current%s", $this->TableName, $this->ExtractVars))));
			echo sprintf("<td class=\"navCell_1 alignCenter\"><a href=\"%s\" width=\"70\">Previous</a></td>", $newLink);
		} else {
			echo "<td class=\"navCell_1\"><span class=\"fade\">Previous</span></td>";
		}
		for($i=$start; $i<$end+1; $i++){
			$tempClass = "navCell_1";
			if(($i) == $this->CurrentPage){
				$tempClass = "navCell_2";
			}
			$newLink = htmlentities(sprintf("%s?%s_Current=%s%s", $_SERVER['SCRIPT_NAME'], $this->TableName, ($i), extractVars(sprintf("%s_Current%s", $this->TableName, $this->ExtractVars))));
			echo sprintf("<td class=\"%s\"><a href=\"%s\">%s</a></td>", $tempClass, $newLink, ($i));
		}
		if($this->CurrentPage < $this->TotalPages){
			$newLink =  htmlentities(sprintf("%s?%s_Current=%s%s", $_SERVER['SCRIPT_NAME'], $this->TableName, ($this->CurrentPage+1), extractVars(sprintf("%s_Current%s", $this->TableName, $this->ExtractVars))));
			echo sprintf("<td class=\"navCell_1 alignCenter\"><a href=\"%s\">Next</a></td>", $newLink);
		} else {
			echo "<td class=\"navCell_1\"><span class=\"fade\">Next</span></td>";
		}
		if($this->CurrentPage < $this->TotalPages){
			$newLink = htmlentities(sprintf("%s?%s_Current=%s%s", $_SERVER['SCRIPT_NAME'], $this->TableName, $this->TotalPages, extractVars(sprintf("%s_Current%s", $this->TableName, $this->ExtractVars))));
			echo sprintf("<td class=\"navCell_1 alignCenter\"><a href=\"%s\">Last</a></td>", $newLink);
		} else {
			echo "<td class=\"navCell_1\"><span class=\"fade\">Last</span></td>";
		}
		echo "</tr></table>";
	}

	function GetTableHeader(){

		$tempStr = "<table style=\"text-align:center; border-collapse:collapse\" class=\"DataTable\">";
		$tempStr .= "<thead><tr>";
		// generate field based information
		for($i=0; $i < count($this->Fields); $i+=$this->NFields){
			if($this->Fields[$i+2] != 'hidden') {
				if($this->OrderBy == $this->Fields[$i+1]){
					if(strtoupper($this->Order) == "ASC"){
						$newLink = sprintf("%s?%s_Sort=%s&amp;%s_Ord=DESC%s", $_SERVER['SCRIPT_NAME'], $this->TableName, $this->Fields[$i+1], $this->TableName, extractVars(sprintf("%s_Sort,%s_Ord%s", $this->TableName, $this->TableName, $this->ExtractVars)));
					} else {
						$newLink = sprintf("%s?%s_Sort=%s&amp;%s_Ord=ASC%s", $_SERVER['SCRIPT_NAME'], $this->TableName, $this->Fields[$i+1], $this->TableName, extractVars(sprintf("%s_Sort,%s_Ord%s", $this->TableName, $this->TableName, $this->ExtractVars)));
					}
					$tempStr  .= sprintf("<th class=\"dataHeadOrdered\" style=\"white-space:nowrap;\" onClick=\"window.self.location.href='%s'\">%s <span class=\"iconOrdered\"><img src=\"%s\" width=\"11\" height=\"10\"></span></th>", $newLink, $this->Fields[$i], $this->UseImage);
				} else {
					$newLink = sprintf("%s?%s_Sort=%s%s", $_SERVER['SCRIPT_NAME'], $this->TableName, $this->Fields[$i+1], extractVars(sprintf("%s_Sort%s", $this->TableName, $this->ExtractVars)));
					$tempStr  .= sprintf("<th style=\"white-space:nowrap;\" onClick=\"window.self.location.href='%s'\">%s <span class=\"iconOrdered\"><img src=\"./images/blank.gif\" width=\"11\" height=\"10\"></span></th>", $newLink, $this->Fields[$i]);
				}
			}
		}

		for($i=0; $i < count($this->Inputs); $i+=$this->NInputs){
			if($this->Inputs[$i+1] == 'Y') {
				if($this->OrderBy == $this->Inputs[$i+2]){
					if(strtoupper($this->Order) == "ASC"){
						$newLink = sprintf("%s?%s_Sort=%s&amp;%s_Ord=DESC%s", $_SERVER['SCRIPT_NAME'], $this->TableName, $this->Inputs[$i+2], $this->TableName, extractVars(sprintf("%s_Sort,%s_Ord%s", $this->TableName, $this->TableName, $this->ExtractVars)));
					} else {
						$newLink = sprintf("%s?%s_Sort=%s&amp;%s_Ord=ASC%s", $_SERVER['SCRIPT_NAME'], $this->TableName, $this->Inputs[$i+2], $this->TableName, extractVars(sprintf("%s_Sort,%s_Ord%s", $this->TableName, $this->TableName, $this->ExtractVars)));
					}
					$tempStr .= sprintf("<th class=\"dataHeadOrdered\" style=\"width:1%%; white-space:nowrap;\" onclick=\"window.self.location.href='%s'\">%s <span class=\"iconOrdered\"><img src=\"%s\" width=\"11\" height=\"10\"></span></th>", $newLink, $this->Inputs[$i], $this->UseImage);
				} else {
					$newLink = sprintf("%s?%s_Sort=%s%s", $_SERVER['SCRIPT_NAME'], $this->TableName, $this->Inputs[$i+2], extractVars(sprintf("%s_Sort%s", $this->TableName, $this->ExtractVars)));
					$tempStr .= sprintf("<th style=\"width:1%%; white-space:nowrap;\" onClick=\"window.self.location.href='%s'\">%s <span class=\"iconOrdered\"><img src=\"./images/blank.gif\" width=\"11\" height=\"10\"></span></th>", $newLink, $this->Inputs[$i]);
				}
			} else {
				$tempStr .= sprintf("<th style=\"width:1%%; white-space:nowrap;\">%s</th>", $this->Inputs[$i]);
			}
		}

		// generate link based information
		if(count($this->Links) > 0){
			$tempStr  .= sprintf("<th colspan=\"%s\">&nbsp;</th>", (count($this->Links) / 5));
		}

		$tempStr  .= "</tr></thead>";
		return $tempStr;
	}

	function ExecuteSQL(){
		$this->FormatSQL();
		$this->Table = new DataQuery($this->SQL, $this->Connection);
	}

	function Disconnect(){
		$this->Table->Disconnect();
	}

	function Next(){
		$this->Table->Next();
	}
	/*
	getTableBody
	formats the SQL query and appends the
	returned data to the tableHTML variable
	*/
	function GetTableBody(){
		$this->ExecuteSQL();
		$this->TableHTML .= "<tbody>";
		if($this->Table->TotalRows > 0){
			do{
				$this->TableHTML .= $this->GetRow();
				$this->Table->Next();
			} while($this->Table->Row);
		} else {
			$this->TableHTML .= sprintf("<tr class=\"dataRow\"><td colspan=\"%s\">No Records Found</td></tr>", $this->NFields + $this->NInputs + $this->LinkColumns);
		}
		$this->Table->Disconnect();
		$this->TableHTML .= "</tbody>";
	}

	function GetRow(){
		$tempStr = "<tr class=\"dataRow\">";

		$highlight = false;
		$highlightIndex = -1;
		$failed = false;

		foreach($this->BackgroundCondition as $index=>$condition) {
			if(is_array($condition['Column'])) {

                for($i=0; $i<count($this->Fields); $i+=$this->NFields){
                    for($k=0; $k<count($condition['Column']); $k++) {
                        if($condition['Column'][$k] == $this->Fields[$i+1]) {

                            switch($condition['Operator'][$k]) {
                                case '>':
									if(stripslashes($this->Table->Row[$this->Fields[$i+1]]) <= (isset($this->Table->Row[$condition['Value'][$k]]) ? stripslashes($this->Table->Row[$condition['Value'][$k]]) : $condition['Value'][$k])) {
                                        $failed = true;
                                    }
                                    break;

                                case '<':
									if(stripslashes($this->Table->Row[$this->Fields[$i+1]]) >= (isset($this->Table->Row[$condition['Value'][$k]]) ? stripslashes($this->Table->Row[$condition['Value'][$k]]) : $condition['Value'][$k])) {
                                        $failed = true;
                                    }
                                    break;

                                case '!=':
									if(stripslashes($this->Table->Row[$this->Fields[$i+1]]) == (isset($this->Table->Row[$condition['Value'][$k]]) ? stripslashes($this->Table->Row[$condition['Value'][$k]]) : $condition['Value'][$k])) {
                                        $failed = true;
                                    }
                                    break;

                                default:
									if(stripslashes($this->Table->Row[$this->Fields[$i+1]]) != (isset($this->Table->Row[$condition['Value'][$k]]) ? stripslashes($this->Table->Row[$condition['Value'][$k]]) : $condition['Value'][$k])) {
                                        $failed = true;
                                    }
                                    break;
                            }
                        }
                    }
                }

                if(!$failed) {
                    $highlight = true;
                    $highlightIndex = $index;
                }

            } else {
                if(!$highlight) {
                    for($i=0; $i < count($this->Fields); $i+=$this->NFields){
                        if($condition['Column'] == $this->Fields[$i+1]) {
                            switch($condition['Operator']) {
                                case '>':
									if(stripslashes($this->Table->Row[$this->Fields[$i+1]]) > (isset($this->Table->Row[$condition['Value']]) ? stripslashes($this->Table->Row[$condition['Value']]) : $condition['Value'])) {
                                        $highlight = true;
                                    }
                                    break;

                                case '<':
									if(stripslashes($this->Table->Row[$this->Fields[$i+1]]) < (isset($this->Table->Row[$condition['Value']]) ? stripslashes($this->Table->Row[$condition['Value']]) : $condition['Value'])) {
									    $highlight = true;
                                    }
                                    break;

                                case '!=':
									if(stripslashes($this->Table->Row[$this->Fields[$i+1]]) != (isset($this->Table->Row[$condition['Value']]) ? stripslashes($this->Table->Row[$condition['Value']]) : $condition['Value'])) {
									    $highlight = true;
                                    }
                                    break;

                                default:
									if(stripslashes($this->Table->Row[$this->Fields[$i+1]]) == (isset($this->Table->Row[$condition['Value']]) ? stripslashes($this->Table->Row[$condition['Value']]) : $condition['Value'])) {
                                       $highlight = true;
                                    }
                                    break;
                            }

                            if($highlight) {
								$highlightIndex = $index;
							}
                        }
                    }
                }
            }
        }

        // generate field based information
		for($i=0; $i < count($this->Fields); $i+=$this->NFields){
			if($this->Fields[$i+2] != 'hidden') {
				if($this->OrderBy == $this->Fields[$i+1]){
					$tempStr .= sprintf("<td class=\"dataOrdered\" style=\"%s; text-align:%s;\">", (($highlight) ? sprintf('background-color: %s', $this->BackgroundCondition[$highlightIndex]['Dark']) : ''), $this->Fields[$i+2]);
				} else {
					$tempStr .= sprintf("<td style=\"%s; text-align:%s\">", (($highlight) ? sprintf('background-color: %s', $this->BackgroundCondition[$highlightIndex]['Light']) : ''), $this->Fields[$i+2]);
				}
				$tempStr .= sprintf("%s&nbsp;</td>", stripslashes($this->Table->Row[$this->Fields[$i+1]]));
			}
		}

		for($i=0; $i < count($this->Inputs); $i+=$this->NInputs){
			if($this->Inputs[$i+5] == 'text') {
				$key = sprintf('%s_%s', $this->Inputs[$i+3], stripslashes($this->Table->Row[$this->Inputs[$i+4]]));
				$tempStr .= sprintf("<td %s %s><input type=\"text\" name=\"%s\" value=\"%s\" %s /></td>", (($this->Inputs[$i+1] == 'Y') && ($this->OrderBy == $this->Inputs[$i+2])) ? 'class="dataOrdered"' : '', (($highlight) ? sprintf('style="background-color: %s"', $this->BackgroundCondition[$highlightIndex]['Light']) : ''), $key, isset($_REQUEST[$key]) ? $_REQUEST[$key] : (($this->Inputs[$i+1] == 'Y') ? (isset($this->Table->Row[$this->Inputs[$i+2]]) ? stripslashes($this->Table->Row[$this->Inputs[$i+2]]) : '') : $this->Inputs[$i+2]), $this->Inputs[$i+6]);

			} elseif($this->Inputs[$i+5] == 'checkbox') {
				$key = sprintf('%s_%s', $this->Inputs[$i+3], stripslashes($this->Table->Row[$this->Inputs[$i+4]]));
				$tempStr .= sprintf("<td %s %s align=\"center\"><input type=\"checkbox\" name=\"%s\" value=\"%s\" %s %s /></td>", (($this->Inputs[$i+1] == 'Y') && ($this->OrderBy == $this->Inputs[$i+2])) ? 'class="dataOrdered"' : '', (($highlight) ? sprintf('style="background-color: %s"', $this->BackgroundCondition[$highlightIndex]['Light']) : ''), $key, isset($_REQUEST[$key]) ? $_REQUEST[$key] : (isset($this->Table->Row[$this->Inputs[$i+2]]) ? stripslashes($this->Table->Row[$this->Inputs[$i+2]]) : ''), $this->Inputs[$i+6], (isset($_REQUEST[$key]) || (isset($this->Table->Row[$this->Inputs[$i+2]]) && ($this->Table->Row[$this->Inputs[$i+2]] == 'Y'))) ? 'checked="checked"' : '');

			} elseif($this->Inputs[$i+5] == 'radio') {
				$key = $this->Inputs[$i+3];
				$tempStr .= sprintf("<td %s %s align=\"center\"><input type=\"radio\" name=\"%s\" value=\"%s\" %s %s /></td>", (($this->Inputs[$i+1] == 'Y') && ($this->OrderBy == $this->Inputs[$i+2])) ? 'class="dataOrdered"' : '', (($highlight) ? sprintf('style="background-color: %s"', $this->BackgroundCondition[$highlightIndex]['Light']) : ''), $key, (isset($this->Table->Row[$this->Inputs[$i+2]]) ? stripslashes($this->Table->Row[$this->Inputs[$i+2]]) : ''), $this->Inputs[$i+6], (isset($_REQUEST[$key]) && ($_REQUEST[$key] == (isset($this->Table->Row[$this->Inputs[$i+2]]) ? stripslashes($this->Table->Row[$this->Inputs[$i+2]]) : ''))) ? 'checked="checked"' : '');

			} elseif($this->Inputs[$i+5] == 'select') {
				$key = sprintf('%s_%s', $this->Inputs[$i+3], stripslashes($this->Table->Row[$this->Inputs[$i+4]]));
				$tempStr .= sprintf("<td %s %s>", (($this->Inputs[$i+1] == 'Y') && ($this->OrderBy == $this->Inputs[$i+2])) ? 'class="dataOrdered"' : '', (($highlight) ? sprintf('style="background-color: %s"', $this->BackgroundCondition[$highlightIndex]['Light']) : ''));
				$tempStr .= sprintf("<select name=\"%s\" %s>", $key, $this->Inputs[$i+6]);

				if(isset($this->Options[$this->Inputs[$i]])) {
					foreach($this->Options[$this->Inputs[$i]] as $values) {
						$selected = ($values[0] == (isset($_REQUEST[$key]) ? $_REQUEST[$key] : (($this->Inputs[$i+1] == 'Y') ? (isset($this->Table->Row[$this->Inputs[$i+2]]) ? stripslashes($this->Table->Row[$this->Inputs[$i+2]]) : '') : $this->Inputs[$i+2]))) ? true : false;

						$tempStr .= sprintf('<option value="%s" %s>%s</option>', $values[0], ($selected) ? 'selected="selected"' : '', $values[1]);
					}
				}

				$tempStr .= sprintf("</select>");
				$tempStr .= sprintf("</td>");
			}
		}

		for($j=0; $j < count($this->Links); $j+=5) {
			$isDisabled = false;

			if(count($this->Links[$j+4]) == 3) {
				$isDisabled = true;

				for($i=0; $i < count($this->Fields); $i+=$this->NFields) {
					if($this->Links[$j+4][0] == $this->Fields[$i+1]) {
						switch($this->Links[$j+4][1]) {
							case '>':
								if(stripslashes($this->Table->Row[$this->Fields[$i+1]]) > (isset($this->Table->Row[$this->Links[$j+4][2]]) ? stripslashes($this->Table->Row[$this->Links[$j+4][2]]) : $this->Links[$j+4][2])) {
									$isDisabled = false;
								}
								break;
							case '<':
								if(stripslashes($this->Table->Row[$this->Fields[$i+1]]) < (isset($this->Table->Row[$this->Links[$j+4][2]]) ? stripslashes($this->Table->Row[$this->Links[$j+4][2]]) : $this->Links[$j+4][2])) {
									$isDisabled = false;
								}
								break;
							case '!=':
								if(stripslashes($this->Table->Row[$this->Fields[$i+1]]) != (isset($this->Table->Row[$this->Links[$j+4][2]]) ? stripslashes($this->Table->Row[$this->Links[$j+4][2]]) : $this->Links[$j+4][2])) {
									$isDisabled = false;
								}
								break;
							default:
								if(stripslashes($this->Table->Row[$this->Fields[$i+1]]) == (isset($this->Table->Row[$this->Links[$j+4][2]]) ? stripslashes($this->Table->Row[$this->Links[$j+4][2]]) : $this->Links[$j+4][2])) {
									$isDisabled = false;
								}
								break;
						}
					}
				}
			}

			$tempLink = $this->Links[$j];

			if($this->Links[$j+2] != false){
				$tempLink = sprintf(htmlspecialchars(urldecode($this->Links[$j])), $this->Table->Row[$this->Links[$j+2]]);
			}

			$tempStr .= sprintf("<td style=\"white-space:nowrap; %s; text-align:center; width:16px;\">", (($highlight) ? sprintf('background-color: %s', $this->BackgroundCondition[$highlightIndex]['Light']) : ''));

			if(!$isDisabled) {
				$tempStr .= sprintf("<a href=\"%s\" %s>%s</a>", $tempLink, ($this->Links[$j+3]) ? 'target="_blank"' : '', $this->Links[$j+1]);
			} else {
				$tempStr .= '&nbsp;';
			}

			$tempStr .= sprintf("</td>");
		}

		$tempStr .= "</tr>";
		return $tempStr;
	}

	/*
	formatSQL
	updates the SQL query requested with the following:
	1. column to sort
	2. the order of the column
	3. the start and end records to return
	*/
	function FormatSQL() {
		$SQLOrder = '';
		if(!empty($this->OrderBy)){
			$SQLOrder = sprintf(" ORDER BY %s %s", $this->OrderBy, $this->Order);
		}
		$this->SQL = sprintf("%s %s LIMIT %s, %s", $this->SQL, $SQLOrder, $this->LimitStart, $this->LimitEnd);
	}
	/*
	finalise !important
	this function must be called to finalise the data table
	if not called the data table may not function properly
	*/
	function Finalise(){
		$this->GetTotalRows();
		$this->GetOrder();
		$this->GetCurrentPage();
		$this->GetLimits();
	}
	/*
	getTotalRows
	uses the unedited SQL query to find the total number of results
	for the query and returns the total pages.
	*/
	function GetTotalRows(){
		$data = new DataQuery((!empty($this->TotalRowSQL) ? $this->TotalRowSQL : $this->SQL), $this->Connection);
		$this->TotalRows = !empty($this->TotalRowSQL) ? $data->Row['TotalRows'] : $data->TotalRows;
		$data->Disconnect();

		$this->GetMaxRows();
		$this->TotalPages = ceil($this->TotalRows/$this->MaxRows);
	}

	function SetTotalRowSQL($sql) {
		$this->TotalRowSQL = $sql;
	}

	/*
	getOrder
	there are two order variables to consider
	1. the order whether ascending or descending
	2. the column to order by
	*/
	function GetOrder(){
		// find the order
		$tempOrder = sprintf("%s_Ord", $this->TableName);
		if(isset($_REQUEST[$tempOrder])){
			$this->Order = $_REQUEST[$tempOrder];
			
			$this->SetSession('order', $this->Order);
		}
		
		$this->Order = $this->GetSession('order');
		
		// set the image to be used based on order
		if(strtoupper($this->Order) == "ASC"){
			$this->UseImage = $this->AscImage;
		} else {
			$this->UseImage = $this->DescImage;
 		}
		// determine the field to sort
		$tempOrderBy = sprintf("%s_Sort", $this->TableName);
		if(isset($_REQUEST[$tempOrderBy])){
			$this->OrderBy = $_REQUEST[$tempOrderBy];

			$this->SetSession('sort', $this->OrderBy);
		}

		$this->OrderBy = $this->GetSession('sort');
	}
	/*
	getCurrentPage
	the current page has a default value of 1
	this function replaces the default page with a URI
	requested page if it exists.
	*/
	function GetCurrentPage(){
		$tempReq = sprintf("%s_Current", $this->TableName);
		if(isset($_REQUEST[$tempReq])) {
			$this->CurrentPage = ($_REQUEST[$tempReq] > 0) ? $_REQUEST[$tempReq] : 1;
			
			$this->SetSession('page', $this->CurrentPage);
		}
		
		$this->CurrentPage = $this->GetSession('page');
	}
	/*
	getMaxRows
	resets the maxRows if value specified in the URI
	*/
	function GetMaxRows(){
		if(strtoupper($this->MaxRows) == "ALL"){
			$this->MaxRows = $this->TotalRows;
		} else {
			$tempReq = sprintf("%s_MaxRows", $this->TableName);
			if(isset($_REQUEST[$tempReq])){
				$this->MaxRows = $_REQUEST[$tempReq];
			}
		}
	}
	/*
	getLimits
	returns the start row and the maximum rows to be returned
	*/
	function GetLimits(){
		$this->LimitStart = ($this->CurrentPage - 1) * $this->MaxRows;
		$this->LimitEnd = $this->MaxRows;
	}

	function AddLink($url, $theLink, $useField=false, $keepQS=true, $newPage=false, $conditions = array()) {
		$this->LinkColumns++;

		if(!empty($_SERVER['QUERY_STRING']) && ($keepQS)){
			$qsVars = extractVars($this->ExtractVarsLink);
			$temp = "%s" . $qsVars;
			$url = sprintf($url, $temp);
		}

		array_push($this->Links, $url, $theLink, $useField, $newPage, $conditions);
	}

	function AddInput($fieldName, $populateWithColumn='Y', $populateData='', $namePrefix='', $identifier='', $inputType='text', $attributes='') {
		$this->Inputs[] = trim($fieldName);
		$this->Inputs[] = trim($populateWithColumn);
		$this->Inputs[] = trim($populateData);
		$this->Inputs[] = trim($namePrefix);
		$this->Inputs[] = trim($identifier);
		$this->Inputs[] = trim($inputType);
		$this->Inputs[] = trim($attributes);
	}

	function AddInputOption($fieldName, $optionValue, $optionDisplay){
		$this->Options[$fieldName][] = array($optionValue, $optionDisplay);
	}
}