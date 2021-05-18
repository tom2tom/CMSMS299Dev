<?php

namespace AdminSearch;

use CMSMS\Stylesheet;
use CMSMS\StylesheetOperations;
use CMSMS\Utils;
use const CMS_DB_PREFIX;
use const CMS_ROOT_PATH;
use function check_permission;
use function cms_relative_path;
use function cmsms;
use function get_secure_param;
use function get_userid;

final class Css_slave extends Slave
{
  public function get_name()
  {
    $mod = Utils::get_module('AdminSearch');
    return $mod->Lang('lbl_css_search');
  }

  public function get_description()
  {
    $mod = Utils::get_module('AdminSearch');
    return $mod->Lang('desc_css_search');
  }

  public function check_permission()
  {
    $userid = get_userid();
    return check_permission($userid,'Manage Stylesheets');
  }

  private function check_css_matches(Stylesheet $css)
  {
    $fname = ( $this->search_casesensitive() ) ? 'stripos' : 'strpos'; // TODO handle mb_* matches
    $needle = $this->get_text();
    if( $fname($css->get_name(),$needle) !== false ) return true;
    if( $fname($css->get_content(),$needle) !== false ) return true;
    if( $this->search_descriptions() && $fname($css->get_description(),$needle) !== false ) return true;
    return false;
  }

  private function get_mod()
  {
    // static properties here >> StaticProperties class ?
    static $_mod;
    if( !$_mod ) $_mod = Utils::get_module('AdminSearch');
    return $_mod;
  }

  private function get_css_match_info(Stylesheet $css)
  {
    $fname = ( $this->search_casesensitive() ) ? 'stripos' : 'strpos'; // TODO handle mb_* matches
    $needle = $this->get_text();
    $content = $css->get_content();
    $html = '';
    $pos = $fname($content,$needle);
    if( $pos !== false ) { //TODO loop while
      $html = Tools::contextize($needle, $content, $pos);
    }
    $urlext = get_secure_param();
    $one = $css->get_id();
    $url = 'editstylesheet.php'.$urlext.'&css='.$one; // OR view?
    $title = $css->get_name();
    if( $css->get_content_file() ) {
      $file = $css->get_content_filename();
      $title = $css->get_name().' ('.cms_relative_path($file,CMS_ROOT_PATH).')';
    }
    $tmp = [
     'title'=>$title,
     'description'=>Tools::summarize($css->get_description()),
     'edit_url'=>$url,
     'text'=>$html
    ];
    return $tmp;
  }

  public function get_matches()
  {
    $db = cmsms()->GetDb();
//    $mod = $this->get_mod();
    // get all stylesheets' ids
    $sql = 'SELECT id FROM '.CMS_DB_PREFIX. StylesheetOperations::TABLENAME.' ORDER BY name';
    $all_ids = $db->GetCol($sql);
    $output = [];
    if( $all_ids ) {
      $chunks = array_chunk($all_ids,15);
      foreach( $chunks as $chunk ) {
        $css_list = StylesheetOperations::get_bulk_stylesheets($chunk);
        foreach( $css_list as $css ) {
          if( $this->check_css_matches($css) ) {
            $output[] = $this->get_css_match_info($css);
		  }
        }
      }
    }
    return $output;
  }
} // class
