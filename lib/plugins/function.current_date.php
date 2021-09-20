<?php
/*
Plugin to get the current date in a specified or site-default format
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
use CMSMS\Utils;

function smarty_function_current_date($params, $template)
{
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

	$out = date($format, time());
	if( !empty($params['ucwords']) ) {
		$out = ucwords($out);
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
D function smarty_cms_help_function_current_date()
{
	echo _ld('tags', 'help_generic',
	'This plugin retrieves the current date in a specified or site-default format',
	'current_date ucwords=1',
<li>format: optional formatter recognized by PHP date()</li>
<li>ucwords: optional boolean, trueish to apply PHP ucwords() to the result</li>
EOS
	);
}
*/
function smarty_cms_about_function_current_date()
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
