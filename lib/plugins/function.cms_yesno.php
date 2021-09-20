<?php
/*
Plugin to generate the contents of a 2-item selector element.
Copyright (C) 2018-2021 CMS Made Simnple Foundation <foundatio@cmsmadesimple.org>

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

function smarty_function_cms_yesno($params, $template)
{
	$opts = [lang('no'),lang('yes')];

	$out = '';
	foreach( $opts as $k => $v ) {
		$out .= '<option value="'.$k.'"';
		if( isset($params['selected']) && $k == $params['selected'] ) $out .= ' selected="selected"';
		$out .= '>'.$v.'</option>';
	}
	$out .= "\n";

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_cms_yesno()
{
	echo _ld('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
EOS
	);
}
*/
/*
function smarty_cms_help_function_cms_yesno()
{
	echo _ld('tags', 'help_generic', 'This plugin does ...', 'cms_yesno ...', <<<'EOS'
<li>selected</li>
EOS
	);
}
*/
