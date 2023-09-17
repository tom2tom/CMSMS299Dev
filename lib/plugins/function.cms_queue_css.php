<?php
/*
Plugin which records a stylesheet file to be accumulated for inclusion in a page or template
Copyright (C) 2019-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\Lone;
// since 3.0
function smarty_function_cms_queue_css($params, $template)
{
	if( empty($params['file']) ) {
		return '';
	}
	$file = trim($params['file']);
	$combiner = Lone::get('StylesMerger');
	$priority = (int)($params['priority'] ?? 0);
	if( $combiner->queue_file($file, $priority) ) {
		return '';
	}
	trigger_error('Failed to merge stylesheet '.$file);
}

function smarty_cms_help_function_cms_queue_css()
{
	echo _ld('tags', 'help_generic',
	'This plugin records a styles-file for later accumulation into a single link element for a page-head.',
	'cms_queue_css file=&quot;path/to/whatever.css&quot;',
	'<li>file: full filesystem path of a .css file</li>
<li>priority: optional integer 0 (use default priority) or 1 (highest)...3</li>'
	);
	echo 'See also the complementary {cms_render_css} tag.';
}

function smarty_cms_about_function_cms_queue_css()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Robert Campbell Dec 2019', "<li>$n</li>");
}
