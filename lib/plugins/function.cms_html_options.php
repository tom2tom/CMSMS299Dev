<?php
/*
Plugin to generate a single- or multi-select element
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\FormUtils;

function smarty_function_cms_html_options($params, $template)
{
	if( isset($params['options']) ) {
		$options = $params['options'];
	}
	elseif( isset($params['value']) && isset($params['label']) ) {
		$options = [
			'label' => $params['label'],
			'value' => $params['value'],
		];
		if( isset($params['title']) ) $options['title'] = $params['title'];
		if( isset($params['class']) ) $options['class'] = $params['class'];
	}
	else {
		return '';
	}

	$out = '';
	if( $options ) {
		$selected = null;
		if( isset($params['selected']) ) {
			$selected = $params['selected'];
			if( !is_array($selected) ) $selected = explode(',', $selected);
		}
		$out = FormUtils::create_option($params['options'], $selected);
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_cms_html_options()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Robert Campbell 2013', "<li>$n</li>");
}
*/
function smarty_cms_help_function_cms_html_options()
{
	//TODO missing param descriptors
	echo _ld('tags', 'help_generic',
	'This plugin generates a single- or multi-select element',
	'cms_html_options ...',
	'<li>options: optional array of parameters to pass to FormUtils::create_option()</li>
<li>value: </li>
<li>label: </li>
<li>title: optional element title-attribute</li>
<li>class: optional element class(es)-attribute</li>
<li>selected: optional array or comma-separated string of value(s) to be initially selected</li>'
	);
}