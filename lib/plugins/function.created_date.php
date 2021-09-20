<?php
/*
Plugin to get the creation date/time of a site page
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppParams;
use CMSMS\SingleItem;
use CMSMS\Utils;

function smarty_function_created_date($params, $template)
{
	$out = lang('unknown');
	$content_obj = SingleItem::App()->get_content_object();
	if( is_object($content_obj) ) {
		$time = $content_obj->GetCreationDate();
		if( $time > -1 ) {
			if( !empty($params['format']) ) {
				$format = trim($params['format']);
				if( strpos($format, '%') !== false ) {
					$format = Utils::convert_dt_format($format); // migrate strftime format
				}
			}
			else {
				$format = AppParams::get('date_format', 'Y-m-d');
			}
			// TODO handled 'timed' format
			$out = date($format, $time);
		}
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_help_function_created_date()
{
	echo _ld('tags', 'help_generic', 'This plugin does ...', 'created_date ...', <<<'EOS'
<li>format</li>
EOS
	);
}
*/
function smarty_cms_about_function_created_date()
{
	echo <<<'EOS'
<p>Author: Ted Kulp &lt;ted@cmsmadesimple.org&gt;</p>
<p>Change History:</p>
<ul>
<li>Sept 2021 generate output using date() instead of deprecated strftime()</li>
<li>Sept 2021 Revert to date()-compatible site setting 'date_format' if no 'format' parameter is supplied</li>
</ul>
EOS;
}
