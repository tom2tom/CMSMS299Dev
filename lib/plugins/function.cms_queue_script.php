<?php
/*
Plugin which accumulates javascript to be included in a page or template
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use function CMSMS\get_scripts_manager;

// since 2.99
function smarty_function_cms_queue_script($params, $template)
{
	if( !isset($params['file']) ) { return ''; }
	$combiner = get_scripts_manager();
	$file = trim($params['file']);
	$priority = (int)($params['priority'] ?? 0);
	$res = $combiner->queue_file($file, $priority);
	if (!$res) trigger_error('TODO');
	return '';
}
/*
D function smarty_cms_help_function_cms_queue_script()
{
	echo _ld('tags', 'help_generic',
	'This plugin records a javascript-file for later accumulation into a single script element for a page.
',
	'cms_queue_script file=&quot;path/to/whatever.js&quot;',
	<<<'EOS'
	<li>file: full filesystem path of a .js file</li>
	<li>priority: optional integer 0(use default priority) or 1(highest)...3</li>
EOS
	);
	echo 'See also the complementary {cms_render_scripts} tag.';
}
*/
function smarty_cms_about_function_cms_queue_script()
{
	echo _ld('tags', 'about_generic',
	<<<EOS
<p>Author: Robert Campbell</p>
<p>Version: 1.0</p>
EOS
	,
	lang('none')
	);
}
