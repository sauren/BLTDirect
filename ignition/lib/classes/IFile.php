<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/IDirectory.php");

	class IFile{
		var $Name;
		var $FileName;
		var $Directory;
		var $Path;
		var $OnConflict;
		var $Extension;
		var $Size;
		var $Debug = false;

		// Upload Specific Variables
		var $Extensions;
		var $Timeout;
		var $SizeLimit;

		// Error Handling
		var $Errors;
		var $NumErrors;
		var $Content;

		function IFile($fileName=NULL, $dir=NULL){
			if(!is_null($fileName)){
				$this->SetName($fileName);
			}
			$this->SetDirectory($dir);
			// Set Defaults
			$this->Timeout = 1800;
			$this->Extensions = "jpg,gif,jpeg";
			$this->SizeLimit = 20000;
			// Set Error Handlers
			$this->OnConflict = "error";
			$this->Errors = array();
			$this->NumErrors =0;
		}

		function Create($content){
			$this->Debugger("Creating a New File.");

			// check whether we can write to location
			$dir = new IDirectory($this->Directory);
			if(!$dir->Exists()){
				if(!$dir->Create()){
					$this->Debugger("Could not create directory.");
					return false;
				}
			}
			$filename = $this->Directory . $this->FileName;
			// now create the file in the directory

			if(!$handle = fopen($filename, 'wb')){
				$this->Debugger("can't open file for writing");
				return false;
			}
			if (fwrite($handle, $content) === FALSE) {
				$this->Debugger("could not write to file");
				return false;
			   }
			fclose($handle);
			return true;
		}

		function CreateUniqueName($tempName=NULL){
			$this->Debugger("Creating a new unique name for file.");
			$i = 0;
			$name = (is_null($tempName))?$this->Name:substr($tempName, 0, strrpos($tempName, '.'));
			$extension = (is_null($tempName))?$this->Extension:substr($tempName, strrpos($tempName, '.')+1);
			
			while (++$i) {
				// Check if file does not exist
				if (!file_exists($this->Directory.$name.'_'.$i.'.'.$extension)) {
					// Set the unique FileName
					$tempStr = sprintf("%s_%s.%s", $name, $i, $extension);
					$this->SetName($tempStr);

					// return true to break the while statement
					return true;
				}
			}
		}

		function Copy($newDirectory=NULL, $newName=NULL){
			$this->Debugger("Copying original file to a new directory and/or file name");
			$tempName = $this->FileName;
			$tempDir = $this->Directory;

			if(!is_null($newName)) $tempName = $newName;
			if(!is_null($newDirectory)) $tempDir = $newDirectory;

			$newFile = $tempDir . $tempName;
			$oldFile = $this->Directory . $this->FileName;
			return @copy($oldFile, $newFile);
		}

		function Upload($field){
			$this->Debugger("IFile Upload Initiated.");
			// Set the timeout limit for script execution
			@set_time_limit($this->Timeout);
			//$this->Path = sprintf("/%s%s/", substr($_SERVER['PHP_SELF'], 1, strrpos($_SERVER['PHP_SELF'], '/')), $this->Directory);
			// Correction made to path
			//$this->Path = str_replace("./", "", $this->Path);
			$this->Path = $this->Directory;
			$this->Path = str_replace("./", "", $this->Path);
			$this->Path = str_replace("//", "", $this->Path);
			$this->Debugger(sprintf("Created a new Path for the file: %s", $this->Path));
			// Check if the Directory Exists
			// If not create one
			$dir = new IDirectory($this->Directory);
			if(!$dir->Exists()){
				$this->Debugger("Directory did not exist. Attempting to create the directory.");
				if(!$dir->Create()){
					$this->Debugger("Could not create the directory.");
					$this->AddError('directorypermission', __LINE__);
					return false;
				}
			}

			$this->SetName($_FILES[$field]['name']);
			$this->Debugger(sprintf("Set the name, filename and extension. FileName=%s, Name=%s, Extension=%s", $this->FileName, $this->Name, $this->Extension));
			$this->SetSize($_FILES[$field]['size']);
			$this->Debugger(sprintf("Set the file size: Size=%s", $this->Size));
			$this->CleanName();

			// Check filesize if limit is given
			$this->Debugger("Checking File Size");
			if (!empty($this->SizeLimit) && !$this->CheckSize()) {
				$this->Debugger(sprintf("Size limit was exceeded. SizeLimit=%s, FileSize=%s", $this->SizeLimit, $this->Size));
				return false;
			}

			// Check the filename extension
			$this->Debugger("Checking Extensions");
			if (!empty($this->Extensions) && !$this->CheckExtensions()) {
				return false;
			}

			// Check if file is uploaded correctly
			$this->Debugger("Checking File Upload");
			if (is_uploaded_file($_FILES[$field]['tmp_name'])) {
				// Check if filename exists
				if (file_exists($this->Directory.$this->FileName)) {
					// What to do if filename exists
					switch (strtolower($this->OnConflict)) {
						// Overwrite the file
						case 'overwrite':
							// Overwrite the existing file
							return $this->Move($this->Directory.$this->FileName, $_FILES[$field]['tmp_name']);
							break;
						// Report error
						case 'error':
							$this->AddError('conflict', __LINE__);
							return false;
							break;
						// Make the file name unique
						case 'makeunique':
							$this->CreateUniqueName();
							return $this->Move($this->Directory.$this->FileName, $_FILES[$field]['tmp_name']);
							break;
					}
				} else {
					// If filename does not exist
					return $this->Move($this->Directory.$this->FileName, $_FILES[$field]['tmp_name']);
				}
			} elseif (!empty($this->FileName)) {
				// Size is 0 or FileName has not uploaded correctly
				$this->AddError('empty', __LINE__);
				return false;
			}
		}

		function Delete(){
			return @unlink($this->Directory.$this->FileName);
		}

		function Move($new, $old=NULL){
			$oldPath = (!is_null($old))?$old:$this->Directory.$this->FileName;
			$newPath = $new;
			if (is_writeable($this->Directory) && move_uploaded_file($oldPath, $newPath)) {
				chmod($newPath, 0644);
				return true;
			} else {
				$this->AddError('directorypermission', __LINE__);
				return false;
			}
		}

		function Rename($newFileName){
			if (!empty($this->FileName)) {
				$rename = $newFileName;

				// Check if filename exists
				if (file_exists($this->Directory.$rename)) {
					// What to do if filename exists
					switch (strtolower($this->OnConflict)) {
						// Overwrite the file
						case 'overwrite':
							unlink($this->Directory.$rename);
							if (!rename($this->Directory.$this->FileName, $this->Directory.$rename)) {
								$this->AddError('Unable to rename file using mask', __LINE__);
								return false;
							}
							break;
						// Give error message
						case 'error':
							$this->AddError('The File already exists. Error whilst renaming.', __LINE__);
							return false;
							break;
						// Skip renaming and delete the uploaded file
						case 'skip':
							unlink($this->Directory.$this->FileName);
							break;
						// Make an unique name
						case 'makeunique':
							$oldFile = $this->FileName;
							
							$this->CreateUniqueName($rename);
							
							$rename = $this->FileName;
							$this->FileName = $oldFile;

							if (!rename($this->Directory.$this->FileName, $this->Directory.$rename)) {
								$this->AddError('The file could not be renamed to a unique name.', __LINE__);
								return false;
							}
							break;
					}
				} else {
					// If filename does not exist
					if (!rename($this->Directory.$this->FileName, $this->Directory.$rename)) {
						$this->AddError('The file did not exist but could not be renamed', __LINE__);
						return false;
					}
				}
				// Update the name in the fileinfo
				$this->SetName($rename);
				return true;
			}
		}

		function Exists(){
			return file_exists($this->Directory . $this->FileName);
		}

		function CleanName(){
			$this->FileName = substr($this->FileName, strrpos($this->FileName, ':'));
			$this->FileName = preg_replace("/\s+|;|\+|=|\[|\]|'|,|\\|\"|\*|<|>|\/|\?|\:|\|/i", "_", $this->FileName);
			$this->Name = substr($this->Name, strrpos($this->Name, ':'));
			$this->Name = preg_replace("/\s+|;|\+|=|\[|\]|'|,|\\|\"|\*|<|>|\/|\?|\:|\|/i", "_", $this->Name);
		}

		function SetName($newName){
			$this->FileName = $newName;
			$this->Name = substr($newName, 0, strrpos($newName, '.'));
			$this->Extension = substr($newName, strrpos($newName, '.')+1);
			$this->Debugger(sprintf("Setting new file name to FileName=%s, Name=%s, Extension=%s", $newName, $this->Name, $this->Extension));
		}

		function SetSize($size = NULL){
			if (is_null($size)) {
				if (file_exists($this->Directory . $this->FileName)) {
					$this->Size = round((filesize($this->Directory.$this->FileName)/1024), 0);
				}
			} else {
				$this->Size = round(($size/1024), 0);
			}
		}

		function CheckSize(){
			if ($this->SizeLimit < $this->Size) {
				$this->AddError('size', __LINE__);
				return false;
			} else {
				return true;
			}
		}

		function CheckExtensions(){
			$allow = false;

			// Loop through each extension
			foreach (explode(',', $this->Extensions) as $extension) {
				// is it allowed
				if (strtoupper($this->Extension) == strtoupper($extension)) {
					$allow = true;
				}
			}

			// Give error when not allowed
			if (!$allow && !empty($this->FileName)) {
				$this->AddError('extension', __LINE__);
				$this->Debugger("The Extension was not allowed.");
				return false;
			} elseif($allow){
				$this->Debugger("The Extension was allowed.");
				return true;
			}
		}

		function ForceUploadFailure($line=NULL){
			$this->Debugger("The Upload was forced to Fail at Line " . $line);
			return $this->Delete();
		}

		function AddError($error, $line=NULL){
			$str = "";
			switch(strtolower($error)){
				case 'filepermission':
					$str = sprintf("Not enough permissions. <br>The file &quot;%s&quot; could not be created. Please check your directory permissions or contact your administrator.", $this->FileName);
					$this->ForceUploadFailure($line);
					break;
				case 'directorypermission':
					$str = sprintf("Not enough permissions.<br>The directory &quot;%s&quot; could not be created. Please check your directory permissions or contact your administrator.", $this->Directory);
					$this->ForceUploadFailure($line);
					break;
				case 'size':
					$str = sprintf("File size exceeds limit!<br>The file &quot;%s&quot; exceeds the limit of %sKb.", $this->FileName, $this->SizeLimit);
					$this->ForceUploadFailure($line);
					break;
				case 'extension':
					$str = sprintf("File extension not permitted.<br>The file &quot;%s&quot; does not have an extension matching those allowed (%s).", $this->FileName, $this->Extensions);
					$this->ForceUploadFailure($line);
					break;
				case 'empty':
					$str = sprintf("An error has occured whilst saving the uploaded file! &quot;%s&quot; did not upload correctly or is empty.", $this->FileName);
					$this->ForceUploadFailure($line);
					break;
				case 'conflict':
					$str = sprintf("A conflict occured! &quot;%s&quot; already exists.", $this->FileName);
					break;
				default:
					$str = $error;
					break;
			}
			array_push($this->Errors, sprintf("<strong>File %s Error %d</strong>: %s", $this->FileName, ++$this->NumErrors, $str));
			return true;
		}

		function GetError(){
			return $this->Errors;
		}

		function Debugger($str){
			if($this->Debug) echo $str ."<br><br>";
		}

		// To Do
		// Required by Copy
		function ConflictHandler(){
		}

		function SetDirectory($dir=NULL){
			$this->Directory = (is_null($dir))?'./':$dir;
			$len = strlen($this->Directory);
			if($this->Directory[$len-1] != "/") $this->Directory .= "/";
		}

		function Read(){
			$string = '';
			$file = $this->Directory . $this->FileName;
			$lines = file($file);
			foreach ($lines as $line_num => $line) {
			   $string .= $line;
			}
			return $string;
		}
	}
?>