<?php
/*
Plugin to retrive the date/time when the current page was last modified.
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

use CMSMS\SingleItem;

function smarty_function_modified_date($params, $template)
{
	$str = lang('unknown');
	$content_obj = SingleItem::App()->get_content_object();
	if( is_object($content_obj) ) {
		$time = $content_obj->GetModifiedDate();
	    if( $time > -1 ) {
			if( !empty($params['format']) ) {
				$format = $params['format'];
			}
			else {
				$format = '%x %X'; // TODO user- or site-default
			}
			$str = strftime($format, $time);
		}
	}
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $str);
		return '';
	}
	return $str;
}

function smarty_cms_about_function_modified_date()
{
	echo <<<'EOS'
<p>Author: Ted Kulp &lt;ted@cmsmadesimple.org&gt;</p>
<p>Change History:</p>
<ul>
<li>None</li>
</ul>
EOS;
}
/*
function smarty_cms_help_function_modified_date()
{
	echo lang_by_realm('tags', 'help_generic', 'This plugin does ...', 'modified_date ...', <<<'EOS'
<li>format</li>
EOS
	);
}
*/
