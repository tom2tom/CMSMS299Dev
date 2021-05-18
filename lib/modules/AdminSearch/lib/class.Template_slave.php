<?php

namespace AdminSearch;

use CMSMS\Template;
use CMSMS\TemplateOperations;
use CMSMS\Utils;
use const CMS_DB_PREFIX;
use const CMS_ROOT_PATH;
use function check_permission;
use function cms_relative_path;
use function cmsms;
use function get_secure_param;
use function get_userid;

final class Template_slave extends Slave
{
  public function get_name()
  {
    $mod = Utils::get_module('AdminSearch');
    return $mod->Lang('lbl_template_search');
  }

  public function get_description()
  {
    $mod = Utils::get_module('AdminSearch');
    return $mod->Lang('desc_template_search');
  }

  public function check_permission()
  {
    $userid = get_userid();
    return check_permission($userid,'Modify Templates');
  }

  private function check_tpl_match(Template $tpl)
  {
    $fname = ( $this->search_casesensitive() ) ? 'stripos' : 'strpos'; // TODO handle mb_* matches
    $needle = $this->get_text();
    if( $fname($tpl->get_name(),$needle) !== false ) return true;
    if( $fname($tpl->get_content(),$needle) !== false ) return true;
    if( $this->search_descriptions() && $fname($tpl->get_description(),$needle) !== false ) return true;
    return false;
  }

  private function get_mod()
  {
    // static properties here >> StaticProperties class ?
    static $_mod;
    if( !$_mod ) $_mod = Utils::get_module('AdminSearch');
    return $_mod;
  }

  private function get_tpl_match_info(Template $tpl)
  {
    $fname = ( $this->search_casesensitive() ) ? 'stripos' : 'strpos'; // TODO handle mb_* matches
    $one = $tpl->get_id();
    $needle = $this->get_text();
    $html = '';
    $content = $tpl->get_content();
    $pos = $fname($content,$needle);
    if( $pos !== false ) { // TODO loop while any match
      $html = Tools::contextize($needle, $content, $pos);
    }
//    $url = $this->get_mod()->create_url( 'm1_','admin_edit_template','', [ 'tpl'=>$one ] ); //TODO edittemplate.php.$urlext.'&tpl='.$one - not a DM action-URL
//    $url = str_replace('&amp;','&',$url);
    $urlext = get_secure_param();
    $url = 'edittemplate.php'.$urlext.'&tpl='.$one; // OR view?

    if( $tpl->get_content_file() ) {
      $file = $tpl->get_content_filename();
      $title = $tpl->get_name().' ('.cms_relative_path($file,CMS_ROOT_PATH).')';
    }
    else {
      $title = $tpl->get_name();
    }
    $tmp = [
     'title'=>$title,
     'description'=>Tools::summarize($tpl->get_description()),
     'edit_url'=>$url,
     'text'=>$html
    ];
    return $tmp;
  }

  public function get_matches()
  {
    $db = cmsms()->GetDb();
    $mod = $this->get_mod();
    // get all template ids
    $sql = 'SELECT id FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' ORDER BY name ASC';
    $all_ids = $db->GetCol($sql);
    $output = [];
    if( $all_ids ) {
      $chunks = array_chunk($all_ids,15);
      foreach( $chunks as $chunk ) {
        $tpl_list = TemplateOperations::get_bulk_templates($chunk);
        foreach( $tpl_list as $tpl ) {
          if( $this->check_tpl_match($tpl) ) $output[] = $this->get_tpl_match_info($tpl);
        }
      }
    }
    return $output;
  }
} // class
