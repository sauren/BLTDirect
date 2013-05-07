<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Log.php');
	
	class GoogleLog extends Log{
		function GoogleLog(){
			$this->Owner = 'GoogleCheckout';
		}
		
		function LogRequest($data){
			//$this->Add('REQUEST', $data);
		}
		
		function LogError($data){
			//$this->Add('ERROR', $data);
		}
		
		function LogResponse($data){
			//$this->Add('RESPONSE', $data);
		}
	}
?>