<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/IFile.php");

	class Image extends IFile{
		var $MinWidth;
		var $MinHeight;
		var $MaxWidth;
		var $MaxHeight;
		var $NewWidth;
		var $NewHeight;
		var $ServerComponent;
		var $AspectRatio;
		var $OriginalFileName;
		var $Quality;

		// Image Attr
		var $Width;
		var $Height;
		var $Type;
		var $Format;
		var $Attributes;
		var $ImageTypes = array(1 => 'GIF', 2 => 'JPG', 3 => 'PNG', 4 => 'SWF', 5 => 'PSD', 6 => 'BMP', 7 => 'TIFF (intel byte order)', 8 => 'TIFF (motorola byte order)', 9 => 'JPC', 10 => 'JP2', 11 => 'JPX', 12 => 'JB2', 13 => 'SWC (SWF-Compressed)', 14 => 'IFF', 15 => 'WBMP', 16 => 'XBM');

		function Image($fileName=NULL, $dir=NULL){
			// Set Defaults
			$this->AspectRatio = true;
			$this->Quality = 70;
			$this->ServerComponent = $GLOBALS['SERVER_IMG_PROCESSOR'];
			$this->MaxWidth = 100000;
			$this->MaxHeight = 100000;
			parent::IFile($fileName, $dir);
		}

		function Resize(){
			switch ($this->ServerComponent) {
				case 'GD':
					return $this->ResizeUsingGD();
					break;
				case 'GD2':
					return $this->ResizeUsingGD();
					break;
				case 'NetPBM':
					return $this->ResizeUsingNetPBM();
					break;
				default:
					$this->AddImageError("The Server Component selected is not valid.");
					return false;
					break;
			}
		}

		function AddToGallery(){
		}

		function CheckDimensions(){
			$this->Debugger("Checking Image Dimension");
			// Get the imageSize
			if ($this->GetDimensions()) {
				// Check if it's too small
				if ((!empty($this->MinWidth) && $this->Width < $this->MinWidth) || (!empty($this->MinHeight) && $this->Height < $this->MinHeight)) {
					//$this->AddImageError('tooSmall', $file->fileName);
					return false;
				}
				// Check if it's too big
				if ((!empty($this->MaxWidth) && $this->Width > $this->MaxWidth) || (!empty($this->MaxHeight) && $this->Height > $this->MaxHeight)) {
					//$this->AddImageError('tooBig', $file->fileName);
					return false;
				}
			}
			$this->GetFormat();
			return true;
		}

		function AddImageError($error){
			$str = "";
			switch(strtolower($error)){
				case 'toosmall':
					$str = sprintf("Image size error.<br>The image &quot;%s&quot; is too small. Images must be a minimum of %s x %s pixels in size.", $this->FileName, $this->MinWidth, $this->MinHeight);
					$this->ForceUploadFailure();
					break;
				case 'toobig':
					$str = sprintf("Image size error.<br>The image &quot;%s&quot; is too big. Images must be smaller than %s x %s pixels in size.", $this->FileName, $this->MaxWidth, $this->MaxHeight);
					$this->ForceUploadFailure();
					break;
				case 'gdinstall':
					$str = "Image processor error.<br>The image GD image processor library could not be loaded.";
					$this->ForceUploadFailure();
					break;
				default:
					$str = $error;
					break;
			}
			$this->AddError($str);
			return true;
		}

		function SetDimensions($width, $height){
			$this->Width = $width;
			$this->Height = $height;
			$this->Attributes = sprintf("width=\"%s\" height=\"%s\"", $this->Width, $this->Height);
		}

		function GetDimensions(){
			if (!$this->FileName || !file_exists($this->Directory.$this->FileName)) {
				return false;
			}
			return (list($this->Width, $this->Height, $this->Type, $this->Attributes) = getimagesize($this->Directory.$this->FileName))?true:false;
		}

		function SetMinDimensions($width, $height){
			$this->MinWidth = $width;
			$this->MinHeight = $height;
		}

		function SetMaxDimensions($width, $height){
			$this->MaxWidth = $width;
			$this->MaxHeight = $height;
		}

		function GetFormat($int=NULL){
			return $this->Format = (!is_null($int))?$this->ImageTypes[$int]:$this->ImageTypes[$this->Type];
		}

		function CalculateDimensions($imgWidth=NULL, $imgHeight=NULL) {
			if(is_null($imgWidth) || is_null($imgHeight)){
				$this->GetDimensions();
				$imgWidth = $this->Width;
				$imgHeight = $this->Height;
			}
			$this->AspectRatio = (is_null($this->AspectRatio))?false:true;

			if (($this->MaxWidth < $imgWidth || $this->MaxHeight < $imgHeight) && $this->AspectRatio) {
				if ($this->MaxWidth >= $this->MaxHeight) {
					$this->NewWidth = round($this->MaxHeight*($imgWidth/$imgHeight), 0);
					$this->NewHeight = round($this->MaxHeight, 0);
				} else {
					$this->NewWidth = round($this->MaxWidth, 0);
					$this->NewHeight = round($this->MaxWidth*($imgHeight/$imgWidth), 0);
				}
				if ($this->NewWidth > $this->MaxWidth) {
					$this->NewWidth = round($this->MaxWidth, 0);
					$this->NewHeight = round($this->MaxWidth*($imgHeight/$imgWidth), 0);
				}
				if ($this->NewHeight > $this->MaxHeight) {
					$this->NewWidth = round($this->MaxHeight*($imgWidth/$imgHeight), 0);
					$this->NewHeight = round($this->MaxHeight, 0);
				}
			} else {
				if ($this->AspectRatio) {
					$this->NewWidth = round($imgWidth, 0);
					$this->NewHeight = round($imgHeight, 0);
				} else {
					$this->NewWidth = round($this->MaxWidth, 0);
					$this->NewHeight = round($this->MaxHeight, 0);
				}
			}
		}

		function ResizeUsingGD(){
			$this->Debugger("Resizing using the GD library");
			if (!extension_loaded('gd')) {
				if (!dl('gd.so')) {
					$this->Debugger("Could not load the GD library.");
					$this->AddImageError('gdinstall');
					return false;
				}
			}
			if (!empty($this->FileName)){
				$this->OriginalFileName = $this->FileName;
				$this->Debugger("Resize Required");
				$this->CalculateDimensions($this->Width, $this->Height);
				$this->GDResize();
				$this->Debugger("Reset Dimensions");
				$this->SetDimensions($this->NewWidth, $this->NewHeight);
			}
			return true;
		}

		function ResizeUsingNetPBM(){
			if ($this->FileName != "") {
				$this->OriginalFileName = $this->FileName;
				$this->CalculateDimensions($this->Width, $this->Height);
				$this->NetPBMResize();
				$this->SetDimensions($this->NewWidth, $this->NewHeight);
			}
			return true;
		}

		function GDResize() {
			// Get funs array for GD/GD2 library
			$gdFuncs = get_extension_funcs("gd");
			$this->Debugger("Get Image Dimensions");
			$this->GetDimensions();
			switch ($this->Type) {
				case 1:
				  // GD Library 1.6
				  if (!array_search("imagecreatefromgif", $gdFuncs)) {
						$this->Debugger("imagecreatefromgif function is not supported");
						$this->AddImageError("imagecreatefromgif function is not supported");
						return false;
				  }
				  $oldImg = @imagecreatefromgif($this->Directory.$this->FileName);
				  break;
				case 2:
				  if (!array_search("imagecreatefromjpeg", $gdFuncs)) {
						$this->Debugger("imagecreatefromjpeg function is not supported");
						$this->AddImageError("imagecreatefromjpeg function is not supported");
						return false;
					}
				  $oldImg = @imagecreatefromjpeg($this->Directory.$this->FileName);
				  break;
				case 3:
				  if (!array_search("imagecreatefrompng", $gdfuncs)) {
						$this->Debugger("imagecreatefrompng function is not supported");
						$this->AddImageError('gdinvalid', 'png');
						return false;
					}
				  $oldImg = @imagecreatefrompng($this->Directory.$this->FileName);
				  break;
				default:
				  $this->Debugger("not a valid imagetype");
				  $this->AddImageError("not a valid imagetype");
				  return false;
				  break;
			}
			$gdInfo = (!function_exists("gd_info"))?$this->gd_info():gd_info();

		    if (array_search("imagecreatetruecolor", $gdFuncs) && array_search("imagecopyresampled", $gdFuncs) && (!stristr($gdInfo["GD Version"],"1.") || $this->ServerComponent == "GD2")) {
				// Requires GD 2.0.1 or higher
				if ($newImg = @imagecreatetruecolor($this->NewWidth, $this->NewHeight)) {

					$trnprt_indx = imagecolortransparent($oldImg);

            		// If we have a specific transparent color
		            if ($trnprt_indx >= 0) {

		                // Get the original image's transparent color's RGB values
		                $trnprt_color = @imagecolorsforindex($oldImg, $trnprt_indx);

		                // Allocate the same color in the new image resource
		                $trnprt_indx = @imagecolorallocate($newImg, 255, 255, 255);
		                //$trnprt_indx = @imagecolorallocate($newImg, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);

		                // Completely fill the background of the new image with allocated color.
		                @imagefill($newImg, 0, 0, $trnprt_indx);

		                // Set the background color for new image to transparent
		                @imagecolortransparent($newImg, $trnprt_indx);
		            }

					@imagecopyresampled($newImg, $oldImg, 0, 0, 0, 0, $this->NewWidth, $this->NewHeight, $this->Width, $this->Height);
				}
			}

			if (!$newImg) {
				$newImg = @imagecreate($this->NewWidth,$this->NewHeight);
				@imagecopyresized($newImg, $oldImg, 0, 0, 0, 0, $this->NewWidth, $this->NewHeight, $this->Width, $this->Height);
			}

			// CreateUniqueName if required
			if (file_exists($this->Directory.$this->Name.".jpg") && ($this->Name.".jpg" != $this->FileName) && (strtolower($this->OnConflict) == "makeunique")) {
				$this->CreateUniqueName($this->Name.".jpg");
			}

			// Write new image
			$fileName = $this->Name.".jpg";
			@unlink($this->Directory.$this->OriginalFileName);
			@imagejpeg($newImg, $this->Directory.$fileName, $this->Quality);
			$this->SetName($fileName);

			@imagedestroy($oldImg);
			@imagedestroy($newImg);
			return true;
		}

		function gd_info() {
			$gdInfoArray = Array(
				"GD Version" => "",
				"JPG Support" => 0,
				"GIF Read Support" => 0,
				"GIF Create Support" => 0,
				"PNG Support" => 0,
				"WBMP Support" => 0,
				"XBM Support" => 0,
				"FreeType Support" => 0,
				"FreeType Support" => 0,
				"FreeType Linkage" => "",
				"T1Lib Support" => 0
			);
			$gifSupport = 0;
			// turn on output buffering
			ob_start();
			eval("phpinfo();");
			// store buffer content info
			$bufferInfo = ob_get_contents();
			// discard of buffer contents
			ob_end_clean();

			foreach(explode("\n", $bufferInfo) as $line) {
				// Get GD Version
				if(strpos($line, "GD Version")!==false) {
					$gdInfoArray["GD Version"] = trim(str_replace("GD Version", "", strip_tags($line)));
				} else {
					$gdInfoArray["GD Version"] = "Unknown, probably 1.x.x";
				}

				// Get JPEG Support Info
				if(strpos($line, "JPG Support") !== false) $gdInfoArray["JPG Support"] = (trim(str_replace("JPG Support", "", strip_tags($line))) === "enabled")?1:0;

				// Get GIF Support Info
				if(strpos($line, "GIF Support") !== false) {
					$gifSupport = trim(str_replace("GIF Support", "", strip_tags($line)));
					if ($gifSupport==="enabled") {
						$gdInfoArray["GIF Read Support"]   = 1;
						$gdInfoArray["GIF Create Support"] = 1;
					}
				}
				if(strpos($line, "GIF Read Support") !== false) $gdInfoArray["GIF Read Support"] = (trim(str_replace("GIF Read Support", "", strip_tags($line))) === "enabled")?1:0;
				if(strpos($line, "GIF Create Support") !== false) $gdInfoArray["GIF Create Support"] = (trim(str_replace("GIF Create Support", "", strip_tags($line))) === "enabled")?1:0;

				// Get PNG Support
				if(strpos($line, "PNG Support") !== false) $gdInfoArray["PNG Support"] = (trim(str_replace("PNG Support", "", strip_tags($line))) === "enabled")?1:0;

				// Get WBMP Support
				if(strpos($line, "WBMP Support") !== false) $gdInfoArray["WBMP Support"] = (trim(str_replace("WBMP Support", "", strip_tags($line))) === "enabled")?1:0;

				// Get XBM Support
				if(strpos($line, "XBM Support") !== false) $gdInfoArray["XBM Support"] = (trim(str_replace("XBM Support", "", strip_tags($line))) === "enabled")?1:0;

				// Get FreeType Support
				if(strpos($line, "FreeType Support") !== false) $gdInfoArray["FreeType Support"] = (trim(str_replace("FreeType Support", "", strip_tags($line))) === "enabled")?1:0;
				if(strpos($line, "FreeType Linkage")!== false) $gdInfoArray["FreeType Linkage"] = trim(str_replace("FreeType Linkage", "", strip_tags($line)));

				// Get T1Lib Support
				if(strpos($line, "T1Lib Support") !== false) $gdInfoArray["T1Lib Support"] = (trim(str_replace("T1Lib Support", "", strip_tags($line))) === "enabled")?1:0;
			}
			return $gdInfoArray;
		}

		function NetPBMResize() {
			$tmpFile = tempnam($this->Directory, "sip");
			$this->GetDimensions();
			switch ($this->Type) {
			  case 1:
				  // GIF
					if (!file_exists(dirname(__FILE__)."/giftopnm")) {
						$this->Debugger("giftopnm is not found");
						$this->AddImageError('giftopnm is not found');
						return false;
					} else {
						system(dirname(__FILE__)."/giftopnm ".$this->Directory.$this->OriginalFileName.">".$tmpFile);
					}
				  break;
				case 2:
				  // JPEG
					if (!file_exists(dirname(__FILE__)."/jpegtopnm")) {
						$this->Debugger("jpegtopnm is not found");
						$this->AddImageError('jpegtopnm is not found');
						return false;
					} else {
						system(dirname(__FILE__)."/jpegtopnm ".$this->Directory.$this->OriginalFileName.">".$tmpFile);
					}
				  break;
				case 3:
					// PNG
					if (!file_exists(dirname(__FILE__)."/pngtopnm")) {
						$this->Debugger("pngtopnm is not found");
						$this->AddImageError('pngtopnm is not found');
						return false;
					} else {
						system(dirname(__FILE__)."/pngtopnm ".$this->Directory.$this->OriginalFileName.">".$tmpFile);
					}
					break;
				default:
					$this->Debugger("not a valid imagetype");
				    $this->AddImageError('not a valid imagetype');
					return false;
				    break;
			}
			// CreateUniqueName if required
			if (file_exists($this->Directory.$this->Name.".jpg") && ($this->Name.".jpg" != $this->FileName) && ($this->OnConflict == "makeunique")) {
				$this->SetName($this->CreateUniqueName($this->Name.".jpg"));
			}
			$fileName = $this->Name.".jpg";
			unlink($this->Directory.$this->OriginalFileName);
			system(dirname(__FILE__)."/pnmscale -xy ".$this->NewWidth." ".$this->NewHeight." ".$tmpFile." | ".dirname(__FILE__)."/ppmtojpeg -qual ".$this->Quality." >".$this->Directory.$fileName);
			$this->SetName($fileName);
			unlink($tmpFile);
			return true;
		}
	}
?>