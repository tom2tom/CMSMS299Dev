<?php
/*
Light-weight content-generator for CMSMS admin pages.
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

Derived from FormOne class v.1.8 2020-jan-13 <https://github.com/EFTEC/FormOne>
Copyright (C) 2018-2021 Jorge Castro Castillo <contacto@eftec.cl>
Licensed under the terms of the GNU Lesser General Public License
version 2.1 or later.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple. 
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\internal\Smarty;

/**
 * Light-weight content-generator for admin pages
 *
 * This class generates html representing a variety of relatively common
 * and simple page-elements. The class is primarily intended to support
 * centralised interaction with data 'hooked' from modules. The aim is
 * to present such data to the user in a way consistent with whatever is
 * hard-coded for related core-system data.
 * Hence light-weight: no support for grids, tables, specialist elements ...
 * The generated content can be returned directly or after processing
 * in a nominated smarty template.
 *
 * @since 2.99
 */
class ContentBuilder
{
    private $more = []; //overloaded properties store
    private $codetype = ENT_HTML5; // or any other PHP entitizing-flag : ENT_HTML401, ENT_XML1, ENT_XHTML, ENT_HTML5
    private $idForm = ''; // id attribute of a generated form (independant of id of included element(s))
    private $actionForm; // form action/URL
    private $type; // element identifier (in short-form for inputs e.g. just 'checkbox')
    private $prefix = ''; // prepended to the element's 'name' and 'id' attributes
    private $id; // element id attribute (will have prepended prefix if that's defined)
    private $name; // element name attribute (will have prepended prefix if that's defined)
    private $value;
    private $label; // label text (if relevant) or legend text for a fieldset
    private $level = 1; // heading level 1..6
    private $container = []; // outer element containing disparate types (form, fieldset)
    private $members = []; // members of multi-part element ($this->container, select, radio?)
    private $elemInner; // string, custom content (if any) to be placed inside the element
    private $elemAfter; // string, custom content (if any) to be placed after the element
    private $allAfter = []; // string(s)[], un-rendered content (if any, probably js) to be appended to rendered output

    private $template; // smarty template for rendering the output (admin filename.tpl or actual-template string)
    private $assign; // template variable name for this element
    private $tplvars = []; // supplementary template variables

    private $classes = []; // current element class-attribute specific members
    // every-element same-as-type class-attribute members
    private $classXType = [];
    // other attribute(s) (or js?) for the current element, each array-member like 'attrname' => 'value' or 'attrname' => null
    private $extras = [];
    // attribute(s) for every member added to a multi-part element (select etc), each array-member like 'attrname' => 'value' or 'attrname' => null
    private $bind = [];
    // boolean attributes
    private $checked = false;
    private $disabled = false;
    private $readonly = false;
    private $required = false;

    /* *
     * Constructor
     *
     * @param type $whatever
     */
/*  public function __construct()
    {
    }
*/
    public function __set($key, $value)
    {
        if (property_exists($this, $key)/* && $key !== 'more'*/) {
            $this->$key = $value;
        } else {
            $this->more[$key] = $value;
        }
    }

    public function render()
    {
        switch ($this->type) {
            case 'text':
            case 'hidden':
            case 'email':
            case 'number':
            case 'password':
                $out = $this->renderInput($this->type);
                break;
            case 'checkbox':
                $out = $this->renderCheckbox();
                break;
            case 'radio':
                $out = $this->renderRadio();
                break;
            case 'textarea':
                $out = $this->renderTextArea();
                break;
            case 'label':
                $out = $this->renderLabel();
                break;
            case 'submit':
            case 'button':
                $out = $this->renderButton($this->type);
                break;
            case 'select':
                $out = $this->renderSelect();
                break;
            case 'fieldset':
                $out = $this->renderFieldSet();
                break;
            case 'form':
                $out = $this->renderForm();
                break;
            case 'element':
                $out = $this->renderElement();
                break;
            case 'bare':
                $out = $this->renderBare();
                break;
            default:
                $out = '';
                trigger_error("{$this->type} type not supported");
        }
        $out = $this->renderRaw($out);
        if ($this->allAfter) {
            $out .= implode("\n", $this->allAfter) . "\n";
        }
        $this->reset();
        return $out;
    }

    public function renderRaw($out)
    {
        if ($out && !empty($this->$template)) {
            if (empty($this->tplvars)) {
                $data = [];
            } else {
                $data = $this->tplvars;
            }
            if (empty($this->assign)) {
                $this->assign = $this->type.'.'.$this->id;
            }
            $data[$this->assign = $out];
            $tpl = (new Smarty())->createTemplate('string:'.$this->$template, $data);
            return $tpl->fetch();
        }
        return $out;
    }

    private function renderForm()
    {
        //TODO c.f. FormUtils::create_form_start()
        $method = 'POST'; // TODO variable
        $enctype = 'multipart/form-data'; //TODO variable
        if (!empty($this->$actionForm)) {
            $action = $this->$actionForm;
        } else {
            $action = basename(__FILE__);
        }
        $actAttr = ($action) ? " action=\"$action\"" : '';
        $out = "<form id=\"{$this->idForm}\" name=\"TODO\" method=\"$method\" enctype=\"$enctype\"{$actAttr}>\n";
        if ($this->elemInner) $out .= $this->elemInner . "\n";
        $out .= 'TODO'; //render all members of $this->container
        $out .= "</form>\n";
        if ($this->elemAfter) $out .= $this->elemAfter . "\n";
        return $out;
    }

    private function renderSelect()
    {
        //TODO check multi-select support
        $out = "<select{$this->renderId()}{$this->renderClasses()}{$this->renderExtra()}>\n";
        foreach ($this->members as $item) {
			$value = $TODOitem['W'];
			$extra = $TODOitem['X'];
            $selected = ($this->value == $TODOitem['Y']) ? ' selected' : '';
			$text = $this->entitize($TODOitem['Z']);
			$out .= "<option value=\"$value\"{$extra}{$selected}>$text</option>\n";
        }
        $out .= "</select>\n";
        if ($this->elemAfter) $out .= $this->elemAfter . "\n";
        return $out;
    }

    private function renderFieldSet()
    {
        $out = "<fieldset{$this->renderId()}{$this->renderClasses()}{$this->renderExtra()}>\n";
        if ($this->label) {
            $out .= ' <legend>'
              . $this->entitize($this->label)
              . "</legend>\n";
        }
        foreach ($this->container as $item) {
            $out .= ' ' . $this->renderMember($item) . "\n";
        }
        $out .= "</fieldset>\n";
        if ($this->elemAfter) $out .= $this->elemAfter . "\n";
        return $out;
    }

    private function renderMember($item)
    {
        switch ($item->type) {
            default:
            return '';
        }
    }

    private function renderId()
    {
        if ($this->id === null) {
            $this->id = $this->name; //possibly also null, mabbe also append a counter-siffix
        }
        if ($this->name === null) {
            $this->name = $this->id; // if any, or else ...
        }
        return " name='" . $this->prefix . $this->name . "' id='" . $this->prefix . $this->id . "'";
    }

    private function renderClasses()
    {
        if (isset($this->classXType[$this->type])) {
            $this->classes[] = $this->classXType[$this->type];
        }
        if ($this->classes) {
            $this->classes = array_unique($this->classes, SORT_STRING);
            return " class='" . implode(' ', $this->classes) . "'";
        } else {
            return ''; // no class
        }
    }

    private function renderExtra()
    {
        $out = '';
        foreach ($this->extras as $key => $extra) {
            if ($extra === null) {
                $out .= " {$key}";
            } else {
                $out .= " {$key}='{$extra}'";
            }
        }
        if ($this->required) { // && !isset($this->extras['required'])
            $out .= ' required';
        } elseif ($this->disabled) { //&& !isset($this->extras['disabled'])
            $out .= ' disabled';
        } elseif ($this->readonly) { //&& !isset($this->extras['readonly'])
            $out .= ' readonly';
        }
        return $out;
    }

    private function renderInput($type)
    {
        $out =
         "<input type=\"$type\"{$this->renderId()}{$this->renderClasses()}{$this->renderExtra()}{$this->renderValue()} />\n";
        return $out;
    }

    // NOTE this busts a value containing tag(s), e.g. image in a button label
    // see renderTaggedValue()
    private function renderValue($attr = true)
    {
        if ($attr) {
           return ' value="' . $this->entitize($this->value, true) . '"';
        }
        return $this->entitize($this->value);
    }

    private function renderTaggedValue($attr = true)
    {
        if ($attr) {
            return ' value="' . addcslashes($this->value, '"') . '"';
        }
        return $this->value;
    }

    private function renderCheckbox()
    {
        $checked = ($this->checked || !$this->value) ? ' checked' : '';
        $out =
         "<input type=\"checkbox\"{$this->renderId()}{$this->renderClasses()}{$this->renderExtra()}{$this->renderValue()}$checked />\n"
         . $this->entitize($this->label) . "\n";
        if ($this->elemAfter) $out .= $this->elemAfter . "\n";
        return $out;
    }

    private function renderRadio()
    {
        //TODO custom separator
        $checked = ($this->checked || !$this->value) ? ' checked' : '';
        $out =
         "<input type=\"radio\"{$this->renderId()}{$this->renderClasses()}{$this->renderExtra()}{$this->renderValue()}$checked />\n"
         . $this->entitize($this->label);
        return $out;
    }

    private function renderTextArea()
    {
        $out = "<textarea{$this->renderId()}{$this->renderClasses()}{$this->renderExtra()}>\n"
         . $this->value
         . "\n</textarea>\n";
        if ($this->elemAfter) $out .= $this->elemAfter . "\n";
        return $out;
    }

    private function renderLabel()
    {
        $for = ($this->id) ? $this->prefix . $this->id : $this->prefix . $this->name;
        $out = "<label for=\"$for\"{$this->renderClasses()}{$this->renderExtra()}>"
          . $this->entitize($this->label)
          . "</label>\n";
        if ($this->elemAfter) $out .= $this->elemAfter . "\n";
        return $out;
    }

    private function renderButton($type)
    {
        $out =
          "<button type=\"$type\"{$this->renderId()}{$this->renderClasses()}{$this->renderExtra()} {$this->renderValue()}>{$this->label}";
        $out .= "</button>\n";
        if ($this->elemAfter) $out .= $this->elemAfter . "\n";
        return $out;
    }

    private function renderElement()
    {
        return $this->value; //TODO e.g. specific elements <p>, <hN>
    }

    private function renderPara()
    {
        $out = "<p{$this->renderClasses()}{$this->renderExtra()}>"
         . $this->entitize($this->value)
         . "</p>\n";
        return $out;
    }

    private function renderHeading()
    {
        $l = $this->level;
        $out = "<h{$l}{$this->renderClasses()}{$this->renderExtra()}>"
          . $this->entitize($this->value)
          . "</h{$l}>\n";
        return $out;
    }

    private function renderBare()
    {
        //TODO stet any Element(s) in the value
        return $this->entitize($this->value);
    }

    private function reset($hard = false)
    {
        $this->name = null;
        $this->id = null;
        $this->type = null;
        $this->classes = [];
        $this->value = null;
        $this->checked = null;
        $this->label = null;
        $this->container = [];
        $this->members = [];
        $this->bind = [];
        $this->extras = [];
        $this->elemInner = null;
        $this->elemAfter = null;
        $this->disabled = false;
        $this->readonly = false;
        $this->required = false;
        $this->template = null;
        $this->assign = null;
        $this->tplvars = [];
        if ($hard) {
            //TODO reset everything else
            $this->allAfter = null;
        }
    }

    private function entitize($content, $escape_quotes = false)
    {
        $flags = ($escape_quotes) ? ENT_QUOTES : ENT_NOQUOTES;
        $flags != ENT_SUBSTITUTE | $this->codetype;
        return htmlentities($content, $flags, null, false);
    }

    /**
     * Set form id
     *
     * @param string $idForm
     *
     * @return $this
     */
    public function idForm($idForm)
    {
        $this->idForm = $idForm;
        return $this;
    }

    /**
     * Set element type
     * For input-types, this is a shortform-alias e.g. 'hidden'
     *
     * @param string $type one of:
     ** single inputs:
     * 'text',
     * 'hidden',
     * 'password',
     * 'email',
     * 'number',
     * 'checkbox',
     * 'textarea',
     * 'submit',
     * 'button',
     *
     ** collections:
     * 'form'
     * 'select',
     * 'radio',
     * 'fieldset',
     *
     ** non-inputs:
     * 'label',
     * 'element', any other Element-derived tag: <p/> <h1--6/> <hr/> <img> <svg> etc
     * 'bare',   text|html not in an Element (hence a text-node in the browser)
     *
     * @return $this
     */
    public function type($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set the element's name-prefix and id-prefix
     *
     * @param string $prefix
     *
     * @return $this
     */
    public function prefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Set element name
     *
     * @param string $name
     *
     * @return $this
     */
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set element id
     *
     * @param string $id
     *
     * @return $this
     */
    public function id($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set element disabled-state
     *
     * @param boolean $state Default true
     *
     * @return $this
     */
    public function disabled($state = true)
    {
        $this->disabled = $state;
        if ($state) {
            $this->required = false;
        }
        return $this;
    }

    /**
     * Set element read-only state
     *
     * @param bool $state Default true
     *
     * @return $this
     */
    public function readonly($state = true)
    {
        $this->readonly = $state;
        if ($state) {
            $this->required = false;
        }
        return $this;
    }

    /**
     * Set element required state
     *
     * @param bool $state Default true
     *
     * @return $this
     */
    public function required($state = true)
    {
        $this->required = $state;
        if ($state) {
            $this->disabled = false;
            $this->readonly = false;
        }
        return $this;
    }

    /**
     * Set element checked state
     * @see FormBuilder::renderCheckbox()
     * @see FormBuilder::renderRadio()
     *
     * @param bool $state Default true
     *
     * @return $this
     */
    public function checked($state = true)
    {
        $this->checked = $state;
        return $this;
    }

    /**
     * Add a class to the element
     *
     * @param string $class
     *
     * @return $this
     */
    public function addClass($class)
    {
        if (!in_array($class, $this->classes)) {
            $this->classes[] = $class;
        }
        return $this;
    }

    /**
     * Add a class to be applied to all elements of the specified type
     *
     * @param $type
     * @param $class
     *
     * @return $this
     */
    public function classType($type, $class)
    {
        $this->classXType[$type] = $class;
        return $this;
    }

    /**
     * Set element value
     *
     * @param string|integer|boolean|double $value
     *
     * @return $this
     */
    public function value($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Set element label text
     * This is used for types label, checkbox, radiobutton and button
     *
     * @param string $label
     *
     * @return $this
     */
    public function label($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Set fieldset-element legend text
     *
     * @param string $legend
     *
     * @return $this
     */
    public function legend($legend)
    {
        $this->label = $legend;
        return $this;
    }

    /**
     * Set heading-element level
     *
     * @param number $level 1..6
     *
     * @return $this
     */
    public function level($level)
    {
        $this->level = max(6, min(1, (int)$level));
        return $this;
    }

    /**
     * Add multiple parts/members to a multi-part element
     * This is for types form, select, fieldset, radio
     * @see FormBuilder::addMember()
     * @see FormBuilder::bind()
     * @see FormBuilder::renderSelect()
     *
     * @param array $items, each member an array (of attributes? TODO) which
     *  supplement or override attributes set by the most recent bind()
     *
     * @return $this
     */
    public function addMembers(array $items)
    {
        if ($items) {
            foreach ($items as $attrs) {
                $this->addMember($attrs); //TODO array
            }
        }
        return $this;
    }

    /**
     * Add a part/member to a multi-part element
     * This is for types select, fieldset, ?radio
     * @see FormBuilder::bind()
     * @see FormBuilder::renderSelect()
     *
     * @param array $attrs element-member attributes, each array-member
     *  like 'attrname' => 'value' or 'attrname' => null
     *
     * @return $this
     */
    public function addMember(array $attrs)
    {
        if ($attrs) {
        //TODO
        }
        return $this;
    }

    /**
     * Alias of addAttr()
     * @see FormBuilder::addAttr()
     *
     * @param string $attrname
     * @param mixed $value string|null
     *
     * @return $this
     */
    public function addExtra($attrname, $value = null)
    {
        return $this->addAttr($attrname, $value);
    }

    /**
     * Add an attribute to the element.
     * If the value is empty, the attribute is rendered as $attrname alone
     * e.g. 'disabled'
     *
     * @param string $attrname
     * @param mixed $value string|null
     *
     * @return $this
     */
    public function addAttr($attrname, $value = null)
    {
        $this->extras[$attrname] = $value;
        return $this;
    }

    /**
     * Add default attribute(s) to be used for every member of a
     * multi-part element
     * @see FormBuilder::addItem()
     *
     * @param array $bind
     *
     * @return $this
     */
    public function bind($bind)
    {
        $this->bind = array_merge_recursive($this->bind ?? [], $bind);
        return $this;
    }

    /**
     * Set the element's 'extra inner content'
     *
     * @param string $html
     *
     * @return $this
     */
    public function inner($html)
    {
        $this->elemInner = $html;
        return $this;
    }

    /**
     * Set the element's 'extra content'
     *
     * @param string $html
     *
     * @return $this
     */
    public function after($html)
    {
        $this->elemAfter = $html;
        return $this;
    }

    /**
     * Set the element's click-event handler
     *
     * @param string $js
     *
     * @return $this
     */
    public function onClick($js)
    {
        return $this->addJScript('onclick', $js);
    }

    /**
     * Set the element's change-event handler
     *
     * @param string $js
     *
     * @return $this
     */
    public function onChange($js)
    {
        return $this->addJScript('onchange', $js);
    }

    /**
     * Add a javascript-attribute (typically an event-handler) to an element
     *
     * @param string $attrname e.g. 'onnamedevent'
     * @param string $js script e.g. 'dosomething(this);'
     *
     * @return $this
     */
    public function addJScript(string $attrname, string $js)
    {
        $this->extras[$attrname] = $this->entitize($js, true);
        return $this;
    }

    /**
     * Add to the verbatim 'postscript' of the generated content
     *
     * @param mixed $text string | string[], probably js
     *
     * @return $this
     */
    public function last($text)
    {
        if ($text) {
            if (is_array($text)) {
                $this->allAfter = array_merge($this->allAfter ?? [], $text);
            } else {
                if (!isset($this->allAfter)) $this->allAfter = [];
                $this->allAfter[] = $text;
            }
        }
        return $this;
    }

    /**
     * Set the smarty-template to be used for generating output
     *
     * @param string $tpl admin-template filename (whatever.tpl) or
     *  actual template content
     *
     * @return $this
     */
    public function setTemplate(string $tpl)
    {
        $this->template = $tpl;
        return $this;
    }

    /**
     * Set the smarty-template variable to be used for this element when
     * generating output by means of the template
     *
     * @param string $name
     *
     * @return $this
     */
    public function nameTplvar(string $name)
    {
        $this->tplname = $name;
        return $this;
    }

    /**
     * Add other smarty-template parameter(s) to be passed to the template
     * for use when generating output
     *
     * @param mixed $name string | array of params each like string name => mixed value
     * @param mixed $value optional value corresponding to string $name, default null
     *
     * @return $this
     */
    public function addTplvar($name, $value = null)
    {
        if (is_array($name)) {
            $this->tplvars = $name + $this->tplvars ?? []; //PHP7+
        } else {
            $this->tplvars[$name] = $value;
        }
        return $this;
    }
}
