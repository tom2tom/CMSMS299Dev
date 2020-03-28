<?php
#Class for handling css content as a resource
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

use CmsApp;
use Smarty_Resource_Custom;
use const CMS_DB_PREFIX;
use function cms_error;
use function cms_to_stamp;
use function endswith;

/**
 * A class for handling database-sourced css content as a smarty resource.
 *
 * @package CMS
 * @author Robert Campbell
 * @internal
 * @ignore
 *
 * @since 1.12
 */
class layout_stylesheet_resource extends Smarty_Resource_Custom //fixed_smarty_custom_resource
{
    // static properties here >> StaticProperties class ?
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
     * @param string  $name    template identifier (name or id)
     * @param string  &$source store for retrieved template content, if any
     * @param int     &$mtime  store for retrieved template modification timestamp
     */
    protected function fetch($name,&$source,&$mtime)
    {
        // clean up the input
        $name = trim($name);
        if( !$name ) {
            $mtime = false;
            return;
        }

		if( isset(self::$loaded[$name]) ) {
			$data = self::$loaded[$name];
		}
		else {
			if( !self::$db ) {
				self::$db = CmsApp::get_instance()->GetDb();
				// TODO DT field for modified
				self::$stmt = self::$db->Prepare('SELECT id,name,content,contentfile,modified_date FROM '.CMS_DB_PREFIX.'layout_stylesheets WHERE id=? OR name=?');
			}
			$rst = self::$db->Execute(self::$stmt,[$name,$name]);
	    	if( !$rst || $rst->EOF() ) {
				if( $rst ) $rst->Close();
				cms_error('Missing stylesheet: '.$name);
	            $mtime = false;
				return;
			}
			else {
				$data = $rst->FetchRow();
				self::$loaded[$data['id']] = $data;
				self::$loaded[$data['name']] = &$data;
				$rst->Close();
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
        $text = '/* cmsms stylesheet: '.$name.' modified: '.strftime('%x %X',$mtime).' */'."\n".$data['content'];
        if( !endswith($text,"\n") ) $text .= "\n";
        $source = $text;
    }
} // class
