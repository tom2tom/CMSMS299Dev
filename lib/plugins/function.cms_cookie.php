<?php
/*
Plugin to operate on a secure cookie
Copyright (C) 2022-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

//since 3.0
use CMSMS\SignedCookieOperations;

function smarty_function_cms_cookie($params, $template)
{
	if (!isset($params['action'])) {
		$params['action'] = (!empty($params['value'])) ? 'set' : 'get';
	}

	switch ($params['action']) {
		case 'set':
		case 'get':
		case 'erase':
		case 'exists':
			if (!empty($params['name'])) {
				$ops = new SignedCookieOperations();
				break;
			}
		// no break here
		default:
			return '';
	}

	switch ($params['action']) {
		case 'set':
			if (isset($params['value']) && ($params['value'] || is_numeric($params['value']))) {
				$value = $ops->set($params['name'], $params['value'], ($params['expires'] ?? 0));
			} else {
				$value = false;
			}
			break;
		case 'get':
			$value = $ops->get($params['name']);
			break;
		case 'erase':
			$value = $ops->exists($params['name']);
			if ($value) { $ops->erase($params['name']); }
			break;
		case 'exists':
			$value = $ops->exists($params['name']);
			break;
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $value);
		return '';
	}
	if (is_bool($value)) {
		return ($value) ? '' : '<!-- cookie error -->';
	}
	return $value;
}
/*
function smarty_cms_about_function_cms_cookie()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', '2021', "<li>$n</li>");
}
*/
function smarty_cms_help_function_cms_cookie()
{
	echo _ld('tags', 'help_generic',
	'This plugin performs a specified action on a CMSMS secure-cookie',
	'cms_cookie action=... name=...',
	"<li>action: optional action, one of 'set' (default if 'value' is present), 'get' (default if no 'value'), 'erase', 'exists'</li>
<li>name: cookie identifier</li>
<li>value (when setting): cookie value, a non-empty scalar or json-encodable non-scalar</li>
<li>expires (when setting): optional expiry timestamp, default 0 hence a session-cookie</li>"
	);
	echo "For actions other than 'get', returns '' or a html comment reflecting failure";
}