<?php
/*
Plugin to retrieve the URL of an uploaded thumbnail file specified among the supplied params
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\SingleItem;

function smarty_function_thumbnail_url($params, $template)
{
	$file = trim($params['file'] ?? '');
	if( !$file ) {
		trigger_error('thumbnail_url plugin: invalid file parameter');
		return '';
	}

	$dir = SingleItem::Config()['uploads_path'];
	$add_dir = trim(($params['dir'] ?? ''), ' \/');

	if( $add_dir ) {
		$test = $dir.DIRECTORY_SEPARATOR.$add_dir;
		if( !is_dir($test) || !is_readable($test) ) {
			trigger_error("thumbnail_url plugin: dir=$add_dir invalid directory name specified");
			return '';
		}
	}

	$out = '';
	$file = 'thumb_'.$file;
	$fullpath = $dir.DIRECTORY_SEPARATOR.$file;
	if( is_file($fullpath) && is_readable($fullpath) ) {
		// convert to URL
		$out = CMS_UPLOADS_URL.'/';
		if( $add_dir ) $out .= strtr($add_dir, '\\', '/') . '/';
		$out .= $file;
	}
	else {
		trigger_error("thumbnail_url plugin: invalid file $fullpath specified");
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_thumbnail_url()
{
	echo _ld('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
EOS
	);
}
*/
/*
D function smarty_cms_help_function_thumbnail_url()
{
	echo _ld('tags', 'help_generic',
	'This plugin retrieves the URL of an uploaded thumbnail file',
	'thumbnail_url file=whatever',
	<<<'EOS'
<li>file: name of wanted file</li>
<li>dir: optional uploads-folder-relative filepath where the file is stored</li>
EOS
	);
}
*/
