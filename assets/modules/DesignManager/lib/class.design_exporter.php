<?php
# DesignManager module class: handles design exports.
# Copyright (C) 2012-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace DesignManager;

use cms_url;
use cms_utils;
use DesignManager\Design;
use CmsLayoutStylesheet;
use CmsLayoutTemplate;
use CmsLayoutTemplateType;
use CMSMS\CmsException;
use CMSMS\StylesheetOperations;
use CMSMS\TemplateOperations;
use const CMS_ROOT_PATH;
use const CMS_ROOT_URL;
use const CMS_VERSION;
use function cms_join_path;
use function cmsms;
use function endswith;
use function startswith;

class design_exporter
{
    private $_design;
    private $_tpl_list;
    private $_css_list;
    private $_files;
    private $_image = null;
    private $_description;
    // static properties here >> StaticProperties class ?
    public static $_mm_types;
    public static $_nav_types;

    private static $_dtd = <<<EOT
<!DOCTYPE design [
  <!ELEMENT design (name,description,generated,cmsversion,template+,stylesheet*,file*)>
  <!ELEMENT name (#PCDATA)>
  <!ELEMENT description (#PCDATA)>
  <!ELEMENT generated (#PCDATA)>
  <!ELEMENT cmsversion (#PCDATA)>
  <!ELEMENT template (tkey,tname,tdesc,tdata,ttype_originator,ttype_name)>
  <!ELEMENT tkey (#PCDATA)>
  <!ELEMENT tname (#PCDATA)>
  <!ELEMENT tdesc (#PCDATA)>
  <!ELEMENT tdata (#PCDATA)>
  <!ELEMENT ttype_originator (#PCDATA)>
  <!ELEMENT ttype_name (#PCDATA)>
  <!ELEMENT stylesheet (csskey,cssname,cssdesc,cssmediatype,cssmediaquery,cssdata)>
  <!ELEMENT csskey (#PCDATA)>
  <!ELEMENT cssname (#PCDATA)>
  <!ELEMENT cssdesc (#PCDATA)>
  <!ELEMENT cssmediatype (#PCDATA)>
  <!ELEMENT cssmediaquery (#PCDATA)>
  <!ELEMENT cssdata (#PCDATA)>
  <!ELEMENT file (fkey,fvalue,fdata?)>
  <!ELEMENT fkey (#PCDATA)>
  <!ELEMENT fvalue (#PCDATA)>
  <!ELEMENT fdata (#PCDATA)>
]>

EOT;

    public function __construct(Design $design)
    {
        $this->_design = $design;
        if( !is_array(self::$_mm_types ) ) {
            self::$_mm_types = CmsLayoutTemplateType::load_all_by_originator('MenuManager');
            self::$_nav_types = CmsLayoutTemplateType::load_all_by_originator('Navigator');
            if( (!is_array(self::$_mm_types) || count(self::$_mm_types) == 0) && (!is_array(self::$_nav_types) || count(self::$_nav_types) == 0) ) {
                throw new Exception('Cannot find any Navigation template types (is MenuManager or Navigator installed and enabled?');
            }
        }
    }

    public function get_description()
    {
        if( is_null($this->_description) ) return $this->_design->get_description();
    }

    public function set_description($text)
    {
        $this->_description = $text;
    }

    /**
     * internal
     */
    public function _get_signature($fn,$type = 'URL')
    {
        if( is_array($this->_files) ) {
            foreach( $this->_files as $key => $data ) {
                if( $fn == $data ) return $key;
            }
        }
        $sig = '__'.$type.',,'.md5($fn).'__';
        if( !is_array($this->_files) ) $this->_files = [];
        $this->_files[$sig] = $fn;
        return $sig;
    }

    private function _parse_css_for_urls($content)
    {
        $ob = &$this;
        $regex='/url\s*\(\"*(.*)\"*\)/i';
        $content = preg_replace_callback($regex, function($matches) use ($ob)
            {
                $config = cmsms()->GetConfig();
                $url = $matches[1];
	            //TODO generally support the websocket protocol
                if( !startswith($url,'http') || startswith($url,CMS_ROOT_URL) || startswith($url,'[[root_url]]') ) {
                    $sig = $ob->_get_signature($url);
                    $sig = 'url('.$sig.')';
                    return $sig;
                }
                return $matches[0];
            }, $content);

        return $content;
    }

    private function _parse_tpl_urls($content)
    {
        $ob = &$this;

        $temp_fix_cmsselflink = function($matches) use ($ob) {
            // GCB (required name param)
            $out = preg_replace_callback("/href\s*=[\\\"']{0,1}([a-zA-Z0-9._\ \:\-\/]+)[\\\"']{0,1}/i",
                function($matches) use ($ob)
                {
                    return str_replace($matches[1],'ignore::'.$matches[1],$matches[0]);
                },$matches[0]);
            return $out;
        };

        $undo_fix_cmsselflink = function($matches) use ($ob) {
            // GCB (required name param)
            $out = preg_replace_callback("/href\s*=[\\\"']{0,1}(ignore\:\:[a-zA-Z0-9._\ \:\-\/]+)[\\\"']{0,1}/i",
                function($matches) use ($ob)
                {
                    $rep = substr($matches[1],8);
                    return str_replace($matches[1],$reo,$matches[0]);
                },$matches[0]);
            return $out;
        };

        // replace cms_selflink stuff with an ignore
        $regex='/\{cms_selflink.*\}/';
        $content = preg_replace_callback( $regex, $temp_fix_cmsselflink, $content );

        // compars root url to another url
        // handle relative paths
        // and no schema
        $is_same_host = function(cms_url $url1,cms_url $url2) {
            if( $url1->get_host() != $url2->get_host() && $url2->get_host() != '') return false;
            if( $url1->get_port() != $url2->get_port() ) return false;
            if( $url1->get_scheme() != $url2->get_scheme() && $url2->get_scheme() != '') return false;
            $p1 = $url1->get_path();
            $p2 = $url2->get_path();
            if( $p1 != $p2 && !startswith($p2,$p1) ) return false;
            return true;
        };

        $ob = &$this;
        $config = cmsms()->GetConfig();
        $types = ['href', 'src', 'url'];
        foreach( $types as $type ) {
            $innerT = '[a-z0-9:?=&@/._-]+?';
            $content = preg_replace_callback("|$type\=([\"'`])(".$innerT.')\\1|i',
                function($matches) use ($ob,$type,&$is_same_host,$config)
                {
                    $url = $matches[2];
                    $root_url = new cms_url(CMS_ROOT_URL);
                    $the_url = new cms_url($url);
                    if( !startswith($url,'ignore::') && $is_same_host($root_url,$the_url) ) {
                        $sig = $ob->_get_signature($url);
                        //return $sig;
                        return " $type=\"$sig\"";
                    }
                    return $matches[0];
                }, $content);
        }

        // remove ignore stuff on cms_selflink
        $regex='/\{cms_selflink.*\}/';
        $content = preg_replace_callback( $regex, $undo_fix_cmsselflink, $content );

        return $content;
    }

    public function parse_stylesheets()
    {
        if( is_null($this->_css_list) ) {
            $this->_css_list = [];

            $csslist = $this->_design->get_stylesheets();
            if( $csslist ) {
                foreach( $csslist as $css_id ) {
                    $css_ob = StylesheetOperations::get_stylesheet($css_id);

                    $new_content = $this->_parse_css_for_urls($css_ob->get_content());
                    $sig = $this->_get_signature($css_ob->get_name(),'CSS');
                    $new_css_ob = clone $css_ob;
                    $new_css_ob->set_name($sig);
                    $new_css_ob->set_content($new_content);

                    if( !is_array($this->_css_list) ) $this->_css_list = [];
                    $this->_css_list[] = ['name'=>$css_ob->get_name(),'obj'=>$new_css_ob];
                }
            }
        }
    }

    public function list_stylesheets()
    {
        $this->parse_stylesheets();
        if( $this->_css_list ) {
            $out = [];
            foreach( $this->_css_list as $rec ) {
                $out[] = $rec['obj']->get_name();
            }
            return $out;
        }
    }

    public function _add_template($name,$type = 'TPL')
    {
        switch( $type ) {
        case 'TPL':
            $tpl_ob = null;
            if( is_object($name) ) {
                $tpl_ob = $name;
                $name = $tpl_ob->get_name();
            }
            else {
                $tpl_ob = TemplateOperations::get_template($name);
            }
            $sig = $this->_get_signature($tpl_ob->get_name(),$type);

            // recursion...
            $new_content = $this->_parse_tpl_urls($tpl_ob->get_content());
            $new_content = $this->_get_sub_templates($new_content);
            $sig = $this->_get_signature($tpl_ob->get_name(),'TPL');
            $new_tpl_ob = clone $tpl_ob;
            $new_tpl_ob->set_name($sig);
            $new_tpl_ob->set_content($new_content);

            if( !is_array($this->_tpl_list) ) $this->_tpl_list = [];
            $this->_tpl_list[$sig] = ['name'=>$name,'obj'=>$new_tpl_ob];
            return $sig;

        case 'MM':
            // MenuManager file template
            $mod = cms_utils::get_module('MenuManager');
            if( !$mod ) throw new Exception('MenuManager file template specified, but MenuManager could not be loaded.');

            $content = $mod->GetTemplateFromFile($name);
            if( !$content ) throw new Exception('Could not find MenuMaager template '.$name);
            $content = $this->_parse_tpl_urls($content);

            // create a new CmsLayoutTemplate object for this template
            // and add it to the list.
            // notice we don't recurse.
            $new_tpl_ob = new CmsLayoutTemplate;
	        $new_tpl_ob->set_originator($mod->GetName());
            $new_tpl_ob->set_content($content);
            $name = substr($name,0,-4);
            $type = 'TPL';
            $sig = $this->_get_signature($name,$type);
            $new_tpl_ob->set_name($sig);
            // it's a menu manager template
            // we need to get a 'type' for this.
            $new_tpl_ob->set_type(self::$_mm_types[0]);
            $this->_tpl_list[$sig] = ['name'=>$name,'obj'=>$new_tpl_ob];
            return $sig;
        } // switch
    }

    private function _get_sub_templates($template)
    {
        $ob = &$this;

        $replace_mm = function($matches) use ($ob) {
            // Menu Manager (optional template param)
            $mod = cms_utils::get_module('MenuManager');
            if( !$mod ) throw new Exception('MenuManager tag specified, but MenuManager could not be loaded.');

            $have_template = false;
            $out = preg_replace_callback("/template\s*=[\\\"']{0,1}([a-zA-Z0-9._\ \:\-\/]+)[\\\"']{0,1}/i",
                function($matches) use ($ob,&$have_template)
                {
                   $the_tpl = $matches[1];
                   if( ($pos = strpos($matches[1],' ')) !== false ) $the_tpl = substr($matches[1],0,$pos);
                   $type = 'TPL';
                   if( endswith($the_tpl,'.tpl') ) $type = 'MM';
                   $sig = $ob->_add_template($the_tpl,$type);
                   $have_template = true;
                   $out = str_replace($the_tpl,$sig,$matches[0]);
                   return $out;
               },$matches[0]);

            if( !$have_template ) {
                // MenuManager default template.
                $tpl = TemplateOperations::get_default_template_by_type('MenuManager::navigation');
                $sig = $ob->_add_template($tpl->get_name());
                $out = substr($matches[0],0,-1).' template=\''.$sig.'\'}';
            }
            return $out;
        };

        $replace_navigator = function($matches) use ($ob) {
            // Navigator (optional template param)
            $mod = cms_utils::get_module('Navigator');
            if( !$mod ) throw new Exception('Navigator tag specified, but Navigator could not be loaded.');

            $have_template = false;
            $out = preg_replace_callback("/template\s*=[\\\"']{0,1}([a-zA-Z0-9._\ \:\-\/]+)[\\\"']{0,1}/i",
                function($matches) use ($ob,&$have_template)
                {
                    $have_template = true;
                    $sig = $ob->_add_template($matches[1]);
                    return str_replace($matches[1],$sig,$matches[0]);
                },$matches[0]);
            if( !$have_template ) {
                // Navigator default template.
                $tpl = TemplateOperations::get_default_template_by_type('Navigator::navigation');
                $sig = $ob->_add_template($tpl->get_name());
                $out = substr($matches[0],0,-1).' template=\''.$sig.'\'}';
            }
            return $out;
        };

        $replace_gcb = function($matches) use ($ob) {
            // GCB (required name param)
            $out = preg_replace_callback("/name\s*=[\\\"']{0,1}([a-zA-Z0-9._\ \:\-\/]+)[\\\"']{0,1}/i",
                function($matches) use ($ob)
                {
                    $sig = $ob->_add_template($matches[1]);
                    return str_replace($matches[1],$sig,$matches[0]);
                },$matches[0]);
            return $out;
        };

        $replace_include = function($matches) use ($ob) {
            // include (required file param)
            $out = preg_replace_callback("/file\s*=[\\\"']{0,1}([a-zA-Z0-9._\ \:\-\/]+)[\\\"']{0,1}/i",
                function($matches) use ($ob)
                {
                    if( !startswith($matches[1],'cms_template:') ) {
                        throw new Exception('Only templates that use {include} with cms_template resources can be exported.');
                    }
                    $tpl = substr($matches[1],strlen('cms_template:'));
                    $sig = $ob->_add_template($tpl);
                    return str_replace($matches[1],'cms_template:'.$sig,$matches[0]);
                },$matches[0]);
            return $out;
        };

        $regex='/\{menu.*\}/';
        $template = preg_replace_callback( $regex, $replace_mm, $template );

        $regex='/\{.*MenuManager.*\}/';
        $template = preg_replace_callback( $regex, $replace_mm, $template );

        $regex='/\{.*Navigator.*\}/';
        $template = preg_replace_callback( $regex, $replace_navigator, $template );

        $regex='/\{global_content.*\}/';
        $template = preg_replace_callback( $regex, $replace_gcb, $template );

        $regex='/\{include.*\}/';
        $template = preg_replace_callback( $regex, $replace_include, $template );

        return $template;
    }

    public function parse_templates()
    {
        if( is_null($this->_tpl_list) ) {
            $this->_tpl_list = [];

            $idlist = $this->_design->get_templates();
            if( $idlist ) {
                $tpllist = TemplateOperations::get_bulk_templates($idlist);
                if( count($idlist) != count($tpllist) ) throw new Exception('Internal error... could not directly load all of the templates associated with this design');
                foreach( $tpllist as $tpl ) {
                    $this->_add_template($tpl);
                }
            }
        }
    }

    public function list_templates()
    {
        $this->parse_templates();
        if( $this->_tpl_list ) {
            $out = [];
            foreach( $this->_tpl_list as $rec ) {
                $out[] = $one['obj']->get_name();
            }
            return $out;
        }
    }

    public function list_files()
    {
        $this->parse_stylesheets();
        $this->parse_templates();
        if( $this->_files ) return $this->_files;
    }

    private function _open_tag($elem,$lvl = 1)
    {
        return str_repeat('  ',$lvl)."<{$elem}>\n";
    }

    private function _close_tag($elem,$lvl = 1)
    {
        return str_repeat('  ',$lvl)."</{$elem}>\n";
    }

    private function _output($elem,$txt,$lvl = 1)
    {
        return str_repeat('  ',$lvl).'<'.$elem.'>'.$txt.'</'.$elem.">\n";
    }

    private function _output_data($elem,$data,$lvl = 1)
    {
        $data = '<![CDATA['.base64_encode($data).']]>';
        return $this->_output($elem,$data,$lvl);
    }

    private function _xml_output_template(CmsLayoutTemplate $tpl,$name,$lvl = 0)
    {
        if( $tpl->get_content() == '' ) throw new Exception('Cannot export empty template');
        $output = $this->_open_tag('template',$lvl);
        $output .= $this->_output('tkey',$tpl->get_name(),$lvl+1);
        $output .= $this->_output_data('tname',$name,$lvl+1);
        $output .= $this->_output_data('tdesc',$tpl->get_description(),$lvl+1);
        $output .= $this->_output_data('tdata',$tpl->get_content(),$lvl+1);
        if( !$tpl->get_type_id() ) throw new Exception('Cannot get template type for '.$tpl->get_name());

        $type = CmsLayoutTemplateType::load($tpl->get_type_id());
        $output .= $this->_output_data('ttype_originator',$type->get_originator(),$lvl+1);
        $output .= $this->_output_data('ttype_name',$type->get_name(),$lvl+1);
        $output .= $this->_close_tag('template',$lvl);
        return $output;
    }

    private function _xml_output_stylesheet(CmsLayoutStylesheet $css,$name,$lvl = 0)
    {
        if( $css->get_content() == '' ) throw new Exception('Cannot export empty stylesheet');
        $output = $this->_open_tag('stylesheet',$lvl);
        $output .= $this->_output('csskey',$css->get_name(),$lvl+1);
        $output .= $this->_output_data('cssname',$name,$lvl+1);
        $output .= $this->_output_data('cssdesc',$css->get_description(),$lvl+1);
        $output .= $this->_output_data('cssmediatype',implode(',',$css->get_media_types()),$lvl+1);
        $output .= $this->_output_data('cssmediaquery',$css->get_media_query(),$lvl+1);
        $output .= $this->_output_data('cssdata',$css->get_content(),$lvl+1);
        $output .= $this->_close_tag('stylesheet',$lvl);
        return $output;
    }

    private function _xml_output_file($key,$value,$lvl = 0)
    {
        if( !startswith($key,'__') || !endswith($key,'__') ) return; // invalid
        $p = strpos($key,',,');
        $nkey = substr($key,0,$p);
        $nkey = substr($nkey,2);

        $mod = cms_utils::get_module('DesignManager');
        $smarty = cmsms()->GetSmarty();
        $output = $this->_open_tag('file',$lvl);
        $output .= $this->_output('fkey',$key,$lvl+1);
        switch($nkey) {
        case 'URL':
            // javascript file or image or something.
            // could have smarty syntax.
            $nvalue = $value;
            if( strpos($value,'[[') !== false ) {
                // smarty syntax with [[ and ]] as delimiters
                $smarty->left_delimiter = '[[';
                $smarty->right_delimiter = ']]';
                $nvalue = $smarty->fetch('string:'.$value);
                $smarty->left_delimiter = '{';
                $smarty->right_delimiter = '}';
            }
            else if( strpos($value,'{') !== false ) {
                // smarty syntax with { and } as delimiters
                $nvalue = $smarty->fetch('string:'.$value);
            }

            // now, it should be a full URL, or start at /
            // gotta convert it to a file.
            // assumes it's a filename relative to root.
            $fn = cms_join_path(CMS_ROOT_PATH,$nvalue);
            if( startswith($nvalue,'/') && !startswith($nvalue,'//') ) {
                $fn = cms_join_path(CMS_ROOT_PATH,$nvalue);
            } elseif( startswith($nvalue,CMS_ROOT_URL) ) {
                $fn = str_replace(CMS_ROOT_URL,CMS_ROOT_PATH,$nvalue);
            }

            if( !is_file($fn) ) throw new Exception($mod->Lang('error_nophysicalfile',$value));

            $data = file_get_contents($fn);
            if( strlen($data) == 0 ) throw new Exception('No data found for '.$value);

            $nvalue = basename($nvalue);
            $output .= $this->_output('fvalue',$nvalue,$lvl+1);
            $output .= $this->_output_data('fdata',$data,$lvl+1);
            break;

        case 'TPL':
            // template signature...
            // just need the key and value.
            $output .= $this->_output('fvalue',$value,$lvl+1);
            break;

        case 'CSS':
            // stylesheet signature
            // just need the key and value.
            $output .= $this->_output('fvalue',$value,$lvl+1);
            break;

        case 'MM':
            // menu manager file template
            // just need the key and value.
            $output .= $this->_output('fvalue',$value,$lvl+1);
            break;

        default:
            break;
        }
        $output .= $this->_close_tag('file',$lvl);
        return $output;
    }

    public function get_xml()
    {
        $this->parse_stylesheets();
        $this->parse_templates();

        $output = '<?xml version="1.0" encoding="ISO-8859-1"?>';
        $output .= self::$_dtd;
        $output .= $this->_open_tag('design',0);
        $output .= $this->_output('name',$this->_design->get_name());
        $output .= $this->_output_data('description',$this->_design->get_description());
        $output .= $this->_output_data('generated',time());
        $output .= $this->_output_data('cmsversion',CMS_VERSION);
        foreach( $this->_tpl_list as $rec ) {
            $output .= $this->_xml_output_template($rec['obj'],$rec['name'],1);
        }
        foreach( $this->_css_list as $rec ) {
            $output .= $this->_xml_output_stylesheet($rec['obj'],$rec['name'],1);
        }
        if( $this->_files ) {
            foreach( $this->_files as $key => $value ) {
                $output .= $this->_xml_output_file($key,$value,1);
            }
        }
        $output .= $this->_close_tag('design',0);
        return $output;
    }
} // class
