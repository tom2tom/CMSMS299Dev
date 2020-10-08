<?php
#Plugin to get page content (of any sort) via a hooklist
#Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\HookOperations;

function smarty_function_gather_content($params, $template)
{
	$listname = (!empty($params['list'])) ? $params['list'] : 'gatherlist';
	$aout = HookOperations::do_hook_accumulate($listname);
	$out = ($aout) ? implode("\n", $aout) : ''; //TODO if multi-dimension array

	if (isset($params['assign'])) {
		$template->assign(trim($params['assign']),$out);
		return;
	}
	return $out;
}

function smarty_cms_help_function_gather_content()
{
	echo lang_by_realm('tags','help_function_gather_content');
}

function smarty_cms_about_function_gather_content()
{
	echo <<<'EOS'
<p>Author: CMS Made Simple Foundation &lt;foundation@cmsmadesimple.org&gt;</p>
<p>Version: 1.0</p>
<p>
Change History:<br />
None
</p>
EOS;
}
