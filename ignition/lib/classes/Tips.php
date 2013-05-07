<?php
	/*
		Class:		Tips.php
		Version:	1.0
		Product:	Ignition
		Authoer:	Geoff Willings
		
		Copyright (c) Deveus Software, 2004
		
		Notes:
	*/
	class Tips{
		var $Reference;
		var $Icon;
		var $Alt = 'Show hint for this item in the Tips Window.';
		var $Link = '<a href="%s?action=tip&id=%s" target="i_hlp_content">%s</a>';
		
		function Tips($ref='tips.php', $icon='icon_help_1.gif', $width=16, $height=16){
			$this->Reference = $ref;
			$this->Icon = sprintf("<img src=\"%simages/%s\" alt=\"%s\" width=\"%d\" height=\"%d\" align=\"absmiddle\" border=\"0\" class=\"tipIcon\">",
								$GLOBALS['IGNITION_ROOT'],
								$icon,
								$this->Alt,
								$width,
								$height);
		}
		
		function GetHTML($id){
			$tempHTML = sprintf($this->Link,
								$this->Reference,
								$id,
								$this->Icon);
			return $tempHTML;
		}
		
		function Display($id){
			$tempTitle = "Sorry..";
			$tempDescription = "the Tips Window mechanism is under development. <br><br> Ignition's rapid prototyping environment will allow developer support for the Tips mechanism upon release.";
			$page = new Page($tempTitle, $tempDescription);
			$page->Display('header');
			$page->Display('footer');
			require_once('lib/common/app_footer.php');
		}
		
	}
	
?>
