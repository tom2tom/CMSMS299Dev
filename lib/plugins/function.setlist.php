<?php
/*
Plugin to migrate the content of a JSON-encoded string to a corresponding array of individual values.
Copyright (C) 2011-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

function smarty_function_setlist($params, $template)
{
	if (empty($params['var'])) {
		$params['var'] = 'var';
	}

	if (substr($params['value'], 0, 1) != '{') {
		$params['value'] = '{'.$params['value'];
	}

	$opens = substr_count($params['value'], '{');
	$closes = substr_count($params['value'], '}');
	while ($closes < $opens) {
		$params['value'] .= '}';
		$closes++;
	}

	$newlist = json_decode($params['value'], true);
	$template->assign($params['var'], $newlist);
}

function smarty_cms_help_function_setlist()
{
	echo _ld('tags', 'help_generic',
	'This plugin populates an array within a template',
	'setlist var=\'varname\' value=\'{ldelim}"red":"#f00","green":"#0f0","blue":"#00f"{rdelim}\'}
{foreach $varname as $color=>$colorcode}
 {$color} is {$colorcode}
{/foreach}
</code></pre><br>
It uses JSON syntax (with implicit curly-brace wrappers), so you can do crazy stuff:
<pre><code>
{capture assign="json_sample_struct"}"layered":{ldelim}"bar":"baz"{rdelim},"flat":"blank","layered2":{ldelim}"qux":"quux","crox":"bagg"{rdelim}{/capture}
{setlist var=\'nested\' value=$json_sample_struct',
	'<li>var: name of variable to which the array will be assigned. Default \'var\'</li>
<li>value: string which json_decode() can transform into an array</li>'
	);
}

function smarty_cms_about_function_setlist()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'SjG 2011', "<li>$n</li>");
}
