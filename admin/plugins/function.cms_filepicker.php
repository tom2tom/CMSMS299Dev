<?php
/*
Plugin to generate a file-selector element for uploading a file.
Copyright (C) 2004-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you may redistribute it and/or modify
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

use CMSMS\Utils;

function smarty_function_cms_filepicker($params, $template)
{
	$out = '';
	$name = trim($params['name'] ?? '');
	if( $name ) {
		$filepicker = Utils::get_filepicker_module();
		if( $filepicker ) {

			$prefix = trim($params['prefix'] ?? '');
			$name = $prefix.$name;
			$value = trim($params['value'] ?? '');
			$required = cms_to_bool($params['required'] ?? false);

			$profile_name = trim($params['profile'] ?? '');
			$profile = $filepicker->get_profile_or_default($profile_name);
			$parms = [];
			$top = trim($params['top'] ?? '');
			if( $top ) $parms['top'] = $top;
			$type = trim($params['type'] ?? ''); // per FileType enum (e.g. 0 OR NONE)
			if( is_numeric($type) ) {
				$parms['type'] = (int)$type;
			} elseif( $type ) {
				$parms['typename'] = strtoupper($type);
			}
			if( $parms ) $profile = $profile->overrideWith($parms);

			$out = $filepicker->get_html($name, $value, $profile, $required);
		}
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_cms_filepicker()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Ted Kulp 2004', "<li>$n</li>");
}
*/
function smarty_cms_help_function_cms_filepicker()
{
	echo _ld('tags', 'help_generic',
	'This plugin generates a file-selector element for uploading a file',
	'cms_filepicker ...',
	'<li>name: the name-attribute of the selector element</li>
<li>prefix: optional string to prepend to the element-name</li>
<li>profile: optional name of a file-system controls-set specifying permissions etc</li>
<li>required: optional flag, whether some file must be selected</li>
<li>top: optional topmost/base website folder from which, or from a descendant of which, the file may be selected</li>
<li>type: optional FileType identifier e.g. ANY or 99</li>
<li>value: optional initial value of the selector element</li>'
	);
}
