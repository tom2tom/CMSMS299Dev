<?php
/*
Function to send Events berfore different types and stages template compilation.
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

use CMSMS\Events;

function smarty_prefilter_precompilefunc($source, Smarty_Internal_Template $template)
{
	$type = $template->source->type;

	if( startswith($type,'tmp_') ) $type = 'template';

	switch ($type) {
	case 'cms_stylesheet':
	case 'stylesheet':
		Events::SendEvent('Core', 'StylesheetPreCompile', ['stylesheet'=>&$source]);
		break;

	case 'content':
		Events::SendEvent('Core', 'ContentPreCompile', ['content' => &$source]);
		break;

	case 'cms_template':
// handled by cms_template	case 'cms_file':
//		case 'tpl_top':
//		case 'tpl_body':
//		case 'tpl_head':
	case 'template':
		Events::SendEvent('Core', 'TemplatePreCompile', ['template' => &$source, 'type' => $type]);
	break;

	default:
		break;
	}

	Events::SendEvent('Core', 'SmartyPreCompile', ['content' => &$source]);

	return $source;
}
/* NOT published in UI
function smarty_cms_about_prefilter_precompilefunc()
{
	echo _ld('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
EOS
	);
}
*/
/*
function smarty_cms_help_prefilter_precompilefunc()
{
	$n = lang('none');
	echo _ld('tags', 'help_generic',
	'This function sends appropriate Events before different types and stages of template compilation, and processes any responses',
	'precompilefunc',
	"<li>$n</li>"
	);
}
*/
