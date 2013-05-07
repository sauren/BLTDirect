<?php

require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/RowSet.php');

class DataQuery {
	var $DataConnection;
	var $Connection;
	var $RecordSet;
	var $TotalRows;
	var $InsertID;
	var $AffectedRows;
	var $ServerVersion;
	var $Query;
	var $Row;
	var $ErrorNumber;
	var $ErrorMessage;

	private static $allowCaching = true;

	function DataQuery($sql = null, $connection = null) {
		$this->DataConnection = !is_null($connection) ? $connection : $GLOBALS['DBCONNECTION'];
		$this->Connection = $this->DataConnection->Resource;
		$this->Row = array();

		if(!is_null($sql)) {
			$this->Execute($sql);
		}
	}

	public static function allowCaching($allow) {
		self::$allowCaching = $allow;
	}

	function Execute($sql, $dieOnFailure = true){
		$this->Query = $sql;
		$this->ErrorNumber = '';
		$this->ErrorMessage = '';

		$time = microtime(true);

		$this->RecordSet = mysql_query($this->Query, $this->Connection);
		
		Application::addTiming('Database', $time, $this->Query);
		
		if($this->RecordSet === false) {
			$this->ErrorNumber = mysql_errno($this->Connection);
			$this->ErrorMessage = mysql_error($this->Connection);

			if($dieOnFailure) {
				if(($this->ErrorNumber > 0) && ($this->ErrorNumber < 2000)) {
					$logHeader = array();
					$logHeader[] = sprintf("Date Executed: %s", date('Y-m-d H:i:s'));
					$logHeader[] = sprintf("SQL Error No: %s", mysql_errno());
					$logHeader[] = sprintf("SQL Error: %s", mysql_error($this->Connection));
					$logHeader[] = '';
	
					$log = array();
					$log[] = "QUERY";
					$log[] = '=====';
					$log[] = $this->Query;
	
					$log = array_merge($logHeader, $log);
					$log = implode("\n", $log);
					
					evance_error_handler(E_USER_ERROR, 'Database Error', __FILE__, __LINE__, $log);
				}
			}
			
			return false;
		}

		switch(strtoupper($this->Query[0])){
			case 'S':
				if(isset($this->Query[2]) && (strtoupper($this->Query[2]) == 'L')) { 
					$this->GetTotalRows();
					$this->Next();
				}
				break;
			case 'I':
				$this->GetInsertId();
				$this->Close();
				break;
			case 'D':
				$this->GetAffectedRows();
				$this->Close();
				break;
			case 'U':
				$this->GetAffectedRows();
				$this->Close();
				
				if(!DEVELOPER && class_exists('Zend_Cache')) {
					if(self::$allowCaching) {
						if(preg_match_all('/(?:([\'"])(?:\\\\|\\\1|(?!\1).|\1\1)*\1|(?:(?<!\d)-)?\d+(?:\.\d+)?(?:[eE]-?\d+)?|\.\.|(?:\w+\.)*\w+|[<>=|]{2}|\S)/', strtolower($this->Query), $matches)) {
							$table = $matches[0][1];
							$key = null;
							
							$foundWhere = false;
							$foundKey = false;
							
							foreach($matches[0] as $index=>$match) {
								if($foundWhere) {
									if(($match == 'id') || ($match == $table.'id') || ($match == $table.'_id') || ($match == substr($table, 0, -1).'id') || ($match == substr($table, 0, -1).'_id')) {
										if(isset($matches[0][$index+1]) && ($matches[0][$index+1] == '=')) {
											if(isset($matches[0][$index+2])) {
												$key = trim($matches[0][$index+2], '\'');
												
												$foundKey = true;
												break;
											}
										}
									}
								}
								
								if($match == 'where') {
									$foundWhere = true;
								}
							}
							
							if($foundKey) {
								$cacheKeys = array($table . '__' . $key);
								
								foreach($cacheKeys as $cacheId) {
									$cache = Zend_Cache::factory('Output', $GLOBALS['CACHE_BACKEND']);
									$cache->remove($cacheId);
								}
							}
						}
					}
				}

				break;
		}

		return true;
	}
	
	public static function Upsert($table, $data=array()) {
		$fields = join(', ', array_keys($data));
		$values = join(', ', $data);
		
		$updates = array();
		foreach ($data as $column=>$value) {
			$updates[] = "{$column}={$value}";
		}
		$updates = join(",\n", $updates);
		
		$sql = <<<SQL
insert into {$table}
({$fields})
values
({$values})
on duplicate key update
{$updates}
SQL;

        // execute the statement and return the number of affected rows
        $data = new DataQuery($sql);
        return $data;
	}

	public static function FetchOne($sql) {
		$data = new DataQuery($sql);
		$value = $data->Row ? reset($data->Row) : null;
		$data->Disconnect();
		return $value;
	}

	public static function FetchPairs($sql) {
		$pairs = array();

		$data = new DataQuery($sql);
		$data->Reset();

		for (; $data->Row; $data->NextAsArray()) {
			$pairs[$data->Row[0]] = $data->Row[1];
		}
		
		$data->Disconnect();

		return $pairs;
	}

	function GetTotalRows(){
		$this->TotalRows = mysql_num_rows($this->RecordSet);
	}

	function Next(){
		$this->Row = mysql_fetch_assoc($this->RecordSet);
	}

	function NextAsArray(){
		$this->Row = mysql_fetch_array($this->RecordSet);
	}

	function FetchAllObjects() {
		$this->Reset();
		$objs = array();

		$obj = null;

		for (; $obj = mysql_fetch_object($this->RecordSet);) {
			$objs[] = $obj;
		}

		return $objs;
	}

	function Reset(){
		if ($this->TotalRows > 0) {
			$this->Row = mysql_data_seek($this->RecordSet, 0);
		}
	}

	function GetInsertId(){
		$this->InsertID = mysql_insert_id($this->Connection);
	}

	function Disconnect(){
		$this->Close();
	}

	function Close(){
		if(is_resource($this->RecordSet)){
			@mysql_free_result($this->RecordSet);
		}
	}

	function GetAffectedRows(){
		$this->AffectedRows = mysql_affected_rows($this->Connection);
	}

	function GetVersion(){
		$this->ServerVersion = mysql_get_server_info($this->Connection);
		return $this->ServerVersion;
	}
}