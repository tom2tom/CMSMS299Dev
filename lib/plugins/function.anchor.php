<?php
/*
Plugin to generate an anchor-element, or an URL-only, for a fragment on the current page.
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

function smarty_function_anchor($params, $template)
{
	$content = CMSMS\SingleItem::App()->get_content_object();
	if( !is_object($content) ) return '';

	$class = '';
	$title = '';
	$tabindex = '';
	$accesskey = '';
	if( !empty($params['class']) ) $class = ' class="'.$params['class'].'"';
	if( !empty($params['title']) ) $title = ' title="'.$params['title'].'"';
	if( !empty($params['tabindex']) ) $tabindex = ' tabindex="'.$params['tabindex'].'"';
	if( !empty($params['accesskey']) ) $accesskey = ' accesskey="'.$params['accesskey'].'"';

	$url = $content->GetURL().'#'.trim($params['anchor']);
//	$url = str_replace('&amp;','***',$url);
//	$url = str_replace('&', '&amp;', $url);
//	$url = str_replace('***','&amp;',$url);

	if( isset($params['onlyhref']) && cms_to_bool($params['onlyhref']) ) {
		$tmp = $url;
	}
	else {
		$text = $params['text'] ?? '<!-- anchor tag: no text provided -->anchor';
		$tmp = '<a href="'.$url.'"'.$class.$title.$tabindex.$accesskey.'>'.$text.'</a>';
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $tmp);
		return '';
	}
	return $tmp;
}
/*
function smarty_cms_about_function_anchor()
{
	echo _ld('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
EOS
	);
}
*/
/*
function smarty_cms_help_function_anchor()
{
	echo _ld('tags', 'help_generic', 'This plugin does ...', 'anchor ...', <<<'EOS'
<li>class</li>
<li>title</li>
<li>tabindex</li>
<li>accesskey</li>
<li>onlyhref</li>
<li>text</li>
EOS
	);
}
*/
