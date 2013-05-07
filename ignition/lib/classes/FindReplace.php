<?php

	class FindReplace{
		var $Find;
		var $Replace;
		function FindReplace(){
			$this->Find = array();
			$this->Replace = array();
		}
		function Add($find, $replace){
			$this->Find[] = $find;
			$this->Replace[] = $replace;
		}
		
		function Execute($content){
			return preg_replace($this->Find, $this->Replace, $content);
		}
	}
?>