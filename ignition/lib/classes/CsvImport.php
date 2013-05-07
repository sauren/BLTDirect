<?php
	/*
		Class:		CsvImport.php
		Version:	1.0
		Product:	Ignition
		Author:		Geoff Willings
		
		Copyright (c) Deveus Software, 2004
		
		Notes:
		(*)	Created 07 Feb 2004
		
		TODO:
		(*) Detect No, Full and Partial Translations
	*/
	class CsvImport{
		var $FileName;
		var $Handle;
		var $Data;
		var $TotalRows;
		
		var $HasFieldNames;
		var $FieldNames;
		var $Delimeter;
		var $Enclose;
		
		var $Lines;
		var $Length;
		
		var $Timeout = 300;
		
		function CsvImport($fileName=NULL, $delimit=',', $encl='"'){
			if(!is_null($fileName)) $this->FileName = $fileName;
			$this->HasFieldNames = false;
			$this->Length = 1000;
			$this->Delimiter = $delimit;
			$this->Enclose = $encl;
			$this->FieldNames = array();
		}
		
		function Open($fileName=NULL){
			// Check if a filename was supplied
			if(!is_null($fileName)) $this->FileName = $fileName;
			
			if($this->Handle = @fopen($this->FileName, "r")){
				$this->PreProcess();
				return true;
			} 
			return false;
		}
		
		function Close(){
			return @fclose($this->Handle);
		}
		
		function SetFieldNames($arr){
			for($i=0; $i < count($arr); $i++){
				if($this->HasFieldNames){
					$this->FieldNames[] = $arr[$i];
				} else {
					$this->FieldNames[] = "Field ". $i;
				}
			}
		}
		
		function PreProcess(){
			// We need the preprocess to determine certain aspects of the CSV we are parsing.
			// Get Line Length
			$length = 1000;
		    $array = file($this->FileName);
			$this->TotalRows = count($array);
		    for($i=0;$i<$this->TotalRows;$i++)
		    {
			   if ($length < strlen($array[$i])) $length = strlen($array[$i]);
		    }
			// Free the memory
		    unset($array);
			$this->Length = $length;
			
			$this->Next();
			$this->SetFieldNames($this->Data);
			if($this->HasFieldNames) $this->Next();
		}
		
		function Next(){
			$this->Data = fgetcsv($this->Handle, $this->Length, $this->Delimiter, $this->Enclose);
			return true;
		}
	}
?>