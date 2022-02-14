<?php
/*
Class for handling layout templates as a resource
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\SingleItem;
use CMSMS\Utils;
use function CMSMS\log_error;
use function CMSMS\sanitizeVal;

/**
 * A class for handling layout templates as a Smarty resource.
 *
 * Handles file- and database-sourced content, numeric and string template identifiers,
 * with or without related originator i.e. 'originator;name' or just 'name'.
 * Ignores formerly-deployed page-partition suffixes ';top', ';head', ';body'.
 *
 * @package CMS
 * @internal
 * @ignore
 * @since 1.12
 */
class Smarty_Resource_cms_template extends Smarty_Resource_Custom
{
    /**
     * populate Source Object with meta data from Resource, and work
     * around Smarty's filepath-setting limitation
     *
     * @param Smarty_Template_Source   $source    source object
     * @param Smarty_Internal_Template $_template template object
     */
    public function populate(Smarty_Template_Source $source,Smarty_Internal_Template $_template = null)
    {
        parent::populate($source,$_template);
        if ($source->exists) {
            if (!is_numeric($source->name)) {
                $source->filepath = $source->type . ':' . sanitizeVal($source->name,CMSSAN_FILE);
            }
        }
    }

    /**
     * @param string $name  template identifier, numeric id (i.e. whole-page) or string name (possibly with originator or a deprecated page-component)
     * @param mixed $source store for retrieved template content, if any
     * @param int $mtime    store for retrieved template modification timestamp, if $source is populated
     */
    protected function fetch($name,&$source,&$mtime)
    {
        $name = trim($name);
        if( is_numeric($name) ) {
            $name = 0 + $name;
        }
        elseif( !$name ) {
            return;
        }

        if( $name == 'notemplate' ) {
            $source = '{content}';
            $mtime = time(); // never cache...
            return;
        }
        elseif( startswith($name,'appdata;') ) {
            $name = substr($name,8);
            $source = Utils::get_app_data($name);
            $mtime = time();
            return;
        }

        if( strpos($name,';') !== false ) {
            $parts = explode(';',$name);
            if( count($parts) != 2 || !$parts[1] ) {
                log_error('Unrecognized template',$name);
                return;
            }
            if( !($parts[1] == 'top' || $parts[1] == 'head' || $parts[1] == 'body') ) {
                $name = $parts[1];
                $orig = $parts[0];
                if ($orig === '' || strcasecmp($orig,'Core') == 0) {
                    $orig = '__CORE__';
                }
                $so = ' originator=? AND';
                $args = [$orig,$name,$name];
            }
            else { // ignore deprecated pseudo-partitions
                $name = $parts[0];
                $orig = false;
                $so = '';
                $args = [$name,$name];
            }
        }
        elseif (strpos($name,'::') !== false ) {
            $parts = explode('::',$name);
            if( count($parts) != 2 || !$parts[1] ) {
                log_error('Unrecognized template',$name);
                return;
            }
            $name = $parts[1];
            $orig = $parts[0];
            if ($orig === '' || strcasecmp($orig,'Core') == 0) {
                $orig = '__CORE__';
            }
            $so = ' originator=? AND';
            $args = [$orig,$name,$name];
        }
        else {
            $orig = false;
            $so = '';
            $args = [$name,$name];
        }

        // here we replicate CMSMS\Template::get_content() without the overhead of loading that class
        $db = SingleItem::Db();
        $sql = 'SELECT id,name,content,contentfile,COALESCE(modified_date,create_date,\'2000-1-1 00:00:01\') AS modified FROM '.CMS_DB_PREFIX.'layout_templates WHERE'.$so.' (id=? OR name=?)';
        $data = $db->getRow($sql,$args);
        if( $data ) {
            if( $data['contentfile'] ) {
                $fp = cms_join_path(CMS_ASSETS_PATH,'templates',$data['content']);
                if( is_readable($fp) && is_file($fp) ) {
                    try {
                        $data['content'] = file_get_contents($fp);
                    } catch( Throwable $t ) {
//                      trigger_error('cms_template resource: '.$t->getMessage());
                        log_error('Failed to load template file',basename($fp).','.$t->getMessage());
                        return;
                    }
                }
                else {
                    log_error('Missing template file',basename($fp));
                    return;
                }
            }
            //sanitize, in case some malicious content was stored
            // munge PHP tags TODO ok if these tags are already obfuscated ?
            //TODO maybe disable SmartyBC-supported {php}{/php} in $text BUT actual current smarty delim's
            $text = preg_replace(['/<\?php/i','/<\?=/','/<\?(\s|\n)/','~\{/?php\}~'],['&#60;&#63;php','&#60;&#63;=','&#60;&#63; ',''],$data['content']);
            $data['content'] = str_replace('`','&#96;',$text);
        }
        else {
            log_error('Missing template',($orig) ? $orig.';'.$name : $name);
            return;
        }

        if( strpos($data['content'],'<body>') === false ) {
            $source = $data['content'];
            $mtime = ( $data['modified'] ) ? cms_to_stamp($data['modified']) : time() - 86400;
            return;
        }

        // out-of-order processing to allow header tailoring
        // NOTE this content-parsing ignores optional content per
        // https://www.w3.org/TR/2011/WD-html5-20110525/syntax.html#optional-tags
        $content = $data['content'];
        $pos1 = stripos($content,'<head');
        $pos2 = stripos($content,'<header',(int)$pos1);
        if( $pos1 === false || $pos1 == $pos2 ) {
            $topcontent = '';
        }
        else {
            $topcontent = trim(substr($content,0,$pos1));
        }

        $pos3 = stripos($content,'</head>',(int)$pos1);
        if( $pos1 === false || $pos1 == $pos2 || $pos3 === false ) {
            $headercontent = '';
        }
        else {
            $headercontent = trim(substr($content,$pos1,$pos3-$pos1+7));
        }

        if( $pos3 !== false ) {
            $bodycontent = trim(substr($content,$pos3+7));
        }
        else {
            $bodycontent = $content;
        }

        $id = $data['id'];
        $source = <<<EOS
{\$parts=[]}
{capture append=parts}$topcontent{/capture}
{capture append=parts}$bodycontent{/capture}
{capture append=parts}$headercontent{/capture}
{\$parts[0] = CMSMS\\tailorpage('PageTopPreRender',$id,\$parts[0])}
{\$parts[0]}
{CMSMS\\tailorpage('PageTopPostRender',$id)}
{\$parts[2] = CMSMS\\tailorpage('PageHeadPreRender',$id,\$parts[2])}
{\$parts[2]}
{CMSMS\\tailorpage('PageHeadPostRender',$id)}
{\$parts[1] = CMSMS\\tailorpage('PageBodyPreRender',$id,\$parts[1])}
{\$parts[1]}
{CMSMS\\tailorpage('PageBodyPostRender',$id)}
EOS;
        $mtime = ( $data['modified'] ) ? cms_to_stamp($data['modified']) : time() - 86400;
    }
} // class
