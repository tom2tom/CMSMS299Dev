<?php
#Plugin to generate html and js for a syntax highlight textarea
#Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\FormUtils;

function smarty_function_syntax_area($params, $template)
{
	$parms = array_intersect_key($params,[
		'name'=>1,
		'modid'=>1,
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

	$elemid = $parms['id'] ?? $parms['htmlid'] ?? 'work_area';
	unset($parms['id']);
	$parms['htmlid'] = $elemid;
	$s = '';
	if( !empty($parms['rows']) ) $s .= 'height:'.(int)$parms['rows'].'em;';
	elseif( !empty($parms['height']) )$s .= 'height:'.(int)$parms['height'].'em;';
	if( !empty($parms['cols']) ) $s .= 'width:'.(int)$parms['cols'].'em;';
	elseif( !empty($parms['width']) ) $s .= 'width:'.(int)$parms['width'].'em;';
	$s = 'style="'.$s.'min-height:2em;"';
	$parms['addtext'] = ( isset($parms['addtext']) ) ? $parms['addtext'].' '.$s : $s;
	$t = $params['typer'] ?? '';
	if( $t ) {
		$tt = basename($t);
		$p = strrpos($tt, '.');
		$tt = substr($tt, ($p !== false) ? $p+1:0);
		$parms['type'] = strtolower($tt);
	}

	$out = FormUtils::create_textarea($parms);

	$parms = array_intersect_key($params,[
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

	$jscript = get_editor_script($parms);
	if( $jscript ) {
		if( CmsApp::get_instance()->test_state(CmsApp::STATE_ADMIN_PAGE) ) {
			$theme = cms_utils::get_theme_object();
			if( !empty($jscript['head']) ) {
				$theme->add_headtext($jscript['head']); // css
			}
			if( !empty($jscript['foot']) ) {
				$theme->add_footertext($jscript['foot']);
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
				$out .= <<<EOS
<script defer type="text/javascript">
//<![CDATA[
{$jscript['foot']}
//]]>
</script>
EOS;
			}
		}
	}

	if( isset($params['assign']) ) {
		$template->assign(trim($params['assign']),$out);
		return;
	}
	return $out;
}

function smarty_cms_help_function_syntax_area()
{
	echo <<<'EOS'
<h3>What does it do?</h3>
Generates html and js for a syntax-highlight textarea element.
<h4>Parameters:</h4>
As for <code>FormUtils::create_textarea()</code><br />
<ul>
<li>name: element name (only relevant for form submission, but the backend method always wants it)</li>
<li>modid: submitted-parameter prefix ('m1_' etc)</li>
<li>prefix: alias for modid</li>
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
<br />
As for <code>get_editor_script()</code><br />
<ul>
<li>edit: bool whether editable (default) or read-only</li>
<li>handle: js variable identifier (optional, internal use) </li>
<li>htmlid: (same as above)</li>
<li>theme: name of editor theme to use instead of the default (optional)</li>
<li>typer: syntax identifier</li>
<li>workid: div-tag id (optional, internal use)</li>
</ul>
<br />
As always<br />
assign
EOS;
}

function smarty_cms_about_function_syntax_area()
{
	echo <<<'EOS'
<p>Initial release May 2019</p>
<p>Change History:<br />
<ul>
</ul>
</p>
EOS;
}
