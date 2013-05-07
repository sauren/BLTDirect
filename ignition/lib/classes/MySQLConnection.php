<?php
class MySQLConnection{
	var $Resource;
	var $DbHost;
	var $DbName;
	var $DbUsername;
	var $DbPassword;

	function __construct($dbHost = null, $dbName = null, $dbUsername = null, $dbPassword = null){
		$this->DbHost = is_null($dbHost) ? $GLOBALS['DB_HOST'] : $dbHost;
		$this->DbName = is_null($dbName) ? $GLOBALS['DB_NAME'] : $dbName;
		$this->DbUsername = is_null($dbUsername) ? $GLOBALS['DB_USERNAME'] : $dbUsername;
		$this->DbPassword = is_null($dbPassword) ? $GLOBALS['DB_PASSWORD'] : $dbPassword;

		$this->Resource = @mysql_connect($this->DbHost, $this->DbUsername, $this->DbPassword, true);

		if ($this->Resource === false) {
			header("HTTP/1.1 503 Service Temporarily Unavailable");
			echo "Sorry, the site is experiencing high traffic. Please try again later.";
			exit;
		}

		mysql_select_db($this->DbName, $this->Resource);
        mysql_query("SET NAMES utf8", $this->Resource);
        mysql_query("SET CHARACTER SET utf8", $this->Resource);
        mysql_query("SET character_set_client=utf8", $this->Resource);
        mysql_query("SET character_set_connection=utf8", $this->Resource);
        mysql_query("SET character_set_database=utf8", $this->Resource);
	}

	function __destruct() {
		$this->Close();
	}
	
	function Close(){
		@mysql_close($this->Resource);
	}
}