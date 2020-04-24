<?php
#Plugin to...
#Copyright (C) 2013-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

function smarty_function_cms_html_options($params, $template)
{
	$options = null;
	if( !isset($params['options']) ) {
		if( isset($params['value']) && isset($params['label']) ) {
			$opt = [];
			$opt['label'] = $params['label'];
			$opt['value'] = $params['value'];
			if( isset($params['title']) ) $opt['title'] = $params['title'];
			if( isset($params['class']) ) $opt['class'] = $params['class'];
			$options = $opt;
		}
		else {
			return;
		}
	}
	else {
		$options = $params['options'];
	}

	$out = null;
	if( $options ) {
		$selected = null;
		if( isset($params['selected']) ) {
			$selected = $params['selected'];
			if( !is_array($selected) ) $selected = explode(',',$selected);
		}
		$out = CMSMS\FormUtils::create_option($params['options'],$selected);
	}

	if( isset($params['assign']) ) {
		$template->assign(trim($params['assign']),$out);
		return;
	}
	return $out;
}
