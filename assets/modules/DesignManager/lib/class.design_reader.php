<?php
/*
DesignManager module class for processing CMSMS2 designs.
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
namespace DesignManager;

use CMSMS\Exception;
use CMSMS\Lone;
use CMSMS\Stylesheet;
use CMSMS\StylesheetOperations;
use CMSMS\Template;
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;
use DesignManager\reader_base;
use DesignManager\xml_reader;
use Throwable;
use XMLReader;
use const CMSSAN_FILE;
use const PHP_EOL;
use function cms_join_path;
use function CMSMS\sanitizeVal;
use function file_put_contents;
use function get_server_permissions;
use function startswith;

class design_reader extends reader_base
{
    private $_xml;
    private $_scanned;
    private $_raw_design_info = [];
    private $_tpl_info = [];
    private $_css_info = [];
    private $_file_map = [];
    private $_new_design_description;

    public function __construct($fn)
    {
        $this->_xml = new xml_reader();
        $this->_xml->open($fn);
        $this->_xml->SetParserProperty(XMLReader::VALIDATE,TRUE);
    }

    public function validate()
    {
        while( $this->_xml->read() ) {
            if( !$this->_xml->isValid() ) {
                throw new Exception('Invalid XML FILE ');
            }
        }
        // it validates.
    }

    private function _scan(): void
    {
        if( $this->_scanned ) return;

        $in = [];

        $get_in = function() use ($in): string {
            if( $in ) {
                return end($in);
            }
            return '';
        };

        $cur_key = '';
        while( $this->_xml->read() ) {
            switch( $this->_xml->nodeType ) {
            case XmlReader::ELEMENT:
                switch( $this->_xml->localName ) {
                case 'design':
                case 'template':
                case 'stylesheet':
                case 'file':
                    $in[] = $this->_xml->localName;
                    break 2;

                case 'name':
                    if( $get_in() != 'design' ) {
                        // validity error.
                    }
                    else {
                        $name = $this->_xml->localName;
                        $this->_xml->read();
                        $this->_raw_design_info[$name] = $this->_xml->value;
                    }
                    break 2;

                case 'description':
                case 'generated':
                case 'cmsversion':
                    if( $get_in() != 'design' ) {
                        // validity error.
                    }
                    else {
                        $name = $this->_xml->localName;
                        $this->_xml->read();
                        $this->_raw_design_info[$name] = base64_decode($this->_xml->value);
                    }
                    break 2;

                case 'tkey':
                    if( $get_in() != 'template' ) {
                        // validity error.
                    }
                    else {
                        $this->_xml->read();
                        $cur_key = $this->_xml->value;
                        $this->_tpl_info[$cur_key] = ['key'=>$cur_key];
                    }
                    break 2;

                case 'tname':
                    if( $get_in() != 'template' || !$cur_key ) {
                        // validity error.
                    }
                    else {
                        $this->_xml->read();
                        $this->_tpl_info[$cur_key]['name'] = $this->_xml->value;
                    }
                    break 2;

                case 'tdesc':
                    if( $get_in() != 'template' || !$cur_key ) {
                        // validity error.
                    }
                    else {
                        $this->_xml->read();
                        $this->_tpl_info[$cur_key]['desc'] = $this->_xml->value;
                    }
                    break 2;

                case 'tdata':
                    if( $get_in() != 'template' || !$cur_key ) {
                        // validity error.
                    }
                    else {
                        $this->_xml->read();
                        $this->_tpl_info[$cur_key]['data'] = $this->_xml->value;
                    }
                    break 2;

                case 'ttype_originator':
                case 'ttype_name':
                    if( $get_in() != 'template' || !$cur_key ) {
                        // validity error.
                    }
                    else {
                        $key = $this->_xml->localName;
                        $this->_xml->read();
                        $this->_tpl_info[$cur_key][$key] = $this->_xml->value;
                    }
                    break 2;

                case 'csskey':
                    if( $get_in() != 'stylesheet' ) {
                        // validity error.
                    }
                    else {
                        $this->_xml->read();
                        $cur_key = $this->_xml->value;
                        $this->_css_info[$cur_key] = ['key'=>$cur_key];
                    }
                    break 2;

                case 'cssname':
                    if( $get_in() != 'stylesheet' || !$cur_key ) {
                        // validity error.
                    }
                    else {
                        $this->_xml->read();
                        $this->_css_info[$cur_key]['name'] = $this->_xml->value;
                    }
                    break 2;

                case 'cssdesc':
                    if( $get_in() != 'stylesheet' || !$cur_key ) {
                        // validity error.
                    }
                    else {
                        $this->_xml->read();
                        $this->_css_info[$cur_key]['desc'] = $this->_xml->value;
                    }
                    break 2;

                case 'cssdata':
                    if( $get_in() != 'stylesheet' || !$cur_key ) {
                        // validity error.
                    }
                    else {
                        $this->_xml->read();
                        $this->_css_info[$cur_key]['data'] = $this->_xml->value;
                    }
                    break 2;

                case 'cssmediatype':
                    if( $get_in() != 'stylesheet' || !$cur_key ) {
                        // validity error.
                    }
                    else {
                        $this->_xml->read();
                        $this->_css_info[$cur_key]['mediatype'] = $this->_xml->value;
                    }
                    break 2;

                case 'cssmediaquery':
                    if( $get_in() != 'stylesheet' || !$cur_key ) {
                        // validity error.
                    }
                    else {
                        $this->_xml->read();
                        $this->_css_info[$cur_key]['mediaquery'] = $this->_xml->value;
                    }
                    break 2;

                case 'fkey':
                    if( $get_in() != 'file' ) {
                        // validity error.
                    }
                    else {
                        $this->_xml->read();
                        $cur_key = $this->_xml->value;
                        $this->_file_map[$cur_key] = ['key'=>$cur_key];
                    }
                    break 2;

                case 'fvalue':
                    if( $get_in() != 'file' || !$cur_key ) {
                        // validity error.
                    }
                    else {
                        $this->_xml->read();
                        $this->_file_map[$cur_key]['value'] = $this->_xml->value;
                    }
                    break 2;

                case 'fdata':
                    if( $get_in() != 'file' || !$cur_key ) {
                        // validity error.
                    }
                    else {
                        $this->_xml->read();
                        $this->_file_map[$cur_key]['data'] = $this->_xml->value;
                    }
                    break 2;
                }
                break;

            case XmlReader::END_ELEMENT:
                switch( $this->_xml->localName ) {
                case 'design':
                case 'template':
                case 'stylesheet':
                case 'file':
                    if( $in ) {
                        array_pop($in);
                    }
                    $cur_key = '';
                    break 2;
                }
            }
        }
        $this->_scanned = TRUE;
    }

    private function _get_name($key)
    {
        if( isset($this->_file_map[$key]) ) return $this->_file_map[$key]['value'];
    }

    public function get_design_info()
    {
        $this->_scan();
        return $this->_raw_design_info;
    }

    public function set_new_description($description = '')
    {
      $this->_new_design_description = $description;
    }

    public function get_template_list()
    {
        $this->_scan();
        $out = [];
        foreach( $this->_tpl_info as $key => $one ) {
//          $name = $this->_get_name($key);
            $rec = [];
            $rec['name'] = base64_decode($one['name']);
            $rec['newname'] = TemplateOperations::get_unique_template_name($rec['name']);
            $rec['key'] = $key;
            $rec['desc'] = base64_decode($one['desc']);
            $rec['data'] = base64_decode($one['data']);
            $rec['type_originator'] = base64_decode($one['ttype_originator']);
            $rec['type_name'] = base64_decode($one['ttype_name']);
            $out[] = $rec;
        }
        return $out;
    }

    public function get_stylesheet_list()
    {
        $this->_scan();
        $out = [];
        foreach( $this->_css_info as $key => $one ) {
//          $name = $this->_get_name($key);
            $rec = [];
            $rec['name'] = base64_decode($one['name']);
            $rec['newname'] = StylesheetOperations::get_unique_name($rec['name']);
            $rec['key'] = $key;
            $rec['desc'] = base64_decode($one['desc']);
            $rec['data'] = base64_decode($one['data']);
            $rec['mediatype'] = base64_decode($one['mediatype']);
            $rec['mediaquery'] = base64_decode($one['mediaquery']);
            $out[] = $rec;
        }
        return $out;
    }

    protected function validate_template_names()
    {
        $this->_scan();

        $templates = TemplateOperations::template_query(['as_list'=>1]);
        $tpl_names = array_values($templates);

        foreach( $this->_file_map as $key => &$rec ) {
            if( !startswith($key,'__TPL,,') ) continue;

            if( in_array($rec['value'],$tpl_names) ) {
                // gotta come up with a new name
                $orig_name = $rec['value'];
                $n = 1;
                while( $n < 10 ) {
                    $n++;
                    $new_name = $orig_name.' '.$n;
                    if( !in_array($new_name,$tpl_names) ) {
                        $rec['old_value'] = $rec['value'];
                        $rec['value'] = $new_name;
                        break;
                    }
                }
            }
        }
        unset($rec);
    }

    protected function validate_stylesheet_names()
    {
        $this->_scan();

        $stylesheets = StylesheetOperations::get_all_stylesheets(TRUE);
        $css_names = array_values($stylesheets);

        foreach( $this->_file_map as $key => &$rec ) {
            if( !startswith($key,'__CSS,,') ) continue;

            if( in_array($rec['value'],$css_names) ) {
                // gotta come up with a new name
                $orig_name = $rec['value'];
                $n = 1;
                while( $n < 10 ) {
                    $n++;
                    $new_name = $orig_name.' '.$n;
                    if( !in_array($new_name,$css_names) ) {
                        $rec['old_value'] = $rec['value'];
                        $rec['value'] = $new_name;
                        break;
                    }
                }
            }
        }
        unset($rec);
    }

    public function get_destination_dir()
    {
        $name = $this->get_new_name();
        $dirname = sanitizeVal($name,CMSSAN_FILE);
        $config = Lone::get('Config');
        $dir = cms_join_path($config['uploads_path'],'designs',$dirname);
        $perms = get_server_permissions()[3];
        @mkdir($dir,$perms,TRUE);
        if( !is_dir($dir) || !is_writable($dir) ) {
            throw new Exception('Could not create directory, or could not write in directory '.$dir);
        }

        return $dirname;
    }

    public function import()
    {
        $this->validate_template_names();
        $this->validate_stylesheet_names();

        $config  = Lone::get('Config');
        $newname = $this->get_new_name();
        $destdir = $this->get_destination_dir();
        $info    = $this->get_design_info();

        // create new design... fill it with info
        $design = new Design();
        // $design->set_owner(get_userid(FALSE));
        $design->set_name($newname);
        $description = $this->get_suggested_description();

        if(empty($description))
        {
            $description = $info['description'];
            if( $description ) $description .= "\n----------------------------------------\n";
            $description .= 'Generated '.date(DATE_RFC1036,$info['generated']).PHP_EOL;
            $description .= 'By CMSMS version: '.$info['cmsversion'].PHP_EOL;
            $description .= 'Imported '.date(DATE_RFC1036);
        }

        $design->set_description($description);

        // expand URL FILES to become real files
        // don't have to worry about duplicated filenames (hopefully)
        // because the destinaton directory is unique.
        foreach( $this->_file_map as $key => &$rec ) {
            if( !startswith($key,'__URL,,') ) continue;
            if( !isset($rec['data']) || $rec['data'] == '' ) continue;

            $destfile = cms_join_path($config['uploads_path'],'designs',$destdir,$rec['value']);
            file_put_contents($destfile,base64_decode($rec['data']));
            $rec['tpl_url'] = "{uploads_url}/designs/$destdir/{$rec['value']}";
            $rec['css_url'] = "[[uploads_url]]/designs/$destdir/{$rec['value']}";
        }
        unset($rec);

        // expand stylesheets
        foreach( $this->get_stylesheet_list() as $css ) {
            $stylesheet = new Stylesheet();
            $stylesheet->set_name($css['newname']);
            if( isset($css['desc']) && $css['desc'] != '' ) $stylesheet->set_description($css['desc']);

            $content = $css['data'];
            foreach( $this->_file_map as $key => &$rec ) {
                if( !startswith($key,'__URL,,') ) continue;
                if( !isset($rec['css_url']) ) continue;
                $content = str_replace($key,$rec['css_url'],$content);
            }
            unset($rec);

            if( $css['mediatype'] ) {
               $tmp = explode(',',$css['mediatype']);
               for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
                  $str = trim($tmp[$i]);
                  if( $str ) $stylesheet->add_media_type($str);
               }
            }

            if( $css['mediaquery'] ) $stylesheet->set_media_query(trim($css['mediaquery']));

            // save the stylesheet and add it to the design.
            $stylesheet->set_content($content);
            $stylesheet->save();
            $design->add_stylesheet($stylesheet);
        }

        // expand templates
        $me = 'DesignManager'; //TODO
        $tpl_recs = $this->get_template_list();
        foreach( $tpl_recs as &$rec ) {
            $template = new Template();
            $template->set_originator($me);
            $template->set_name($rec['newname']);
            if( !empty($rec['desc']) ) $template->set_description($rec['desc']);
            $content = $rec['data'];

            // substitute URL keys for the values.
            foreach( $this->_file_map as $key => &$rec ) {
                if( startswith($key,'__URL,,') ) {
                    // handle URL keys... handles image links etc.
                    if( !isset($rec['tpl_url']) ) continue;
                    $content = str_replace($key,$rec['tpl_url'],$content);
                }
                elseif( startswith($key,'__CSS,,') ) {
                    // handle CSS keys... for things like {cms_stylesheet name='xxxx'}
                    if( !isset($rec['value']) ) continue;
                    $content = str_replace($key,$rec['value'],$content);
                }
                elseif( startswith($key,'__TPL,,') ) {
                    // handle TPL keys... for things like {include file='xxxx'}
                    // or calling a module with a specific template.
                    if( !isset($rec['value']) ) continue;
                    $content = str_replace($key,$rec['value'],$content);
                }
            }
            unset($rec);

            // substitute other template-keys in this content
            foreach( $tpl_recs as $rec2 ) {
               if( $rec2['key'] == $rec['key'] ) continue;
               $content = str_replace($rec2['key'],$rec2['newname'],$content);
            }

            $template->set_content($content);

            // template type:
            // - try to find the template type
            // - if not, set the type to 'generic'.
            try {
                $typename = $rec['type_originator'].'::'.$rec['type_name'];
                $type_obj = TemplateType::load($typename);
                $template->set_type($type_obj);
            }
            catch( Throwable $e ) {
                // should log something here.
                $type_obj = TemplateType::load(TemplateType::CORE.'::generic');
                $template->set_type($type_obj);
            }

            $template->save();
            $rec['newname'] = $template->get_name();
            $design->add_template($template);
        }
        unset($rec);

        $design->save();
    } // import
} // class
