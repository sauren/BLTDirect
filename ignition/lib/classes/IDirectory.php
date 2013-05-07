<?php
	class IDirectory{
		var $Name;
		var $Path;
		var $Permissions;
		var $Errors;
		
		function IDirectory($dir){
			$this->Name = $dir;
		}
		
		function Create(){
			$dirs = explode('/', $this->Name);
			$tempDir = $dirs[0];
			$check = false;
			
			for ($i = 1; $i < count($dirs); $i++) {
				if (is_writeable($tempDir)) {
					$check = true;
				} else {
					$error = $tempDir;
					return false;
				}
				
				$tempDir .= '/'.$dirs[$i];
				// Check if directory exist
				if (!is_dir($tempDir)) {
					if ($check) {
						// Create directory
						if(!mkdir($tempDir, 0777)) return false;
						if(!chmod($tempDir, 0777)) return false;
						$d = dir($tempDir);
						$d->close();
					} else {
						// Not enough permissions
						return false;
					}
				}
			}
			return true;
		}
		
		function Chmod($permission){
			if($this->Exists() && is_writeable($this->Name)){
				return chmod($this->Name, $permission);
			} else {
				return false;
			}
		}
		
		function Delete(){
		}
		
		function Rename(){
		}
		
		function Move(){
		}
		
		function Exists(){
			if (!is_dir($this->Name)) {
				return false;
			} else {
				return true;
			}
		}
		
		function Error(){
		}
	}
?>