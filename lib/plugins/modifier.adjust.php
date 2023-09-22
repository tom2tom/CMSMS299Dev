<?php
/*
Plugin to modify template content by applying a PHP callable.
Copyright (C) 2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

/**
 * Smarty plugin
 * Type:    modifier
 * Name:    adjust
 * Purpose: allows modifying a value by applying a PHP method to it.
 *  A replacement for deprecated direct use of callable modifiers in templates
 *
 * @param mixed $value (possibly placeholder string '&#;')
 * @param callable $callable
 * @param varargs other arguments for $callable
 * @return mixed, never null (empty string is substituted for that)
 */
function smarty_modifier_adjust($value, $callable, ...$args)
{
	//TODO support callables whitelist(s) in future c.f. smarty security policy
	if ($value) {
		if ($args) {
			if ($value == '&#;') {
				$out = call_user_func($callable, ...$args);
			} else {
				$out = call_user_func($callable, $value, ...$args);
			}
		} else {
			$out = call_user_func($callable, $value);
		}
		return (!is_null($out)) ? $out : '';
	} else {
		//we'll assume that the function to be called does nothing to a falsy value
		//although in principle some funcs might validly handle such value
		return (!is_null($value)) ? $value : '';
	}
}