<?php
#Class for handling layout templates as a resource
#Copyright (C) 2004-2012 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2012-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
 * @copyright Copyright (c) 2012, Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since 1.12
 */
class layout_template_resource extends fixed_smarty_custom_resource
{
	private function &get_template($name)
	{
		$obj = CmsLayoutTemplate::load($name);
		$ret = new stdClass;
		$ret->modified = $obj->get_modified();
		$ret->content = $obj->get_content();
		return $ret;
	}

	/**
	 *
	 * @param string $name  resource-file path, optionally with trailing ';[section]'
	 * @param type $source  store for retrieved file content
	 * @param int $mtime    store for file modification timestamp
	 */
	protected function fetch($name, &$source, &$mtime)
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
			$tpl = $this->get_template($name);
			if( !is_object($tpl) ) return;
		}
		catch( Exception $e ) {
			cms_error('Missing template: '.$name);
			return;
		}

		$mtime = $tpl->modified;

        $section = $parts[1] ?? null;
		switch( trim($section) ) {
		case 'top':
			$pos1 = stripos($tpl->content,'<head');
			$pos2 = stripos($tpl->content,'<header');
			if( $pos1 === FALSE || $pos1 == $pos2 ) return;
			$source = trim(substr($tpl->content,0,$pos1));
			return;

		case 'head':
			$pos1 = stripos($tpl->content,'<head');
			$pos1a = stripos($tpl->content,'<header');
			$pos2 = stripos($tpl->content,'</head>');
			if( $pos1 === FALSE || $pos1 == $pos1a || $pos2 === FALSE ) return;
			$source = trim(substr($tpl->content,$pos1,$pos2-$pos1+7));
			return;

		case 'body':
			$pos = stripos($tpl->content,'</head>');
			if( $pos !== FALSE ) {
				$source = trim(substr($tpl->content,$pos+7));
			}
			else {
				$source = $tpl->content;
			}
			return;

		default:
			$source = trim($tpl->content);
			return;
		}
	}
} // class
