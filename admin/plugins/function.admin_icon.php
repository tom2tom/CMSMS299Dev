<?php
/*
Plugin to get page-content representing an admin icon
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

use CMSMS\AppState;
use CMSMS\SingleItem;

function smarty_function_admin_icon($params, $template)
{
	$icon = null;

	if( AppState::test(AppState::ADMIN_PAGE) ) {
		$tagparms = ['class'=>'systemicon'];
		foreach( $params as $key => $value ) {
			switch( $key ) {
			case 'icon':
			case 'module':
				$icon = trim($value);
				break;
			case 'width':
			case 'height':
			case 'alt':
			case 'rel':
			case 'class':
			case 'id':
			case 'name':
			case 'title':
			case 'accesskey':
				$tagparms[$key] = trim($value);
				// no break here
			default:
				break;
			}
		}
	}

	if( $icon ) {
		$themeObject = SingleItem::Theme();
		if( !isset($tagparms['alt']) ) $tagparms['alt'] = pathinfo($icon, PATHINFO_FILENAME);

		if( isset($params['module']) ) {
			$out = $themeObject->get_module_icon($icon, $tagparms);
		}
		else {
			$out = $themeObject->get_icon($icon, $tagparms);
		}
	}
	else {
		$out = ''; // no error-feedback
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_admin_icon()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Ted Kulp 2004', "<li>$n</li>");
}
*/
function smarty_cms_help_function_admin_icon()
{
	//TODO property details
	echo _ld('tags', 'help_generic',
	'This plugin generates page-content representing an icon for display in an admin page',
	'admin_icon icon= ...',
	'<li>icon: admin-theme relative filesystem path of &quot;standard&quot; admin icon file (extension absent or ignored)</li>
<li>module: (instead of icon) name of the module whose representative icon is wanted</li>
<li>optional element properties:
<ul>
<li>accesskey: </li>
<li>alt: </li>
<li>class: </li>
<li>height: </li>
<li>id: </li>
<li>name: </li>
<li>rel: </li>
<li>title: </li>
<li>width: </li>
</ul></li>'
	);
}