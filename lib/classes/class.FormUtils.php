<?php
# A class providing functionality for generating page-elements.
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with the program. If not, see <https://www.gnu.org/licenses/licenses.html>.

namespace CMSMS;

use cms_config;
use cms_siteprefs;
use cms_utils;
use CmsApp;
use CmsCoreCapabilities;
use CMSMS\HookManager;
use const CMS_ROOT_URL;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use function cms_htmlentities;
use function cms_to_bool;
use function endswith;
use function sanitize;

/**
 * A static class providing functionality for generating page elements.
 *
 * @package CMS
 * @license GPL
 * @author  Robert Campbell
 * @since   2.0
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

    // static properties here >> StaticProperties class ?
    /**
     * Names of rich-text-editor modules specified for use during the current request
     * @ignore
     * @deprecated since 2.3
     */
    protected static $_activated_wysiwyg = [];

    /**
     * Names of syntax-highlight-editor modules specified for use during the current request
     * @ignore
     * @deprecated since 2.3
     */
    protected static $_activated_syntax = [];

    /* *
     * @ignore
     */
//    protected function __construct() {}

    /**
     * Migrate content of string $addtext to members of $converted
     * @ignore
     * @since 2.3
     * @param string $addtext element attributes, may be empty
     * @param array  $converted where results are stored
     */
    protected static function splitaddtext(&$addtext, &$converted)
    {
        if ($addtext) {
            $patn = '~([[:alnum:]]*?)\s*=\s*(["\'])([[:alnum:][:punct:] \\/]*?)\2~u';
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
     * Get xhtml for a form element.
     * This is an interface between the deprecated CMSModule methods for form
     * element creation, and their replacements in this class.
     *
     * @since 2.3
     * @deprecated since 2.3 needed only while the CMSModule methods are supported
     *
     * @param object $mod    The initiator module, a CMSModule derivative
     * @param string $method Name of deprecated-method that was called in $mod. Like 'Create*'
     * @param array  $parms  Parameters supplied to the called method
     *
     * @return string
     */
    public static function create(&$mod, string $method, array $parms) : string
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
                        //no braek here
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
                        //default format/mode parameter
                        $parms['href'] = $mod->create_url($id, $action, ($returnid ?? ''), ($params ?? []), !empty($inline), !empty($targetcontentonly), ($prettyurl ?? ''));
                        //no break here
                    case 'tooltip':
                        $myfunc = 'create_tooltip';
                        break;
                }
            }

            if ($myfunc) {
                if (isset($parms['id'])) {
                    $parms['modid'] = $parms['id']; //CHECKME
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
     * @since 2.3
     * @param array $parms element parameters/attributes
     * @param array $must key(s) which must be set in $parms, each with a value-check code.
     * @return mixed Error-message string (template) or false if no error
     */
    protected static function must_attrs(array &$parms, array $must)
    {
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
                            $parms[$key] = $tmp = sanitize($tmp);
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
     * @since 2.3
     * @param array  $parms element parameters/attributes. Must include 'name', unless $withname = false
     * @param bool   $withname optional flag whether a 'name' parameter is required. Default true
     * @param array  $alts optional extra renames for keys in $parms, each member like 'oldname'=>'newname'
     *
     * @return mixed Error-message string, or false if no error
     */
    protected static function clean_attrs(array &$parms, bool $withname = true, array $alts = [])
    {
        //aliases
        $alts += ['classname'=>'class'];
        foreach ($alts as $key => $val) {
            if (isset($parms[$key])) {
                $parms[$val] = $parms[$key];
                unset($parms[$key]);
            }
        }

        extract($parms, EXTR_SKIP);

        if ($withname) {
            if (empty($name)) {
                return sprintf(self::ERRTPL, 'name', '%s');
            }
        } else {
            $name = '';
        }
        //identifiers
        if (!empty($htmlid)) {
            $tmp = $htmlid;
            if (empty($modid) && empty($prefix)) {
                if (!empty($id)) {
                    $modid = $id;
                } else {
                    $modid = '';
                }
            } elseif (!empty($prefix)) {
                $modid = $prefix;
            }
        } elseif (!empty($modid)) {
            $tmp = $modid.$name;
        } elseif (!empty($prefix)) {
            $modid = $prefix;
            $tmp = $prefix.$name;
        } elseif (!empty($id)) {
            //alias for $htmlid or $modid - assume the former
            $modid = '';
            $tmp = $id;
        } elseif (CmsApp::get_instance()->is_frontend_request()) {
            $modid = 'cntnt01';
            $tmp = $modid.$name;
        } else {
            $modid = 'm1_';
            $tmp = $modid.$name;
        }
        unset($parms['htmlid']);
        unset($parms['modid']);
        unset($parms['prefix']);

        if ($withname) {
            $parms['name'] = sanitize($modid.$name);
        }
        $tmp = sanitize($tmp);
        if ($tmp && $tmp !== 'm1_') {
            $parms['id'] = $tmp;
        } elseif ($withname) {
            $parms['id'] = $parms['name'];
        } elseif ($modid && $modid !== 'm1_') {
            $parms['id'] = $modid;
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
        return false;
    }

    /**
     * Generate output representing scalar members of $parms that are not in $excludes
     * $parms key(s) may be numeric, in which case only the value is used.
     * $parms with array-value are automatically ignored.
     * There is no 'sanitization' of URL keys or values.
     * @ignore
     * @since 2.3
     * @param array $parms element parameters/attributes
     * @param array $excludes $parms key(s) to be skipped
     * @return string
     */
    protected static function join_attrs(array &$parms, array $excludes) : string
    {
        $out = '';
        foreach ($parms as $key=>$val) {
            if (!(is_array($val) || in_array($key, $excludes))) {
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
     * @param string[]|string $selected  The selected elements
     * @return string The generated <option> element(s).
     * @see FormUtils::create_options()
     */
    public static function create_option($data, $selected = null) : string
    {
        if (!is_array($data)) {
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
                    $out .= self::create_option($one, $selected);
                }
                $out .= '</optgroup>';
            }
        } else {
            foreach ($data as $rec) {
                $out .= self::create_option($rec, $selected);
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
    public static function create_options($options, $selected = '') : string
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
     * Get xhtml for a dropdown selector
     * @see also FormUtils::create_select()
     *
     * @param string $name The name attribute for the select name
     * @param array  $list_options  Options as per the FormUtils::create_options method
     * @param mixed  $selected string|string[], selected value(s) as per the FormUtils::create_option method
     * @param array  $params Array of additional options including: multiple,class,title,id,size
     * @deprecated Use create_select() with appropriate parameters instead
     * @return string The HTML content for the <select> element.
     */
    public static function create_dropdown(string $name, array $list_options, $selected, array $params = []) : string
    {
        $parms = ['type'=>'drop', 'name'=>$name, 'options'=>$list_options, 'selectedvalue'=>$selected] + $params;
        return self::create_select($parms);
    }

    /**
     * Get xhtml for a selector (checkbox, radiogroup, list, dropdown)
     *
     * @since 2.3
     *
     * @param array  $parms   Attribute(s)/definition(s) to be included in
     *  the element, each member like name=>value. Any name may be numeric,
     *  in which case only the value is used. Must include at least 'type' and
     * 'name' and at least 2 of 'htmlid', 'modid', 'id', the latter being an
     *  alias for either 'htmlid' or 'modid'.
    *   Recognized types are 'check','radio','list', 'drop'
     *
     * @return string
     */
    public static function create_select(array $parms) : string
    {
        //must have these $parms, each with a usable value
        $err = self::must_attrs($parms, ['type'=>'c', 'name'=>'c']);
        if (!$err) {
            //common checks
            $err = self::clean_attrs($parms, true, ['items'=>'options']);
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

                if (isset($selectedvalue) && $selectedvalue == $value) {
                    $parms['checked'] = 'checked';
                }

                $out = '<input type="checkbox"';
                $out .= self::join_attrs($parms, ['type', 'selectedvalue']);
                $out .= ' />'."\n";
                break;
            case 'radio':
                $err = self::must_attrs($parms, ['options'=>'a', 'selectedvalue'=>'v']);
                if ($err) {
                    break;
                }

                $each = '<input' . self::join_attrs($parms, [
                 'id',
                 'selectedvalue',
                 'delimiter',
                ]);
                $i = 1;
                $count = count($options);
                $out = '';
                foreach ($options as $key=>$val) {
                    $out .= $each . ' id="'.$id.$name.$i.'" value="'.$val.'"';
                    if ($val == $selectedvalue) {
                        $out .= ' checked="checked"';
                    }
                    $out .= ' /><label for="'.$id.$name.$i.'">'.$key .'</label>';
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
                $out .= '>'.$contents.'</select>'."\n";
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
     * Get xhtml for a single-element input (text, textarea, button, submit etc)
     *
     * @since 2.3
     *
     * @param array  $parms   Attribute(s)/definition(s) to be included in
     *  the element, each member like name=>value. Any name may be numeric,
     *  in which case only the value is used. Must include at least 'type' and
     * 'name' and at least 2 of 'htmlid', 'modid', 'id', the latter being an
     *  alias for either 'htmlid' or 'modid'
     *
     * @return string
     */
    public static function create_input(array $parms) : string
    {
        if ($parms['type'] != 'textarea') {
            //must have these $parms, each with a usable value
            $err = self::must_attrs($parms, ['type'=>'c', 'name'=>'c']);
            if (!$err) {
                //common checks
                $err = self::clean_attrs($parms, true, ['text'=>'value', 'contents'=>'value']);
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
            $parms['value'] = ($value && $type == 'text') ? cms_htmlentities($value) : $value;

            $out = '<input';
            $out .= self::join_attrs($parms, ['modid']);
            return $out.' />'."\n";
        }
        unset($parms['type']); //don't confuse with 'wantedsyntax'
        return self::create_textarea($parms);
    }

    /**
     * Record a syntax-highlight-editor module specified during generation of a textarea.
     * @internal
     * @ignore
     */
    protected static function add_syntax(string $module_name)
    {
        if ($module_name) {
            if (!in_array($module_name, self::$_activated_syntax)) {
                self::$_activated_syntax[] = $module_name;
            }
        }
    }

    /**
     * Get the specified syntax-highlighter module(s)
     *
     * @return array
     */
    public static function get_requested_syntax_modules()
    {
        return self::$_activated_syntax;
    }

    /**
     * Record a richtext-editor (aka wysiwyg) module specified during generation
     * of a textarea.
     * For frontend editing, the {cms_init_editor} plugin must be included in the
     * head part of the page/template, to process the info recorded by this method.
     *
     * @internal
     * @ignore
     * @param string module_name (required)
     * @param string id (optional) the id of the textarea element)
     * @param string stylesheet_name (optional) the name of a stylesheet to include with this area (some WYSIWYG editors may not support this)
     */
    protected static function add_wysiwyg(string $module_name, string $id = self::NONE, string $stylesheet_name = self::NONE)
    {
        if ($module_name) {
            if (!isset(self::$_activated_wysiwyg[$module_name])) {
                self::$_activated_wysiwyg[$module_name] = [];
            }
            self::$_activated_wysiwyg[$module_name][] = ['id' => $id, 'stylesheet' => $stylesheet_name];
        }
    }

    /**
     * Get the specified richtext editor module(s)
     *
     * @return array
     */
    public static function get_requested_wysiwyg_modules()
    {
        return self::$_activated_wysiwyg;
    }

    /**
     * Get xhtml for a text area input
     * The area may be used with a richtext editor or syntax highlight editor.
     * If so, the related js, css etc are not generated here.
     *
     * @param array $parms   Attribute(s)/property(ies) to be included in the
     *  element, each member like name=>value. Any name may be numeric, in which
     *  case only the value is used.
     * Recognized:
     *   name          = (required string) name attribute for the text area element.
     *   modid/prefix  = (optional string) id given to the module on execution.  If not specified, '' will be used.
     *   id/htmlid = (optional string) id attribute for the text area element.  If not specified, name is used.
     *   class/classname = (optional string) class attribute for the text area element.  Some values will be added to this string.
     *                   default is cms_textarea
     *   forcemodule/forcewysiwyg = (optional string) used to specify the editor-module to enable.  If specified, the module name will be added to the
     *                   class attribute.
     *   enablewysiwyg = (optional boolean) used to specify whether a richtext-editor is required for the textarea.  Sets the language to html.
     *         Deprecated since 2.3. Instead, generate and record content (js, css etc) directly
     *   wantedsyntax  = (optional string) used to specify the language (html,css,php,smarty) to use.  If non empty indicates that a
     *                   syntax-highlight editor is required for the textarea. Deprecated since 2.3. Instead, generate and record content (js etc) directly
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
    public static function create_textarea(array $parms) : string
    {
        $err = self::must_attrs($parms, ['name'=>'c']);
        if (!$err) {
            //common checks
            $err = self::clean_attrs($parms, true, [
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
        $module = null;

        if ($enablewysiwyg) { //deprecated since 2.3
            // we want a wysiwyg
            if (empty($parms['class'])) {
                $parms['class'] = 'cmsms_wysiwyg'; //not for CSS ?!
            } else {
                $parms['class'] .= ' cmsms_wysiwyg';
            }
            $module = ModuleOperations::get_instance()->GetWYSIWYGModule($forcemodule);
            if ($module && $module->HasCapability(CmsCoreCapabilities::WYSIWYG_MODULE)) {
                // TODO use $config['content_language']
                $parms['data-cms-lang'] = 'html'; //park badly-named variable
                $module_name = $module->GetName();
                $parms['class'] .= ' '.$module_name;  //not for CSS ?!
                if (empty($cssname)) {
                    $cssname = self::NONE;
                }
                self::add_wysiwyg($module_name, $id, $cssname);
            }
        }

        if (!isset($wantedsyntax)) { $wantedsyntax = ''; }
        if (!$module && $wantedsyntax) {
            $parms['data-cms-lang'] = $wantedsyntax; //park
            $module = ModuleOperations::get_instance()->GetSyntaxHighlighter($forcemodule);
            if ($module && $module->HasCapability(CmsCoreCapabilities::SYNTAX_MODULE)) {
                $module_name = $module->GetName();
                if (empty($parms['class'])) {
                    $parms['class'] = $module_name; //not for CSS ?!
                } else {
                    $parms['class'] .= ' '.$module_name;
                }
                self::add_syntax($module_name);
            }
        }

        if ($value && $enablewysiwyg && !$wantedsyntax) {
            if (!isset($encoding)) {
                $encoding = ''; //use the system-default
            }
            $value = cms_htmlentities($value, ENT_NOQUOTES, $encoding);
        }

        $out = '<textarea';
        $out .= self::join_attrs($parms, [
         'type',
         'modid',
         'value',
         'enablewysiwyg',
         'forcemodule',
         'wantedsyntax',
         'encoding',
         'cssname',
        ]);
        $out .= '>'.$value.'</textarea>'."\n";
        return $out;
    }

    /**
     * Get xhtml for a label for another element
     *
     * @since 2.3
     *
     * @param array  $parms   Attribute(s)/property(ies) to be included in the
     *  element, each member like name=>value. Any name may be numeric, in which
     *  case only the value is used. Must include at least 'name' and 'labeltext'
     *
     * @return string
     */
    public static function create_label(array $parms) : string
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
        $contents = cms_htmlentities($parms['labeltext']);
        $out .= '>'.$contents.'</label>'."\n";
        return $out;
    }

    /**
     * Get xhtml for the start of a module form
     *
     * @since 2.3
     *
     * @param object $mod    The initiator module, a CMSModule derivative
     * @param array  $parms  Attribute(s)/property(ies) to be included in
     *  the element, each member like name=>value. Any name may be numeric,
     *  in which case only the value is used. Must include at least 'action'
     *
     * @return string
     */
    public static function create_form_start(&$mod, array $parms) : string
    {
        static $_formcount = 1;
        //must have these $parms, each with a usable value
        $err = self::must_attrs($parms, ['action'=>'c']);
        if (!$err) {
            $err = self::clean_attrs($parms, false);
        }
        if ($err) {
            $tmp = sprintf($err, __METHOD__);
            assert(!$err, $tmp);
            return '<!-- ERROR: '.$tmp.' -->';
        }

        extract($parms);

        $idsuffix = (!empty($idsuffix)) ? sanitize($idsuffix) : '';
        if ($idsuffix === '') {
            $idsuffix = $_formcount++;
        }

        if (isset($parms['classname'])) {
            $parms['class'] = $parms['classname'];
            unset($parms['classname']);
        }

        $method = (!empty($method)) ? sanitize($method) : 'post';

        if (!empty($returnid) || $returnid === 0) {
            $returnid = (int)$returnid; //OR filter_var() ?
            $content_obj = cms_utils::get_current_content(); //CHECKME ever relevant when CREATING a form?
            $goto = ($content_obj) ? $content_obj->GetURL() : 'index.php';
            if (strpos($goto, ':') !== false && CmsApp::get_instance()->is_https_request()) {
                //TODO generally support the websocket protocol
                $goto = str_replace('http:', 'https:', $goto);
            }
        } else {
            $goto = 'moduleinterface.php';
        }

        if (empty($enctype)) unset($parms['enctype']);

        $out = '<form id="'.$id.'moduleform_'.$idsuffix.'" method="'.$method.'" action="'.$goto.'"';
        $out .= self::join_attrs($parms, [
         'name',
         'id',
         'modid',
         'idsuffix',
         'returnid',
         'action',
         'method',
         'inline',
        ]);
        $out .= '>'."\n".
        '<div class="hidden">'."\n".
         // TODO if $method == 'get', also support secure action-parameters via GetParameters class
        '<input type="hidden" name="mact" value="'.$mod->GetName().','.$id.','.$action.','.($inline?1:0).'" />'."\n";
       if ($returnid != '') { //NB not strict - it may be null
            $out .= '<input type="hidden" name="'.$id.'returnid" value="'.$returnid.'" />'."\n";
            if ($inline) {
                $config = cms_config::get_instance();
                $out .= '<input type="hidden" name="'.$config['query_var'].'" value="'.$returnid.'" />'."\n";
            }
        } else {
            $out .= '<input type="hidden" name="'.CMS_SECURE_PARAM_NAME.'" value="'.$_SESSION[CMS_USER_KEY].'" />'."\n";
        }
        $excludes = ['module','action','id'];
        foreach ($params as $key=>$val) {
//          $val = TODOfunc($val); urlencode ? serialize?
            if (!in_array($key, $excludes)) {
                if (is_array($val)) {
//TODO e.g. serialize $out .= '<input type="hidden" name="'.$id.$key.'" value="'.TODO.'" />'."\n";
                } else {
                    $out .= '<input type="hidden" name="'.$id.$key.'" value="'.$val.'" />'."\n";
                }
            }
        }
        $out .= '</div>'."\n";
        return $out;
    }

    /**
     * Get xhtml for the end of a module form
     *
     * @since 2.3
     *
     * This is basically just a wrapper around </form>, but might be
     * extended in the future. It's here mainly for consistency.
     *
     * @return string
     */
    public static function create_form_end() : string
    {
        return '</form>'."\n";
    }

    /**
     * Get xhtml for the start of a fieldset, with optional legend
     *
     * @since 2.3
     *
     * @return string
     */
    public function create_fieldset_start(array $parms) : string
    {
        // no 'name', no compulsory
        extract($parms);

        if (isset($classname)) {
            $parms['class'] = $parms['classname'];
            unset($parms['classname']);
        }

        $out = '<fieldset';
        $out .= self::join_attrs($parms, ['modid',]);
        $out .= '>'."\n";
        if (!empty($legend) || (isset($legend) && is_numeric($legend))) {
            $out .= '<legend';
            //$out .= self::join_attrs($TODO);
            $contents = cms_htmlentities($legend);
            $out .= '>'.$contents.'</legend>'."\n";
        }
        return $out;
    }

    /**
     * Get xhtml for the end of a fieldset
     *
     * @since 2.3
     *
     * This is basically just a wrapper around </fieldset>, but might be
     * extended in the future. It's here mainly for consistency.
     *
     * @return string
     */
    public static function create_fieldset_end() : string
    {
        return '</fieldset>'."\n";
    }

    /**
     * Get xhtml for a link to run a module action, or just the URL for that
     * action
     *
     * @since 2.3
     *
     * @param object $mod    The initiator module, a CMSModule derivative
     * @param array  $parms  Attribute(s)/property(ies) to be included in
     *  the element, each member like name=>value. Any name may be numeric,
     *  in which case only the value is used.
     *  Must include at least 'action'
     *
     * @return string
     */
    public static function create_action_link(&$mod, array $parms) : string
    {
        //must have these $parms, each with a usable value
        $err = self::must_attrs($parms, ['action'=>'c']);
        if (!$err) {
            $err = self::clean_attrs($parms, false);
        }
        if ($err) {
            $tmp = sprintf($err, __METHOD__);
            assert(!$err, $tmp);
            return '<!-- ERROR: '.$tmp.' -->';
        }

        extract($parms);

        //optional
        if (!empty($returnid) || $returnid === 0) {
            $returnid = (int)$returnid;
        } else {
            $returnid = '';
        }

        if (empty($params) || !is_array($params)) {
            $params = [];
        }

        $prettyurl = (!(empty($prettyurl) || $prettyurl == ':NOPRETTY:')) ? preg_replace('~[^\w/]~', '', $prettyurl) : '';

        $out = $mod->create_url($id, $action, $returnid, $params, !empty($inline), !empty($targetcontentonly), $prettyurl, $format ?? 0);

        if (!$onlyhref) {
            $out = '<a href="' . $out . '"';
            $out .= self::join_attrs($parms, [
            'modid',
            'action',
            'returnid',
            'inline',
            'targetcontentonly',
            'prettyurl',
            'warn_message',
            'contents',
            'onlyhref',
            ]);
            if ($warn_message) {
                $out .= ' onclick="cms_confirm_linkclick(this,\''.$warn_message.'\');return false;"';
            }
            $out .= '>'.$contents.'</a>';
        }
        return $out;
    }

    /**
     * Get xhtml for a link to a site page, essentially a go-back facilitator. Or only the url
     *
     * @param object $mod    The initiator module, a CMSModule derivative
     * @param array  $parms  Attribute(s)/property(ies) to be included in
     *  the element, each member like 'name'=>'value', may include:
     *  string $htmlid The id-attribute to be applied to the created element
     *  string $modid The id given to the module on execution
     *  string $id An alternate for either of the above id's
     *  mixed  $returnid The id to eventually return to, '' or int > 0
     *  string $contents The activatable text for the displayed link
     *  string TODO support activatable image too
     *  array  $params An array of paramters to be included in the URL of the link. Each member like $key=>$value.
     *  bool   $onlyhref A flag to determine if only the href section should be returned
     *  others deemed relevant and provided by the caller
     *
     * @return string
     */
    public static function create_return_link(&$mod, array $parms) : string
    {
        //TODO any must-have's here ?
        $err = self::clean_attrs($parms, false);
        if ($err) {
            $tmp = sprintf($err, __METHOD__);
            assert(!$err, $tmp);
            return '<!-- ERROR: '.$tmp.' -->';
        }

        extract($parms);

        if (!empty($returnid) || $returnid === 0) {
            $returnid = (int)$returnid; //'' or int > 0
        } else {
            $returnid = '';
        }

        if (empty($params) || !is_array($params)) {
            $params = [];
        }
        // create the url
        $out = $mod->create_pageurl($id, $returnid, $params, false); //i.e. not $for_display

        if ($out) {
            if (!$onlyhref) {
                $out = '<a href="'.$out.'"';
                $out .= self::join_attrs($parms, [
                 'modid',
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
     * Get xhtml for a link to show a site page
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
    public static function create_content_link(array $parms) : string
    {
        //must have these $parms, each with a usable value
        $err = self::must_attrs($parms, ['pageid'=>'i']);
        if (!$err) {
            $err = self::clean_attrs($parms, false);
        }
        if ($err) {
            $tmp = sprintf($err, __METHOD__);
            assert(!$err, $tmp);
            return '<!-- ERROR: '.$tmp.' -->';
        }

        extract($parms);

        $out = '<a href="';
        $config = cms_config::get_instance();
        if ($config['url_rewriting'] == 'mod_rewrite') {
            // mod_rewrite
            $contentops = ContentOperations::get_instance();
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
         'modid',
         'contents',
        ]);
        $out .= '>'.$contents.'</a>';
        return $out;
    }

    /**
     * Get xhtml for a tooltip, which may be specified to be a span or a link
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
    public static function create_tooltip(array $parms) : string
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

        $out .= self::join_attrs($parms, ['href', 'forcewidth', 'contents', 'helptext',]);

        $helptext = cms_htmlentities($helptext);
        $out .= ' title="'.$helptext.'"';

        if (!empty($forcewidth) && is_numeric($forcewidth)) {
            $out .= ' style="width:'.$forcewidth.'px";'; //TODO merge with other style $parms
        }

        if (empty($href)) {
            $contents = cms_htmlentities($contents);
        }
        $out .= '>'.$contents;
        if (empty($href)) {
            $out .= '</span>';
        } else {
            $out .= '</a>'."\n";
        }
        return $out;
    }

    /**
     * Get xhtml for a nest of ul(s) and li's suitable for a popup/context menu
     *
     * @since 2.9
     * @param array $items Each member is an assoc. array, with member 'content' and optional 'children' sub-array
     * @param array  $parms Attribute(s)/property(ies) to be included in
     *  the element, each member like 'name'=>'value'. Of special note:
     *  optional attrs 'class' (for top level), 'submenuclass' (for ul's) and/or 'itemclass' (for li's)
     *
     * @return string
     */
    public static function create_menu(array $items, array $parms = [], $level = 0) : string
    {
        static $mainclass = null;

        if (empty($parms['class'])) {
            if (!$mainclass) {
                $mainclass = HookManager::do_hook_first_result('ThemeMenuCssClass');
                if (!$mainclass) {
                    $mainclass = 'ContextMenu';
                }
            }
            $parms['class'] = $mainclass;
        }
        if ($level == 0) {
            $out = '<div';
            if ($parms) {
                self::clean_attrs($parms, false);
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
