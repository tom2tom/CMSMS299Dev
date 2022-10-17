<?php
/*
Plugin to generate an anchor-element, or an URL-only, for a fragment on the current page.
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Lone;

function smarty_function_anchor($params, $template)
{
	$to = (isset($params['anchor'])) ? trim($params['anchor']) : '';
	if ($to === '') {
		return '<!-- anchor tag: no anchor provided -->';
	}

	$url = '';
	if (!empty($_SERVER['QUERY_STRING'])) {
		//$_SERVER['QUERY_STRING'] like 'page=news/99/107/somename'
		$config = Lone::get('Config');
		$tmp = $config['query_var'].'=';
		$path = str_ireplace($tmp, '', $_SERVER['QUERY_STRING']); // TODO sanitize $_SERVER value somewhere
		if ($path != $_SERVER['QUERY_STRING']) {
			$url = $config['root_url'].'/'.trim($path, ' /');
		}
	}
	if (!$url) {
		//this is useless for runtime-populated pages e.g. News details
		$content = cmsms()->get_content_object();
		if (!is_object($content)) {
			return ''; // never any assignment ?
		}
		$url = $content->GetURL();
	}
	$url = preg_replace('/&(?!amp;)/', '&amp;', $url.'#'.rawurlencode($to));

	if (!empty($params['onlyhref']) && cms_to_bool($params['onlyhref'])) {
		$tmp = $url;
	} else {
		$class = '';
		if (isset($params['class']) && $params['class'] !== '') {
			$class = ' class="'.$params['class'].'"';
		}
		$title = '';
		if (isset($params['title']) && $params['title'] !== '') {
			$title = ' title="'.$params['title'].'"';
		}
		$tabindex = '';
		if (isset($params['tabindex']) && $params['tabindex'] !== '') {
			$tabindex = ' tabindex="'.(int)$params['tabindex'].'"';
		}
		$accesskey = '';
		if (isset($params['accesskey']) && $params['accesskey'] !== '') {
			$accesskey = ' accesskey="'.$params['accesskey'].'"';
		}
		$text = trim($params['text'] ?? '');
		if ($text === '') {
			$text = htmlentities($to).'<!-- anchor tag: no text provided -->';
		}
		$tmp = '<a href="'.$url.'"'.$class.$title.$tabindex.$accesskey.'>'.$text.'</a>';
	}

	if (!empty($params['assign'])) {
		$template->assign(trim($params['assign']), $tmp);
		return '';
	}
	return $tmp;
}

function smarty_cms_about_function_anchor()
{
	$s = '<li>Oct 2022 generate URL from $_SERVER to support runtime-populated pages</li>';
	echo _ld('tags', 'about_generic', 'Ted Kulp 2004', $s);
}

function smarty_cms_help_function_anchor()
{
	echo _ld('tags', 'help_generic',
	'This plugin generates an anchor-element, or an URL-only, for a fragment on the current page',
	'anchor ...',
	'<li>(optional)class: name of class(es) to be applied to the element</li>
<li>(optional)title: title attribute of the element</li>
<li>(optional)tabindex: tabindex attribute for the element</li>
<li>(optional)accesskey: access key attribute for the element</li>
<li>(optional)onlyhref: whether to get only the href Default false</li>
<li>text: Displayed link text</li>'
	);
}
