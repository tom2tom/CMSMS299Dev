<?php
/*
Plugin to get the release-name of the current version of CMSMS.
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

function smarty_function_cms_versionname($params, $template)
{
	$val = AppParams::get('cms_version_name');
	if (!$val && defined('CMS_VERSION_NAME')) {
		$val = CMS_VERSION_NAME;
	}
	if (!$val) {
		$val = $CMS_VERSION_NAME ?? 'Anonymous'; // deprecated global var
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $val);
		return '';
	}
	return $val;
}

function smarty_cms_about_function_cms_versionname()
{
	echo <<<'EOS'
<p>Author: Ted Kulp &lt;ted@smsmadesimple.org&gt;</p>
<p>Version: 1.0</p>
<p>Change History:<br/>
None
</p>
EOS;
}
/*
D function smarty_cms_help_function_cms_versionname()
{
	echo lang_by_realm('tags', 'help_generic', 'This plugin does ...', 'cms_versionname', "<li>none</li>");
}
*/
