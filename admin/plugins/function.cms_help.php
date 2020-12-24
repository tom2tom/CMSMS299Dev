<?php
#Plugin to generate page content enabling popup help
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\AdminUtils;

function smarty_function_cms_help($params, $template)
{
	$out = AdminUtils::get_help_tag($params);

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_cms_help()
{
	echo lang_by_realm('tags', 'about_generic', 'rel', <<<'EOS'
<li>detail</li>
EOS
	);
}
*/
function smarty_cms_help_function_cms_help()
{
	echo lang_by_realm('tags', 'help_generic',
	'This plugin generates page elements and script which enable popup help',
	'cms_help params...',
	<<<'EOS'
<li>key1: lang/translation realm for the strings used</li>
<li>realm: alias for key1</li>
<li>key2: lang key for help body-content</li>
<li>key:  alias for key2</li>
<li>title: lang key for help title</li>
<li>titlekey: alias for title</li>
EOS
	);
}
