<?php
class Page{
	var $UseTemplate;
	var $Template;
	var $Title;
	var $Head;
	var $Body;
	var $OnLoad;
	var $Description;
	var $DisplayTitle;
	var $Editor;
	var $EditorMode;
	var $ShowDocType;

	function Page($title = null, $description = null){
		$this->ShowDocType = false;
		$this->Title = $title;
		$this->Description = $description;
		$this->Editor = false;
		$this->EditorMode = 'advanced';
		$this->UseTemplate = false;
		$this->Template = 'i_content_display.tpl';
		$this->Head = '';
		$this->Body = '';
		$this->OnLoad = '';
		$this->DisplayTitle = true;
	}

	function DisableTitle(){
		$this->DisplayTitle = false;
	}

	function SetTemplate($file){
		$this->Template = $file;
		$this->UseTemplate = true;
	}

	function LinkScript($file, $language="JavaScript", $type="text/javascript"){
		$this->Head .= sprintf("<script language=\"%s\" src=\"%s\" type=\"%s\"></script>\n", $language, $file, $type);
	}

	function LinkCSS($href, $rel="stylesheet", $type="text/css"){
		$this->Head .= sprintf("<link href=\"%s\" rel=\"%s\" type=\"%s\" />\n", $href, $rel, $type);
	}

	function AddToHead($content){
		$this->Head .= $content;
		$this->Head .= "\n";
	}

	function AddToBody($content){
		$this->Body .= $content;
		$this->Body .= "\n";
	}

	function AddOnLoad($content){
		$this->OnLoad .= $content . "; ";
	}

	function AddDocType(){
		$this->ShowDocType = true;
	}

	function SetFocus($elementId){
		$this->AddOnLoad(sprintf("document.getElementById('%s').focus()", $elementId));
	}

	function SetEditor($boolean){
		$this->Editor = $boolean;
	}

	function SetEditorMode($mode){
		$this->EditorMode = $mode;
	}

	function Display($section = NULL){
		if($section == NULL || ($this->UseTemplate)){
			$tempHTML = file($GLOBALS['DIR_WS_ADMIN']. 'lib/templates/' . $this->Template);
			$patterns = array("/\[TITLE\]/",
							  "/\[HEAD\]/",
							  "/\[BODY\]/",
							  "/\[ONLOAD\]/",
							  "/\[DESCRIPTION\]/");

			$replacements = array($this->Title,
								  $this->Head,
								  $this->Body,
								  $this->OnLoad,
								  $this->Description);

			foreach($tempHTML as $string){
				echo preg_replace($patterns, $replacements, $string);
			}
		} elseif(strtolower($section) == 'header'){
			if($this->OnLoad != '') $this->OnLoad = sprintf(" onLoad=\"%s\"", $this->OnLoad);

			header("Content-Type: text/html; charset=UTF-8");

		if($this->ShowDocType) {
			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		}

		echo '<html>';
		echo '<head>';
		echo '<title>Ignition - ' . $this->Title . ' </title>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		echo '<link rel="stylesheet" href="css/i_import.css" type="text/css" />';
		
		if(DEPLOYMENT != "bltdirect.com") {
			echo '<link href="css/i_test.css" rel="stylesheet" type="text/css" />';
		}
		
		echo '<link rel="stylesheet" href="css/Print.css" type="text/css" media="print" />';
		echo '<link rel="shortcut icon" href="favicon.ico" />';
		echo '<script src="js/generic_1.js" language="javascript"></script>';
		echo '<script src="js/HttpRequest.js" language="javascript"></script>';
		echo '<script src="js/HttpRequestData.js" language="javascript"></script>';
		echo '<script src="js/tiny_mce/tiny_mce.js" language="javascript"></script>';

		if($this->Editor) {
			?>
			<script language="javascript" type="text/javascript">
				var stored_field_name = null;
				var stored_win = null;
				var root = '<?php print $GLOBALS['HTTP_SERVER']; ?>';
				var host = root + 'ignition/';
				var media = root + 'library/media/';
				var temp = 'ignition/temp/';

				<?php
				if($this->EditorMode == 'advanced') {
					?>
					tinyMCE.init({
						mode : "textareas",
						theme : "advanced",
						skin : "o2k7",
						skin_variant : "silver",
						plugins : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,wordcount,advlist,autosave",
						theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
						theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
						theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
						theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak,restoredraft",
						extended_valid_elements : "iframe[src|width|height|name|align]",
						theme_advanced_toolbar_location : "top",
						theme_advanced_toolbar_align : "left",
						theme_advanced_statusbar_location : "bottom",
						theme_advanced_resizing : true,
						theme_advanced_resize_horizontal : false,
					    plugin_insertdate_dateFormat : "%d/%m/%Y",
					    plugin_insertdate_timeFormat : "%H:%m:%s",
						file_browser_callback : "fileBrowserCallBack",
						paste_auto_cleanup_on_paste : true,
						paste_strip_class_attributes : "all",
						relative_urls : false,
						remove_script_host : false,
						convert_urls : false,
						content_css : "css/tinymce_content.css"
					});
					<?php
				} elseif($this->EditorMode == 'simple') {
					?>
					tinyMCE.init({
						mode : "textareas",
						theme : "simple",
						relative_urls : false,
						remove_script_host : false,
						convert_urls : false,
						content_css : "css/tinymce_content.css"
					});
					<?php
				}
				?>

				function fileBrowserCallBack(field_name, url, type, win) {
					stored_field_name = field_name;
					stored_win = win;

					if(type == 'image') {
						popUrl(host + 'popFindMedia.php?callback=foundMedia', 650, 500);
					} else if(type == 'file') {
						popUrl(host + 'popFindFile.php?callback=foundFile', 650, 500);
					}
				}

				function foundMedia(id, title, src) {
					insertURL(media + src);
				}

				function foundFile(id, title, src) {
					insertURL(temp + src);
				}

				function insertURL(url) {
					stored_win.document.forms[0].elements[stored_field_name].value = url;
				}
			</script>
			<?php
		}

		echo $this->Head;
		echo '</head>';
		echo '<body ' . $this->OnLoad . '>';

		if($this->Title != NULL && ($this->DisplayTitle)){
			echo sprintf('<span class="pageTitle">%s</span><br>', $this->Title);

			if($this->Description != NULL){
				echo sprintf('<span class="pageDescription">%s</span><br>',  $this->Description);
				}
			}

			echo "<br />";

		} elseif(strtolower($section) == 'footer'){
			echo "</body></html>";
		}
	}
}
?>