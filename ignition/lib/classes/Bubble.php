<?php
class Bubble {
	var $Title;
	var $Description;
	var $Icon;
	var $Class;

	function __construct($title, $description, $icon='', $class='bubbleInfo'){
		$this->Title = $title;
		$this->Description = $description;
		$this->Icon = $icon;
		$this->Class = $class;
	}

	function GetHTML(){
		return sprintf('<table class="%s" cellspacing="0"><tr><td valign="top">%s	%s%s</td></tr></table>', $this->Class, $this->Icon, !empty($this->Title) ? sprintf('<strong>%s</strong><br />', $this->Title) : '', $this->Description);
	}
}