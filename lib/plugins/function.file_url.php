<?php
/*
Plugin to generate the URL of an uploaded file.
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

use CMSMS\Lone;

function smarty_function_file_url($params, $template)
{
	$file = trim($params['file'] ?? '');
	if( !$file ) {
		trigger_error('file_url plugin: invalid file parameter');
		return '';
	}

	$dir = Lone::get('Config')['uploads_path'];
	$add_dir = trim(($params['dir'] ?? ''), ' \/');

	if( $add_dir ) {
		$add_dir = strtr($add_dir, '\/', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
		$dir .= DIRECTORY_SEPARATOR.$add_dir;
		if( !is_dir($dir) || !is_readable($dir) ) {
			trigger_error("file_url plugin: dir=$add_dir invalid directory name specified");
			return '';
		}
	}

	$fullpath = $dir.DIRECTORY_SEPARATOR.$file;
	if( !is_file($fullpath) || !is_readable($fullpath) ) {
		// no error log here
		return '';
	}

	// convert to an URL
	$out = CMS_UPLOADS_URL.'/';
	if( $add_dir ) $out .= $add_dir . '/';
	$out .= $file;
	$out = strtr($out, '\\', '/');
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}

function smarty_cms_about_function_file_url()
{
	echo _ld('tags', 'about_generic', 'Ted Kulp 2004','<li>'._la('none').'</li>');
}

function smarty_cms_help_function_file_url()
{
	echo _ld('tags', 'help_generic',
	'This plugin retrieves the URL of an uploaded file',
	'file_url ...',
	'<li>file: name of wanted file</li>
<li>dir: optional uploads-folder-relative filepath of directory containing the wanted file</li>'
	);
}