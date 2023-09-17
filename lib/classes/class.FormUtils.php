<?php
/*
A class providing functionality for generating page-elements.
Copyright (C) 2016-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/licenses.html>.
*/
namespace CMSMS;

use CMSMS\CapabilityType;
use CMSMS\Crypto;
use CMSMS\HookOperations;
use CMSMS\Lone;
use const CMS_ROOT_URL;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use const CMSSAN_PUNCT;
use function cms_to_bool;
use function cmsms;
use function CMSMS\is_frontend_request;
use function CMSMS\is_secure_request;
use function CMSMS\sanitizeVal;
use function CMSMS\specialize;
use function endswith;
use function startswith;

/**
 * A class of static methods which generate various page-elements.
 *
 * @package CMS
 * @license GPL
 * @author  Robert Campbell
 * @since   3.0
 * @since   2.0 as global-space CmsFormUtils
 */
class FormUtils
{
    /**
     * @ignore
     */
    const NONE = '__none__';
    //untranslated error messages
    private const ERRTPL = 'parameter "%s" is required for %s';
    private const ERRTPL2 = 'a valid "%s" parameter is required for %s';

    // static properties here >> Lone property|ies ?
    /**
     * Names of and related parameters for rich-text-editor modules specified
     * for use during the current request
     * In principle, might be > 1 modname and/or > 1 use of any modname
     * Array members like modname=>[['id' => $id, 'stylesheet' => $stylesheet_name], ...]
     * @ignore
     * @deprecated since 3.0
     */
    protected static $_activated_wysiwyg = [];

    /**
     * Names of syntax-highlight-editor modules specified for use during
     * the current request
     * In principle, might be > 1 instance of any modname and/or > 1 modname
     * Array members like modname=>[['id' => $id], ...]
     * @ignore
     * @deprecated since 3.0
     */
    protected static $_activated_syntax = [];

    /* *
     * @ignore
     */
//    protected function __construct() {}

    /**
     * Migrate content of string $addtext to members of $converted
     * @ignore
     * @since 3.0
     * @param string $addtext element attributes, may be empty
     * @param array  $converted where results are stored
     */
    protected static function splitaddtext(&$addtext, &$converted)
    {
        if ($addtext) {
            // see https://www.w3.org/TR/2011/WD-html5-20110525/syntax.html#attributes-0
            $patn = '~([^\x00-\x1f />"\'=]*?)\s*=\s*(["\'])([[:alnum:][:punct:] \\/]*?)\2~u';
            if (preg_match_all($patn, $addtext, $matches)) {
                foreach ($matches[1] as $i => $key) {
                    if (isset($converted[$key])) {
                        $converted[$key] .= ' '.$matches[3][$i];
                    } else {
                        $converted[$key] = $matches[3][$i];
                    }
                    $addtext = str_replace($matches[0][$i], '', $addtext);
                }
            }
            $addtext = trim($addtext);
            if ($addtext) {
                $converted[] = $addtext;
                $addtext = '';
            }
        }
    }

    /**
     * Get html for a form element.
     * This is an interface between the deprecated CMSModule methods for
     * form-element creation, and their replacements in this class.
     *
     * @since 3.0
     * @deprecated since 3.0 needed only while the CMSModule content-creation methods are supported
     *
     * @param object $mod    The initiator module, a CMSModule derivative
     * @param string $method Name of deprecated-method that was called in $mod. Like 'Create*'
     * @param array  $parms  Parameters supplied to the called method
     *
     * @return string
     */
    public static function create($mod, string $method, array $parms): string
    {
        //interpret & translate $method
        if (strncasecmp($method, 'create', 6) == 0) {
            $myfunc = '';
            $withmod = false;
            if (stripos($method, 'input', 6) == 6) {
                $detail = strtolower(substr($method, 11));
                switch ($detail) {
                    case 'checkbox':
                        $detail = 'check';
                        $myfunc = 'create_select';
                        break;
                    case 'dropdown':
                        $detail = 'drop';
                        $myfunc = 'create_select';
                        break;
                    case 'radiogroup':
                        $detail = 'radio';
                        $myfunc = 'create_select';
                        break;
                    case 'selectlist':
                        $detail = 'list';
                        $myfunc = 'create_select';
                    case 'datalist':
                    //TODO
                        break;
                    case 'textwithlabel':
                    //TODO
                        break;
                    case 'datetimelocal':
                        $detail = 'datetime-local';
                        $myfunc = 'create_input';
                        break;
                    default:
                        $myfunc = 'create_input';
                        break;
                }
                $parms = ['type' => $detail] + $parms;
            } elseif (stripos($method, 'form', 6) == 6) {
                $detail = strtolower(substr($method, 10));
                switch ($detail) {
                    case 'start':
                        $withmod = true;
                        //no break here
                    case 'end':
                        $myfunc = 'create_form_'.$detail;
                }
            } elseif (stripos($method, 'fieldset', 6) == 6) {
                $detail = strtolower(substr($method, 14));
                switch ($detail) {
                    case 'start':
                    case 'end':
                        $myfunc = 'create_fieldset_'.$detail;
                }
            } else {
                switch (strtolower(substr($method, 6))) {
                    case 'syntaxarea':
                        if (empty($parms['cols'])) {
                            $parms['cols'] = '80';
                        }
                        if (empty($parms['rows'])) {
                            $parms['rows'] = '15';
                        }
                        $parms['enablewysiwyg'] = false;
                        $parms['wantedsyntax'] = 'html'; //TODO per config
                        //no break here
                    case 'textarea':
                        $myfunc = 'create_textarea';
                        break;
                    case 'fileuploadinput':
                        $myfunc = 'create_input';
                        $parms = ['type' => 'file'] + $parms;
                        break;
                    case 'frontendformstart':
                        if (empty($parms['action'])) {
                            $parms['action'] = 'default';
                        }
                        $parms['inline'] = true;
                        $myfunc = 'create_form_start';
                        break;
                    case 'labelforinput':
                        $myfunc = 'create_label';
                        break;
                    case 'frontendlink':
                        $parms['inline'] = true;
                        $parms['targetcontentonly'] = false;
                        $withmod = true;
                        $myfunc = 'create_action_link';
                        break;
                    case 'link':
                        $withmod = true;
                        $myfunc = 'create_action_link';
                        break;
                    case 'contentlink':
                        $myfunc = 'create_content_link';
                        break;
                    case 'returnlink':
                        $withmod = true;
                        $myfunc = 'create_return_link';
                        break;
                    case 'tooltiplink':
                        extract($parms);
                        $parms['href'] = $mod->create_url($getid, $action, ($returnid ?? ''), ($params ?? []), !empty($inline), !empty($targetcontentonly), ($prettyurl ?? ''), 2);
                        //no break here
                    case 'tooltip':
                        $myfunc = 'create_tooltip';
                        break;
                }
            }

            if ($myfunc) {
                if (isset($parms['id'])) {
                    $parms['getid'] = $parms['id']; //CHECKME
                }
                if (isset($parms['addtext'])) {
                    $tmp = $parms['addtext'];
                    unset($parms['addtext']);
                    if ($tmp !== '') {
                        self::splitaddtext($tmp, $parms);
                    }
                }
                if ($withmod) {
                    return self::$myfunc($mod, $parms);
                }
                return self::$myfunc($parms);
            }
        }
        return '';
    }

    /**
     * Check existence of compulsory members of $parms, and they each have an acceptable value
     * @ignore
     * @since 3.0
     * @param array $parms element parameters/attributes
     * @param array $must key(s) which must be set in $parms, each with a value-check code.
     * @return mixed Error-message string (template) or false if no error
     */
    protected static function must_attrs(array &$parms, array $must)
    {
        if (isset($parms['attrs']) && is_array($parms['attrs'])) {
            $tmp = $parms['attrs'];
            unset($parms['attrs']);
            $parms = array_merge($parms, $tmp);
        }

        foreach ($must as $key=>$val) {
            if (!isset($parms[$key])) {
                return sprintf(self::ERRTPL, $key, '%s');
            }
            $tmp = $parms[$key];
            switch ($val) {
                case 'v': //any value i.e. the key exists
                    break;
                case 'c': //acceptable/sanitized string
                case 'e': //false/null/empty is also acceptable
                    if ($tmp || (int)($tmp + 0) === 0) {
                        if (is_string($tmp)) {
                            $parms[$key] = $tmp = sanitizeVal($tmp, CMSSAN_PUNCT);
                            if ($tmp) {
                                break;
                            }
                        }
                    }
                    if ($val == 'c') {
                        return sprintf(self::ERRTPL2, $key, '%s');
                    }
                    break;
                case 's': //any non-empty string
                    if (!is_string($tmp) || $tmp === '') {
                        return sprintf(self::ERRTPL2, $key, '%s');
                    }
                    break;
                case 'i': //int or string equivalent
                    $tmp = filter_var($tmp, FILTER_SANITIZE_NUMBER_INT);
                    if ($tmp) {
                        $parms[$key] = (int)$tmp;
                        break;
                    } else {
                        return sprintf(self::ERRTPL2, $key, '%s');
                    }
                    // no break
                case 'n': //any number or string equivalent
                    $tmp = filter_var($tmp, FILTER_SANITIZE_NUMBER_FLOAT);
                    if ($tmp) {
                        $parms[$key] = $tmp + 0;
                        break;
                    } else {
                        return sprintf(self::ERRTPL2, $key, '%s');
                    }
                    // no break
                case 'a': //any non-empty array
                    if ($tmp) {
                        break;
                    } else {
                        return sprintf(self::ERRTPL2, $key, '%s');
                    }
            }
        }
        return false;
    }

    /**
     * Check and update element-properties.
     * @ignore
     * @since 3.0
     * @param array  $parms element parameters/attributes
     * @param array  $alts optional extra renames for keys in $parms, each member like 'oldname'=>'newname'
     *
     * @return mixed Error-message string, or false if no error. $parms[] will probably be different.
     */
    protected static function clean_attrs(array &$parms, array $alts = [])
    {
        /* $parms[] members of particular interest here (all optional):
         name         = name attribute for the text area element.
         getid/prefix = submitted-variable name-prefix.
         id/htmlid    = id attribute for the element.  If not specified, and name is present, then 'getid'.'name' is used.
         class/classname = class attribute (or space-separated attrubutes) for the element.
        */
        //aliases
        $alts += ['classname'=>'class', 'id'=>'htmlid', 'prefix'=>'getid'];
        foreach ($alts as $key => $val) {
            if (isset($parms[$key]) && !isset($parms[$val])) {
                $parms[$val] = $parms[$key];
                unset($parms[$key]);
            }
        }

        if (!isset($parms['getid'])) {
            if (isset($parms['htmlid']) && $parms['htmlid'] == 'm1_') {
                $parms['getid'] = 'm1_'; // assume the 'other' id was intended
                unset($parms['htmlid']);
            } elseif (is_frontend_request()) {
                $parms['getid'] = 'cntnt01'; // frontend default
            } else {
                $parms['getid'] = 'm1_'; // admin default
            }
        }

        extract($parms, EXTR_SKIP);

        //identifiers
        if (!isset($name)) {
            $name = '';
        }
        if (!isset($htmlid) || $htmlid == $getid) {
            if ($name) {
                $htmlid = $getid.$name;
                $parms['htmlid'] = $htmlid;
            } else {
                $htmlid = '';
                unset($parms['htmlid']);
            }
        }

        $patn = '/[\x00-\x1f "\';=?^`&@<>(){}\\/\x7f-\xff]/'; // name may be like 'X[]' or X[y]
        if ($htmlid) {
            $parms['id'] = preg_replace($patn, '', $htmlid);
        }
        if ($name) {
            if (!empty($getid)) {
                $name = $getid.$name;
            }
            $parms['name'] = preg_replace($patn, '', $name);
        }

        //expectable bools
        foreach (['disabled', 'readonly', 'required'] as $key) {
            if (isset($$key)) {
                if (cms_to_bool($$key)) {
                    $parms[$key] = $key;
                } elseif ($$key !== $key) {
                    unset($parms[$key]);
                }
            }
        }
        //expectable ints
        foreach (['maxlength', 'size', 'step', 'cols', 'rows', 'width', 'height'] as $key) {
            if (isset($$key)) {
                $tmp = filter_var($$key, FILTER_SANITIZE_NUMBER_INT);
                if ($tmp || (int)$$key === 0) {
                    $parms[$key] = $tmp;
                } else {
                    return sprintf(self::ERRTPL2, $key, '%s');
                }
            }
        }
        //numbers generally
        foreach (['min', 'max'] as $key) {
            if (isset($$key)) {
                $tmp = filter_var($$key, FILTER_SANITIZE_NUMBER_FLOAT);
                if ($tmp || (int)$$key === 0) {
                    $parms[$key] = $tmp;
                } else {
                    return sprintf(self::ERRTPL2, $key, '%s');
                }
            }
        }
        return false; // actually, success!
    }

    /**
     * Generate output representing scalar members of $parms that are not in $excludes
     * $parms key(s) may be numeric, in which case only the value is used.
     * $parms with array-value are automatically ignored. $parms with
     * empty ('') value are ignored. $parms 'htmlid' and 'getid' are ignored.
     * There is no 'sanitization' of URL keys or values.
     * @ignore
     * @since 3.0
     * @param array $parms element parameters/attributes
     * @param array $excludes $parms key(s) to be skipped
     * @return string
     */
    protected static function join_attrs(array &$parms, array $excludes): string
    {
        $excludes += [-99 => 'htmlid', -98 => 'getid'];
        $out = '';
        foreach ($parms as $key=>$val) {
            if (!(in_array($key, $excludes) || $val === '' || is_array($val))) {
                if (!is_numeric($key)) {
                    if ($key != 'addtext') {
                        $out .= ' '.$key.'='.'"'.$val.'"';
                    } else {
                        $out .= ' '.$val;
                    }
                } else {
                    $out .= ' '.$val;
                }
            }
        }
        return $out;
    }

    /**
     * A simple recursive utility function to create an option, or a set of options,
     * for a select list or multiselect list.
     *
     * Accepts an associative 'option' array with at least two populated keys: 'label' and 'value'.
     * If 'value' is not an array then a single '<option>' will be created.
     * Or if 'value' is itself an array, then an 'optgroup' will be
     * created with its values e.g:
     * $tmp = [
     *   'label'=>'myoptgroup','value'=> [
     *      [ 'label'=>'opt1','value'=>'value1' ],
     *      [ 'label'=>'opt2','value'=>'value2' ]
     *   ]
     * ];
     * The 'option' array may have additional keys for 'title' and 'class' e.g:
     * $tmp = [ 'label'=>'opt1','value'=>'value1','title'=>'My title','class'=>'foo'
     * ];
     *
     * @param array $data The option data
     * @param mixed string[]|string $selected  The selected element(s)
     * @return string The generated <option> element(s).
     * @see FormUtils::create_options()
     */
    public static function create_option(/*array */$data, /*mixed */$selected = ''): string
    {
        if (!is_array($data) || !$data) {
            return '';
        }

        $out = '';
        if (isset($data['label']) && isset($data['value'])) {
            if (!is_array($data['value'])) {
                $out .= '<option value="'.trim($data['value']).'"';
                if ($selected == $data['value'] || is_array($selected) && in_array($data['value'], $selected)) {
                    $out .= ' selected="selected"';
                }
                if (!empty($data['title'])) {
                    $out .= ' title="'.trim($data['title']).'"';
                }
                if (!empty($data['class'])) {
                    $out .= ' class="'.trim($data['class']).'"';
                }
                $out .= '>'.$data['label'].'</option>';
            } else {
                $out .= '<optgroup label="'.$data['label'].'">';
                foreach ($data['value'] as $one) {
                    $out .= self::create_option($one, $selected); //recurse
                }
                $out .= '</optgroup>';
            }
        } else {
            foreach ($data as $rec) {
                $out .= self::create_option($rec, $selected); //recurse
            }
        }
        return $out;
    }

    /**
     * Create a series of options suitable for use in a select input element.
     * The options data may be a simple associative array e.g:
     *   [ 'value1'=>'label1','value2'=>'label2' ]
     * or a nested array e.g:
     *   [
     *    [ 'label'=>'label1','value'=>'value1' ],
     *    [ 'label'=>'label2','value'=>'value2' ]
     *   ]
     * 'title' and/or 'class' members may be included as appropriate
     * @param array $options options data
     * @param mixed $selected string value or array of them
     * @return string
     * @see FormUtils::create_options()
     */
    public static function create_options($options, $selected = ''): string
    {
        if (!is_array($options) || !$options) {
            return '';
        }

        $out = '';
        foreach ($options as $key => $value) {
            if (!is_array($value)) {
                $out .= self::create_option(['label'=>$key,'value'=>$value], $selected);
            } else {
                $out .= self::create_option($value, $selected);
            }
        }
        return $out;
    }

    /**
     * Get html for a dropdown selector
     * @see also FormUtils::create_select()
     *
     * @param string $name The name attribute for the select name
     * @param array  $list_options  Options as per the FormUtils::create_options method
     * @param mixed  $selected string|string[], selected value(s) as per the FormUtils::create_option method
     * @param array  $params Array of additional options including: multiple,class,title,id,size
     * @deprecated Use create_select() with appropriate parameters instead
     * @return string The HTML content for the <select> element.
     */
    public static function create_dropdown(string $name, array $list_options, $selected, array $params = []): string
    {
        $parms = ['type'=>'drop', 'name'=>$name, 'options'=>$list_options, 'selectedvalue'=>$selected] + $params;
        return self::create_select($parms);
    }

    /**
     * Get html for a selector (checkbox, radiogroup, list, dropdown)
     *
     * @since 3.0
     *
     * @param array  $parms   Attribute(s)/definition(s) to be included in
     *  the element, each member like name=>value. Any name may be numeric,
     *  in which case only the value is used. Must include at least 'type' and
     * 'name' and at least 2 of 'htmlid', 'getid', 'id', the latter being an
     *  alias for either 'htmlid' or 'getid'.
    *   Recognized types are 'check','radio','list', 'drop'
     *
     * @return string
     */
    public static function create_select(array $parms): string
    {
        //must have these $parms, each with a usable value
        $err = self::must_attrs($parms, ['type'=>'c', 'name'=>'c']);
        if (!$err) {
            //common checks
            $err = self::clean_attrs($parms, ['items'=>'options']);
        }
        if ($err) {
            $tmp = sprintf($err, __METHOD__);
            assert(!$err, $tmp);
            return '<!-- ERROR: '.$tmp.' -->';
        }
        extract($parms);

        //custom checks & setup
        switch ($type) {
            case 'check':
                $err = self::must_attrs($parms, ['value'=>'v']);
                if ($err) {
                    break;
                }

                if (isset($value) && !$value) { // silly but possible choice for a checkbox
                    $value = 0;
                    $parms['value'] = 0; // don't ignore '' value
                }
                if (isset($selectedvalue) && $selectedvalue == $value) {
                    $parms['checked'] = 'checked';
                }

                $out = '<input type="checkbox"';
                $out .= self::join_attrs($parms, [
                'type',
                'selectedvalue',
                ]);
                $out .= '>'.PHP_EOL;
                break;
            case 'radio':
                $err = self::must_attrs($parms, ['options'=>'a', 'selectedvalue'=>'v']);
                if ($err) {
                    break;
                }

                $each = '<input' . self::join_attrs($parms, [
                 'id',
                 'getid',
                 'htmlid',
                 'selectedvalue',
                 'delimiter',
                ]);
                $i = 1;
                $count = count($options);
                $out = '';
                foreach ($options as $key=>$val) {
                    $out .= $each . ' id="'.$name.$i.'" value="'.$val.'"';
                    if ($val == $selectedvalue) {
                        $out .= ' checked';
                    }
                    $out .= '><label for="'.$name.$i.'">'.$key.'</label>';
                    if ($i < $count && $delimiter) {
                        $out .= $delimiter;
                    }
                    $out .= "\n";
                    ++$i;
                }
                break;
            case 'drop':
                unset($parms['multiple']);
                //no break here
            case 'list':
                $err = self::must_attrs($parms, ['options'=>'a']);
                if ($err) {
                    break;
                }

                if ($type == 'list') {
                    if ($multiple) {
                        $parms['multiple'] = 'multiple';
                        // adjust name if element allows multiple-selection
                        if (!endswith($name, '[]')) {
                            $parms['name'] = $name . '[]';
                        }
                    } else {
                        unset($parms['multiple']);
                    }
                }

                $selected = '';
                if (!empty($selectedvalue)) {
                    $selected = $selectedvalue; //maybe array
                } elseif (isset($selectedindex)) {
                    $selectedindex = (int)$selectedindex;
                    if ($selectedindex < 1) {
                        $selected = reset($options);
                    } else {
                        $keys = array_keys($options);
                        if (isset($keys[$selectedindex])) {
                            $selected = $options[$keys[$selectedindex]];
                        }
                    }
                }

                $out = '<select' . self::join_attrs($parms, [
                 'type',
                 'selectedindex',
                 'selectedvalue',
                ]);
                $contents = self::create_options($options, $selected);
                $out .= '>'.$contents.'</select>'.PHP_EOL;
                break;
            default:
                $err = sprintf(self::ERRTPL2, 'type', '%s');
                break;
        }
        if (!$err) {
            return $out;
        }
        $tmp = sprintf($err, __METHOD__);
        assert(!$err, $tmp);
        return '<!-- ERROR: '.$tmp.' -->';
    }

    /**
     * Get html for a single-element input (text, textarea, button, submit etc)
     *
     * @since 3.0
     *
     * @param array  $parms   Attribute(s)/definition(s) to be included in
     *  the element, each member like name=>value. Any name may be numeric,
     *  in which case only the value is used. Must include at least 'type'
     *  and 'name' and at least 2 of 'htmlid', 'getid', 'id', the latter
     *  being an alias for either 'htmlid' or 'getid'
     *
     * @return string
     */
    public static function create_input(array $parms): string
    {
        if ($parms['type'] != 'textarea') {
            //must have these $parms, each with a usable value
            $err = self::must_attrs($parms, ['type'=>'c', 'name'=>'c']);
            if (!$err) {
                //common checks
                $err = self::clean_attrs($parms, ['text'=>'value', 'contents'=>'value']);
            }
            if ($err) {
                $tmp = sprintf($err, __METHOD__);
                assert(!$err, $tmp);
                return '<!-- ERROR: '.$tmp.' -->';
            }

            extract($parms);
            //custom checks
            $value = $parms['value'] ?? '';
            //TODO tailoring for lots of html5 types
            if ($value && $type == 'text') {
                $value = specialize($value);
            }

            $out = '<input';
            $out .= self::join_attrs($parms, [
            'value', // might be acceptably empty, so add this one manually
            ]);
            return $out.' value="'.$value.'">'.PHP_EOL;
        }
        unset($parms['type']); //don't confuse with 'wantedsyntax'
        return self::create_textarea($parms);
    }

    /**
     * Record a syntax-highlight-editor module specified during generation
     * of a textarea.
     * @internal
     * @ignore
     * @param string module_name (required)
     * @param string id (optional) the id attribute of the textarea element
     */
    protected static function add_syntax(string $modname, string $id = self::NONE)
    {
        if ($modname) {
            if (!isset(self::$_activated_syntax[$modname])) {
                self::$_activated_syntax[$modname] = [];
            }
            self::$_activated_syntax[$modname][] = ['id' => $id];
        }
    }

    /**
     * Get the recorded syntax-highlighter module(s)
     *
     * @return array
     */
    public static function get_requested_syntax_modules()
    {
        return self::$_activated_syntax;
    }

    /**
     * Record a richtext-editor (aka wysiwyg) module specified during
     * generation of a textarea.
     * For frontend editing, the {cms_init_editor} plugin must be included
     * in the head section of the page/template, to process the info
     * recorded by this method.
     *
     * @internal
     * @ignore
     * @param string module_name (required)
     * @param string id (optional) the id attribute of the textarea element
     * @param string stylesheet_name (optional) the name of a stylesheet to include with this area (some WYSIWYG editors may not support this)
     */
    protected static function add_wysiwyg(string $modname, string $id = self::NONE, string $stylesheet_name = self::NONE)
    {
        if ($modname) {
            if (!isset(self::$_activated_wysiwyg[$modname])) {
                self::$_activated_wysiwyg[$modname] = [];
            }
            self::$_activated_wysiwyg[$modname][] = ['id' => $id, 'stylesheet' => $stylesheet_name];
        }
    }

    /**
     * Get the recorded richtext editor module(s)
     *
     * @return array
     */
    public static function get_requested_wysiwyg_modules()
    {
        return self::$_activated_wysiwyg;
    }

    /**
     * Get html for a text area input
     * The area may be used with a richtext editor or syntax highlight editor.
     * If so, the related js, css etc are not generated here.
     *
     * @param array $parms   Attribute(s)/property(ies) to be included in the
     *  element, each member like name=>value. Any name may be numeric, in which
     *  case only the value is used.
     * Recognized:
     *   name          = (required string) name attribute for the text area element.
     *   getid/prefix  = (optional string) submitted-variable name-prefix. If not specified, '' will be used.
     *   id/htmlid     = (optional string) id attribute for the text area element. If not specified, name is used.
     *   class/classname = (optional string) class attribute for the text area element.
     *                   Some values will be added to this string. Default is cms_textarea
     *   forcemodule/forcewysiwyg = (optional string) used to specify the editor-module to enable.  If specified, the module name will be added to the
     *                   class attribute.
     *   enablewysiwyg = (optional boolean) used to specify whether a richtext-editor is required for the textarea.  Sets the language to html.
     *                   Deprecated since 3.0. Instead, generate and record content (js, css etc) directly
     *   wantedsyntax  = (optional string) used to specify the language (html,css,php,smarty) to use.  If non empty indicates that a
     *                   syntax-highlight editor is required for the textarea. Deprecated since 3.0. Instead, generate and record content (js etc) directly
     *   cols/width    = (optional integer) columns of the text area (css or the syntax/wysiwyg module may override this)
     *   rows/height   = (optional integer) rows of the text area (css or the syntax/wysiwyg module may override this)
     *   maxlength     = (optional integer) maxlength attribute of the text area (syntax/wysiwyg module may ignore this)
     *   required      = (optional boolean) indicates a required field.
     *   placeholder   = (optional string) placeholder attribute of the text area (syntax/wysiwyg module may ignore this)
     *   value/text    = (optional string) default text for the text area, will undergo entity conversion.
     *   encoding      = (optional string) default utf-8 encoding for entity conversion.
     *   addtext       = (optional string) additional text to add to the textarea element.
     *   cssname/stylesheet = (optional string) Pass this stylesheet name to the WYSIWYG area if any.
     *
     * note: if wantedsyntax is empty, AND enablewysiwyg is false, then just a plain text area is created.
     *
     * @return string
     */
    public static function create_textarea(array $parms): string
    {
        $err = self::must_attrs($parms, ['name'=>'c']);
        if (!$err) {
            //common checks
            $err = self::clean_attrs($parms, [
             'height'=>'rows',
             'width'=>'cols',
             'text'=>'value',
             'type'=>'wantedsyntax',
             'forcewysiwyg'=>'forcemodule',
             'stylesheet'=>'cssname',
            ]);
        }
        if ($err) {
            $tmp = sprintf($err, __METHOD__);
            assert(!$err, $tmp);
            return '<!-- ERROR: '.$tmp.' -->';
        }

        extract($parms);

        // do we want rich-text-editing for this textarea ?
        $enablewysiwyg = !empty($enablewysiwyg) && cms_to_bool($enablewysiwyg);

        if (empty($cols) || $cols <= 0) {
            $parms['cols'] = ($enablewysiwyg) ? 80 : 20;
        }
        if (empty($rows) || $rows <= 0) {
            $parms['rows'] = ($enablewysiwyg) ? 15 : 5;
        }
        if (!empty($maxlength) && $maxlength <= 0) {
            unset($parms['maxlength']);
        }

        if (!isset($value)) { $value = ''; }
        if (!isset($forcemodule)) { $forcemodule = ''; }
        $mod = null;

        if ($enablewysiwyg) { //deprecated since 3.0
            // we want a wysiwyg
            if (empty($parms['class'])) {
                $parms['class'] = 'cmsms_wysiwyg'; //not for CSS ?!
            } else {
                $parms['class'] .= ' cmsms_wysiwyg';
            }
            $mod = Lone::get('ModuleOperations')->GetWYSIWYGModule($forcemodule);
            if ($mod && $mod->HasCapability(CapabilityType::WYSIWYG_MODULE)) {
                // TODO use $config['content_language'] for [in]direct frontend content
                $parms['data-cms-lang'] = 'html'; //park badly-named variable
                $modname = $mod->GetName();
                $parms['class'] .= ' '.$modname;  //not for CSS ?!
                if (empty($cssname)) {
                    $cssname = self::NONE;
                }
                self::add_wysiwyg($modname, $id, $cssname);
                $x = array_pop(self::$_activated_wysiwyg[$modname]);
                self::$_activated_wysiwyg[$modname][] = $x + $parms;
            }
        }

        if (!isset($wantedsyntax)) { $wantedsyntax = ''; }
        if (!$mod && $wantedsyntax) {
            $parms['data-cms-lang'] = $wantedsyntax; //park
            $mod = Lone::get('ModuleOperations')->GetSyntaxHighlighter($forcemodule);
            if ($mod && $mod->HasCapability(CapabilityType::SYNTAX_MODULE)) {
                $modname = $mod->GetName();
                if (empty($parms['class'])) {
                    $parms['class'] = $modname; //not for CSS ?!
                } else {
                    $parms['class'] .= ' '.$modname;
                }
                self::add_syntax($modname, $id);
                $x = array_pop(self::$_activated_syntax[$modname]);
                self::$_activated_syntax[$modname][] = $x + $parms;
            }
        }

        if ($value && $enablewysiwyg && !$wantedsyntax) {
            if (!isset($encoding)) {
                $encoding = ''; //use the system-default
            }
            $value = specialize($value, ENT_NOQUOTES, $encoding);
        }

        $out = '<textarea';
        $out .= self::join_attrs($parms, [
         'type',
         'value',
         'enablewysiwyg',
         'forcemodule',
         'wantedsyntax',
         'encoding',
         'cssname',
        ]);
        $out .= '>'.$value.'</textarea>'.PHP_EOL;
        return $out;
    }

    /**
     * Get html for a label for another element
     *
     * @since 3.0
     *
     * @param array  $parms   Attribute(s)/property(ies) to be included in the
     *  element, each member like name=>value. Any name may be numeric, in which
     *  case only the value is used. Must include at least 'name' and 'labeltext'
     *
     * @return string
     */
    public static function create_label(array $parms): string
    {
        //must have these $parms, each with a usable value
        $err = self::must_attrs($parms, ['name'=>'c', 'labeltext'=>'c']);
        if (!$err) {
            $err = self::clean_attrs($parms);
        }
        if ($err) {
            $tmp = sprintf($err, __METHOD__);
            assert(!$err, $tmp);
            return '<!-- ERROR: '.$tmp.' -->';
        }

        $out = '<label for="'.$parms['name'].'"';
        $out .= self::join_attrs($parms, ['name', 'labeltext']);
        $contents = specialize($parms['labeltext']);
        $out .= '>'.$contents.'</label>'.PHP_EOL;
        return $out;
    }

    /**
     * Get html for the start of a module form
     *
     * @since 3.0
     *
     * @param object $mod    The initiator module, a CMSModule derivative
     * @param array  $parms  Attribute(s)/property(ies) to be included in
     *  the element, each member like name=>value. Any name may be numeric,
     *  in which case only the value is used. Must include at least 'action'.
     *  Relevant members will become form-attributes, others will become
     *  hidden-inputs.
     *
     * @return string
     */
    public static function create_form_start($mod, array $parms): string
    {
        static $_formcount = 1;
        //must have these $parms, each with a usable value
        $err = self::must_attrs($parms, ['action'=>'c']);
        if (!$err) {
            $err = self::clean_attrs($parms);
        }
        if ($err) {
            $tmp = sprintf($err, __METHOD__);
            assert(!$err, $tmp);
            return '<!-- ERROR: '.$tmp.' -->';
        }

        extract($parms);

        $getid = (!empty($getid)) ? sanitizeVal($getid) : '';
        if ($getid === '') {
            $getid = 'm1_';
        }

        $idsuffix = (!empty($idsuffix)) ? sanitizeVal($idsuffix) : '';
        if ($idsuffix === '') {
            $idsuffix = $_formcount++;
        }

        if (isset($parms['classname'])) {
            $parms['class'] = $parms['classname'];
            unset($parms['classname']);
        }

        $inline = (!empty($inline)) ? 1 : 0;

        $method = (!empty($method)) ? sanitizeVal($method) : 'post';

        if (isset($returnid) && ($returnid || $returnid === 0)) {
            $returnid = (int)$returnid; //OR filter_var() ?
            $content_obj = cmsms()->get_content_object();
            $goto = ($content_obj) ? $content_obj->GetURL() : 'index.php';
            if (strpos($goto, ':') !== false && is_secure_request()) {
                //TODO generally support the websocket protocol 'wss' : 'ws'
                $goto = str_replace('http:', 'https:', $goto);
            }
        } else {
            $config = Lone::get('Config');
            $goto = CMS_ROOT_URL.'/'.$config['admin_dir'].'/moduleinterface.php'; // NOT /lib/...
        }

        if (empty($enctype)) unset($parms['enctype']);

        // identify secure/special params, to become hidden inputs
        $plain = [];
        foreach ($parms as $key=>$val) {
            if (startswith($key, CMS_SECURE_PARAM_NAME)) {
                $plain[] = $key;
            } elseif ($key == 'extraparms') {
                foreach ($parms['extraparms'] as $key=>$val) {
                    if (startswith($key, CMS_SECURE_PARAM_NAME)) {
                        $plain[] = $key;
                    }
                }
            }
        }

        $excludes = array_merge([
            'name',
            'id',
            'idsuffix',
            'returnid',
            'action',
            'method',
            'inline',
            'extraparms',
        ], $plain);

        $out = '<form id="'./*3.0 breaker ? $id.*/'moduleform_'.$idsuffix.'" method="'.$method.'" action="'.$goto.'"';
        $out .= self::join_attrs($parms, $excludes);
        $out .= '>'."\n".
        '<div class="hidden">'."\n".
        '<input type="hidden" name="mact" value="'.$mod->GetName().','.$getid.','.$action.','.($inline?1:0).'">'."\n";
        if (isset($returnid) && $returnid != '') { //NB not strict - it may be null
            $out .= '<input type="hidden" name="'.$getid.'returnid" value="'.$returnid.'">'."\n";
            if ($inline) {
                $config = Lone::get('Config');
                $out .= '<input type="hidden" name="'.$config['query_var'].'" value="'.$returnid.'">'."\n";
            }
        } elseif (isset($_SESSION[CMS_USER_KEY])) { //there is a logged-in user TODO or this is a login-related form
            $out .= '<input type="hidden" name="'.CMS_SECURE_PARAM_NAME.'" value="'.$_SESSION[CMS_USER_KEY].'">'."\n";
        }
        if (!empty($parms['extraparms'])) {
            $arr = $parms['extraparms'];
            unset($parms['extraparms']);
            $parms = array_merge($parms, $arr);
        }
        foreach ($plain as $key) {
            $out .= '<input type="hidden" name="'.$key.'" value="'.$parms[$key].'">'."\n";
        }
        $excludes = array_merge([
            'module',
            'getid',
            'htmlid',
            'id',
            'idsuffix',
            'returnid',
            'action',
            'method',
            'inline',
            'extraparms',
            ], $plain);
        foreach ($parms as $key=>$val) {
//          $val = TODOfunc($val); urlencode ? serialize?
            if (!in_array($key, $excludes)) {
                if (is_array($val)) {
//TODO e.g. serialize $out .= '<input type="hidden" name="'.$getid.$key.'" value="'.TODO.'">'."\n";
                } else {
                    $out .= '<input type="hidden" name="'.$getid.$key.'" value="'.$val.'">'."\n";
                }
            }
        }
        $out .= '</div>'.PHP_EOL;
        return $out;
    }

    /**
     * Get html for the end of a module form
     *
     * @since 3.0
     *
     * This is basically just a wrapper around </form>, but might be
     * extended in the future. It's here mainly for consistency.
     *
     * @return string
     */
    public static function create_form_end(): string
    {
        return '</form>'.PHP_EOL;
    }

    /**
     * Get html for the start of a fieldset, with optional legend
     *
     * @since 3.0
     *
     * @return string
     */
    public function create_fieldset_start(array $parms): string
    {
        // no 'name', no compulsory
        extract($parms);

        if (isset($classname)) {
            $parms['class'] = $parms['classname'];
            unset($parms['classname']);
        }

        $out = '<fieldset';
        $out .= self::join_attrs($parms, []);
        $out .= '>'.PHP_EOL;
        if (isset($legend) && ($legend || is_numeric($legend))) {
            $out .= '<legend';
            //$out .= self::join_attrs($TODO);
            $contents = specialize($legend);
            $out .= '>'.$contents.'</legend>'.PHP_EOL;
        }
        return $out;
    }

    /**
     * Get html for the end of a fieldset
     *
     * @since 3.0
     *
     * This is basically just a wrapper around </fieldset>, but might be
     * extended in the future. It's here mainly for consistency.
     *
     * @return string
     */
    public static function create_fieldset_end(): string
    {
        return '</fieldset>'.PHP_EOL;
    }

    /**
     * Get html for an anchor element which when activated will run a
     * module action.
     * Or get only the URL for such link.
     *
     * @since 3.0
     *
     * @param object $mod    The initiator module, a CMSModule derivative
     * @param array  $parms  Attribute(s)/property(ies) to be included in
     *  the element, each member like name=>value. Any name may be numeric,
     *  in which case only the value is used.
     *  Must include at least 'action'.
     *
     * @return string
     */
    public static function create_action_link($mod, array $parms): string
    {
        if (isset($parms['id']) && !isset($parms['getid'])) {
            $parms['getid'] = $parms['id'];
            unset($parms['id']);
        }
        //must have these $parms, each with a usable value
        $err = self::must_attrs($parms, ['action' => 'c','getid' => 'c']);
        if (!$err) {
            $err = self::clean_attrs($parms);
        }
        if ($err) {
            $tmp = sprintf($err, __METHOD__);
            assert(!$err, $tmp);
            return '<!-- ERROR: '.$tmp.' -->';
        }

        extract($parms);

        //optional
        if (!empty($returnid)) {
            $returnid = (int)$returnid;
        } elseif (isset($returnid) && $returnid === 0) {
            $returnid = 0;
        } else {
            $returnid = '';
        }
        if (empty($params) || !is_array($params)) {
            $params = [];
        }
        if (!isset($id)) {
            $id = ''; // probably unused, but preserves PHP's happiness
        }

        $prettyurl = (!(empty($prettyurl) || $prettyurl == ':NOPRETTY:')) ? preg_replace('~[^\w/]~', '', $prettyurl) : '';

        $out = $mod->create_url($getid, $action, $returnid, $params, !empty($inline), !empty($targetcontentonly), $prettyurl, !empty($relative), ($format ?? 0));

        if (empty($onlyhref)) {
            $out = '<a href="' . $out . '"';
            if (!empty($warn_message)) {
                if (empty($getid)) {
                    $parms['id'] = $id = 'alink'.Crypto::random_string(5, true);
                }
            }
            $out .= self::join_attrs($parms, [
            'id',
            'action',
            'returnid',
            'inline',
            'targetcontentonly',
            'prettyurl',
            'warn_message',
            'contents',
            'onlyhref',
            ]);
            $out .= '>' . $contents . '</a>';
            if (!empty($warn_message)) {
                $msg = addcslashes($warn_message, "'\n\r");
                $out .= <<<EOS
<script>
$(function() {
 $('#{$id}').on('click', function() {
  cms_confirm_linkclick(this, '$msg');
  return false;
 });
});
</script>
EOS;
            }
        }
        return $out;
    }

    /**
     * Get html for an anchor element referring to a site page,
     * essentially a go-back facilitator after a module-action.
     * Or get only the URL for such link.
     *
     * @param object $mod    The initiator module, a CMSModule derivative
     * @param array  $parms  Attribute(s)/property(ies) to be included in
     *  the element, each member like 'name'=>value, may include:
     *  htmlid   string The id-attribute to be applied to the created element
     *  getid    string Submitted-variable name-prefix
     *  id       string An alternate for either of the above id's
     *  returnid mixed The page-id (if any) to eventually return to, '' or alias or int > 0
     *  contents string The activatable text for the displayed link
     *  params   array of paramters to be included in the URL of the link. Each member like $key=>$value.
     *  onlyhref bool Flag to determine if only the href section should be returned
     *  others deemed relevant and provided by the caller
     *  TODO support activatable image
     * @return string
     */
    public static function create_return_link($mod, array $parms): string
    {
        //TODO any must-have's here ?
        $err = self::clean_attrs($parms);
        if ($err) {
            $tmp = sprintf($err, __METHOD__);
            assert(!$err, $tmp);
            return '<!-- ERROR: '.$tmp.' -->';
        }

        extract($parms);

        if (isset($returnid)) {
            if (is_numeric($returnid)) {
                $returnid = (int)$returnid;
            } else {
                $returnid = trim((string)$returnid);
            }
        } else {
            $returnid = '';
        }

        if (!isset($params) || !is_array($params)) {
            $params = [];
        }
        // create the url
        $out = $mod->create_pageurl($getid, $returnid, $params); //i.e. not $for_display

        if ($out) {
            if (!$onlyhref) {
                $out = '<a href="'.$out.'"';
                $out .= self::join_attrs($parms, [
                 'returnid',
                 'contents',
                 'onlyhref',
                ]);
                $out .= '>'.$contents.'</a>';
            }
        }
        return $out;
    }

    /**
     * Get html for an anchor which when activated will show a site page
     *
     * @param array  $parms Attribute(s)/property(ies) to be included in
     *  the element, each member like 'name'=>'value', may include:
     *  int $pageid the page id of the page we want to direct to
     *  string $contents The activatable text for the displayed link
     *  string TODO support activatable image too
     *  others deemed relevant and provided by the caller
     *
     * @return string
     */
    public static function create_content_link(array $parms): string
    {
        //must have these $parms, each with a usable value
        $err = self::must_attrs($parms, ['pageid'=>'i']);
        if (!$err) {
            $err = self::clean_attrs($parms);
        }
        if ($err) {
            $tmp = sprintf($err, __METHOD__);
            assert(!$err, $tmp);
            return '<!-- ERROR: '.$tmp.' -->';
        }

        extract($parms);

        $out = '<a href="';
        $config = Lone::get('Config');
        if ($config['url_rewriting'] == 'mod_rewrite') {
            // mod_rewrite
            $contentops = Lone::get('ContentOperations');
            $alias = $contentops->GetPageAliasFromID($pageid);
            if ($alias) {
                $out .= CMS_ROOT_URL.'/'.$alias.($config['page_extension'] ?? '.shtml');
            } else {
                $tmp = 'no alias for page with id='.$pageid;
                assert(!$alias, $tmp);
                return '<!-- ERROR: '.$tmp.' -->';
            }
        } else {
            // not mod rewrite
            $out .= CMS_ROOT_URL.'/index.php?'.$config['query_var'].'='.$pageid;
        }
        $out .= '"';
        $out .= self::join_attrs($parms, [
         'pageid',
         'contents'
        ]);
        $out .= '>'.$contents.'</a>';
        return $out;
    }

    /**
     * Get html for a tooltip, which may be specified to be a span or a link
     *
     * @param array  $parms Attribute(s)/property(ies) to be included in
     *  the element, each member like 'name'=>'value'. Must include
     *  string 'contents' The text displayed in a span or link. Alias 'linktext'
     *  string 'helptext' The tip text displayed on pointer-device-hover. Alias 'tooltiptext'
     *  May include:
     *  string 'href'     An URL to go to when the link is clicked
     *  int    'forcewidth' Pixel-width of the displayed $contents
     *  others deemed relevant and provided by the caller
     *
     * @return string
     */
    public static function create_tooltip(array $parms): string
    {
        //aliases
        foreach ([
         'linktext' => 'contents',
         'classname' => 'class',
         'tooltiptext' => 'helptext',
        ] as $key => $val) {
            if (isset($parms[$key])) {
                $parms[$val] = $parms[$key];
                unset($parms[$key]);
            }
        }
        //must have these $parms, each with a usable value
        $err = self::must_attrs($parms, ['contents'=>'s', 'helptext'=>'s']);
        if ($err) {
            $tmp = sprintf($err, __METHOD__);
            assert(!$err, $tmp);
            return '<!-- ERROR: '.$tmp.' -->';
        }

        extract($parms);

        if (!empty($href)) {
            $out = '<a href="'.$href.'"';
        } else {
            $out = '<span';
        }

        $out .= self::join_attrs($parms, ['href', 'forcewidth', 'contents', 'helptext']);

        $helptext = specialize($helptext);
        $out .= ' title="'.$helptext.'"';

        if (!empty($forcewidth) && is_numeric($forcewidth)) {
            $out .= ' style="width:'.$forcewidth.'px";'; //TODO merge with other style $parms
        }

        if (empty($href)) {
            $contents = specialize($contents);
        }
        $out .= '>'.$contents;
        if (empty($href)) {
            $out .= '</span>';
        } else {
            $out .= '</a>'.PHP_EOL;
        }
        return $out;
    }

    /**
     * Get html for a nest of ul(s) and li's suitable for a popup/context menu
     *
     * @since 3.0
     * @param array $items Each member is an assoc. array, with member 'content' and optional 'children' sub-array
     * @param array  $parms Attribute(s)/property(ies) to be included in
     *  the element, each member like 'name'=>'value'. Of special note:
     *  optional attrs 'class' (for top level), 'submenuclass' (for ul's) and/or 'itemclass' (for li's)
     *
     * @return string
     */
    public static function create_menu(array $items, array $parms = [], $level = 0): string
    {
        static $mainclass = null;

        if (empty($parms['class'])) {
            if (!$mainclass) {
                $mainclass = HookOperations::do_hook_first_result('ThemeMenuCssClass');
                if (!$mainclass) {
                    $mainclass = 'ContextMenu';
                }
            }
            $parms['class'] = $mainclass;
        }
        if ($level == 0) {
            $out = '<div';
            if ($parms) {
                $err = self::clean_attrs($parms);
                $out .= self::join_attrs($parms, ['submenuclass, itemclass']);
            }
            $out .= '>';
        } else {
            $out = '';
        }
        $uc = (empty($parms['submenuclass'])) ? '' : $parms['submenuclass'].' '.$parms['submenuclass'].$level;
        $ic = (empty($parms['itemclass'])) ? "" : $parms['itemclass'].' '.$parms['itemclass'].$level;
        $out .= <<<EOS
 <ul{$uc}>
EOS;
        foreach ($items as $item) {
            $c = $item['content'];
            if (empty($item['children'])) {
                $out .= <<<EOS
  <li{$ic}>$c</li>
EOS;
            } else {
                $out .= <<<EOS
  <li{$ic}>$c
    <div class="sub-level"><ul>
EOS;
                $out .= self::create_menu($item['children'], $parms, $level+1);
                $out .= <<<'EOS'
    </ul></div>
  </li>
EOS;
            }
        }

        $out .= <<<'EOS'
 </ul>
EOS;
        if ($level == 0) {
            $out .= <<<'EOS'
</div>
EOS;
        }
        return $out;
    }
} // class
