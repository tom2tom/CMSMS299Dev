<?php
#Class for handling layout templates as a resource
#Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS\internal;

use cms_utils;
use CmsLayoutTemplate;
use Exception;
use Smarty_Resource_Custom;
use stdClass;
use function cms_error;
use function startswith;

/**
 * A class for handling layout templates as a resource.
 *
 * Handles numeric and string template names, suffixes ;top ;head or ;body.
 *
 * @package CMS
 * @author Robert Campbell
 * @internal
 * @ignore
 *
 * @since 1.12
 */
class layout_template_resource extends Smarty_Resource_Custom
{
	/**
	 * @param string $name  resource-file path, optionally with trailing ';[section]'
	 * @param type $source  store for retrieved file content
	 * @param int $mtime    store for file modification timestamp
	 */
	protected function fetch($name,&$source,&$mtime)
	{
		if( $name == 'notemplate' ) {
			$source = '{content}';
			$mtime = time(); // never cache...
			return;
		}
		else if( startswith($name,'appdata;') ) {
			$name = substr($name,8);
			$source = cms_utils::get_app_data($name);
			$mtime = time();
			return;
		}

		$source = '';
		$mtime = 0;
		$parts = explode(';',$name,2);
		$name = $parts[0];

		try {
			$obj = LayoutTemplateOperations::load_template($name);
			if( $obj ) {
				$content = $obj->get_content();
				$mtime = $obj->get_modified();
			}
			else return;
		}
		catch( Exception $e ) {
			cms_error('Missing template: '.$name);
			return;
		}

		$section = $parts[1] ?? null;
		switch( trim($section) ) {
		case 'top':
			$pos1 = stripos($content,'<head');
			$pos2 = stripos($content,'<header');
			if( $pos1 === FALSE || $pos1 == $pos2 ) return;
			$source = trim(substr($content,0,$pos1));
			return;

		case 'head':
			$pos1 = stripos($content,'<head');
			$pos1a = stripos($content,'<header');
			$pos2 = stripos($content,'</head>');
			if( $pos1 === FALSE || $pos1 == $pos1a || $pos2 === FALSE ) return;
			$source = trim(substr($content,$pos1,$pos2-$pos1+7));
			return;

		case 'body':
			$pos = stripos($content,'</head>');
			if( $pos !== FALSE ) {
				$source = trim(substr($content,$pos+7));
			}
			else {
				$source = $content;
			}
			return;

		default:
			$source = trim($content);
			return;
		}
	}
} // class
