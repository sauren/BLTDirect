<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Category.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");

class UrlAlias {
	private static $aliases;
	
	public static function getUrl($type, $referenceId) {
		$type = strtolower($type);
		
		if(is_null(self::$aliases)) {
			self::$aliases = array();
		}
		
		if(!isset(self::$aliases[$type])) {
			self::$aliases[$type] = array();
			
			$data = new DataQuery(sprintf("SELECT Alias, Type, Reference_ID FROM url_alias WHERE Type LIKE '%s'", mysql_real_escape_string($type)));
			while($data->Row) {
				self::$aliases[$type][$data->Row['Reference_ID']] = $data->Row['Alias'];
				
				$data->Next();
			}
			$data->Disconnect();
		}
		
		if(isset(self::$aliases[$type][$referenceId])) {
			return self::$aliases[$type][$referenceId];
		}

		return null;
	}
	
	var $ID;
	var $Alias;
	var $Type;
	var $ReferenceID;
	var $Error;

	function UrlAlias($id = null) {
		$this->Error = array();

		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM url_alias WHERE Url_Alias_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Alias = $data->Row['Alias'];
			$this->Type = $data->Row['Type'];
			$this->ReferenceID = $data->Row['Reference_ID'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add($connection = null) {
		$this->Error = array();

		$this->Alias = strtolower(str_replace('\\', '/', str_replace('_', $GLOBALS['URLALIAS_DIVIDER'], str_replace(' ', $GLOBALS['URLALIAS_DIVIDER'], trim($this->Alias)))));
		$this->Alias = preg_replace('/[^a-z0-9\-\/.]/i', '', $this->Alias);

		if(substr($this->Alias, -1) == '/') {
			$this->Alias = substr($this->Alias, 0, -1);
		}

		$first = substr($this->Alias, 0, 1);

		if((strlen($this->Alias) == 0) || (($first == '/') && (strlen($this->Alias) == 1))) {
			$this->Error[] = 'URL Alias must contain a valid URL path.';
			return false;
		}

		if($first != '/') {
			$this->Alias = '/' . $this->Alias;
		}

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM url_alias WHERE Alias LIKE '%s'", mysql_real_escape_string($this->Alias)), $connection);
		if($data->Row['Count'] > 0) {
			$this->Alias = $this->MakeUnique($this->Alias, 1, $connection);
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("INSERT INTO url_alias (Alias, Type, Reference_ID) VALUES ('%s', '%s', %d)", mysql_real_escape_string(stripslashes($this->Alias))), mysql_real_escape_string($this->Type, mysql_real_escape_string($this->ReferenceID)), $connection);

		$this->ID = $data->InsertID;

		return true;
	}

	function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM url_alias WHERE Url_Alias_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function MakeUnique($alias, $index = 1, $connection = null) {
		$temp = sprintf('%s%s%d', $alias, $GLOBALS['URLALIAS_DIVIDER'], $index);

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM url_alias WHERE Alias LIKE '%s'", mysql_real_escape_string($temp)), $connection);
		if($data->Row['Count'] > 0) {
			$index++;
			$temp = $this->MakeUnique($alias, $index, $connection);
		}
		$data->Disconnect();

		return $temp;
	}
}
?>