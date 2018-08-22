<?php
#Function to get includable styles and/or scripts
#Deprecated since 2.3, retained only to prevent fatal errors
#Copyright (C) 2004-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

function smarty_function_cms_jquery($params, Smarty_Internal_Template $template)
{
	$out = cms_get_jquery(); // returns only a comment
	if( isset($params['assign']) ) {
		$template->assign(trim($params['assign']),$out);
		return;
	}

	return $out;
}