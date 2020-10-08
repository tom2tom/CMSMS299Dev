<?php
#Plugin to ...
#Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AppSingle;

function smarty_function_thumbnail_url($params, $template)
{
	$config = AppSingle::Config();
	$dir = $config['uploads_path'];
	$file = trim(get_parameter_value($params,'file'));
	$add_dir = trim(get_parameter_value($params,'dir'));
	$assign = trim(get_parameter_value($params,'assign'));

	if( !$file ) {
		trigger_error('thumbnail_url plugin: invalid file parameter');
		return;
	}

	if( $add_dir ) {
		if( startswith( $add_dir, '/') ) $add_dir = substr($add_dir,1);
		$test = $dir.'/'.$add_dir;
		if( !is_dir($test) || !is_readable($test) ) {
			trigger_error("thumbnail_url plugin: dir=$add_dir invalid directory name specified");
			return;
		}
	}

	$out = null;
	$file = 'thumb_'.$file;
	$fullpath = $dir.'/'.$file;
	if( is_file($fullpath) && is_readable($fullpath) ) {
		// convert it to a url
		$out = CMS_UPLOADS_URL.'/';
		if( $add_dir ) $out .= $add_dir.'/';
		$out .= $file;
	}

	if( $assign ) {
		$template->assign($assign,$out);
		return;
	}
	return $out;
}
