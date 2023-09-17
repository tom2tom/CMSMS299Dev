<?php
/*
Plugin to generate html and js for a syntax highlight textarea
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

//use CMSMS\Lone;
use CMSMS\AppState;
use CMSMS\FormUtils;

function smarty_function_syntax_area($params, $template)
{
	$parms = array_intersect_key($params, [
		'name'=>1,
		'getid'=>1,
		'actionid'=>1,
		'prefix'=>1,
		'id'=>1,
		'htmlid'=>1,
		'class'=>1,
		'classname'=>1,
		'cols'=>1,
		'width'=>1,
		'rows'=>1,
		'height'=>1,
		'maxlength'=>1,
		'value'=>1,
		'text'=>1,
		'addtext'=>1,
	]);

	if (empty($parms['name'])) $parms['name'] = 'editor_content';
	$elemid = $parms['id'] ?? $parms['htmlid'] ?? 'work_area';
	unset($parms['id']);
	$parms['htmlid'] = $elemid;
	if (!empty($parms['actionid'])) $parms['getid'] = $parms['actionid'];
	unset($parms['actionid']);
	$s = '';
	if( !empty($parms['rows']) ) $s .= 'height:'.(int)$parms['rows'].'em;';
	elseif( !empty($parms['height']) )$s .= 'height:'.(int)$parms['height'].'em;';
	if( !empty($parms['cols']) ) $s .= 'width:'.(int)$parms['cols'].'em;';
	elseif( !empty($parms['width']) ) $s .= 'width:'.(int)$parms['width'].'em;';
	$s = 'style="'.$s.'min-height:2em;"';
	$parms['addtext'] = ( !empty($parms['addtext']) ) ? $parms['addtext'].' '.$s : $s;
	$t = $params['typer'] ?? '';
	if( $t ) {
		$tt = basename($t);
		$p = strrpos($tt, '.');
		$tt = substr($tt, ($p !== false) ? $p+1:0);
		$parms['type'] = strtolower($tt);
	}

	$out = FormUtils::create_textarea($parms);

	$parms = array_intersect_key($params, [
		'edit'=>1,
		'handle'=>1,
		'theme'=>1,
		'typer'=>1,
		'workid'=>1,
	]) + [
		'edit'=>true,
		'htmlid'=>$elemid,
		'typer'=>$t,
	];

	$jscript = get_syntaxeditor_setup($parms);
	if( $jscript ) {
		if( AppState::test(AppState::ADMIN_PAGE) ) {
//			$themeObject = Lone::get('Theme');
			if( !empty($jscript['head']) ) {
				add_page_headtext($jscript['head']); // css ?
			}
			if( !empty($jscript['foot']) ) {
				add_page_foottext($jscript['foot']);
			}
		}
		else {
			if( !empty($jscript['head']) ) {
				$out = <<<EOS
{$jscript['head']}
$out
EOS;
			}
			if( !empty($jscript['foot']) ) {
//				$nonce = get_csp_token();
				$out .= <<<EOS
<script defer>
{$jscript['foot']}
</script>
EOS;
			}
		}
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}

function smarty_cms_about_function_syntax_area()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'May 2019', "<li>$n</li>");
}

function smarty_cms_help_function_syntax_area()
{
	echo '<h3>What does it do?</h3>
This plugin generates html and javascript for a syntax-highlight textarea element.
<h4>Parameters:</h4>
As for <code>FormUtils::create_textarea()</code><br>
<ul>
<li>name: element name (only relevant for form submission, but the backend method always wants it)</li>
<li>getid: submitted-parameter prefix (\'m1_\' etc)</li>
<li>actionid: alias for getid</li>
<li>prefix: alias for getid</li>
<li>id: id for the created element id="whatever"</li>
<li>htmlid: alias for id</li>
<li>class: class name(s) to apply to the element</li>
<li>classname: alias for class</li>
<li>cols: number initial size</li>
<li>width: alias for cols</li>
<li>rows: number initial size</li>
<li>height: alias for rows</li>
<li>maxlength: content length-limit</li>
<li>value: initial content</li>
<li>text: alias for value</li>
<li>addtext: additional attribute(s) for the element e.g. style="whatever" cms-data-X="whatever" readonly</li>
</ul>
<br>
As for <code>get_syntaxeditor_setup()</code><br>
<ul>
<li>edit: bool whether editable (default) or read-only</li>
<li>handle: js variable identifier (optional, internal use) </li>
<li>htmlid: (same as above)</li>
<li>theme: name of editor theme to use instead of the default (optional)</li>
<li>typer: syntax identifier</li>
<li>workid: div-tag id (optional, internal use)</li>
</ul>
<br>
and/or any of Smarty\'s generic parameters (nocache, assign etc)';
}