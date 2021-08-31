<?php
/*
Plugin which accumulates stylesheet files to be included in a page or template
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

// since 2.99
function smarty_function_cms_queue_css($params, $template)
{
	if( !isset($params['file']) ) return '';
	$combiner = CMSMS\SingleItem::App()->GetStylesManager();

	$file = trim($params['file']);
	$priority = (int)($params['priority'] ?? 0);
	$res = $combiner->queue_file($file, $priority);
	if (!$res) trigger_error('TODO');
	return '';
}

function smarty_cms_help_function_cms_queue_css()
{
	echo lang_by_realm('tags', 'help_generic',
	'This plugin records a styles-file for later accumulation into a single link element for a page-head.
',
	'cms_queue_css file=&quot;path/to/whatever.css&quot;',
	<<<'EOS'
	<li>file: full filesystem path of a .css file</li>
	<li>priority: optional integer 0(use default priority) or 1(highest)...3</li>
EOS
	);
	echo 'See also the complementary {cms_render_css} tag.';
}

function smarty_cms_about_function_cms_queue_css()
{
	echo lang_by_realm('tags', 'about_generic',
	<<<EOS
<p>Author: Robert Campbell</p>
<p>Version: 1.0</p>
EOS
	,
	lang('none')
	);
}
