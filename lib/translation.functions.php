<?php
/*
Translation functions
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\LangOperations;
use CMSMS\NlsOperations;

/**
 * Translation functions
 * @package CMS
 * @license GPL
 */
/**
 * Retrieve a translation for the specified string in the specified realm.
 * e.g. lang_by_realm('tasks','my_string')
 * Any argument(s) after the realm and the key are merged into the string using vsprintf
 *
 * @since 1.8
 * @param varargs $args, of which
 *  1st = realm name (required string)
 *  2nd = language key (required string)
 *  further argument(s) (optional string|number, generally, or array of same)
 * @return mixed string|null
 */
function lang_by_realm(...$args)
{
    return LangOperations::lang_from_realm(...$args);
}

/**
 * [Dis]allow accessing admin realm stings from within a frontend request.
 *
 * @internal
 * @ignore
 * @param bool $flag Optional permission-state. Default true.
 */
function allow_admin_lang(bool $flag = true)
{
    LangOperations::allow_nonadmin_lang($flag);
}

/**
 * Retrieve a translated string from the default 'admin' realm.
 * e.g. lang('title');
 *
 * This method will throw a notice if it is called during a frontend request
 *
 * @see lang_by_realm
 * @param varargs $args, of which
 *  1st = language key (required string)
 *  further argument(s) (optional string|number, generally, or array of same)
 * @return mixed string|null
 */
function lang(...$args)
{
    return LangOperations::lang(...$args);
}

/**
 * Retrieve a list of installed languages that is suitable for use in a dropdown.
 *
 * @param boolean $allow_none Optionally adds 'none' (translated to current language) to the top of the list.
 * @return associative array of lang keys and display strings.
 * @internal
 * @deprecated
 */
function get_language_list(bool $allow_none = true) : array
{
    $tmp = [];
    $langs = NlsOperations::get_installed_languages();
    if( $langs ) {
        if( $allow_none ) $tmp[''] = lang('nodefault');
        asort($langs);
        foreach( $langs as $key ) {
            $obj = NlsOperations::get_language_info($key);
            $value = $obj->display();
            $value2 = $obj->fullname();
            if( $value2 && $value2 != $value ) {
                $value .= ' ('.$value2.')';
            }
            $tmp[$key] = $value;
        }
    }
    return $tmp;
}
