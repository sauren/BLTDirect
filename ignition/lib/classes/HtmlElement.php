<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/XmlBuilder.php');

class HtmlElement extends XmlElement
{
    var $Class;
    Var $Style;

    function HtmlElement($tag, $content=null, $id=null, $class=null,
                         $attributes=null, $complex=false, $closed=false)
    {
        if(parent::XmlElement($tag, $id, $attributes, $content, $complex, $closed)){
            if(!empty($class)) $this->Class = $class;
            $this->PropertiesToAttributes();
            $this->Add($tag, $content, $id, $attributes, $complex, $closed);
            return true;
        } else return false;
    }

    function Add($tag, $content=null, $id=null, $attributes=null,
                 $complex=false, $closed=false)
    {
        parent::Add($tag, $id, $attributes, $content, $complex, $closed);
    }

    function AddChild($tag, $content=null, $id=null, $class=null,
                      $attributes=null, $complex=false, $closed=false)
    {
        $this->Elements[] = new HtmlElement($tag, $content, $id, $class,
                                            $attributes, $complex, $closed);
    }
    function AddBreak(){
        $this->Elements[] = new HtmlElement('br', null, null, null, null, false, true);
    }
    function AddHRule(){
        $this->Elements[] = new HtmlElement('hr', null, null, null, null, false, true);
    }

    function PropertiesToAttributes(){
        if(!empty($this->Class))
            $this->Attributes['class'] = str_replace('"', "'", $this->Class);
        if(!empty($this->Style))
            $this->Attributes['style'] = str_replace('"', "'", $this->Style);
    }

    function GenerateAttributes(){
        $this->PropertiesToAttributes();
        $attrList = '';
        if(is_array($this->Attributes)){
            foreach($this->Attributes as $name => $val){
                $attrList .= " $name=\"$val\"";
            }
        }
        return $attrList;
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
                $render .= ">\n";
                $render .= (empty($this->Content))?
                           "$indent</{$this->Tag}>\n":
                           "$indent{$this->Content}\n".
                           "$indent</{$this->Tag}>\n";
            }
        }
        return $render;
    }
}

class HtmlElementP extends HtmlElement
{
    function HtmlElementP($content=null, $id=null, $class=null,
                          $attributes=null, $complex=false)
    {
        parent::HtmlElement('p', $content, $id, $class, $attributes, $complex, false);
    }
}

class HtmlElementSpan extends HtmlElement
{
    function HtmlElementSpan($content=null, $id=null, $class=null,
                             $attributes=null, $complex=false)
    {
        parent::HtmlElement('span', $content, $id, $class,
                            $attributes, $complex, false);
    }
}
class HtmlElementDiv extends HtmlElement
{
    function HtmlElementDiv($id=null, $class=null, $attributes=null, $complex=true){
        parent::HtmlElement('div', null, $id, $class, $attributes, $complex, false);
    }
}

class HtmlElementList extends HtmlElement
{
    function HtmlElememtList($tag, $id=null, $class=null, $attributes=null){
        if($tag != 'ol' && $tag != 'ul'){
            $this->Error
                = "A list must open and close with either 'ol' or 'ul' tags.";
            return false;
        }
        parent::HtmlElement($tag, null, $id, $class, $attributes, true, false);
    }

    function AddItem($content, $id=null, $class=null,
                     $attributes=null, $complex=false)
    {
        parent::AddChild('li', $content, $id, $class, $attributes, $complex, false);
    }
}

class HtmlElementTable extends HtmlElement
{

    var $Summary;
    var $Width;
    var $Border;
    var $Frame;
    var $Rules;
    var $CellPadding;
    var $CellSpacing;
    var $VAlign;

    function HtmlElementTable($id=null, $class=null, $attributes=null){
        parent::HtmlElement('table', null, $id, $class, $attributes, true, false);
    }

    function PropertiesToAttributes(){
        parent::PropertiesToAttributes();
        if(!empty($this->Summary))
            $this->Attributes['summary'] = str_replace('"', "'", $this->Summary);
        if(!empty($this->Width))
            $this->Attributes['width'] = str_replace('"', "'", $this->Width);
        if(!empty($this->Border))
            $this->Attributes['border'] = str_replace('"', "'", $this->Border);
        if(!empty($this->Frame))
            $this->Attributes['frame'] = str_replace('"', "'", $this->Frame);
        if(!empty($this->Rules))
            $this->Attributes['rules'] = str_replace('"', "'", $this->Rules);
        if(!empty($this->CellPadding))
            $this->Attributes['cellpadding'] = str_replace('"', "'", $this->CellPadding);
        if(!empty($this->CellSpacing))
            $this->Attributes['cellspacing'] = str_replace('"', "'", $this->CellSpacing);
        if(!empty($this->VAlign))
            $this->Attributes['valign'] = str_replace('"', "'", $this->VAlign);
    }

    function AddRow($id=null, $class=null, $attributes=null){
        parent::AddChild('tr', null, $id, $class, $attributes, true, false);
    }

    function AddColumn($tag, $content='&nbsp;', $id=null,
                       $class=null, $attributes=null, $complex=false)
    {
        if(count($this->Elements) < 1){
            $this->Error = "There is no row to add the column to.";
            return false;
        }
        if(is_string($tag)){
            $td = new HtmlElement($tag, $content, $id, $class, $attributes, $complex, false);
            $this->Elements[count($this->Elements)-1]->Elements[] = $td;
        } elseif(is_object($tag)){
            $td = new HtmlElement('td', null, $id, $class, $attributes, true, false);
            $td->AddChildElement($tag);
            $this->Elements[count($this->Elements)-1]->Elements[] = $td;
        } else {
            $this->Error = sprintf("The child element could not be added.
                                    Its type is %s", gettype($tag));
            return false;
        }
    }
}

class HtmlElementLabel extends HtmlElement
{
	var $For;
	function HtmlElementLabel($content, $for=null, $id=null, $class=null, $attr=null){
		$this->For = $for;
		parent::HtmlElement('label', $content, $id, $class, $attr);
	}

	function PropertiesToAttributes(){
		parent::PropertiesToAttributes();
		if(!empty($this->For))
			$this->Attributes['for'] = str_replace('"', "'", $this->For);
	}
}
class HtmlElementInput extends HtmlElement
{
    var $Type;
    var $Name;
    var $Value;
    var $Checked;
    var $Disabled;
    var $ReadOnly;
    var $Size;
    var $MaxLength;
    var $Src;
    var $Alt;
    var $UseMap;
    var $IsMap;
    var $TabIndex;
    var $AccessKey;
    var $OnClick;
    var $OnMouseOver;
    var $OnMouseOut;
    var $OnFocus;
    var $OnBlur;
    var $OnSelect;
    var $OnChange;
    var $Accept;

    function HtmlElementInput($type, $name, $value, $checked=false,
                              $id=null, $class=null, $attributes=null)
    {
        $this->Type = $type;
        $this->Name = $name;
        $this->Value = $value;
        if($checked) $this->Checked = 'checked';
        $this->PropertiesToAttributes();
        parent::HtmlElement('input', null, $id, $class, $attributes, false, true);
    }

    function PropertiesToAttributes(){
        parent::PropertiesToAttributes();
        if(!empty($this->Type))
            $this->Attributes['type'] = str_replace('"', "'", $this->Type);
        if(!empty($this->Name))
            $this->Attributes['name'] = str_replace('"', "'", $this->Name);
        if(!empty($this->Value))
            $this->Attributes['value'] = str_replace('"', "'", $this->Value);
        if(!empty($this->Checked))
            $this->Attributes['checked'] = str_replace('"', "'", $this->Checked);
        if(!empty($this->Disabled))
            $this->Attributes['disabled'] = str_replace('"', "'", $this->Disabled);
        if(!empty($this->ReadOnly))
            $this->Attributes['readonly'] = str_replace('"', "'", $this->ReadOnly);
        if(!empty($this->Size))
            $this->Attributes['size'] = str_replace('"', "'", $this->Size);
        if(!empty($this->MaxLength))
            $this->Attributes['maxlength'] = str_replace('"', "'", $this->MaxLength);
        if(!empty($this->Src))
            $this->Attributes['src'] = str_replace('"', "'", $this->Src);
        if(!empty($this->Alt))
            $this->Attributes['alt'] = str_replace('"', "'", $this->Alt);
        if(!empty($this->UseMap))
            $this->Attributes['usemap'] = str_replace('"', "'", $this->UseMap);
        if(!empty($this->IsMap))
            $this->Attributes['ismap'] = str_replace('"', "'", $this->IsMap);
        if(!empty($this->TabIndex))
            $this->Attributes['tabindex'] = str_replace('"', "'", $this->TabIndex);
        if(!empty($this->AccessKey))
            $this->Attributes['accesskey'] = str_replace('"', "'", $this->AccessKey);
        if(!empty($this->OnMouseOut))
            $this->Attributes['onmouseout'] = str_replace('"', "'", $this->OnMouseOut);
        if(!empty($this->OnMouseOver))
            $this->Attributes['onmouseover'] = str_replace('"', "'", $this->OnMouseOver);
        if(!empty($this->OnClick))
            $this->Attributes['onclick'] = str_replace('"', "'", $this->OnClick);
        if(!empty($this->OnFocus))
            $this->Attributes['onfocus'] = str_replace('"', "'", $this->OnClick);
        if(!empty($this->OnBlur))
            $this->Attributes['onblur'] = str_replace('"', "'", $this->OnBlur);
        if(!empty($this->OnSelect))
            $this->Attributes['onselect'] = str_replace('"', "'", $this->OnSelect);
        if(!empty($this->OnChange))
            $this->Attributes['onchange'] = str_replace('"', "'", $this->OnChange);
        if(!empty($this->Accept))
            $this->Attributes['accept'] = str_replace('"', "'", $this->Accept);
    }
}

class HtmlElementRadio extends HtmlElementInput
{
    function HtmlElementRadio($name, $value, $checked=false, $id=null,
                             $class=null, $attributes=null)
    {
        parent::HtmlElementInput('radio', $name, $value, $checked,
                                 $id, $class, $attributes, false, true);
    }
}
class HtmlElementCheckBox extends HtmlElementInput
{
    function HtmlElementCheckBox($name, $value, $checked=false, $id=null,
                             $class=null, $attributes=null)
    {
        parent::HtmlElementInput('checkbox', $name, $value, $checked,
                                 $id, $class, $attributes, false, true);
    }
}
class HtmlElementSubmit extends HtmlElementInput
{
    function HtmlElementSubmit($name, $value, $id=null,
                             $class=null, $attributes=null)
    {
        parent::HtmlElementInput('submit', $name, $value, false,
                                 $id, $class, $attributes, false, true);
    }
}
class HtmlElementButton extends HtmlElementInput
{
    function HtmlElementButton($name, $value, $id=null,
                             $class=null, $attributes=null)
    {
        parent::HtmlElementInput('button', $name, $value, false,
                                 $id, $class, $attributes, false, true);
    }
}
class HtmlElementHidden extends HtmlElementInput
{
    function HtmlElementHidden($name, $value, $id=null, $class=null, $attributes=null)
    {
        parent::HtmlElementInput('hidden', $name, $value, null,
                                 $id, $class, $attributes, false, true);
    }
}

class HtmlElementTextArea extends HtmlElementInput
{
    var $Name;
    var $Rows;
    var $Cols;
    var $Disabled;
    var $ReadOnly;
    var $TabIndex;
    var $AccessKey;
    var $OnFocus;
    var $OnBlur;
    var $OnSelect;
    var $OnChange;

    function HtmlElementTextArea($name, $content=null, $rows=null, $cols=null, $id=null, $class=null, $attributes=null){
        if(is_null($rows))
            $this->Rows = '10';
        if(is_null($cols))
            $this->Cols = '80';
        if(is_null($content))
            $content = 'Enter text here...';
        parent::HtmlElementInput('textarea', $content, $id, $class, $attributes, false, false);
    }
}

class HtmlElementText extends HtmlElementInput
{
    var $Name;
    var $Disabled;
    var $ReadOnly;
    var $TabIndex;
    var $AccessKey;
    var $OnFocus;
    var $OnBlur;
    var $OnSelect;
    var $OnChange;

    function HtmlElementText($name, $content=null, $size=null, $id=null, $class=null, $attributes=null){
        if(!is_null($size))
            $this->Size = $this->MaxLength = $size;
        if(is_null($content))
            $content = 'Enter text here...';
        parent::HtmlElementInput('text', $name, $content, $id, $class, $attributes, false, false);
    }
    function PropertiesToAttributes(){
        parent::PropertiesToAttributes();
        if(!empty($this->Name))
            $this->Attributes['name'] = str_replace('"', "'", $this->Name);
        if(!empty($this->Disabled))
            $this->Attributes['disabled'] = str_replace('"', "'", $this->Disabled);
        if(!empty($this->TabIndex))
            $this->Attributes['tabindex'] = str_replace('"', "'", $this->TabIndex);
        if(!empty($this->AccessKey))
            $this->Attributes['accesskey'] = str_replace('"', "'", $this->AccessKey);
        if(!empty($this->OnFocus))
            $this->Attributes['onfocus'] = str_replace('"', "'", $this->OnFocus);
        if(!empty($this->OnBlur))
            $this->Attributes['onblur'] = str_replace('"', "'", $this->OnBlur);
        if(!empty($this->OnSelect))
            $this->Attributes['onselect'] = str_replace('"', "'", $this->OnSelect);
        if(!empty($this->OnChange))
            $this->Attributes['onchange'] = str_replace('"', "'", $this->OnChange);
    }
}

class HtmlElementSelect extends HtmlElement
{
    var $Name;
    var $Size;
    var $Multiple;
    var $Disabled;
    var $TabIndex;
    var $OnFocus;
    var $OnBlur;
    var $OnChange;

    function HtmlElementSelect($name, $id=null, $class=null, $attributes=null){
        $attributes['name'] = $name;
        parent::HtmlElement('select', null, $id, $class, $attributes, true, false);
    }

    function AddOption($value, $description, $selected=false, $id=null, $class=null, $attributes=null){
        $attributes['value'] = $value;
        if($selected)
            $attributes['selected'] = 'selected';
        $option = new HtmlElement('option', $description, $id,
                                  $class, $attributes, false, false);
        $this->Elements[] = $option;
    }

    function PropertiesToAttributes(){
        parent::PropertiesToAttributes();
        if(!empty($this->Name))
            $this->Attributes['name'] = str_replace('"', "'", $this->Name);
        if(!empty($this->Size))
            $this->Attributes['size'] = str_replace('"', "'", $this->Size);
        if(!empty($this->Multiple))
            $this->Attributes['multiple'] = str_replace('"', "'", $this->Multiple);
        if(!empty($this->Disabled))
            $this->Attributes['disabled'] = str_replace('"', "'", $this->Disabled);
        if(!empty($this->TabIndex))
            $this->Attributes['tabindex'] = str_replace('"', "'", $this->TabIndex);
        if(!empty($this->OnFocus))
            $this->Attributes['onfocus'] = str_replace('"', "'", $this->OnFocus);
        if(!empty($this->OnBlur))
            $this->Attributes['onblur'] = str_replace('"', "'", $this->OnBlur);
        if(!empty($this->OnChange))
            $this->Attributes['onchange'] = str_replace('"', "'", $this->OnChange);
    }
}
class HtmlElementForm extends HtmlElement
{
    var $Action;
    var $Method;
    var $EncType;
    var $Accept;
    var $Name;
    var $OnSubmit;
    var $OnReset;
    var $AcceptCharset;

    function HtmlElementForm($action, $method, $id=null, $class=null, $attributes=null){

        if(!is_null($attributes)) $this->Attributes = $attributes;
        $this->Attributes['action'] = $action;
        $this->Attributes['method'] = $method;
        $this->Attributes['enctype'] = 'application/x-www-form-urlencoded';

        parent::HtmlElement('form', null, $id, $class,
                            $this->Attributes, true, false);
    }

    function PropertiesToAttributes(){
        if(!empty($this->Action))
            $this->Attributes['action'] = str_replace('"', "'", $this->Action);
        if(!empty($this->Method))
            $this->Attributes['method'] = str_replace('"', "'", $this->Method);
        if(!empty($this->EncType))
            $this->Attributes['enctype'] = str_replace('"', "'", $this->EncType);
        if(!empty($this->Accept))
            $this->Attributes['accept'] = str_replace('"', "'", $this->Accept);
        if(!empty($this->Name))
            $this->Attributes['name'] = str_replace('"', "'", $this->Name);
        if(!empty($this->OnSubmit))
            $this->Attributes['onsubmit'] = str_replace('"', "'", $this->OnSubmit);
        if(!empty($this->OnReset))
            $this->Attributes['onreset'] = str_replace('"', "'", $this->OnReset);
        if(!empty($this->AcceptCharset))
            $this->Attributes['accept-charset'] = str_replace('"', "'", $this->AcceptCharset);
    }
}

class HtmlElementBr extends HtmlElement
{
    function HtmlElemementBr($id=null, $class=null, $attributes=null){
        parent::HtmlElement('br', null, $id, $class, $attributes, false, true);
    }
}

class HtmlElementHr extends HtmlElement
{
    function HtmlElementHr($id=null, $class=null, $attributes=null){
        parent::HtmlElement('hr', null, $id, $class, $attributes, false, true);
    }
}

class HtmlElementA extends HtmlElement
{
    function HtmlElementA($content, $href=null, $name=null, $id=null,
                          $class=null, $attributes=null)
    {
        $attributes['href'] = $href;
        $attributes['name'] = $name;
        parent::HtmlElement('a', $content, $id, $class, $attributes);

    }
}
?>
