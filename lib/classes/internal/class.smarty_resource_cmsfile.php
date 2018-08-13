<?php
#Class for file resources with extra conditions
#Copyright (C) 2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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

namespace CMSMS\internal;

use function startswith;

/**
 * A resource like the standard file: resource in smarty but the file must be
 * located in or below one of the template directories (no absolute filenames,
 * or ../ stuff to get out of it) and the file cannot be hidden (dot-prefix).
 *
 * It also supports the ;top ;head and ;body section suffixes to process
 * only a portion of the template.
 * @since 2.3
 */
class smarty_resource_cmsfile extends fixed_smarty_custom_resource
{
	/**
	 *
	 * @param string $name  resource-file path, optionally with trailing ';[section]'
	 * @param type $source  store for retrieved file content
	 * @param int $mtime    store for file modification timestamp
	 */
    protected function fetch(string $name, &$source, &$mtime)
    {
        $source = null;
        $parts = explode(';',$name);
        $name = $parts[0];

        // has to be a non-hidden file.
        $bn = basename( $name );
        if( startswith($bn, '.') ) return;

        // has to be a file in or below a template directory.
		$found = false;
        $_p_dirs = $this->smarty->getTemplateDir();
        foreach( $_p_dirs as $dir ) {
            $fn = $dir.$name; //$dir has trailing separator
            if( !is_file($fn) ) continue;

            // now verify that it is below $dir
            $real_file = realpath($fn);
            $real_dir = realpath($dir);
            if( $real_file && startswith( $real_file, $real_dir ) ) {
		        $content = file_get_contents( $real_file );
				$mtime = filemtime( $real_file );
				$found = true;
				break;
			}
        }
        if( !$found ) return;

        $section = $parts[1] ?? '';
        switch( trim($section) ) {
        case 'top':
			$pos1 = stripos($content,'<head');
			$pos2 = stripos($content,'<header');
			if( $pos1 === false || $pos1 == $pos2 ) return;
			$source = trim(substr($content,0,$pos1));
            break;

        case 'head':
			$pos1 = stripos($content,'<head');
			$pos1a = stripos($content,'<header');
			$pos2 = stripos($content,'</head>');
			if( $pos1 === false || $pos1 == $pos1a || $pos2 === false ) return;
			$source = trim(substr($content,$pos1,$pos2-$pos1+7));
            break;

        case 'body':
			$pos = stripos($content,'</head>');
			if( $pos !== false ) {
				$source = trim(substr($content,$pos+7));
			}
			else {
				$source = $content;
			}
            break;

        default:
            $source = $content;
            break;
        }
    }
} // class
