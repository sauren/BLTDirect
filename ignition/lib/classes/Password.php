<?php
class Password{
	var $Length;
	var $AllowChars = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789";
	var $Value;
	
	function Password($newLength = 12){
		$this->Length = $newLength;
		$this->Generate();
	}
	
	function Generate(){
		$ps_len = strlen($this->AllowChars);
		mt_srand((double)microtime()*1000000);
		$pass = "";
		for($i = 0; $i < $this->Length; $i++) {
			$pass .= $this->AllowChars[mt_rand(0,$ps_len-1)];
		}
		$this->Value = $pass;
	}
}