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

function smarty_function_anchor($params, $template)
{
	$content = cms_utils::get_current_content();
	if( !is_object($content) ) return;

	$class='';
	$title='';
	$tabindex='';
	$accesskey='';
	if (isset($params['class'])) $class = ' class="'.$params['class'].'"';
	if (isset($params['title'])) $title = ' title="'.$params['title'].'"';
	if (isset($params['tabindex'])) $tabindex = ' tabindex="'.$params['tabindex'].'"';
	if (isset($params['accesskey'])) $accesskey = ' accesskey="'.$params['accesskey'].'"';

	$url = $content->GetURL().'#'.trim($params['anchor']);
	$url = str_replace('&amp;','***',$url);
	$url = str_replace('&', '&amp;', $url);
	$url = str_replace('***','&amp;',$url);

	if (isset($params['onlyhref']) && cms_to_bool($params['onlyhref'])) {
		$tmp =  $url;
	}
	else {
		$text = get_parameter_value( $params, 'text','<!-- anchor tag: no text provided -->anchor');
		$tmp = '<a href="'.$url.'"'.$class.$title.$tabindex.$accesskey.'>'.$text.'</a>';
	}

	if (isset($params['assign'])){
		$template->assign(trim($params['assign']),$tmp);
		return;
	}
	return $tmp;
}

