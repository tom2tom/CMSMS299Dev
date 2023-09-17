<?php
/*
Plugin to generate html for a textarea element
Copyright (C) 2004-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\DataException;
use CMSMS\FormUtils;

function smarty_function_cms_textarea($params, $template)
{
	if( empty($params['name']) ) throw new DataException('cms_textarea plugin missing parameter: name');

	$out = FormUtils::create_textarea($params);
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}

function smarty_cms_about_function_cms_textarea()
{
	echo _ld('tags', 'about_generic', 'Ted Kulp 2004',
	'<li>May 2019 Adapted to work with CMSMS 3.0 FormUtils::create_textarea()</li>'
	);
}

function smarty_cms_help_function_cms_textarea()
{
	echo '<h3>What does it do?</h3>
Generates html for a textarea element.
<h4>Parameters:</h4>
As for <code>FormUtils::create_textarea()</code><br>
<ul>
<li>name: element name (mandatory, but only relevant for form submission)</li>
<li>getid: submitted-parameter prefix (\'m1_\' etc)</li>
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
And/or Smarty generic parameters: nocache, assign etc';
}