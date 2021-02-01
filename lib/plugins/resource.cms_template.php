<?php
/*
Class for handling generic layout templates as a resource
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
use CMSMS\Utils;
//use Smarty_Resource_Custom;
//use Throwable;
//use const CMS_ASSETS_PATH;
//use const CMS_DB_PREFIX;
//use function cms_error;
//use function cms_join_path;
//use function cms_to_stamp;
//use function startswith;

/**
 * A class for handling generic db- and file-stored layout templates as a resource.
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
class Smarty_Resource_cms_template extends Smarty_Resource_Custom
{
    /**
     * @param string $name  template identifier (name or numeric id)
     * @param mixed $source store for retrieved template content, if any
     * @param int $mtime    store for retrieved template modification timestamp, or false to abort
     */
    protected function fetch($name,&$source,&$mtime)
    {
        $name = trim($name);
        if( is_numeric($name) ) {
            $name = 0 + $name;
        }
        elseif( !$name ) {
            $mtime = false;
            return;
        }

        if( $name == 'notemplate' ) {
            $source = '{content}';
            $mtime = time(); // never cache...
        }
        elseif( startswith($name,'appdata;') ) {
            $name = substr($name,8);
            $source = Utils::get_app_data($name);
            $mtime = time();
        }
        else {
            //TODO get content from relevant theme template(s) if relevant
            //TODO support stylesheet templates
            //TODO support inherited/extended templates
            // here we replicate CMSMS\Template::get_content() without the overhead of loading that class
            $db = AppSingle::Db();
            $sql = 'SELECT id,name,content,contentfile,modified_date FROM '.CMS_DB_PREFIX.'layout_templates WHERE id=? OR name=?';
            $data = $db->GetRow($sql,[$name,$name]);
            if( $data ) {
                if( $data['contentfile'] ) {
                    $fp = cms_join_path(CMS_ASSETS_PATH,'templates',$data['content']);
                    if( is_readable($fp) && is_file($fp) ) {
                        try {
                            $data['content'] = file_get_contents($fp);
                        } catch( Throwable $t ) {
//                            trigger_error('cms_template resource: '.$t->getMessage());
                            cms_error("Template file $fp failed to load: ".$t->getMessage());
                            $mtime = false;
                            return;
                        }
                    }
                    else {
                        cms_error("Template file $fp is missing");
                        $mtime = false;
                        return;
                    }
                }
            }
            else {
                cms_error('Missing template: '.$name);
                $mtime = false;
                return;
            }
        }

        $id = $data['id'];
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

        if( startswith($name, 'cms_template:')/* || startswith( $name, 'cms_file:')*/ ) {
            // out-of-order processing to allow header tailoring
            $pos1 = stripos($content,'<head');
            $pos2 = stripos($content,'<header',(int)$pos1);
            if( $pos1 === FALSE || $pos1 == $pos2 ) {
                $topcontent = '';
            }
            else {
                $topcontent = trim(substr($content,0,$pos1));
            }

            $pos3 = stripos($content,'</head>',(int)$pos1);
            if( $pos1 === FALSE || $pos1 == $pos2 || $pos3 === FALSE ) {
                $headercontent = '';
            }
            else {
                $headercontent = trim(substr($content,$pos1,$pos3-$pos1+7));
            }

            if( $pos3 !== FALSE ) {
                $bodycontent = trim(substr($content,$pos3+7));
            }
            else {
                $bodycontent = $content;
            }

            $source = <<<EOS
{capture assign=toppart}{$topcontent}{/capture}
{capture assign=bodypart}{$bodycontent}{/capture}
{capture assign=headpart}{$headercontent}{/capture}
{send_content_notice type=PageTopPreRender pageid=$id content=\$toppart assign=toppart}
{capture}{\$toppart}{/capture}{\$smarty.capture.default}
{send_content_notice type=PageTopPostRender pageid=$id content=\$smarty.capture.default}
{send_content_notice type=PageHeadPreRender pageid=$id content=\$headpart assign=headpart}
{capture}{\$headpart}{/capture}{\$smarty.capture.default}
{send_content_notice type=PageHeadPostRender pageid=$id content=\$smarty.capture.default}
{send_content_notice type=PageBodyPreRender pageid=$id content=\$bodypart assign=bodypart}
{capture}{\$bodypart}{/capture}{\$smarty.capture.default}
{send_content_notice type=PageBodyPostRender pageid=$id content=\$smarty.capture.default}
EOS;
        }
        else {
            $source = trim($content);
        }
    }
} // class
