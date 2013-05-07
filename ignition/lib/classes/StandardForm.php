<?php
	/*
		Class:		StandardForm
		Version:	1.0
		Product:	Ignition
		Author:		Geoff Willings

		Copyright (c) Deveus Software

		Notes:
		(*)	Created 26 Oct 2004
	*/
	class StandardForm{
		function StandardForm(){
			// no constructor
		}

		function Open(){
			return '<table class="form" cellspacing="0">';
		}
		function Close(){
			return '</table>';
		}

		function AddRow($label, $input, $labelWrap=true, $inputWrap=false){
			$labelWrapTxt = ($labelWrap)?'':'nowrap="nowrap"';
			$inputWrapTxt = ($inputWrap)?'':'nowrap="nowrap"';
			return sprintf('<tr><td class="label" %s>%s</td><td class="input" %s>%s</td></tr>', $labelWrapTxt, $label, $inputWrapTxt, $input);
		}
	}
?>