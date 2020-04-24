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

function smarty_function_setlist($params, $template)
{
	$newlist = [];

	if (!isset($params['var']))
	{
		$params['var']='var';
	}

	if (substr($params['value'],0,1) != '{')
	{
		$params['value'] = '{'.$params['value'];
	}

	$opens = substr_count($params['value'], '{');
	$closes =  substr_count($params['value'], '}');
	while ($closes < $opens)
	{
		$params['value'] = $params['value'].'}';
		$closes += 1;
	}

	$newlist = json_decode($params['value'],true);
	$template->assign($params['var'],$newlist);
}

function smarty_cms_help_function_setlist()
{
	echo <<<'EOS'
<p>Populate arrays directly in your template, e.g.:</p>
<pre>
	{setlist var='varname' value='"red":"#f00","green":"#0f0","blue":"#00f","violet":"#f0f","yellow":"#ff0"'}
	{foreach from=$varname key=color item=colorcode}
		{$color} is {$colorcode}<br />
	{/foreach}
</pre>
<p>It uses JSON syntax (with implicit curly-brace wrappers), so you can do crazy stuff if you choose to:</p>
<pre>
	{capture assign="json_sample_struct"}"layered":{ldelim}"bar":"baz"{rdelim},"flat":"blank","layered2":{ldelim}"qux":"quux","crox":"bagg"{rdelim}{/capture}
	{setlist var='nested' value=$json_sample_struct}
</pre>
<p>This is useful for setting up lists of similar structure in your CSS.</p>
EOS;
}

function smarty_cms_about_function_setlist()
{
	echo <<<'EOS'
<p>Author: SjG</p>
<p>Change History:</p>
<ul>
 <li>None</li>
</ul>
EOS;
}
