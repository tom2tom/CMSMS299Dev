<?php
/*
Plugin to get the date/time when the current page was last modified.
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AppParams;
use CMSMS\Utils;

function smarty_function_modified_date($params, $template)
{
	$out = lang('unknown');
	$content_obj = cmsms()->get_content_object();
	if( is_object($content_obj) ) {
		$datevar = $content_obj->GetModifiedDate();
		if( $datevar > -1 ) {
			if( !empty($params['format']) ) {
				$format = trim($params['format']);
			}
			else {
				$format = AppParams::get('date_format', 'Y-m-d');
			}
			if( strpos($format, 'timed') !== false ) {
				$format = str_replace(['timed', '  '], ['', ' '], $format);
				//ensure time is displayed
				if( strpos($format, '%') !== false ) {
					if( !preg_match('/%[HIklMpPrRSTXzZ]/', $format) ) {
						if (strpos($format, '-') !== false || strpos($format, '/') !== false ) {
							$format .= ' %k:%M';
						}
						else {
							$format .= ' %l:%M %P';
						}
					}
				}
				elseif( !preg_match('/(?<!\\\\)[aABgGhHisuv]/', $format)) {
					if( strpos($format, '-') !== false || strpos($format, '/') !== false ) {
						$format .= ' H:i';
					}
					else {
						$format .= ' g:i a';
					}
				}
			}
			if( strpos($format, '%') !== false ) {
				$out = Utils::dt_format($datevar, $format);
			}
			else {
				$out = date($format, $datevar);
			}
		}
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}

function smarty_cms_help_function_modified_date()
{
	echo _ld('tags', 'help_generic',
	'This plugin gets the date/time when the currently-displayed site page was last modified, formatted as wanted',
	'modified_date ...',
	'<li>(optional) format: PHP date()- and/or strftime()-compatible format. It may be, or include, the special-case \'timed\'. Default site \'date_format\' setting</li>'
	);
}

function smarty_cms_about_function_modified_date()
{
	echo _ld('tags', 'about_generic', 'Ted Kulp 2004',
	'<li>Dec 2021<ul>
 <li>Use site setting \'date_format\' if no \'format\' parameter is supplied</li>
 <li>Support \'timed\' in the format parameter</li>
 <li>If appropriate, generate output using replacement for deprecated strftime()</li>
</ul></li>'
	);
}