<?php
	/*
		Class:		StandardWindow
		Version:	1.0
		Product:	Ignition
		Author:		Geoff Willings
		
		Copyright (c) Deveus Software, 2004
		
		Notes:
		(*)	Created 25 Oct 2004
	*/
	
	class StandardWindow{
		var $Title;
		function StandardWindow($title=''){
			$this->Title = $title;
		}
		
		function Open(){
			return sprintf('<table class="window_2" cellspacing="0"><tr><th>%s</th></tr><tr><td><table class="container" cellspacing="0">', $this->Title);
		}
		
		function Close(){
			return "</tr></table></td></tr></table>\n";
		}
		
		function AddHeader($description, $icon='icon_info_1.gif'){
			return sprintf('<tr><th class="icon"><img src="%simages/%s" width="16" height="16" align="absmiddle"></th><th>%s</th></tr>', $GLOBALS['IGNITION_ROOT'], $icon, $description);
		}
		
		function OpenContent(){
			return '<tr><td colspan="2">';
		}
		function CloseContent(){
			return '</td></tr>';
		}
	}
?>