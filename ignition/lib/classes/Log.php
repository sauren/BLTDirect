<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	class Log{
		var $Owner;

		function Log(){
			$this->Owner = 'System';
		}

		function Add($type, $message){

			if(is_object($message) || is_array($message)){
				ob_start();
					print_r($message);
					$message = ob_get_contents();
				ob_end_clean();
			}

			$sql = sprintf("insert into log (Owner, Type, Log_Message, Created_On) values('%s', '%s', '%s', now())", mysql_real_escape_string(stripslashes($this->Owner)), mysql_real_escape_string(stripslashes($type)), mysql_real_escape_string(stripslashes($message)));
			$data = new DataQuery($sql);
		}
	}
?>