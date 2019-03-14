<?php
#Translation functions
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\LangOperations;
use CMSMS\NlsOperations;

/**
 * Translation functions
 * @package CMS
 * @license GPL
 */
/**
 * Retrieve a translation for a specific string in a specific realm.
 * Called with the realm first, followed by the key, this method will attempt
 * to load the specific realm data if necessary before doing translation.
 *
 * This method accepts a variable number of arguments.  Any arguments after
 * the realm and the key are passed to the key via vsprintf
 *
 * i.e: lang_by_realm('tasks','my_string');
 *
 * @since 1.8
 * @param string $realm The realm
 * @param string $key   The lang key and any related sprintf arguments.
 * @return string
 */
function lang_by_realm(...$args) : string
{
  return LangOperations::lang_from_realm($args);
}

/**
 * Temporarily allow accessing admin realm from within a frontend action.
 *
 * @internal
 * @ignore
 * @return bool
 */
function allow_admin_lang(bool $flag = true) : bool
{
  return LangOperations::allow_nonadmin_lang($flag);
}

/**
 * Return a translated string for the default 'admin' realm.
 * This function is merely a wrapper around the lang_by_realm function
 * that assumes the realm is 'admin'.
 *
 * This method will throw a notice if it is called from a frontend request
 *
 * i.e: lang('title');
 *
 * @param string $key The key to translate and then any vsprintf arguments for the key.
 * @see lang_by_realm
 * @return string
 */
function lang(...$args) : string
{
  return LangOperations::lang($args);
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
	  if( $obj->fullname() ) $value .= ' ('.$obj->fullname().')';
	  $tmp[$key] = $value;
    }
  }
  return $tmp;
}
