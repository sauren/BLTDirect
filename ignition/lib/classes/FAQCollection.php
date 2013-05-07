<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/FAQ.php");

	class FAQCollection{
		var $Item;
		
		function FAQCollection(){
			$this->Item = array();
		}
		
		function Get(){
			$sql = "select * from faq order by Question asc";
			$data = new DataQuery($sql);
			while($data->Row){
				$faq = new FAQ;
				$faq->Question = $data->Row['Question'];
				$faq->Answer = $data->Row['Answer'];
				$this->Item[] = $faq;
				$data->Next();
			}
			$data->Disconnect();
		}
	}
?>
