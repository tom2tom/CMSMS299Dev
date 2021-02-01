<?php
/*
Class for handling layout stylesheets as a resource
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

use CMSMS\AppSingle;
//use Smarty_Resource_Custom;
//use Throwable;
//use const CMS_ASSETS_PATH;
//use const CMS_DB_PREFIX;
//use function cms_error;
//use function cms_join_path;
//use function cms_to_stamp;
//use function endswith;

/**
 * A class for handling db- and file-stored css content as a smarty resource.
 *
 * @package CMS
 * @author Robert Campbell
 * @internal
 * @ignore
 *
 * @since 1.12
 */
class Smarty_Resource_cms_stylesheet extends Smarty_Resource_Custom
{
    // static properties here >> StaticProperties class ?
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
     * @param string  $name    template identifier (name or id)
     * @param string  &$source store for retrieved template content, if any
     * @param int     &$mtime  store for retrieved template modification timestamp
     */
    protected function fetch($name,&$source,&$mtime)
    {
        // clean up the input
        $name = trim($name);
        if( is_numeric($name) ) {
            $name = 0 + $name;
        }
        elseif( !$name ) {
            $mtime = false;
            return;
        }

        if( isset(self::$loaded[$name]) ) {
            $data = self::$loaded[$name];
        }
        else {
            if( !self::$db ) {
                self::$db = AppSingle::Db();
                self::$stmt = self::$db->Prepare('SELECT id,name,content,contentfile,modified_date FROM '.CMS_DB_PREFIX.'layout_stylesheets WHERE id=? OR name=?');
                register_shutdown_function([$this, 'cleanup']);
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
                if( $data['contentfile'] ) {
                    $fp = cms_join_path(CMS_ASSETS_PATH,'styles',$data['content']);
                    if( is_readable($fp) && is_file($fp) ) {
                        try {
                            $data['content'] = file_get_contents($fp);
                        } catch( Throwable $t ) {
//                            trigger_error('cms_stylesheet resource: '.$t->getMessage());
                            cms_error("Stylesheet file $fp failed to load: ".$t->getMessage());
                            $mtime = false;
                            return;
                        }
                    }
                    else {
                        cms_error("Stylesheet file $fp is missing");
                        $mtime = false;
                        return;
                    }
                }

                self::$loaded[$data['id']] = $data;
                self::$loaded[$data['name']] = &$data;
                $rst->Close();
            }
        }
        // TODO DT field for modified
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

    public function cleanup()
    {
        if (!empty(self::$stmt)) self::$stmt->close();
    }
} // class
