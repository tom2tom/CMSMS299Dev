<?php
/*
Function to send Events after different types and stages template compilation.
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Events;

function smarty_postfilter_postcompilefunc($source, Smarty_Internal_Template $template)
{
	$type = $template->source->type;

	switch ($type)	{
	case 'cms_stylesheet':
	case 'stylesheet':
		Events::SendEvent('Core', 'StylesheetPostCompile', ['stylesheet'=>&$source]);
		break;

	case 'content':
		Events::SendEvent('Core', 'ContentPostCompile', ['content' => &$source]);
		break;

	case 'cms_template':
// handled by cms_template	case 'cms_file':
	case 'template':
//		case 'tpl_top':
//		case 'tpl_body':
//		case 'tpl_head':
		Events::SendEvent('Core', 'TemplatePostCompile', ['template'=>&$source, 'type'=>$type]);
	break;

	default:
		break;
	}

	Events::SendEvent('Core', 'SmartyPostCompile', ['content' => &$source]);

	return $source;
}
/* Not published in UI
function smarty_cms_about_postfilter_postcompilefunc()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Ted Kulp 2004', "<li>$n</li>");
}
*/
/*
function smarty_cms_help_postfilter_postcompilefunc()
{
	$n = _la('none');
	echo _ld('tags', 'help_generic',
	'This function sends appropriate Events after different types and stages of template compilation, and processes any responses',
	'postcompilefunc',
	"<li>$n</li>"
	);
}
*/