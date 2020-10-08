<?php
#Plugin to...
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
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\Utils;

function smarty_function_module_available($params, $template)
{
	$name = '';
	if( isset($params['name']) )
	{
		$name = trim($params['name']);
	}
	if( isset($params['m']) )
	{
		$name = trim($params['m']);
	}
	if( isset($params['module']) )
	{
		$name = trim($params['module']);
	}
	$out = FALSE;
	if( $name ) {
		$out = Utils::module_available($name);
	}
	if( isset($params['assign']) )
	{
		$template->assign(trim($params['assign']),$out);
		return;
	}
	return $out;
}

function smarty_cms_about_function_module_available()
{
	echo <<<'EOS'
<p>Author: Robert Campbell &lt;calguy1000@cmsmadesimple.org&gt;</p>
<p>Change History:</p>
<ul>
<li>Initial version (for CMSMS 1.11)</li>
</ul>
EOS;
}
