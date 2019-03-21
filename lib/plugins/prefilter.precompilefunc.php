<?php
#Plugin to...
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Events;

function smarty_prefilter_precompilefunc($tpl_output, $smarty)
{
	$result = explode(':', $smarty->_current_file);

	if (count($result) > 1)	{
		if( startswith($result[0],'tmp_') ) $result[0] = 'template';

		switch ($result[0]) {
		case 'cms_stylesheet':
		case 'stylesheet':
			Events::SendEvent('Core', 'StylesheetPreCompile', ['stylesheet'=>&$tpl_output]);
			break;

		case 'content':
			Events::SendEvent('Core', 'ContentPreCompile', ['content' => &$tpl_output]);
			break;

		case 'cms_template':
		case 'cms_file':
		case 'tpl_top':
		case 'tpl_body':
		case 'tpl_head':
		case 'template':
			Events::SendEvent('Core', 'TemplatePreCompile', ['template' => &$tpl_output, 'type' => $result[0]]);
		break;

		default:
			break;
		}
	}

	Events::SendEvent('Core', 'SmartyPreCompile', ['content' => &$tpl_output]);

	return $tpl_output;
}
