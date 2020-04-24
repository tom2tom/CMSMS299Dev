<?php
#Plugin to generate html for a textarea element
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

use CMSMS\FormUtils;

function smarty_function_cms_textarea($params, $template)
{
	if( !isset($params['name']) ) throw new CmsInvalidDataException('cms_textarea plugin missing parameter: name');

	$out = FormUtils::create_textarea($params);
	if( isset($params['assign']) ) {
		$template->assign(trim($params['assign']),$out);
		return;
	}
	return $out;
}

function smarty_cms_help_function_cms_textarea()
{
	echo <<<'EOS'
<h3>What does it do?</h3>
Generates html for a textarea element.
<h4>Parameters:</h4>
As for <code>FormUtils::create_textarea()</code><br />
<ul>
<li>name: element name (mandatory, but only relevant for form submission)</li>
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
As always<br />
assign
EOS;
}

function smarty_cms_about_function_cms_textarea()
{
	echo <<<'EOS'
<p>Initial release 2004</p>
<p>Change History:<br />
<ul>
<li>Adapted to work with CMSMS 2.3 FormUtils::create_textarea() May 2019</li>
</ul>
</p>
EOS;
}
