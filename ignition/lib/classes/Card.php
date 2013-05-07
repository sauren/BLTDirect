<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CardType.php");
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");
	class Card{
		var $Number;
		var $Expires;
		var $Initial;
		var $Title;
		var $Surname;
		var $Type;

		function Card(){
			$this->Type = new CardType;
		}

		function GetNumber(){
			if(!empty($this->Number)){
				$cardNum = new Cipher($this->Number);
				$cardNum->Decrypt();
			
				return $cardNum->Value;
			} else {
				return '';
			}
		}

		function SetNumber($num){
			$num = str_replace(array(' ', "\t"), '', $num);
			$length = strlen($num);
			$num = substr($num, -4);
			$num = str_pad($num, $length, '*', STR_PAD_LEFT);
			$cardNum = new Cipher($num);
			$cardNum->Encrypt();
			$this->Number = $cardNum->Value;
			return true;
		}

		function PrivateNumber(){
			if(!empty($this->Number)){
				return $this->GetNumber();
			} else {
				return '';
			}
		}
	}
?>