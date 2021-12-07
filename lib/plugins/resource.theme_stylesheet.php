<?php
/*
Class for handling theme stylesheets as a resource
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
 * A class for handling theme-related stylesheets as a smarty resource.
 *
 * @package CMS
 * @internal
 * @ignore
 *
 * @since 2.99
 */
class Smarty_Resource_theme_stylesheet extends Smarty_Resource_Custom
{
    /**
     * @var array intra-request cache of used templates, each member like
     *  name => [ 'id' => props-array, 'name' => ref. to props-array ]
     * @ignore
     */
    private static $loaded = [];

    /**
     * populate Source Object with meta data from Resource, and work
     * around Smarty's filepath-setting limitation
     *
     * @param Smarty_Template_Source   $source    source object
     * @param Smarty_Internal_Template $_template template object
     */
    public function populate(Smarty_Template_Source $source, Smarty_Internal_Template $_template = null)
    {
        parent::populate($source, $_template);
        if ($source->exists) {
            if (!is_numeric($source->name)) {
                $source->filepath = $source->type . ':' . sanitizeVal($source->name, CMSSAN_FILE);
            }
        }
    }

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
            return;
        }

        if( isset(self::$loaded[$name]) ) {
            $data = self::$loaded[$name];
        }
        else {

           //TODO get content from all relevant theme stylesheet(s)
           //TODO support inherited/extended styling

            $db = SingleItem::Db();
            $sql = 'SELECT id,name,content,contentfile,modified_date FROM '.CMS_DB_PREFIX.'layout_stylesheets WHERE id=? OR name=?';
            $rst = $db->execute(self::$stmt, [$name, $name]);
            if( !$rst || $rst->EOF() ) {
                if( $rst ) $rst->Close();
                log_error('Missing stylesheet', $name);
                return;
            }
            else {
                $data = $rst->FetchRow();
                if( $data['contentfile'] ) {
                    $fp = cms_join_path(CMS_ASSETS_PATH, 'styles', $data['content']);
                    if( is_readable($fp) && is_file($fp) ) {
                        try {
                            $data['content'] = file_get_contents($fp);
                        } catch (Throwable $t) {
//                            trigger_error('cms_stylesheet resource: '.$t->getMessage());
                            log_error('Failed to load stylesheet file', basename($fp).','.$t->getMessage());
                            return;
                        }
                    }
                    else {
                        log_error('Missing stylesheet file', basename($fp));
                        return;
                    }
                }
                //sanitize sheet content, in case some malicious stuff was stored
                // PHP tags and/or SmartyBC-supported {php}{/php} and/or '`'
                $text = preg_replace(['/<\?php/i','/<\?=/','/<\?(\s|\n)/','~\[\[/?php\]\]~i'], ['','','',''], $data['content']);
                $data['content'] = str_replace('`', '', $text);

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

        $text = '/* cmsms stylesheet: '.$name.' modified: '.date(DATE_RFC1036,$mtime).' */'."\n".$data['content'];
        if( !endswith($text,"\n") ) $text .= "\n";
        $source = $text;
    }
} // class
