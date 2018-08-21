<?php

namespace AdminSearch;

use cms_utils;
use CmsLayoutTemplate;
use const CMS_DB_PREFIX;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use function check_permission;
use function cms_htmlentities;
use function cmsms;
use function get_userid;

final class oldmodtemplate_slave extends slave
{
  public function get_name()
  {
    $mod = cms_utils::get_module('AdminSearch');
    return $mod->Lang('lbl_oldmodtemplate_search');
  }

  public function get_description()
  {
    $mod = cms_utils::get_module('AdminSearch');
    return $mod->Lang('desc_oldmodtemplate_search');
  }

  public function check_permission()
  {
    return check_permission(get_userid(),'Modify Templates');
  }

  public function get_matches()
  {
    $userid = get_userid();

    $db = cmsms()->GetDb();
    $query = 'SELECT originator,name,content FROM '.CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME.' WHERE content LIKE ?';
    $dbr = $db->GetArray($query,['%'.$this->get_text().'%']);
    if( is_array($dbr) && count($dbr) ) {
      $output = [];
      $urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

      foreach( $dbr as $row ) {
        // here we could actually have a smarty template to build the description.
        $pos = strpos($row['content'],$this->get_text());
        if( $pos !== FALSE ) {
          $start = max(0,$pos - 50);
          $end = min(strlen($row['content']),$pos+50);
          $text = substr($row['content'],$start,$end-$start);
          $text = cms_htmlentities($text);
          $text = str_replace($this->get_text(),'<span class="search_oneresult">'.$this->get_text().'</span>',$text);
          $text = str_replace("\r",'',$text);
          $text = str_replace("\n",'',$text);
        }

        $tmp = ['title'=>"{$row['originator']} + {$row['name']}",'text'=>$text];

        $output[] = $tmp;
      }

      return $output;
    }

  }

  public function get_section_description()
  {
    $mod = cms_utils::get_module('AdminSearch');
    return $mod->Lang('sectiondesc_oldmodtemplates');
  }
} // class

