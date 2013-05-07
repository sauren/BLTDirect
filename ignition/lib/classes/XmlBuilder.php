<?php

class XmlElement
{
	var $Tag;
	var $ID;    # xml id attribute, NOT primary key.
	var $Attributes;
	var $Content;
	var $Elements;
	var $Closed;    # True for <br/> or <img/>. False for <p></p>
	var $Error;

	# NOTE: A complex element is one that can contain children.
	function XmlElement($tag, $id=null, $attributes=null, $content=null,
	$complex=false, $closed=false){
		if($complex === true && $closed === true){
			$this->Error = 'A complex element cannot be closed.';
			return false;
		}
		if(!empty($content) && $closed === true){
			$this->Error = 'A closed element cannot have any contents.'.
			'XmlElements::Tag must be empty.';
			return false;
		}
		$this->Add($tag, $id, $attributes, $content, $complex, $closed);
		return true;
	}
	function Add($tag, $id=null, $attributes=null, $content=null,
	$complex=false, $closed=false)
	{
		$this->Tag = $tag;
		$this->ID = $id;
		$this->Attributes = $attributes;
		$this->Content = $content;
		$this->Closed = $closed;
		$this->Elements = ($complex)? array(): null;
	}
	function SetAttribute($name, $value){
		$this->Attributes[$name] = $value;
	}
	function AppendContent($content){
		$this->Content .= $content;
	}
	function SetContent($content){
		$this->Content = $content;
	}
	function AddChild($tag, $id=null, $attributes=null, $content=null,
	$complex=false, $closed=false)
	{
		$this->Elements[] = new XmlElement($tag, $id, $attributes, $content,
		$complex, $closed);
	}
	function AddChildElement(&$element){
		$this->Elements[] = $element;
	}

	function ToString($tab=0){
		$indent = '';
		$next = $tab;
		while($tab > 0){
			$indent .= '  '; # 2 spaces
			$tab--;
		}
		$next += 1;
		$render = '';
		$attr = $this->GenerateAttributes();
		$id = (empty($this->ID))? '': " id=\"{$this->ID}\"";
		$render = "$indent<{$this->Tag}$id$attr";
		if(is_array($this->Elements)){
			$render .= ">\n";
			foreach($this->Elements as $e){
				$render .= $e->ToString($next);
			}
			$render .= "$indent</{$this->Tag}>\n";
		} else {
			if($this->Closed == true){
				$render .= "/>\n";
			} else {
				$render .= ">";
				if(stristr($this->Content, '>') || stristr($this->Content, '<') || stristr($this->Content, '&')){
					$render .= "<![CDATA[{$this->Content}]]></{$this->Tag}>\n";
				} else {
					$render .= (empty($this->Content))?"</{$this->Tag}>\n":"{$this->Content}</{$this->Tag}>\n";
				}
			}
		}
		return $render;
	}

	function GenerateAttributes(){
		$attrList = '';
		if(is_array($this->Attributes)){
			foreach($this->Attributes as $name => $val){
				$attrList .= " $name=\"$val\"";
			}
		}
		return $attrList;
	}
}
?>