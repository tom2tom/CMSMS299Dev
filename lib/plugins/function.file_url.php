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

use CMSMS\AppSingle;

function smarty_function_file_url($params, $template)
{
	$file = trim(get_parameter_value($params,'file'));
	if( !$file ) {
		trigger_error('file_url plugin: invalid file parameter');
		return;
	}

	$config = AppSingle::Config();
	$dir = $config['uploads_path'];
	$add_dir = trim(get_parameter_value($params,'dir'));

	if( $add_dir ) {
		if( startswith($add_dir,DIRECTORY_SEPARATOR) ) $add_dir = substr($add_dir,1);
		$dir .= DIRECTORY_SEPARATOR.$add_dir;
		if( !is_dir($dir) || !is_readable($dir) ) {
			trigger_error("file_url plugin: dir=$add_dir invalid directory name specified");
			return;
		}
	}

	$fullpath = $dir.DIRECTORY_SEPARATOR.$file;
	if( !is_file($fullpath) || !is_readable($fullpath) ) {
		// no error log here.
		return;
	}

	// convert it to a url
	$out = CMS_UPLOADS_URL.'/';
	if( $add_dir ) $out .= $add_dir.'/';
	$out .= $file;
	$out = strtr($out,'\\','/');

	if( isset($params['assign']) ) {
		$template->assign(trim($params['assign']),$out);
		return;
	}
	return $out;
}

