<?php
/*
Plugin to generate a single- or multi-select element
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
		$out = CMSMS\FormUtils::create_option($params['options'], $selected);
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
	echo _ld('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
EOS
	);
}
*/
/*
function smarty_cms_help_function_cms_html_options()
{
	echo _ld('tags', 'help_generic', 'This plugin does ...', 'cms_html_options ...', <<<'EOS'
<li>options</li>
<li>value</li>
<li>label</li>
<li>title</li>
<li>class</li>
<li>selected</li>
EOS
	);
}
*/
