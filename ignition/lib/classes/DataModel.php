<?php
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

class Column {
	public $ID;
    public $Name;
	private $Value;
    private $Validation;
    private $MinVal;
    private $MaxVal;
    public $Required;
    private $Errrs;
	
	function Column($name, $colName, $validation, $minVal, $maxVal, $required=true)	{
		$this->ID = $name;
        $this->Name = $colName;
        $this->Validation = $validation;
        $this->MinVal = $minVal;
        $this->MaxVal = $maxVal;
        $this->Required = $required;
	}
	
	function Validate($name, $value) {
		$form = new Form('');
		$this->AddToForm($form, $name, $value);
		$form->SetValue($this->ID, $value);
        $valid = $form->Validate();
        $this->Errrs = $form->GetErrorList();
		return $valid;
	}
	
	function AddToForm($form, $field, $value) {
		$form->AddField($this->ID, $field, '', $value, $this->Validation, $this->MinVal, $this->MaxVal, $this->Required);
	}
	
	function Error($name=null) {  // XXX Is $name necessary?
		return $this->Errrs[0];
	}
	
	function Get() {
		return $this->Value;
	}
	
	function Set($value) {
		if ($this->Value === $value) { return; }
        $this->Value = $value;
	}
}

class DataModel {
	public $Columns = array();
	public $Errors = array();
	protected $TableName;
    public $Foreigners = array();

	// Keep a list of non-datamodel foreigners which skip some validation.
	public $OldForeigners = array();
	public $HasMany = array();

	function DataModel($TableName) {
        $this->TableName = $TableName;
	}

    function __clone(){
        foreach($this->Foreigners as $f => $g){
            $f = $this->PopVarFront($f);
            if(isset($this->$f)) $this->$f = clone $this->$f;
        }
    }
	
    public function Table(){
        return $this->TableName;
    }

	public function __set($name, $value) {
        $this->$name = $value;
        if(isset($this->Columns[$name])){
		    $this->Columns[$name]->Set($value);
        }
	}

    public function Field($name, $colName, $validation, $minVal, $maxVal, $required=true, $default=null){
        $this->Columns[$name] = new Column($name, $colName, $validation, $minVal, $maxVal, $required);
        $this->$name = $default;
    }

    public function Foreigner($foreigner, $property, $colName, $dataModel=true){
        $this->Foreigners["{$foreigner}->{$property}"] = $colName;
		if (!$dataModel) {
			$this->OldForeigners["{$foreigner}->{$property}"] = true;
		}
    }
		
	// Has many joins through a join table.
	public function HasMany($plural, $class, $joinTable) {
		$this->HasMany[$plural] = array('model'=>$class, 'table'=>$joinTable);
	}
	
	public function Join($plural) {
		$join = $this->HasMany[$plural];
		$obj = new $join['model'];
		$table = $obj->Table();
		$joinPrimary = $obj->Columns['ID']->Name;
		$this->$plural = $obj->GetAllFromSQL(<<<SQL
SELECT *
FROM {$table}
JOIN {$join['table']} ON {$join['table']}.{$joinPrimary}={$table}.{$joinPrimary}
WHERE {$this->Columns['ID']->Name}={$this->ID}
SQL
			);
		}

	public function Required($name, $require=true){
		$this->Columns[$name]->Required = $require;
	}

	function Get($id = null)
	{
		if(!is_null($id)) $this->ID = $id;

    return $this->GetThis("SELECT * FROM {$this->TableName} WHERE {$this->Columns['ID']->Name}={$this->ID}");
	}

    function GetAllFromSQL($sql)
    {
        $data = new DataQuery($sql);
        $c = array();
        for(;$data->Row; $data->Next()){
            // Must create a new class for each row obj otherwise __get/__set
            // will pass by reference to the original object.  Consequently, all
            // rows will show the data in the last fetched row.
            $class = get_class($this);
            $x = new $class;
            $x->BuildFromResultsRow($data->Row);
            $c[] = $x;
        }
        return $c;
    }

		function GetWhere($sql) {
			return $this->GetThis("SELECT * FROM {$this->TableName} WHERE {$sql}");
		}
	
	function GetAll() {
		$sql = "SELECT * FROM {$this->TableName}";
		return $this->GetAllFromSQL($sql);
	}
	
	function GetAllWhere($whereClause) {
		$sql = "SELECT * FROM {$this->TableName} WHERE $whereClause";
		return $this->GetAllFromSQL($sql);
	}

	function GetAllInclude($inc) {
		$incs = array();
		
		// Support both a string for a single value and an array for multiple.
		if (is_string($inc)) {
			$incs[] = $inc;
		} else {
			$incs = $inc;
		}

		$cols = array('*');
		$joins = array();

		foreach ($incs as $inc) {
			$cols[] = $inc;

			// Find item in Foreginers.
			foreach ($this->Foreigners as $foreigner=>$colName) {
				$a = explode('->', $foreigner);
				if ($a[0] == $inc) {
					$joins[$foreigner] = 'LEFT';
					break;
				}
			}
		}
		return $this->GetColumns($cols, $joins, '1');
	}

	function CountAll() {
		return $this->CountAllWhere('1');
	}

	function CountAllWhere($where) {
		$data = new DataQuery("SELECT Count(*) AS count FROM {$this->TableName} WHERE {$where}");
		$data->Disconnect();

		return $data->Row['count'];
	}

	function FieldDbName($field) {
		return $this->Columns[$field]->Name;
	}

    function GetColumns($cols, $joins, $where=null){
        $sql = $this->BuildQuery($cols, $joins, $where);
        return $this->BuildTreeFromSql($cols, $sql);
    }
	
	function GetColumnsAssoc($cols, $joins, $where=null, $bind) {
		$sql = $this->BuildQuery($cols, $joins, $where);
		return $this->BuildTreeFromSql($cols, $sql, $bind);
	}
	
	function GetErrors($key=''){
		//Recursively returns errors
		if($key) $key = "{$key}->";
		$errors = $this->Errors;
		foreach($errors as $k => $e){
			$newk = $key.$k;
			if($newk != $k){
				$errors[$newk] = str_replace($k, $newk, $e);
				unset($errors[$k]);
			}
		}
		foreach ($this->GetSubDataModels() as $child) {
			$className = get_class($child);
			$errors = array_merge($errors, $child->GetErrors($key.$className));
		}
		return $errors;
	}


	function Add() {
		if (!$this->Validate()) {
			return false;
		}
		$sql = "INSERT INTO {$this->TableName} ";
		$names = '(';
		$values = 'VALUES (';
		foreach ($this->Columns as $name => $column) {
			if ($name === 'ID') { continue; }
			
			$names .= "$column->Name, ";
			// For backwards compatibility, strip slashes first.
			// stripcslashes will recognise (and ignore?) c-like chars
			// such as \n, \r etc.
			$value = stripcslashes($this->$name);
			$value = DataQuery::escape($value);
			$values .= "$value, ";
		}
		foreach ($this->Foreigners as $f => $colName) {
			$p = explode('->', $f);
			// For backwards compatibility, strip slashes first.
			$value = stripcslashes($this->$p[0]->$p[1]);

			if (!$value) {
				continue;
			}

			$names .= "$colName, ";
			$values .= "$value, ";
		}
		
		$names = substr($names, 0, -2) . ')';
		$values = substr($values, 0, -2) . ')';

		$sql .= $names . ' ' . $values;
        $data = new DataQuery($sql);
		$this->ID = $data->InsertID;
		$data->Disconnect();

		$this->UpdateValues();
		return true;
	}
	
	function Update()
	{
		if (!$this->Validate()) {
			return false;
		}
		
		$changes = false;
		
		$sql = "UPDATE {$this->TableName} SET ";
		foreach ($this->Columns as $name => $column) {
			if ($column->Get() != $this->$name) {
				$changes = true;
				// For backwards compatibility, strip slashes first.
				// stripcslashes will recognise (and ignore?) c-like chars
				// such as \n, \r etc.
				$value = stripcslashes($this->$name);
				$value = DataQuery::escape($value);
				$sql .= "{$column->Name}=$value, ";
			}
		}
		foreach ($this->Foreigners as $f => $colName) {
			$p = explode('->', $f);
			if (isset($this->OldForeigners[$f]) || ($this->$p[0]->Columns[$p[1]]->Get() != $this->$p[0]->$p[1])) {
				$changes = true;
				// For backwards compatibility, strip slashes first.
				$value = stripcslashes($this->$p[0]->$p[1]);
				$value = DataQuery::escape($value);
				$sql .= "$colName=$value, ";
			}
		}
		$sql = substr($sql, 0, -2);
		$sql .= " WHERE {$this->Columns['ID']->Name}={$this->ID}";

		if (!$changes) {
			return true;
		}
		
		$data = new DataQuery($sql);
		$data->Disconnect();
		//return $sql;  // Useful when debugging.
		$this->UpdateValues();
		return true;
	}
	
	// If this object already exists in the database call update, else call add.
	function Save($recurse = false, $recurseTable = array()) {
		// e.g. $recurseTable = 
		//	   array(
		//		 'Customer' =>
		//		   array(
		//			 'Contact' => false
		//		   )
		//	   );
		//	Should recurse into customer and then contact, but go no further.
		if (!$this->Validate($recurse, $recurseTable)) {
			return false;
		}
		if($recurse){
			foreach ($this->Foreigners as $f => $fcol) {
				$child = $this->ForeignObjectName($f);
				// Objects not extending this class don't have a save method.
				if(is_object($this->$child)
					&& in_array('DataModel', class_parents($this->$child))
				){
					if($recurseTable[$child]){
						$this->$child->Save(true, $recurseTable[$child]);
					} else {
						$this->$child->Save();
					}
				}
			}
		}
		if (isset($this->ID) && $this->ID) {
			return $this->Update();
		}
		return $this->Add();
	}
	
	protected function UpdateValues(){
		foreach($this->Columns as $c){
			$c->Set($this->{$c->ID});
		}
	}

    protected function BuildFromResultsRowRecurse($property, &$datarow){
        if(strpos($property, '->') === false){
            if(is_object($this->$property)){
                $this->$property->BuildFromResultsRow($datarow);
            } else {
                $val = $datarow[$this->Columns[$property]->Name];
                $this->$property = $val;
                $this->Columns[$property]->Set($val);
            }
        } else {
            $props = explode('->', $property);
            $property = array_shift($props);
            $props = implode('->', $props);
            $this->$property->BuildFromResultsRowRecurse($props, $datarow);
        }
    }
    function BuildTreeFromSql($tree, $sql, $bind=null){
        // Builds dataset in object form. Returns array of objects.
        $obj = array();
        $data = new DataQuery($sql);
        for($i=0; $data->Row; $data->Next(), $i++){
			if ($bind) {
				$newObj = &$obj[$data->Row[$bind]][];
			} else {
				$newObj = &$obj[$i];
			}
            $newObj = clone $this;
            foreach($tree as $t){
                if($t == '*'){
                    foreach($obj[$i]->Columns as $c){
                        $newObj->BuildFromResultsRowRecurse($c->ID, $data->Row);
                    }
                } else {
                    $newObj->BuildFromResultsRowRecurse($t, $data->Row);
                }
            }
        }
        $data->Disconnect();
        return $obj;
    }
	
	// Cannot be recursive for backward compatability reasons.
	function BuildFromAssoc($assoc) {
		foreach ($this->Columns as $key => $value) {
			if (array_key_exists($key, $assoc)) {
				$this->$key = $assoc[$key];
			}
		}
	}
	
	function Validate($recurse = false, $recurseTable = array()) {
		$valid = true;
		foreach ($this->Columns as $key => $column) {
			if (!$column->Validate($key, $this->$key) &&
				($column->Get() != $this->$key || (!$this->ID && $key != 'ID')))
			{
				$this->Errors[$key] = $column->Error($key);
				$valid = false;
			}
		}
		if($recurse){
			foreach($this->Foreigners as $f => $fcol){
				// We do not validate non-DataModel objects.
				if(array_key_exists($f, $this->OldForeigners)) continue;
				$child = $this->foreignObjectName($f);

				if(!is_object($this->$child)){
					//TODO: Throw Exception: Sometimes child shouldn't exist (e.g. nested records)
					// so leave it to calling code to decide whether this is problem or not. 
				}else {
					if($recurseTable[$child]){
						if( $this->$child->Validate(true, $recurseTable[$child]) === false) { $valid = false; }
					} else {
						if( $this->$child->Validate() === false) { $valid = false; }
					}
				}
			}
		}
			
		return $valid;
		
	}
	
	function Delete($id = null)
	{
		if(!is_null($id) && is_numeric($id))
			$this->ID = $id;


		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM {$this->TableName} WHERE {$this->Columns['ID']->Name}=%d", mysql_real_escape_string($this->ID)));

		return true;
	}
	public function BuildFromResultsRow($row) {
		foreach ($this->Columns as $c) {
			$column = $c->Name;
			if ($row[$column]) {
				$c->Set($row[$column]);
                $id = $c->ID;
                $this->$id = $row[$column];
				$this->$column = $row[$column];
			}
		}
        foreach($this->Foreigners as $property => $column){
            $p = explode('->', $property);
            $this->$p[0]->$p[1] = $row[$column];
        }
	}
	
	// This is for compatibility between the old style form validation and the future
	// model validation.
	function AddToForm($form, $type, $field, $attr='') {
		$this->Columns[$field]->AddToForm($form, $field, $this->$field);
		$form->InputFields[$field]->HtmlType = $type;
		$form->InputFields[$field]->Attributes = $attr;
	}
	
	function HTMLField($field, $type, $attr='') {
		if ($type === 'textarea') {
			return "<textarea name=\"$field\" $attr>{$this->$field}</textarea>";
		} else {
			return "<input type=\"$type\" name=\"$field\" value=\"{$this->$field}\" $attr />";
		}
	}

	function GetSubDataModels(){
		$children = array();
		foreach ($this->Foreigners as $f => $fcol) {
            $child = $this->foreignObjectName($f);
			if(!array_key_exists($f, $this->OldForeigners)) {
				$children[] = $this->$child;
			}
		}
		return $children;
	}
	

	private function foreignObjectName($str){
		// arg is foreign key (e.g. 'Contact->ID')
		$vars = explode('->', $str);
		return array_shift($vars);
	}

	private function CreateColumns($list) {
		foreach ($list as $item) {
			$this->Columns[$item[0]] = new Column($item);
		}
	}

    private function PopVarFront(&$dynamicVar){
        $a = explode('->', $dynamicVar);
        $frontVar = array_shift($a);
        $dynamicVar = implode('->', $a);
        return $frontVar;
    }
    private function PopVar(&$dynamicVar){
        // Notice: Supplied argument passed by ref: will be modified.
        // Usage example, if $dynamicVar == 'path->to->variable->name',
        //                      $dynamicVar = 'path->to->variable'
        //                      return 'name'
        $a = explode('->', $dynamicVar);
        $endVar = array_pop($a);
        $dynamicVar = implode('->', $a);
        return $endVar;
    }

    private function LeftTableNamespace($dynamicVar){
        $this->PopVar($dynamicVar);
        $a = explode('->', $dynamicVar);
        if(count($a) > 0){
            $a = explode('->', $dynamicVar);
            $lns = implode('', $a);
            return $this->TableName.$lns;
        } else {
            return $this->TableName;
        }
    }

    private function RightTableNamespace($dynamicVar){
        $o = apply_property($this, $dynamicVar);
        if(!is_object($o)){
            $this->PopVar($dynamicVar);
        }
        $a = explode('->', $dynamicVar);
        $rns = implode('', $a);
        return $this->TableName . $rns;
    }

	private function BuildQuery($cols, $joins, $where=null){
        // Build columns
        // XXX Column names in datasets are not namespaced.  Risk of
        // data corruption possible when populating objects.
        $newcols = array();
        foreach($cols as $c){
            if($c == '*'){
                $namespace = $this->Table();
                foreach($this->Columns as $d){
                    $newcols[] = "$namespace.{$d->Name}";
                }
                foreach($this->Foreigners as $f){
                    $newcols[] = "$namespace.$f";
                }
                continue;
            }
            $o = apply_property($this, $c);
            if(is_object($o)) {
                // If $cols specifies an object to add, rather than properties
                // of an object, add all properties' columns in the object.
                foreach($o->Columns as $d){
                    $namespace = $this->RightTableNamespace($c);
                    $newcols[] = "$namespace.{$d->Name}";
                }
            } else {
                //Add just the property's columns
                $a = $c;
                if(strpos($c, '->') === false){
                    // It must belong to this object.
                    $namespace = $this->Table();
                    $newcols[] = "$namespace.{$this->Columns[$c]->Name}";
                } else {
                    $col = $this->PopVar($a);
                    $o2 = apply_property($this, $a);
                    $namespace = $this->RightTableNamespace($a);
                    $newcols[] = "$namespace.{$o2->Columns[$col]->Name}";
                }
            }
        }
        $colsFormatted = implode(', ', $newcols);

        // Build joins
        $newjoins = '';
        foreach($joins as $key => $type){
            $id = $this->PopVar($key);
            $b = explode('->', $key);
            $a = $key;
            $this->PopVar($a);
            $left = !empty($a) && count($a) > 0? apply_property($this, $a): $this;
            $leftNamespace = $this->LeftTableNamespace($key);
            $leftTable = $left->Table();
            $right = apply_property($this, $key);
            $rightNamespace = $this->RightTableNamespace($key);
            $rightTable = $right->Table();
            $key = $this->PopVar($key);
            $foreignKey = $left->Foreigners["{$key}->{$id}"];
            $s = "$type JOIN $rightTable $rightNamespace ON {$leftNamespace}.$foreignKey = $rightNamespace.{$right->Columns[$id]->Name} ";
            $newjoins .= $s;
        }

        // Build where clause
        if(!is_numeric($this->ID)){
			return false;
		}
        if(is_null($where)) $where = sprintf("{$this->TableName}.{$this->Columns['ID']->Name} = %d", mysql_real_escape_string($this->ID));
        
        return sprintf("SELECT $colsFormatted FROM {$this->TableName} $newjoins WHERE $where");
	}

	// Build this object from SQL.
	private  function GetThis($sql) {
		$data = new DataQuery($sql);

		if($data->TotalRows > 0)
		{
			$this->BuildFromResultsRow($data->Row);

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
}

?>
