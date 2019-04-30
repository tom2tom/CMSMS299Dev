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
use CmsApp;
use Smarty_Resource_Custom;
use const CMS_ASSETS_PATH;
use const CMS_DB_PREFIX;
use function cms_error;
use function cms_join_path;
use function cms_to_stamp;
use function startswith;

/**
 * A class for handling layout templates as a resource.
 *
 * Handles file- and database-sourced content, numeric and string template identifiers,
 * suffixes ;top ;head and/or ;body (whether or not such sections are relevant to the template).
 *
 * @package CMS
 * @internal
 * @ignore
 * @author Robert Campbell
 * @since 1.12
 */
class layout_template_resource extends Smarty_Resource_Custom
{
	/**
	 * @ignore
	 */
	private static $db;

	/**
	 * @ignore
	 * TODO cleanup when done
	 */
	private static $stmt;

	/**
	 * @ignore
	 */
	private static $loaded = [];

	/**
	 * @param string $name  template identifier (name or id), optionally with trailing ';[section]'
	 * @param mixed $source store for retrieved template content, if any
	 * @param int $mtime    store for retrieved template modification timestamp
	 */
	protected function fetch($name,&$source,&$mtime)
	{
		if( $name == 'notemplate' ) {
			$source = '{content}';
			$mtime = time(); // never cache...
			return;
		}
		elseif( startswith($name,'appdata;') ) {
			$name = substr($name,8);
			$source = cms_utils::get_app_data($name);
			$mtime = time();
			return;
		}

		$parts = explode(';',$name,2);
		$name = $parts[0];

		if( isset(self::$loaded[$name]) ) {
			$data = self::$loaded[$name];
		}
		else {
			if( !self::$db ) {
				self::$db = CmsApp::get_instance()->GetDb();
				self::$stmt = self::$db->Prepare('SELECT id,name,content,contentfile,modified_date FROM '.CMS_DB_PREFIX.'layout_templates WHERE id=? OR name=?');
			}
			$rst = self::$db->Execute(self::$stmt,[$name,$name]);
			if( !$rst || $rst->EOF() ) {
				if( $rst ) $rst->Close();
				cms_error('Missing template: '.$name);
				$mtime = false;
				return;
			}
			else {
				$data = $rst->FetchRow();
				$rst->Close();
				self::$loaded[$data['id']] = $data;
				self::$loaded[$data['name']] = &$data;
				if( $data['contentfile'] ) {
					$fp = cms_join_path(CMS_ASSETS_PATH,'templates',$data['content']);
					$lvl = error_reporting();
					error_reporting(0);
					if( is_readable($fp) && is_file($fp) ) {
						$data['content'] = file_get_contents($fp);
					}
					else {
						$data['content'] = "'{* Template file $fp is missing *}";
					}
					error_reporting($lvl);
				}
			}
		}

		if( !empty($data['modified_date']) ) {
			$mtime = cms_to_stamp($data['modified_date']);
		}
		elseif( !empty($data['create_date']) ) {
			$mtime = cms_to_stamp($data['create_date']);
		}
		else {
			$mtime = 1; // not falsy
		}
		$content = $data['content'];

		$section = $parts[1] ?? '';
		switch( trim($section) ) {
		case 'top':
			$pos1 = stripos($content,'<head');
			$pos2 = stripos($content,'<header');
			if( $pos1 === FALSE || $pos1 == $pos2 ) {
				$source = '';
			}
			else {
				$source = trim(substr($content,0,$pos1));
			}
			return;

		case 'head':
			$pos1 = stripos($content,'<head');
			$pos1a = stripos($content,'<header');
			$pos2 = stripos($content,'</head>');
			if( $pos1 === FALSE || $pos1 == $pos1a || $pos2 === FALSE ) {
				$source = '';
			}
			else {
				$source = trim(substr($content,$pos1,$pos2-$pos1+7));
			}
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
