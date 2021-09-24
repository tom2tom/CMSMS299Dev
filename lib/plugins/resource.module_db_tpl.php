<?php
/*
Class for handling db-stored module templates as a resource
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
use function CMSMS\log_error;
use function CMSMS\sanitizeVal;

/**
 * Class for handling db-stored module templates as a resource.
 *
 * @package CMS
 * @internal
 * @ignore
 * @since 1.11
 */
class Smarty_Resource_module_db_tpl extends Smarty_Resource_Custom
{
    // static properties here >> SingleItem ?
    /**
     * @var array intra-request cache of used templates, each member like
     *  name => [ 'content' => whatever, 'modified' => non-0 timestamo ]
     * @ignore
     */
    private static $loaded = [];

    public function populate(Smarty_Template_Source $source, Smarty_Internal_Template $_template = null)
    {
        parent::populate($source, $_template);
        if ($source->exists) {
            $source->filepath = $source->type . ':' . sanitizeVal(strtr($source->name, [';'=>'_']), CMSSAN_FILE);
        }
    }

    /**
     * @param string $name    template identifier like 'modulename;templatename.tpl'
     * @param mixed  &$source store for retrieved template content, if any
     * @param int    &$mtime  store for retrieved template modification timestamp, if $source is populated
     */
    protected function fetch($name,&$source,&$mtime)
    {
        if( isset(self::$loaded[$name]) ) {
            $source = self::$loaded[$name]['content'];
            $mtime = self::$loaded[$name]['modified'];
        }
        else {
            $parts = explode(';',$name);
            if( count($parts) != 2 ) {
                return;
            }
            $db = SingleItem::Db();
            $stmt = $db->prepare('SELECT content,COALESCE(modified_date,create_date,\'2000-1-1 00:00:01\') AS modified FROM '.CMS_DB_PREFIX.'layout_templates WHERE originator=? AND name=?');
            $rst = $db->execute($stmt,$parts);
            if( !$rst || $rst->EOF() ) {
                if( $rst ) $rst->Close();
                $stmt->close();
                log_error('Missing template',$name);
            }
            else {
                $data = $rst->FetchRow();
                $rst->Close();
                $stmt->close();
                if( $data['content'] ) {
                    $data['modified'] = cms_to_stamp($data['modified']);
                    self::$loaded[$name] = $data;
                    $source = $data['content'];
                    $mtime = $data['modified'];
                    return;
                }
                log_error('Empty template',$name);
            }
        }
   }
} // class
