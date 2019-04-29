<?php
#Class for handling module templates as a resource
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
use DateTime;
use Smarty_Resource_Custom;
use const CMS_DB_PREFIX;
use function cms_error;

/**
 * A class for handling module templates as a resource.
 *
 * @package CMS
 * @internal
 * @ignore
 * @author Robert Campbell
 * @since 1.11
 */
class module_db_template_resource extends Smarty_Resource_Custom
{
    /**
     * @ignore
     */
    private static $db;

    /**
     * @ignore
     */
    private static $stmt;

    /**
     * @ignore
     */
    private static $loaded = [];

    /**
     * @param string $name    template identifier like 'modulename;templatename'
     * @param mixed  &$source store for retrieved template content, if any
     * @param int    &$mtime  store for retrieved template modification timestamp
     */
    protected function fetch($name,&$source,&$mtime)
    {
        if( isset(self::$loaded[$name]) ) {
            $data = self::$loaded[$name];
        }
        else {
            if( !self::$db ) {
                self::$db = CmsApp::get_instance()->GetDb();
                self::$stmt = self::$db->Prepare('SELECT content,modified_date FROM '.CMS_DB_PREFIX.'layout_templates WHERE originator=? AND name=?');
            }
            $parts = explode(';',$name);
            $rst = self::$db->Execute(self::$stmt,$parts);
            if( !$rst || $rst->EOF() ) {
                if( $rst ) $rst->Close();
                cms_error('Missing template: '.$name);
                $mtime = false;
                return;
            }
            else {
                $data = $rst->FetchRow();
                $rst->Close();
                self::$loaded[$name] = $data;
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
        $source = $data['content'];
   }
} // class
