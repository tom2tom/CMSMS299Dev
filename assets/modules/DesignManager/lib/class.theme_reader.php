<?php
/*
DesignManager module class: theme_reader, for processing CMSMS1 themes.
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

use CMSMS\Lone;
use CMSMS\Stylesheet;
use CMSMS\StylesheetOperations;
use CMSMS\Template;
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;
use CMSMS\Utils;
use DesignManager\Design;
use DesignManager\reader_base;
use DesignManager\xml_reader;
use Exception;
use const CMS_ROOT_URL;
use const CMSSAN_FILE;
use function cms_join_path;
use function CMSMS\sanitizeVal;
use function file_put_contents;
use function get_userid;
use function startswith;

class theme_reader extends reader_base
{
  private $_xml;
  private $_scanned;
  private $_design_info = [];
  private $_tpl_info = [];
  private $_css_info = [];
  private $_ref_map = [];

  public function __construct($fn)
  {
    $this->_xml = new xml_reader();
    $this->_xml->open($fn);
    //$this->_xml->SetParserProperty(XMLReader::VALIDATE,TRUE);
  }

  private function _scan()
  {
    $in = [];
    $cur_key = null;

    $get_in = function() use ($in) {
      if( ($n = count($in)) ) return $in[$n-1];
    };

    if( $this->_scanned ) return;

    $cur_key = null;
    while( $this->_xml->read() ) {
      switch( $this->_xml->nodeType ) {
        case XmlReader::ELEMENT:
          switch( $this->_xml->localName ) {
          case 'theme':
          case 'template':
          case 'assoc':
          case 'stylesheet':
          case 'reference':
          case 'mmtemplate':
            $in[] = $this->_xml->localName;
            break;

          case 'name':
            if( $get_in() != 'theme' ) {
              // validity error.
            }
            $this->_xml->read();
            $this->_design_info['name'] = $this->_xml->value;
            break;

          case 'tname':
            if( $get_in() != 'template' ) {
              // validity error.
            }
            $this->_xml->read();
            $cur_key = $this->_xml->value;
            if( !isset($this->_tpl_info[$cur_key]) ) $this->_tpl_info[$cur_key] = [];
            if( isset($this->_tpl_info[$cur_key]) ) {
              // error, duplicate template name in XML file
            }
            $this->_tpl_info[$cur_key]['name'] = $cur_key;
            $p = strpos($cur_key,'.');
            if( $p !== FALSE ) {
              $tmp = substr($cur_key,0,$p);
              $this->_tpl_info[$cur_key]['name'] = $cur_key;
            }
            break;

          case 'tdata':
            if( $get_in() != 'template' ) {
              // validity error.
            }
            $this->_xml->read();
            $this->_tpl_info[$cur_key]['data'] = $this->_xml->value;
            break;

          case 'mmtemplate_name':
            if( $get_in() != 'template' ) {
              // validity error.
            }
            $this->_xml->read();
            $cur_key = $this->_xml->value;
            if( !isset($this->_tpl_info[$cur_key]) ) $this->_tpl_info[$cur_key] = [];
            if( isset($this->_tpl_info[$cur_key]) ) {
              // error, duplicate template name in XML file
            }
            $this->_tpl_info[$cur_key]['name'] = $cur_key;
            $this->_tpl_info[$cur_key]['type'] = 'MM';
            $p = strpos($cur_key,'.');
            if( $p !== FALSE ) {
              $tmp = substr($cur_key,0,$p);
              $this->_tpl_info[$cur_key]['name'] = $tmp;
            }
            break;

          case 'mmtemplate_data':
            if( $get_in() != 'template' ) {
              // validity error.
            }
            $this->_xml->read();
            $this->_tpl_info[$cur_key]['data'] = $this->_xml->value;
            break;

          case 'cssname':
            if( $get_in() != 'stylesheet' ) {
              // validity error.
            }
            $this->_xml->read();
            $cur_key = $this->_xml->value;
            if( !isset($this->_css_info[$cur_key]) ) $this->_css_info[$cur_key] = [];
            if( isset($this->_css_info[$cur_key]) ) {
              // error, duplicate stylesheet name in XML file
            }
            $this->_css_info[$cur_key]['name'] = $cur_key;
            break;

          case 'cssdata':
            if( $get_in() != 'stylesheet' ) {
              // validity error.
            }
            $this->_xml->read();
            $this->_css_info[$cur_key]['data'] = $this->_xml->value;
            break;

          case 'cssmediatype':
            if( $get_in() != 'stylesheet' ) {
              // validity error.
            }
            $this->_xml->read();
            $this->_css_info[$cur_key]['mediatype'] = $this->_xml->value;
            break;

          case 'refname':
            if( $get_in() != 'reference' ) {
              // validity error.
            }
            $this->_xml->read();
            $cur_key = $this->_xml->value;
            if( !isset($this->_ref_map[$cur_key]) ) $this->_ref_map[$cur_key] = [];
            if( isset($this->_ref_map[$cur_key]) ) {
              // error, duplicate reference name in XML file
            }
            $this->_ref_map[$cur_key]['name'] = $cur_key;
            break;

          case 'refdata':
            if( $get_in() != 'reference' ) {
              // validity error.
            }
            $this->_xml->read();
            $this->_ref_map[$cur_key]['data'] = $this->_xml->value;
            break;

          case 'reflocation':
            if( $get_in() != 'reference' ) {
              // validity error.
            }
            $this->_xml->read();
            $this->_ref_map[$cur_key]['location'] = $this->_xml->value;
            break;
          }
          break;

        case XmlReader::END_ELEMENT:
          switch( $this->_xml->localName ) {
          case 'theme':
          case 'template':
          case 'stylesheet':
          case 'assoc':
          case 'reference':
          case 'mmtemplate':
            if( $in ) {
              array_pop($in);
            }
            $cur_key = null;
          }
          break;
      }
    }

    $this->_scanned = TRUE;
  }

  public function validate()
  {
    $this->_scan();
    if( !isset($this->_design_info['name']) || $this->_design_info['name'] == '' ) {
      throw new Exception('Invalid XML file (test1)');
    }
    if( !$this->_tpl_info ) {
      throw new Exception('Invalid XML file (test2)');
    }
    if( !$this->_css_info ) {
      throw new Exception('Invalid XML file (test3)');
    }
    // it validates.
  }

  public function get_design_info()
  {
    $this->_scan();

    $mod = Utils::get_module('DesignManager');
    $out = $this->_design_info;
    $out['description'] = 'TODO - set theme description';
    $out['generated'] = 0; // not known.
    $out['cmsversion'] = $mod->Lang('unknown'); // a good, early version number.
    return $out;
  }

  public function get_template_list()
  {
    $this->_scan();
    $out = [];
    foreach( $this->_tpl_info as $key => $one ) {
      $rec = [];
      $rec['name'] = $one['name'];
      $rec['desc'] = '';
      $rec['data'] = base64_decode($one['data']);
      if( isset($one['type']) && $one['type'] == 'MM' ) {
        $rec['type_originator'] = 'MenuManager';
        $rec['type_name'] = 'navigation';
      }
      else {
        $rec['type_originator'] = TemplateType::CORE;
        $rec['type_name'] = 'page';
      }
      $out[$key] = $rec;
    }
    return $out;
  }

  public function get_stylesheet_list()
  {
    $this->_scan();

    $out = [];
    foreach( $this->_css_info as $key => $one ) {
      $rec = [];
      $rec['name'] = $one['name'];
      $rec['desc'] = '';
      $rec['data'] = base64_decode($one['data']);
      $rec['mediatype'] = base64_decode($one['mediatype']);
      $rec['medisaquery'] = '';
      $out[] = $rec;
    }
    return $out;
  }

  protected function get_destination_dir()
  {
    $name = $this->get_new_name();
    $dirname = sanitizeVal($name,CMSSAN_FILE);
    $config = Lone::get('Config');
    $dir = cms_join_path($config['uploads_path'],'themes',$dirname);
    @mkdir($dir,0770,TRUE); // $perms = get_server_permissions()[3];
    if( !is_dir($dir) || !is_writable($dir) ) {
      throw new Exception('Could not create directory, or could not write in directory '.$dir);
    }

    return $dirname;
  }

  protected function validate_template_names()
  {
    $this->_scan();

    $templates = TemplateOperations::template_query(['as_list'=>1]);
    $tpl_names = array_values($templates);

    foreach( $this->_tpl_info as $key => &$rec ) {
      // make sure that this  template doesn't already exist.
      $name = $rec['name'];
      if( in_array($name,$tpl_names) ) {
        $orig_name = $name;
        $n = 1;
        while( $n < 10 ) {
          $n++;
          $new_name = $orig_name.' '.$n;
          if( !in_array($new_name,$tpl_names) ) {
            $rec['name'] = $new_name;
            $rec['old_name'] = $orig_name;
            break;
          }
        }
      }
    }
  }

  protected function validate_stylesheet_names()
  {
    $this->_scan();

    $stylesheets = StylesheetOperations::get_all_stylesheets(TRUE);
    $css_names = array_values($stylesheets);

    foreach( $this->_css_info as $key => &$rec ) {
      if( in_array($rec['name'],$css_names) ) {
        // gotta come up with a new name
        $orig_name = $rec['name'];
        $n = 1;
        while( $n < 10 ) {
          $n++;
          $new_name = $orig_name.' '.$n;
          if( !in_array($new_name,$css_names) ) {
            $rec['old_name'] = $rec['name'];
            $rec['name'] = $new_name;
            break;
          }
        }
      }
    }
  }

  public function import()
  {
    $this->validate();
    $this->validate_template_names();
    $this->validate_stylesheet_names();

    $newname = $this->get_new_name();
    $destdir = $this->get_destination_dir();
    $ref_map =& $this->_ref_map;

    // part1 .. start creating design..
    $design = new Design();
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
    $config = Lone::get('Config');

    // part2 .. expand files.
    foreach( $this->_ref_map as $key => &$rec ) {
      if( !isset($rec['data']) || $rec['data'] == '' ) continue;

      $destfile = cms_join_path($config['uploads_path'],'themes',$destdir,$rec['name']);
      file_put_contents($destfile,base64_decode($rec['data']));
      $rec['tpl_url'] = "{uploads_url}/themes/$destdir/{$rec['name']}";
      $rec['css_url'] = "[[uploads_url]]/themes/$destdir/{$rec['name']}";
    }

    // part3 .. process stylesheets
    $css_info = $this->get_stylesheet_list();
    foreach( $css_info as $name => &$css_rec ) {
      $stylesheet = new Stylesheet();
      $stylesheet->set_name($css_rec['name']);

      $ob = &$this;
      $regex='/url\s*\(\"*(.*)\"*\)/i';
      $css_rec['data'] = preg_replace_callback($regex, function($matches) use ($ob,$ref_map,$destdir)
          {
            $url = $matches[1];
	        //TODO generally support the websocket protocol 'wss' : 'ws'
            if( !startswith($url,'http') || startswith($url,CMS_ROOT_URL) ||
                startswith($url,'[[root_url]]') ) {
              $bn = basename($url);
              if( isset($ref_map[$bn]) ) {
                $out = $ref_map[$bn]['css_url'];
                return 'url('.$out.')';
              }
            }
            return $matches[0];
          },$css_rec['data']);
      if( isset($css_rec['media_type']) ) $stylesheet->add_media_type($css_rec['mediatype']);
      $stylesheet->set_content($css_rec['data']);
      $stylesheet->save();
      $design->add_stylesheet($stylesheet);
    }

    // part4 .. process templates
    $fn1 = function($matches) use ($ob,&$tpl_info) {
      $out = preg_replace_callback("/template\s*=[\\\"']{0,1}([a-zA-Z0-9._\ \:\-\/]+)[\\\"']{0,1}/i",
        function($matches) use ($ob,&$tpl_info)
	    {
           if( isset($tpl_info[$matches[1]]) ) {
            $rec = $tpl_info[$matches[1]];
            $out = str_replace($matches[1],$rec['name'],$matches[0]);
            return $out;
          }
          // find the new name and do a substitution
         return $matches[0];
        },$matches[0]);
      return $out;
    };

    $fn2 = function($matches) use ($ob,&$type,$ref_map,$destdir)
    {
      $url = $matches[2];
      //TODO generally support the websocket protocol 'wss' : 'ws'
      if( !startswith($url,'http') || startswith($url,CMS_ROOT_URL) || startswith($url,'{root_url}') ) {
        $bn = basename($url);
        if( isset($ref_map[$bn]) ) {
          $out = $ref_map[$bn]['tpl_url'];
          $out = " $type=\"$out\"";
          return $out;
        }
      }
      return $matches[0];
    };

    $tpl_info = $this->get_template_list();
    $have_mm_template = FALSE;
	$me = null; //TODO
    foreach( $tpl_info as $name => &$tpl_rec ) {
      if( $tpl_rec['type_originator'] == 'MenuManager' ) $have_mm_template = TRUE;

      $template = new Template();
      $template->set_originator($me);
      $template->set_owner(get_userid(FALSE));
      $template->set_name($tpl_rec['name']);

      $types = ['href', 'src', 'url'];
      $content = $tpl_rec['data'];
      foreach( $types as $type ) {
        $tmp_type = $type;
        $innerT = '[a-z0-9:?=&@/._-]+?';
        $content = preg_replace_callback("|$type\=([\"'`])(".$innerT.')\\1|i', $fn2,$content);
      }

      $content = preg_replace('/\{stylesheet/','{cms_stylesheet',$content);

      $regex='/\{menu.*\}/';
      $content = preg_replace_callback( $regex, $fn1, $content );

      $regex='/\{.*MenuManager.*\}/';
      $content = preg_replace_callback( $regex, $fn1, $content );

      $tpl_rec['data'] = $content;
      $template->set_content($content);
      $template->set_type($tpl_rec['type_originator'].'::'.$tpl_rec['type_name']);
      $template->save();
      $design->add_template($template);
    }

    // part5 ... save design
    $design->save();

    // part6 ... Make sure MenuManager is activated.
    if( $have_mm_template ) {
      Lone::get('ModuleOperations')->ActivateModule('MenuManager',1);
    }
  }
} // class
